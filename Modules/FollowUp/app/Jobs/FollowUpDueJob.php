<?php

namespace Modules\FollowUp\Jobs;

use App\Constants\CommonConst;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\AlertAndNotification\Helpers\NotificationHelper;
use Modules\AlertAndNotification\Helpers\RuleCheckHelper;
use Modules\AlertAndNotification\Models\Rule;
use Modules\FollowUp\Constants\FollowUpConst;
use Modules\FollowUp\Models\FollowUp;

class FollowUpDueJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct() {}

    public function handle(): void
    {
        try {
            i('FollowUpDueJob : started.');

            # Retrieve rules for clients with NO_ACTION status
            // $ruleCheckHelper = new RuleCheckHelper();
            // $rules = $ruleCheckHelper->commonStatusConditionRules(CommonConst::MODULE_FOLLOW_UP,null, FollowUpConst::RULE_FOLLOW_UP_DUE);

            $rule = Rule::where('rule_slug', FollowUpConst::RULE_FOLLOW_UP_DUE)
                ->where('status', '!=', CommonConst::IN_ACTIVE)
                ->first();

            if (!$rule) {
                w('FollowUpDueJob : No active rule found for RULE_FOLLOW_UP_DUE.');
                return;
            }

            $conditions = collect(json_decode($rule->conditions ?? '[]', true))
                ->filter(fn($c) => $c['allow_condition'] ?? false)
                ->values()
                ->all();

            if (empty($conditions)) {
                w('FollowUpDueJob : No valid conditions for RULE_FOLLOW_UP_DUE.');
                return;
            }

            $logic = strtoupper($rule->condition_type ?? 'AND'); # Default to AND

            $followUpQuery = FollowUp::query()->whereNotNull('next_call_datetime');

            $followUpQuery->where(function ($query) use ($conditions, $logic) {
                foreach ($conditions as $condition) {
                    $field = $condition['field'] ?? 'site_visit_datetime';
                    $operator = $condition['operator'] ?? '>';
                    $datatype = $condition['datatype'] ?? 'date';
                    $value = (int)($condition['value'] ?? 0);
                    $slug = $condition['trigger_event'] ?? null;

                    if ($datatype === 'date' && $slug === FollowUpConst::RULE_FOLLOW_UP_DUE) {
                        if ($operator === '>') {
                            $query->where($field, '<', DB::raw("NOW() - INTERVAL '$value days'"));
                        } elseif ($operator === '<') {
                            $query->where($field, '>', DB::raw("NOW() - INTERVAL '$value days'"));
                        } elseif ($operator === '==') {
                            $query->whereDate($field, '=', DB::raw("CURRENT_DATE - INTERVAL '$value days'"));
                        }
                    }

                    # Add other datatype handlers here if needed
                }
            });

            $follow_up_ids = $followUpQuery->pluck('id');

            if ($follow_up_ids->isEmpty()) {
                i('FollowUpDueJob : No follow up matched the rule conditions.');
                return;
            }

            foreach ($follow_up_ids as $follow_up_id) {
                try {
                    $data = followUpRuleNotification($follow_up_id);
                    $notificationHelper = new NotificationHelper();
                    $notificationHelper->handle(FollowUpConst::RULE_FOLLOW_UP_DUE, $data, null, loginUserId());
                    i("FollowUpDueJob : Notification sent for follow-up ID: {$follow_up_id}");
                } catch (\Exception $e) {
                    er("FollowUpDueJob : Error sending notification for follow-up ID: {$follow_up_id}. Error: " . $e->getMessage());
                }
            }

            i('FollowUpDueJob  : completed.');
        } catch (\Exception $e) {
            er('FollowUpDueJob : failed. Error: ' . $e->getMessage());
        }
    }
}
