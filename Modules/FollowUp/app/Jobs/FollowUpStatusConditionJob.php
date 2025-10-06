<?php

namespace Modules\FollowUp\Jobs;

use App\Constants\CommonConst;
use App\Models\AdminControlConfig;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\AlertAndNotification\Helpers\NotificationHelper;
use Modules\AlertAndNotification\Helpers\RuleCheckHelper;
use Modules\FollowUp\Models\FollowUp;
use Throwable;

class FollowUpStatusConditionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const LOG_PREFIX = '[FollowUpStatusConditionJob]';

    public function __construct() {}

    public function handle(): void
    {
        i(self::LOG_PREFIX . ' Job started.');

        try {
            $statuses = AdminControlConfig::where('status_for', CommonConst::MODULE_FOLLOW_UP)
                ->where('position', '!=', 0)->orderBy('position', 'asc')
                ->get();

            $ruleHelper = new RuleCheckHelper();
            $notificationHelper = new NotificationHelper();

            foreach ($statuses as $status) {
                $rules = $ruleHelper->commonStatusConditionRules(CommonConst::MODULE_FOLLOW_UP, $status->slug);

                if (empty($rules)) {
                    i(self::LOG_PREFIX . " No matching rules found for status: {$status->slug}");
                    continue;
                }

                $ids = FollowUp::where('status', $status->slug)->pluck('id')->toArray();

                if (empty($ids)) {
                    i(self::LOG_PREFIX . " No FollowUps found with status: {$status->slug}");
                    continue;
                }

                foreach ($rules as $rule) {
                    $matchedIds = FollowUp::getMatchingIdsFromRule($rule, $ids);

                    foreach ($matchedIds as $id) {
                        try {
                            $notificationHelper->handle(null, followUpRuleNotification($id), $rule->id, loginUserId());
                            i(self::LOG_PREFIX . " Notification sent for FollowUp ID: {$id}");
                        } catch (\Exception $e) {
                            er(self::LOG_PREFIX . " Notification failed for FollowUp ID: {$id}. Error: {$e->getMessage()}");
                        }
                    }
                }
            }

            i(self::LOG_PREFIX . ' Job completed successfully.');
        } catch (Throwable $e) {
            er(self::LOG_PREFIX . ' Job failed: ' . $e->getMessage());
            er(self::LOG_PREFIX . ' Trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }
}
