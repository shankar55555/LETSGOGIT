<?php

namespace Modules\Invoices\Jobs;

use App\Constants\CommonConst;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\AlertAndNotification\Helpers\NotificationHelper;
use Modules\AlertAndNotification\Models\Rule;
use Modules\Invoices\Constants\InvoiceConst;
use Modules\Invoices\Models\Invoice;
use Throwable;

class InvoiceAfterDueJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const LOG_CHANNEL = 'invoice';
    private const LOG_PREFIX = 'Invoice: After Due Date Notification';

    /**
     * Create a new job instance.
     */
    public function __construct() {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::channel(self::LOG_CHANNEL)->info(self::LOG_PREFIX . ' job started.');

        try {
            $rule = $this->getActiveRule();
            if (!$rule) {
                return;
            }

            $conditions = collect(json_decode($rule->conditions ?? '[]', true))
                ->filter(fn($c) => $c['allow_condition'] ?? false)
                ->values()
                ->all();

            if (empty($conditions)) {
                Log::channel(self::LOG_CHANNEL)->warning(self::LOG_PREFIX . ': No valid conditions found in rule.');
                return;
            }

            $logic = strtoupper($rule->condition_type ?? 'AND'); // Default to AND

            $invoiceQuery = Invoice::whereNot('status', InvoiceConst::PAID);

            $invoiceQuery->where(function ($query) use ($conditions, $logic) {
                foreach ($conditions as $condition) {
                    $field = $condition['field'] ?? $field_name ?? 'due_date';
                    $operator = $condition['operator'] ?? '>=';
                    $datatype = $condition['datatype'] ?? 'date';
                    $value = (int)($condition['value'] ?? 0);
                    $slug = $condition['trigger_event'] ?? null;

                    if ($datatype === 'date' && $slug === InvoiceConst::RULE_AFTER_DUE_DATE) {
                        $method = $logic === 'OR' ? 'orWhere' : 'where';

                        if ($operator === '>=') {
                            $query->{$method}($field, '<=', DB::raw("NOW() - INTERVAL '$value days'"));
                        } elseif ($operator === '<=') {
                            $query->{$method}($field, '>=', DB::raw("NOW() - INTERVAL '$value days'"));
                        } elseif ($operator === '==') {
                            $query->{$method}(DB::raw("DATE(due_date)"), '=', DB::raw("CURRENT_DATE - INTERVAL '$value days'"));
                        } elseif ($operator === '>') {
                            $query->{$method}($field, '<', DB::raw("NOW() - INTERVAL '$value days'"));
                        } elseif ($operator === '<') {
                            $query->{$method}($field, '>', DB::raw("NOW() - INTERVAL '$value days'"));
                        }
                    }

                    // Add other datatype handlers here if needed
                }
            });

            $invoiceIds = $invoiceQuery->pluck('id');

            if ($invoiceIds->isEmpty()) {
                Log::channel(self::LOG_CHANNEL)->info(self::LOG_PREFIX . ': No invoices matched the rule conditions.');
                return;
            }

            foreach ($invoiceIds as $invoiceId) {
                $this->processInvoice($invoiceId);
            }
        } catch (Throwable $e) {
            Log::channel(self::LOG_CHANNEL)->error(self::LOG_PREFIX . ' job failed: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }

        Log::channel(self::LOG_CHANNEL)->info(self::LOG_PREFIX . ' job completed.');
    }

    /**
     * Get the active rule for after due date notification.
     */
    private function getActiveRule(): ?Rule
    {
        $rule = Rule::where('rule_slug', InvoiceConst::RULE_AFTER_DUE_DATE)
            ->whereNot('status', CommonConst::IN_ACTIVE)
            ->first();

        if (!$rule) {
            Log::channel(self::LOG_CHANNEL)->warning(self::LOG_PREFIX . ': No active rule found.');
            return null;
        }

        return $rule;
    }

    /**
     * Process a single invoice notification.
     */
    private function processInvoice(int $invoiceId): void
    {
        try {
            $data = invoiceRuleNotification($invoiceId);
            if (!$data) {
                Log::channel(self::LOG_CHANNEL)->warning(self::LOG_PREFIX . ": No notification data for Invoice ID: {$invoiceId}");
                return;
            }

            $notificationHelper = new NotificationHelper();
            $notificationHelper->handle(InvoiceConst::RULE_AFTER_DUE_DATE, $data, null, loginUserId());

            Log::channel(self::LOG_CHANNEL)->info(self::LOG_PREFIX . ": Notification sent for Invoice ID: {$invoiceId}");
        } catch (Throwable $e) {
            Log::channel(self::LOG_CHANNEL)->error(self::LOG_PREFIX . ": Failed to process Invoice ID: {$invoiceId}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
