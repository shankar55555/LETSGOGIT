<?php

namespace Modules\Dashboard\Services;

use App\Constants\CommonConst;
use App\Models\User;
use App\Models\UserAttendance;
use App\Models\UserTarget;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Modules\Clients\Constants\ClientConst;
use Modules\Clients\Models\Client;
use Modules\Invoices\Constants\InvoiceConst;
use Modules\Invoices\Models\Invoice;
use Modules\Leads\Constants\LeadConst;
use Modules\Leads\Models\Lead;
use Modules\Quotations\Constants\QuotationConst;
use Modules\Quotations\Models\Quotation;
use Modules\RolePermission\Constants\RolePermissionConst;
use Nwidart\Modules\Facades\Module;
use Modules\Product\Models\ProductService;
use Modules\SiteVisit\Models\SiteVisit;

class DashboardService
{
    protected $login_user;

    public function __construct()
    {
        $this->login_user = request()->user() ?? Auth::user();
    }

    /**
     * Get dashboard data summary
     *
     * Returns counts for users, leads, clients, quotations, invoices, targets, incentives,
     * and attendance based on the authenticated user.
     *
     * @return array
     */
    public function getDashboard(): array
    {
        # check login user type
        $user_view_id = null;
        $roleSlugs = $this->login_user->roles()->pluck('slug')->toArray();
        $markAttendance = $this->login_user->mark_attendance;
        $isAdmin = !empty(array_intersect($roleSlugs, [RolePermissionConst::SLUG_SUPER_ADMIN, RolePermissionConst::SLUG_ADMIN]));

        # User (Total, in-active) count
        $userCount = $inActiveUserCount = 0;
        $userQuery = User::query();
        $userCount = (clone $userQuery)->count();
        $inActiveUserCount = (clone $userQuery)->where('status', CommonConst::IN_ACTIVE)->count();

        # Client (in-active, total) count 
        $clientCount = $nonInActiveClientCount = 0;
        if (Module::has(CommonConst::MODULE_CLIENT)) {
            $clientsQuery = applyFilteringUser_new(Client::query(), ['created_by', 'assigned_user'], $user_view_id);
            $clientCount = (clone $clientsQuery)->count();
            $nonInActiveClientCount = (clone $clientsQuery)->where('status', '!=', CommonConst::IN_ACTIVE)->count();
        }

        # Lead (in-active, total) count 
        $leadCount = $nonInActiveLeadCount = 0;
        if (Module::has(CommonConst::MODULE_LEAD)) {
            $leadsQuery = applyFilteringUser_new(Lead::query()->whereNot('status', LeadConst::CONVERT_TO_CLIENT), ['created_by', 'assigned_user'], $user_view_id);
            $leadCount = (clone $leadsQuery)->count();
            $nonInActiveLeadCount = (clone $leadsQuery)->where('status', '!=', CommonConst::IN_ACTIVE)->count();
        }

        # Quotation (accepted ,cancelled, total) count 
        $quotationCount = $pendingQuotationCount = $acceptedQuotationCount = $rejectedQuotationCount =  $expiredQuotationCount = 0;
        if (Module::has(CommonConst::MODULE_QUOTATION)) {
            $quotationQuery = applyFilteringUser(Quotation::query(), 'created_by', $user_view_id);
            $quotations = $quotationQuery->get(['status']); # Only need to call get() once
            $quotationCount = $quotations->count();
            $acceptedQuotationCount = $quotations->where('status', QuotationConst::QUOTATION_ACCEPTED)->count();
            $rejectedQuotationCount = $quotations->where('status', QuotationConst::QUOTATION_REJECTED)->count();
            $expiredQuotationCount = $quotations->where('status', QuotationConst::QUOTATION_EXPIRED)->count();
            $pendingQuotationCount = $quotations->whereNotIn('status', [QuotationConst::QUOTATION_ACCEPTED, QuotationConst::QUOTATION_REJECTED, QuotationConst::QUOTATION_EXPIRED])->count();
        }

        # Invoice (pending ,un-paid,total) count 
        $invoiceCount = $unPaidInvoiceCount = 0;
        if (Module::has(CommonConst::MODULE_INVOICE)) {
            $invoiceQuery = applyFilteringUser(Invoice::query(), 'created_by', $user_view_id);
            $invoiceCount = (clone $invoiceQuery)->count();
            $unPaidInvoiceCount = (clone $invoiceQuery)->whereNotIn('status', [InvoiceConst::DRAFT, InvoiceConst::PAID, InvoiceConst::PAID_TO_CANCELLED])->count();
        }

        # Target and incentive Amount 
        $thisMonth = Carbon::now()->startOfMonth()->format('Y-m-d');
        $lastMonth = Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d');
        $userIncentiveAmount = applyFilteringUser(UserTarget::query(), 'user_id', $user_view_id)->whereNotNull('incentive')->where('month', $lastMonth)->sum('incentive');
        $thisMonthUserTotalTarget = applyFilteringUser(UserTarget::query(), 'user_id', $user_view_id)->whereNotNull('target_amount')->where('month', $thisMonth)->sum('target_amount');

        # User Salary and attendance count 
        $attendance = Module::has(CommonConst::MODULE_ATTENDANCE) ? $this->getAttendanceDetail() : ['user_salary' => (int) ($this->login_user->salary ?? 0), 'last_month_salary' => 0, 'last_month_total_attendance' => 0, 'last_month_present_attendance' => 0, 'this_month_total_attendance' => 0, 'this_month_present_attendance' => 0,];

        return array_merge([
            'is_admin' => $isAdmin,
            'user_type' => array_intersect($roleSlugs, [RolePermissionConst::SLUG_SUPER_ADMIN]) ? RolePermissionConst::SUPER_ADMIN : (array_intersect($roleSlugs, [RolePermissionConst::SLUG_ADMIN]) ? RolePermissionConst::ADMIN : RolePermissionConst::EMPLOYEE),
            'mark_attendance' => $markAttendance,
            'user_count' => $userCount,
            'in_active_user_count' => $inActiveUserCount,
            'client_count' => $clientCount,
            'non_in_active_client_count' => $nonInActiveClientCount,
            'lead_count' => $leadCount,
            'non_in_active_lead_count' => $nonInActiveLeadCount,
            'user_incentive_amount' => $userIncentiveAmount,
            "this_month_user_total_target" => $thisMonthUserTotalTarget,
            'quotation_count' => $quotationCount,
            'pending_quotation_count' => $pendingQuotationCount,
            "accepted_quotation_count" => $acceptedQuotationCount,
            "cancel_quotation_count" => $rejectedQuotationCount,
            "expired_quotation_count" => $expiredQuotationCount,
            'invoice_count' => $invoiceCount,
            'un_paid_invoice_count' => $unPaidInvoiceCount,
        ], $attendance);
    }

