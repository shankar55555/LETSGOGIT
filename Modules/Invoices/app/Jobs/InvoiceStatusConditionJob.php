<?php

namespace Modules\Invoices\Jobs;

use App\Constants\CommonConst;
use App\Models\AdminControlConfig;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\AlertAndNotification\Helpers\NotificationHelper;
use Modules\AlertAndNotification\Helpers\RuleCheckHelper;
use Modules\Invoices\Models\Invoice;
use Throwable;

class InvoiceStatusConditionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const LOG_PREFIX = '[InvoiceStatusConditionJob]';

    public function __construct() {}

    public function handle(): void
    {
        i(self::LOG_PREFIX . ' Job started.');

        try {
            $statuses = AdminControlConfig::where('status_for', CommonConst::MODULE_INVOICE)
                ->where('position', '!=', 0)->orderBy('position', 'asc')
                ->get();

            $ruleHelper = new RuleCheckHelper();
            $notificationHelper = new NotificationHelper();

            foreach ($statuses as $status) {
                $rules = $ruleHelper->commonStatusConditionRules(CommonConst::MODULE_INVOICE, $status->slug);

                if (empty($rules)) {
                    i(self::LOG_PREFIX . " No matching rules found for status: {$status->slug}");
                    continue;
                }

                $invoiceIds = Invoice::where('status', $status->slug)->pluck('id')->toArray();

                if (empty($invoiceIds)) {
                    i(self::LOG_PREFIX . " No invoices found with status: {$status->slug}");
                    continue;
                }

                foreach ($rules as $rule) {
                    $matchedIds = Invoice::getMatchingIdsFromRule($rule, 'due_date', $invoiceIds);

                    foreach ($matchedIds as $invoiceId) {
                        try {
                            $notificationHelper->handle(null, invoiceRuleNotification($invoiceId), $rule->id, loginUserId());
                            i(self::LOG_PREFIX . " Notification sent for Invoice ID: {$invoiceId}");
                        } catch (\Exception $e) {
                            er(self::LOG_PREFIX . " Notification failed for Invoice ID: {$invoiceId}. Error: {$e->getMessage()}");
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
