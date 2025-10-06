<?php

namespace Modules\Leads\Jobs;

use App\Constants\CommonConst;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\AlertAndNotification\Helpers\NotificationHelper;
use Modules\AlertAndNotification\Helpers\RuleCheckHelper;
use Modules\Leads\Constants\LeadConst;
use Modules\Leads\Models\Lead;

class LeadNoActionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct() {}

    /**
     * Processes leads with NO_ACTION status and triggers notifications based on matching rules
     *
     * This job retrieves leads with NO_ACTION status, checks them against predefined rules,
     * and sends notifications for matching leads using the NotificationHelper.
     *
     * @return void
     * @throws \Exception If the job encounters a critical error during execution
     */
    public function handle(): void
    {
        try {
            # Log job start
            i('LeadNoActionJob : started');

            # Retrieve rules for leads with NO_ACTION status
            $ruleCheckHelper = new RuleCheckHelper();
            $rules = $ruleCheckHelper->commonStatusConditionRules(CommonConst::MODULE_LEAD, LeadConst::NO_ACTION, LeadConst::RULE_NO_ACTION);

            # Exit if no rules are found
            if (empty($rules)) {
                i('LeadNoActionJob : No matching rules found for NO_ACTION status');
                return;
            }

            # Fetch lead IDs with NO_ACTION status
            $leadIds = Lead::query()
                ->where('status', LeadConst::NO_ACTION)
                ->pluck('id')
                ->toArray();

            # Exit if no leads are found
            if (empty($leadIds)) {
                i('LeadNoActionJob : No leads found with NO_ACTION status');
                return;
            }

            # Initialize notification helper
            $notificationHelper = new NotificationHelper();

            # Process each rule and its matching leads
            foreach ($rules as $rule) {
                # Get lead IDs that match the current rule
                $matchedIds = Lead::getMatchingIdsFromRule($rule, $leadIds);

                # Process each matching lead
                foreach ($matchedIds as $leadId) {
                    try {
                        # Send notification for the lead
                        $ruleSlug = null;
                        $notificationHelper->handle($ruleSlug, leadRuleNotification($leadId), $rule->id, loginUserId());
                        i("LeadNoActionJob : Notification sent successfully for Lead ID: {$leadId}");
                    } catch (\Exception $e) {
                        # Log notification failure
                        er("LeadNoActionJob : Failed to send notification for Lead ID: {$leadId}. Error: {$e->getMessage()}");
                    }
                }
            }

            # Log job completion
            i('LeadNoActionJob : completed successfully');
        } catch (\Exception $e) {
            # Log job failure
            er("LeadNoActionJob : failed. Error: {$e->getMessage()}");
            throw $e; # Re-throw to allow job retry if configured
        }
    }
}