    /**
     * Get attendance details for current and last month
     *
     * @return array
     */
    protected function getAttendanceDetail(): array
    {
        $now = Carbon::now();
        $lastMonth = $now->copy()->subMonth();

        # this Month User Attendance
        $attendanceQuery = UserAttendance::query()->whereYear('attendance_date', $now->year)->whereMonth('attendance_date', $now->month);
        $thisMonthPresent = (clone $attendanceQuery)->where('status', CommonConst::PRESENT)->count();
        $thisMonthHalfPresent = (clone $attendanceQuery)->where('status', CommonConst::HALF_PRESENT)->count();
        $thisMonthTotal = $attendanceQuery->count();
        $thisMonthTotalAttendance = $thisMonthPresent + ($thisMonthHalfPresent / 2);

        # Last Month User Attendance
        $lastAttendanceQuery = UserAttendance::query()->whereYear('attendance_date', $lastMonth->year)->whereMonth('attendance_date', $lastMonth->month);
        $lastMonthPresent = (clone $lastAttendanceQuery)->where('status', CommonConst::PRESENT)->count();
        $lastMonthHalfPresent = (clone $lastAttendanceQuery)->where('status', CommonConst::HALF_PRESENT)->count();
        $lastMonthTotal = $lastAttendanceQuery->count();
        $lastMonthTotalAttendance = $lastMonthPresent + ($lastMonthHalfPresent / 2);

        # Attendance According Make salary 
        $salary = (int) ($this->login_user->salary ?? 0);
        $attendancePercentage = $lastMonthTotal > 0 ? ($lastMonthTotalAttendance / $lastMonthTotal) * 100 : 0;
        $lastMonthSalary = $salary * ($attendancePercentage / 100);

        return [
            'user_salary' => $salary,
            'last_month_salary' => $lastMonthSalary,
            'last_month_total_attendance' => $lastMonthTotal ? $lastMonthTotal : $lastMonth->daysInMonth,
            'last_month_present_attendance' => $lastMonthTotalAttendance,
            'this_month_total_attendance' => $thisMonthTotal,
            'this_month_present_attendance' => $thisMonthTotalAttendance,
        ];
    }

