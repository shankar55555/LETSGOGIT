<?php

namespace Modules\Leads\Jobs;

use App\Constants\CommonConst;
use App\Models\AdminControlConfig;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\AlertAndNotification\Helpers\NotificationHelper;
use Modules\AlertAndNotification\Helpers\RuleCheckHelper;
use Modules\Leads\Models\Lead;
use Throwable;

class LeadStatusConditionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const LOG_PREFIX = '[LeadStatusConditionJob]';

    public function __construct() {}

    public function handle(): void
    {
        i(self::LOG_PREFIX . ' Job started.');

        try {
            $statuses = AdminControlConfig::where('status_for', CommonConst::MODULE_LEAD)
                ->where('position', '!=', 0)->orderBy('position', 'asc')
                ->get();

            $ruleHelper = new RuleCheckHelper();
            $notificationHelper = new NotificationHelper();

            foreach ($statuses as $status) {
                $rules = $ruleHelper->commonStatusConditionRules(CommonConst::MODULE_LEAD, $status->slug);

                if (empty($rules)) {
                    i(self::LOG_PREFIX . " No matching rules found for status: {$status->slug}");
                    continue;
                }

                $ids = Lead::where('status', $status->slug)->pluck('id')->toArray();

                if (empty($ids)) {
                    i(self::LOG_PREFIX . " No Leads found with status: {$status->slug}");
                    continue;
                }

                foreach ($rules as $rule) {
                    $matchedIds = Lead::getMatchingIdsFromRule($rule, $ids);
                    foreach ($matchedIds as $id) {
                        try {
                            $notificationHelper->handle(null, leadRuleNotification($id), $rule->id, loginUserId());
                            i(self::LOG_PREFIX . " Notification sent for Lead ID: {$id}");
                        } catch (\Exception $e) {
                            er(self::LOG_PREFIX . " Notification failed for Lead ID: {$id}. Error: {$e->getMessage()}");
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
