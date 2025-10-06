<?php

namespace Modules\AlertAndNotification\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\AlertAndNotification\Models\Rule;

class RuleService
{
    public function getPaginatedRule(
        int $per_page = 15,
        ?string $search = null,
        ?string $module = null,
        ?string $trigger_event = null,
        ?string $status = null,
        ?string $created_by = null,
        ?string $last_updated_by = null,
    ): LengthAwarePaginator {
        $query = Rule::query()->search($search);
        return $query->when($status, fn($q) => $q->where('status', $status))
            ->when($module, fn($q) => $q->where('module', $module))
            ->when($trigger_event, fn($q) => $q->where('trigger_event', $trigger_event))
            ->when($created_by, fn($q) => $q->where('created_by', $created_by))
            ->when($last_updated_by, fn($q) => $q->where('last_updated_by', $last_updated_by))
            ->with(['creator', 'updater'])
            ->latest()
            ->paginate($per_page);
    }

    public function getRuleById(string $id): Rule
    {
        return Rule::with(['creator', 'updater'])
            ->findOrFail($id);
    }

    public function createRule(array $data): Rule
    {
        return Rule::create($data);
    }

    public function updateRule(string $id, array $data): Rule
    {
        $rule = $this->getRuleById($id);
        $rule->update($data);
        return $rule->fresh();
    }

    public function deleteRule(string $id): void
    {
        $rule = $this->getRuleById($id);
        $rule->delete();
    }

    /**
     * Handle lead-specific rules
     */
    public function handleLeadRules($slug, $control, $value, $datatype, $data)
    {
        $query = DB::table('leads');
        $query->where('id', $data['id']);
        $value = (int) $value;

        switch ($slug) {
            case 'no-action':
                if ($control === '>') {
                    $query->where('updated_at', '<', DB::raw("NOW() - INTERVAL '$value days'"));
                } elseif ($control === '==') {
                    $query->whereDate('updated_at', '=', DB::raw("CURRENT_DATE - INTERVAL '$value days'"));
                }
                break;
        }

        return $query->exists();
    }

    /**
     * Handle lead-specific rules
     */
    public function handleClientRules($slug, $control, $value, $datatype, $data)
    {
        $query = DB::table('clients');
        $query->where('id', $data['id']);
        $value = (int) $value;

        switch ($slug) {
            case 'client-inactive':
                if ($control === '>') {
                    $query->where('updated_at', '<', DB::raw("NOW() - INTERVAL '$value days'"));
                } elseif ($control === '==') {
                    $query->whereDate('updated_at', '=', DB::raw("CURRENT_DATE - INTERVAL '$value days'"));
                }
                break;
        }

        return $query->exists();
    }

    /**
     * Handle quotation-specific rules
     */
    public function handleQuotationRules($slug, $control, $value, $datatype, $data)
    {
        $query = DB::table('quotations');
        $query->where('id', $data['id']);
        $value = (int) $value;

        if ($slug === 'quotation-expired') {
            if ($control === '<') {
                $query->where('valid_uptil', '>', DB::raw('NOW()'))
                    ->where('valid_uptil', '<', DB::raw("NOW() + INTERVAL '$value days'"));
            } elseif ($control === '==') {
                $query->whereDate('valid_uptil', '=', DB::raw("CURRENT_DATE - INTERVAL '$value days'"));
            }
        }

        return $query->exists();
    }

    /**
     * Handle contract-specific rules
     */
    public function handleContractRules($slug, $control, $value, $datatype, $data)
    {
        $query = DB::table('contracts');
        $query->where('id', $data['id']);
        $value = (int) $value;

        if ($slug === 'contract-end-date-passed' || $slug === 'days-before-expiry') {
            if ($control === '<') {
                $query->where('end_date', '>', DB::raw('NOW()'))
                    ->where('end_date', '<', DB::raw("NOW() + INTERVAL '$value days'"));
            } elseif ($control === '>') {
                $query->where('end_date', '<', DB::raw('NOW()'))
                    ->where('end_date', '>', DB::raw("NOW() - INTERVAL '$value days'"));
            }
        }

        return $query->exists();
    }

    /**
     * Handle invoice-specific rules
     */
    public function handleInvoiceRules($slug, $control, $value, $datatype, $data)
    {
        $query = DB::table('invoices');
        $query->where('id', $data['id']);
        $value = (int) $value;

        switch ($slug) {
            case 'days-before-due':
                $query->where('due_date', '>', DB::raw('NOW()'))
                    ->where('due_date', '<', DB::raw("NOW() + INTERVAL '$value days'"));
                break;

            case 'after-due-date':
                $query->where('due_date', '<', DB::raw('NOW()'))
                    ->where('due_date', '>', DB::raw("NOW() - INTERVAL '$value days'"));
                break;

            case 'partial-payment':
                if ($control === '<') {
                    $query->where('amount_paid', '<', $value);
                } elseif ($control === '>') {
                    $query->where('amount_paid', '>', $value);
                } elseif ($control === '%<') {
                    $query->whereRaw("(amount_paid::numeric / NULLIF(total, 0)) * 100 < ?", [$value]);
                } elseif ($control === '%>') {
                    $query->whereRaw("(amount_paid::numeric / NULLIF(total, 0)) * 100 > ?", [$value]);
                }
                break;
        }

        return $query->exists();
    }
    /**
     * Handle lead-specific rules
     */
    public function handleSiteVisitRules($slug, $control, $value, $datatype, $data)
    {
        $query = DB::table('site_visits');
        $query->where('id', $data['id']);
        $value = (int) $value;

        switch ($slug) {
            case 'site-visit-due':
                if ($control === '<') {
                    $query->where('visit_time', '>', DB::raw("NOW() - INTERVAL '$value days'"));
                } elseif ($control === '==') {
                    $query->whereDate('visit_time', '=', DB::raw("CURRENT_DATE - INTERVAL '$value days'"));
                }
                break;
        }

        return $query->exists();
    }

    /**
     * Handle lead-specific rules
     */
    public function handleFollowupRules($slug, $control, $value, $datatype)
    {
        $query = DB::table('follow_ups');
        $value = (int) $value;

        switch ($slug) {
            case 'follow-up-due':
            case 'follow-up-overdue':
                if ($control === '>') {
                    $query->where('site_visit_datetime', '<', DB::raw("NOW() - INTERVAL '$value days'"));
                } elseif ($control === '<') {
                    $query->where('site_visit_datetime', '>', DB::raw("NOW() - INTERVAL '$value days'"));
                } elseif ($control === '==') {
                    $query->whereDate('site_visit_datetime', '=', DB::raw("CURRENT_DATE - INTERVAL '$value days'"));
                }
                break;
        }

        return $query->pluck('id');
    }
}