    /**
     * Get upcoming events (birthdays or anniversaries)
     *
     * @group Dashboard
     * @authenticated
     * @urlParam start_date date optional The start date for events filter.
     * @urlParam end_date date optional The end date for events filter.
     *
     * @param string $modelClass
     * @param array $fields
     * @param array $conditions
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    public function getUpcomingEvents($modelClass, array $fields, array $conditions = [], $startDate = null, $endDate = null)
    {
        $results = [];

        // Convert dates to Carbon
        $startDate = $startDate ? Carbon::parse($startDate) : Carbon::today();
        $endDate = $endDate ? Carbon::parse($endDate) : Carbon::today()->addDays(13);

        foreach ($fields as $key => $column) {
            $query = $modelClass::query();

            // Apply conditions
            foreach ($conditions as $field => [$operator, $value]) {
                if ($operator === 'notIn') {
                    $query->whereNotIn($field, $value);
                } else {
                    $query->where($field, $operator, $value);
                }
            }

            $query->whereNotNull($column);

            // Filter by date range (month-day comparison for recurring events)
            $start = $startDate->format('m-d');
            $end = $endDate->format('m-d');

            if ($start <= $end) {
                // Normal case (same year range)
                $query->whereRaw("TO_CHAR($column, 'MM-DD') BETWEEN ? AND ?", [$start, $end]);
            } else {
                // Case for crossing year end
                $query->where(function ($q) use ($column, $start, $end) {
                    $q->whereRaw("TO_CHAR($column, 'MM-DD') >= ?", [$start])
                        ->orWhereRaw("TO_CHAR($column, 'MM-DD') <= ?", [$end]);
                });
            }

            $results[$key] = $query->get()->toArray();
        }

        return $results;
    }

    /**
     * Get calendar events (Follow-up and Site visits)
     *
     * @group Dashboard
     * @authenticated
     * @bodyParam calendars array required List of calendar types to filter events.
     * @bodyParam start_date date required Start date for event range.
     * @bodyParam end_date date required End date for event range.
     *
     * @param array $calendars
     * @param string $start_date
     * @param string $end_date
     * @return array
     */
    public function getCalendarEvents(array $calendars, string $start_date, string $end_date): array
    {
        $events = [];
        $eventSources = [
            [
                'model' => 'Modules\\FollowUp\\Models\\FollowUp',
                'type' => 'Follow Up',
                'types' => [
                    'Leads Followup' => ['field' => 'lead_id', 'prefix' => 'follow_up_lead', 'url' => '/leads/details/', 'label' => 'Leads Followup'],
                    'Client Followup' => ['field' => 'client_id', 'prefix' => 'follow_up_client', 'url' => '/clients/details/', 'label' => 'Client Followup'],
                ],
                'dateField' => 'next_call_datetime',
            ],
            [
                'model' => 'Modules\\SiteVisit\\Models\\SiteVisit',
                'type' => 'Site Visit',
                'types' => [
                    'Leads Site Visit' => ['field' => 'lead_id', 'prefix' => 'site_visit_lead', 'url' => '/leads/details/', 'label' => 'Leads Site Visit'],
                    'Client Site Visit' => ['field' => 'client_id', 'prefix' => 'site_visit_client', 'url' => '/clients/details/', 'label' => 'Client Site Visit'],
                ],
                'dateField' => 'visit_datetime', //visit_time
            ],
        ];

        foreach ($eventSources as $source) {
            $this->processCalendarEvents($events, $calendars, $source, $start_date, $end_date);
        }

        return $events;
    }

