<?php

namespace Modules\SiteVisit\Jobs;

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
use Modules\SiteVisit\Constants\SiteVisitConst;
use Modules\SiteVisit\Models\SiteVisit;

class SiteVisitDueJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct() {}

    public function handle(): void
    {
        try {
            i('SiteVisitDueJob : started.');

            $rule = Rule::where('rule_slug', SiteVisitConst::RULE_SITE_VISIT_DUE)
                ->where('status', '!=', CommonConst::IN_ACTIVE)
                ->first();

            if (!$rule) {
                w('SiteVisitDueJob : No active rule found for RULE_SITE_VISIT_DUE.');
                return;
            }

            $conditions = collect(json_decode($rule->conditions ?? '[]', true))
                ->filter(fn($c) => $c['allow_condition'] ?? false)
                ->values()
                ->all();

            if (empty($conditions)) {
                w('SiteVisitDueJob : No valid conditions for RULE_SITE_VISIT_DUE.');
                return;
            }

            $logic = strtoupper($rule->condition_type ?? 'AND'); # Default to AND

            $site_visit_query = SiteVisit::query();
            # Always exclude completed and cancelled statuses
            $site_visit_query->whereNotIn('status', [SiteVisitConst::SITE_VISIT_COMPLETED, SiteVisitConst::SITE_VISIT_CANCELLED]);

            $site_visit_query->where(function ($query) use ($conditions, $logic) {
                foreach ($conditions as $condition) {
                    $operator = $condition['operator'] ?? '>';
                    $datatype = $condition['datatype'] ?? 'date';
                    $value = (int)($condition['value'] ?? 0);
                    $slug = $condition['trigger_event'] ?? null;

                    if ($datatype === 'date' && $slug === SiteVisitConst::RULE_SITE_VISIT_DUE) {
                        if ($operator === '<') {
                            $query->where('visit_time', '>', DB::raw("NOW() - INTERVAL '$value days'"));
                        } elseif ($operator === '==') {
                            $query->whereDate('visit_time', '=', DB::raw("CURRENT_DATE - INTERVAL '$value days'"));
                        }
                    }

                    # Add other datatype handlers here if needed
                }
            });

            $site_visit_list_ids = $site_visit_query->pluck('id');

            if ($site_visit_list_ids->isEmpty()) {
                i('SiteVisitDueJob : No site visit matched the rule conditions.');
                return;
            }

            foreach ($site_visit_list_ids as $site_visit_id) {
                try {
                    $data = SiteVisitRuleNotification($site_visit_id); # assumed helper
                    $notificationHelper = new NotificationHelper();
                    $notificationHelper->handle(SiteVisitConst::RULE_SITE_VISIT_DUE, $data, null, loginUserId());
                    i("SiteVisitDueJob : Notification sent for Site Visit ID: {$site_visit_id}");
                } catch (\Exception $e) {
                    er("SiteVisitDueJob : Error sending notification for Site Visit ID: {$site_visit_id}. Error: " . $e->getMessage());
                }
            }

            i('SiteVisitDueJob : completed.');
        } catch (\Exception $e) {
            er('SiteVisitDueJob : failed. Error: ' . $e->getMessage());
        }
    }
}
