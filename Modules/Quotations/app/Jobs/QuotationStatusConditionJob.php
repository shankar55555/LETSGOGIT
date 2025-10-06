<?php

namespace Modules\Quotations\Jobs;

use App\Constants\CommonConst;
use App\Models\AdminControlConfig;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\AlertAndNotification\Helpers\NotificationHelper;
use Modules\AlertAndNotification\Helpers\RuleCheckHelper;
use Modules\Quotations\Models\Quotation;
use Throwable;

class QuotationStatusConditionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const LOG_PREFIX = '[QuotationStatusConditionJob]';

    public function __construct() {}

    public function handle(): void
    {
        i(self::LOG_PREFIX . ' Job started.');

        try {
            $statuses = AdminControlConfig::where('status_for', CommonConst::MODULE_QUOTATION)
                ->where('position', '!=', 0)->orderBy('position', 'asc')
                ->get();

            $ruleHelper = new RuleCheckHelper();
            $notificationHelper = new NotificationHelper();

            foreach ($statuses as $status) {
                $rules = $ruleHelper->commonStatusConditionRules(CommonConst::MODULE_QUOTATION, $status->slug);

                if (empty($rules)) {
                    i(self::LOG_PREFIX . " No matching rules found for status: {$status->slug}");
                    continue;
                }

                $ids = Quotation::where('status', $status->slug)->pluck('id')->toArray();

                if (empty($ids)) {
                    i(self::LOG_PREFIX . " No Quotations found with status: {$status->slug}");
                    continue;
                }

                foreach ($rules as $rule) {
                    $matchedIds = Quotation::getMatchingIdsFromRule($rule, $ids);

                    foreach ($matchedIds as $id) {
                        try {
                            $notificationHelper->handle(null, quotationRuleNotification($id), $rule->id, loginUserId());
                            i(self::LOG_PREFIX . " Notification sent for Quotation ID: {$id}");
                        } catch (\Exception $e) {
                            er(self::LOG_PREFIX . " Notification failed for Quotation ID: {$id}. Error: {$e->getMessage()}");
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