    /**
     * Process calendar events from specific source
     *
     * @param array $events
     * @param array $calendars
     * @param array $source
     * @param string $start_date
     * @param string $end_date
     * @return void
     */
    private function processCalendarEvents(array &$events, array $calendars, array $source, string $start_date, string $end_date): void
    {
        if (!class_exists($source['model'])) {
            return;
        }
        $model = $source['model'];
        $dateField = $source['dateField'];
        $model_ids = [];

        # Filter model IDs based on type and calendar
        if ($source['type'] === 'Follow Up') {
            if (in_array('Leads Followup', $calendars)) {
                $model_ids = array_merge($model_ids, $model::whereHas('lead', fn($q) => applyFilteringUser_new($q, ['created_by', 'assigned_user']))
                    ->whereBetween($dateField, [$start_date, $end_date])
                    ->pluck('id')
                    ->toArray());
            }

            if (in_array('Client Followup', $calendars)) {
                $model_ids = array_merge($model_ids, $model::whereHas('client', fn($q) => applyFilteringUser_new($q, ['created_by', 'assigned_user']))
                    ->whereBetween($dateField, [$start_date, $end_date])
                    ->pluck('id')
                    ->toArray());
            }
        }

        if ($source['type'] === 'Site Visit') {
            if (in_array('Leads Site Visit', $calendars)) {

                //     ->whereHas('site_risk', fn($q) => $q->whereBetween($dateField, [$start_date, $end_date]));
                # $query = $model::where('visit_type', 'inspection')
                #     ->whereHas('lead', fn($q) => applyFilteringUser_new($q, ['created_by', 'assigned_user']))
                #     ->whereHas('site_risk', fn($q) => $q->whereBetween($dateField, [$start_date, $end_date]));

                $model_ids = array_merge($model_ids, $model::whereHas('lead', fn($q) => applyFilteringUser_new($q, ['created_by', 'assigned_user']))
                    ->whereHas('site_risk', fn($q) => $q->whereBetween($dateField, [$start_date, $end_date]))
                    ->pluck('id')
                    ->toArray());
            }

            if (in_array('Client Site Visit', $calendars)) {
                $model_ids = array_merge($model_ids, $model::whereHas('client', fn($q) => applyFilteringUser_new($q, ['created_by', 'assigned_user']))
                    ->whereHas('site_risk', fn($q) => $q->whereBetween($dateField, [$start_date, $end_date]))
                    ->pluck('id')
                    ->toArray());
            }

            $model_ids = array_merge($model_ids, $model::where(fn($q) => applyFilteringUser_new($q, ['visit_assignee']))
                ->whereHas('site_risk', fn($q) => $q->whereBetween($dateField, [$start_date, $end_date]))
                ->pluck('id')
                ->toArray());
        }



        # Remove duplicates
        $model_ids = array_unique($model_ids);

        if (empty($model_ids)) return;

        # Query the actual model items
        $query = $model::query()->whereIn('id', $model_ids);

        if ($source['type'] === 'Site Visit') {
            $query->with('site_risk');
        }

        foreach ($query->get() as $item) {
            foreach ($source['types'] as $calendar => $info) {
                $field = $info['field'];
                if (!empty($item->$field) && $this->shouldIncludeEvent($calendars, $calendar)) {

                    $events[] = $this->makeCalendarEvent(
                        $info['prefix'],
                        $item,
                        $item->id,
                        $item->title ?? ucfirst($info['prefix']),
                        $item->$dateField ?? $item->site_risk->visit_datetime ?? "",
                        $calendar,
                        url($info['url'] . $item->$field),
                        [$field => $item->$field]
                    );
                }
            }
        }
    }
    /**
     * Check if calendar event type should be included
     *
     * @param array $calendars
     * @param string $calendarType
     * @return bool
     */
    private function shouldIncludeEvent(array $calendars, string $calendarType): bool
    {
        return empty($calendars) || in_array($calendarType, $calendars);
    }
    /**
     * Build a calendar event structure
     *
     * @param string $type
     * @param mixed $model
     * @param mixed $id
     * @param string $title
     * @param string $datetime
     * @param string $calendarType
     * @param string $url
     * @param array $additionalData
     * @return array
     */
    private function makeCalendarEvent(string $type, $model, $id, ?string $title = null, string $start, string $calendar, string $url = '', array $extraProps = []): array
    {
        $user_info = $model->lead_id ? $model->lead : $model->client;
        $products = "";
        $time = "";
        if ($type === 'follow_up_lead' && $model->next_call_datetime) {
            # Show only time part of next_call_datetime
            $title = \Carbon\Carbon::parse($model->next_call_datetime)->format('h:i A');
            $time = \Carbon\Carbon::parse($model->next_call_datetime)->format('Y-m-d h:i A');
        } else {
            # Default title fallback
            $title = $user_info->name ?? ucfirst(str_replace('_', ' ', $type));
            if ($model->products) {
                $products = ProductService::whereIn('id', $model->products)->pluck('name')->toArray();
                $products =  implode(",", $products);
            }
        }
        $extraProps['products'] = $products;
        $extraProps['time'] = $time;
        return [
            'id' => "{$type}_{$id}",
            'url' => $url,
            'title' => $title,
            'start' => $start,
            'end' => $start,
            'allDay' => false,
            'extendedProps' => array_merge(['calendar' => $calendar], $extraProps),
        ];
    }

    /**
     * Fetch product names for a given site visit
     *
     * Retrieves the names of products associated with the provided site visit.
     *
     * @param \Modules\SiteVisit\Models\SiteVisit $siteVisit The site visit model instance.
     * 
     * @return array List of product names.
     */
    protected function fetchingProductNames($siteVisit)
    {
        $productNames = [];
        $productIds = $siteVisit->products;
        if (is_array($productIds)) {
            $productNames = ProductService::whereIn('id', $productIds)->pluck('name')->toArray();
        }
        return $productNames;
    }
}
