<?php

namespace Modules\AlertAndNotification\Helpers;

use App\Constants\CommonConst;
use App\Models\AdminControlConfig;
use Modules\AlertAndNotification\Jobs\NotificationJob;
use Modules\AlertAndNotification\Models\Rule;
use Modules\Clients\Models\Client;
use Modules\FollowUp\Models\FollowUp;
use Modules\Invoices\Models\Invoice;
use Modules\Leads\Models\Lead;
use Modules\Quotations\Models\Quotation;
use Modules\SiteVisit\Models\SiteVisit;

/**
 * Class RuleCheckHelper
 *
 * Handles checking and triggering rules related to lead status changes.
 */
class RuleCheckHelper
{
    /**
     * Trigger rule-based notifications when a module's status changes.
     *
     * This method checks if any rules are configured for a specific module's status change,
     * filters relevant data IDs based on the rules, and triggers notification handling.
     *
     * @param string      $module     The module name (e.g., lead, client, invoice).
     * @param string      $status     The new status slug (e.g., 'approved', 'rejected').
     * @param array|null  $ids        Optional specific IDs to filter the data on.
     * @param string|null $oldStatus  Optional previous status (not used in this method).
     *
     * @return void
     */
    public function onlyStatusChangeCheckRule(string $module, string $status, ?array $ids = [], ?string $oldStatus = ''): void
    {
        # Get the status ID from the configuration table
        $status_id = AdminControlConfig::where('status_for', $module)
            ->where('slug', $status)
            ->value('id');

        if (!$status_id) {
            i("Rule Check Helper : No status_id found for module: $module and status: $status");
            return;
        }

        # Fetch rules with STATUS_TRIGGER and active status
        $rules = Rule::where('rule_slug', CommonConst::RULE_STATUS_TRIGGER)
            ->where('status', '!=', CommonConst::IN_ACTIVE)
            ->get()
            ->filter(function ($rule) use ($status_id, $module) {
                $conditions = json_decode($rule->conditions, true);
                return collect($conditions)->contains(function ($condition) use ($status_id, $module) {
                    return isset($condition['trigger_event'], $condition['module'], $condition['action_status']) &&
                        $condition['trigger_event'] === CommonConst::RULE_STATUS_TRIGGER &&
                        $condition['module'] === $module &&
                        $condition['action_status'] == $status_id;
                });
            })->values();

        if ($rules->isEmpty()) {
            i("Rule Check Helper : No applicable rules found for module: $module and status_id: $status_id");
            return;
        }

        # Mapping for model class and notification function by module
        $moduleMap = [
            CommonConst::MODULE_LEAD       => [Lead::class, 'leadRuleNotification'],
            CommonConst::MODULE_CLIENT     => [Client::class, 'clientRuleNotification'],
            CommonConst::MODULE_QUOTATION  => [Quotation::class, 'quotationRuleNotification'],
            CommonConst::MODULE_FOLLOW_UP  => [FollowUp::class, 'followUpRuleNotification'],
            CommonConst::MODULE_INVOICE    => [Invoice::class, 'invoiceRuleNotification'],
            CommonConst::MODULE_SITE_VISIT => [SiteVisit::class, 'SiteVisitRuleNotification'],
        ];

        if (!isset($moduleMap[$module])) {
            w("Rule Check Helper : Module $module not found in moduleMap");
            return;
        }

        # Get model class and notification function
        [$modelClass, $notificationFunc] = $moduleMap[$module];

        if (!method_exists($modelClass, 'getMatchingIdsFromRule')) {
            er("Rule Check Helper : Method getMatchingIdsFromRule does not exist on model: $modelClass");
            return;
        }
        # $notificationHelper = new NotificationHelper();
        foreach ($rules as $rule) {
            # Get filtered record IDs based on the rule
            $filterIds = $modelClass::getMatchingIdsFromRule($rule, $ids);

            if (empty($filterIds)) {
                i("Rule Check Helper : No matching IDs found for rule ID {$rule->id} and module $module");
                continue;
            }

            foreach ($filterIds as $id) {
                if (!is_callable([$modelClass, $notificationFunc])) {
                    er("Rule Check Helper : Notification method $notificationFunc does not exist on $modelClass");
                    continue;
                }

                # Call the appropriate notification function dynamically
                $data = call_user_func($notificationFunc, $id);

                # $notificationHelper->handle(null, $data, $rule->id, loginUserId());
                # Dispatch notification job
                NotificationJob::dispatch(null, $data, $rule->id, loginUserId());
            }
        }
    }

    /**
     * Get rules for a specific module based on status or rule slug.
     *
     * @param string      $module     The module name (e.g., 'lead', 'client').
     * @param string|null $status     Optional status slug to match rules.
     * @param string|null $ruleSlug   Optional rule slug to match rules.
     *
     * @return array An array of matching Rule models.
     */
    public function commonStatusConditionRules(string $module, ?string $status = null, ?string $ruleSlug = null)
    {
        $rules1 = collect();
        if ($ruleSlug) {
            $rules1 = Rule::where('rule_slug', $ruleSlug)
                ->where('status', '!=', CommonConst::IN_ACTIVE)
                ->get()
                ->filter(function ($rule) use ($module) {
                    $conditions = json_decode($rule->conditions, true);
                    return collect($conditions)->contains(function ($condition) use ($module) {
                        return !empty($condition['allow_condition']) &&
                            $condition['module'] === $module &&
                            !empty($condition['datatype']) &&
                            isset($condition['value']) &&
                            is_numeric($condition['value']);
                    });
                })->values();

            i("Rule Check Helper : Fetched " . $rules1->count() . " rules for ruleSlug: {$ruleSlug}");
        }

        $rules2 = collect();
        if ($status) {
            $status_id = AdminControlConfig::where('status_for', $module)->where('slug', $status)->value('id');
            $rules2 = Rule::where('rule_slug', CommonConst::RULE_STATUS_TRIGGER)
                ->where('status', '!=', CommonConst::IN_ACTIVE)
                ->get()
                ->filter(function ($rule) use ($status_id, $module) {
                    $conditions = json_decode($rule->conditions, true);
                    return collect($conditions)->contains(function ($condition) use ($status_id, $module) {
                        return isset($condition['trigger_event'], $condition['module'], $condition['action_status']) &&
                            $condition['module'] === $module &&
                            !empty($condition['condition']) &&
                            (int)$condition['action_status'] === (int)$status_id;
                    });
                })->values();

            i("Rule Check Helper : Fetched " . $rules2->count() . " status-trigger rules for status: {$status}");
        }
        return $rules1->merge($rules2)->all();
    }

    /**
     * Fetch condition-based rules for a given module.
     *
     * @param string      $module   The module name (e.g., 'lead', 'client').
     * @param string|null $ruleSlug Optional slug (currently unused).
     * @return \Illuminate\Support\Collection
     */
    public function getConditionRules(string $module)
    {
        i("Rule Check Helper : getConditionRules " . $module);
        return Rule::where('rule_slug', CommonConst::RULE_STATUS_TRIGGER)
            ->where('status', '!=', CommonConst::IN_ACTIVE)
            ->get()
            ->filter(function ($rule) use ($module) {
                $conditions = json_decode($rule->conditions, true);
                return collect($conditions)->contains(function ($condition) use ($module) {
                    return isset($condition['trigger_event'], $condition['module'], $condition['action_status'], $condition['datatype']) &&
                        ($condition['condition'] === 'condition' || !empty($condition['operator'])) && $condition['module'] === $module && !empty($condition['datatype']);
                });
            })
            ->values();
    }
}
