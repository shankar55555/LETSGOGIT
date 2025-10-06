<?php

namespace Modules\Quotations\Jobs;

use App\Constants\CommonConst;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\AlertAndNotification\Helpers\NotificationHelper;
use Modules\AlertAndNotification\Models\Rule;
use Modules\Quotations\Constants\QuotationConst;
use Modules\Quotations\Models\Quotation;

class QuotationExpiredJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct() {}

    public function handle(): void
    {
        try {
            i('QuotationExpiredJob started.');

            $rule = Rule::where('rule_slug', QuotationConst::RULE_QUOTATION_EXPIRED)
                ->where('status', '!=', CommonConst::IN_ACTIVE)
                ->first();

            if (!$rule) {
                w('QuotationExpiredJob : No active rule found for RULE_QUOTATION_EXPIRED.');
                return;
            }

            $conditions = collect(json_decode($rule->conditions ?? '[]', true))
                ->filter(fn($c) => $c['allow_condition'] ?? false)
                ->values()
                ->all();

            if (empty($conditions)) {
                w('QuotationExpiredJob : No valid conditions for RULE_QUOTATION_EXPIRED.');
                return;
            }

            $logic = strtoupper($rule->condition_type ?? 'AND'); # Default to AND

            $quotationQuery = Quotation::query()->whereNotNull('valid_uptil')->whereNotIn('status', [QuotationConst::QUOTATION_ACCEPTED, QuotationConst::QUOTATION_REJECTED, QuotationConst::QUOTATION_EXPIRED]);

            $quotationQuery->where(function ($query) use ($conditions, $logic) {
                foreach ($conditions as $condition) {
                    $operator = $condition['operator'] ?? '>';
                    $datatype = $condition['datatype'] ?? 'date';
                    $value = (int)($condition['value'] ?? 0);
                    $slug = $condition['trigger_event'] ?? null;

                    if ($datatype === 'date' && $slug === QuotationConst::RULE_QUOTATION_EXPIRED) {
                        if ($operator === '<') {
                            $query->where('valid_uptil', '>', DB::raw('NOW()'))
                                ->where('valid_uptil', '<', DB::raw("NOW() + INTERVAL '$value days'"));
                        } elseif ($operator === '==') {
                            $query->whereDate('valid_uptil', '=', DB::raw("CURRENT_DATE - INTERVAL '$value days'"));
                        }
                    }

                    # Add other datatype handlers here if needed
                }
            });

            $quotationIds = $quotationQuery->pluck('id');

            if ($quotationIds->isEmpty()) {
                i('QuotationExpiredJob : No quotations matched the rule conditions.');
                return;
            }

            foreach ($quotationIds as $quotationId) {
                try {
                    $data = quotationRuleNotification($quotationId);
                    $notificationHelper = new NotificationHelper();
                    $notificationHelper->handle(QuotationConst::RULE_QUOTATION_EXPIRED, $data, null, loginUserId());
                    i("QuotationExpiredJob : Notification sent for Quotation ID: {$quotationId}");
                } catch (\Exception $e) {
                    er("QuotationExpiredJob : Error sending notification for Quotation ID: {$quotationId}. Error: " . $e->getMessage());
                }
            }

            i('QuotationExpiredJob : completed.');
        } catch (\Exception $e) {
            er('QuotationExpiredJob : failed. Error: ' . $e->getMessage());
        }
    }
}
