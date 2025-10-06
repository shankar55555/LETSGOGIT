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

class FollowUpOverDueJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct() {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            i('FollowUpOverDueJob started.');

            $rule = Rule::where('rule_slug', FollowUpConst::RULE_FOLLOW_UP_OVERDUE)
                ->whereNot('status', CommonConst::IN_ACTIVE)
                ->first();

            // $ruleCheckHelper = new RuleCheckHelper();
            // $rules = $ruleCheckHelper->commonStatusConditionRules(CommonConst::MODULE_FOLLOW_UP,null,FollowUpConst::RULE_FOLLOW_UP_OVERDUE);

            if (!$rule) {
                w('FollowUpOverDueJob : Follow Up Over Due rule found for RULE_FOLLOW_UP_OVERDUE.');
                return;
            }

            $conditions = collect(json_decode($rule->conditions ?? '[]', true))
                ->filter(fn($c) => $c['allow_condition'] ?? false)
                ->values()
                ->all();

            if (empty($conditions)) {
                w('FollowUpOverDueJob : No valid conditions for RULE_FOLLOW_UP_OVERDUE.');
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

                    if ($datatype === 'date' && $slug === FollowUpConst::RULE_FOLLOW_UP_OVERDUE) {
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
                i('FollowUpOverDueJob : No follow up matched the rule conditions.');
                return;
            }

            foreach ($follow_up_ids as $follow_up_id) {
                try {
                    $data = followUpRuleNotification($follow_up_id);
                    $notificationHelper = new NotificationHelper();
                    $notificationHelper->handle(FollowUpConst::RULE_FOLLOW_UP_OVERDUE, $data, null, loginUserId());
                    i("FollowUpOverDueJob : Notification sent for follow up ID: {$follow_up_id}");
                } catch (\Exception $e) {
                    er("FollowUpOverDueJob : Error sending notification for follow up ID: {$follow_up_id}. Error: " . $e->getMessage());
                }
            }

            i('FollowUpOverDueJob : completed.');
        } catch (\Exception $e) {
            er('FollowUpOverDueJob : failed. Error: ' . $e->getMessage());
        }
    }
}
