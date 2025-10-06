<?php

namespace Modules\Dashboard\Http\Controllers;

use App\Constants\CommonConst;
use App\Http\Controllers\Controller;
use App\Models\AdminControlConfig;
use App\Models\User;
use Illuminate\Http\{JsonResponse, Request};
use Modules\Dashboard\Services\DashboardService;
use Modules\SiteVisit\Models\SiteRiskManagement;
use Modules\SiteVisit\Models\SiteVisit;
use Symfony\Component\HttpFoundation\Response;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Modules\Clients\Models\Client;
use Modules\Leads\Constants\LeadConst;
use Modules\Leads\Models\Lead;
use Modules\Product\Models\ProductService;
use Modules\RolePermission\Constants\RolePermissionConst;

/**
 * @group Dashboard
 *
 * APIs for retrieving dashboard-related data.
 *
 * @authenticated
 */
class DashboardController extends Controller
{
    protected $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Dashboard Overview
     *
     * Get overall dashboard details.
     */
    public function index(Request $request)
    {
        return $this->actionSuccess('Dashboard retrieved successfully', $this->dashboardService->getDashboard());
    }

    /**
     * Upcoming Team Events
     *
     * Get upcoming events for team members.
     *
     * @queryParam filters object Optional filters in JSON format. Example: {"department": "sales"}
     * @queryParam start_date string Date format: Y-m-d. Example: 2025-07-01
     * @queryParam end_date string Date format: Y-m-d. Example: 2025-07-31
     */
    public function teamUpcomingEvents(Request $request)
    {
        $filters = $request->get('filters', []);
        if (is_string($filters)) {
            $filters = json_decode($filters, true) ?? [];
        }
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $data = $this->dashboardService->getUpcomingEvents(User::class, $filters, ['status' => ['!=', CommonConst::IN_ACTIVE]], $startDate, $endDate);

        return $this->actionSuccess('Team upcoming events retrieved successfully', $data);
    }

    /**
     * Upcoming Lead Events
     *
     * Get upcoming events for leads.
     *
     * @queryParam filters object Optional filters in JSON format. Example: {"status": "active"}
     * @queryParam start_date string Date format: Y-m-d. Example: 2025-07-01
     * @queryParam end_date string Date format: Y-m-d. Example: 2025-07-31
     */
    public function upcomingLeadEvents(Request $request)
    {
        $leadModel = 'Modules\\Leads\\Models\\Lead';
        if (!class_exists($leadModel)) return $this->actionSuccess('Upcoming lead events retrieved successfully', ['birthdays' => [], 'anniversaries' => []]);

        $filters = $request->get('filters', []);
        if (is_string($filters)) {
            $filters = json_decode($filters, true) ?? [];
        }
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $data = $this->dashboardService->getUpcomingEvents($leadModel, $filters, ['status' => ['notIn', [LeadConst::CONVERT_TO_CLIENT, CommonConst::IN_ACTIVE]]], $startDate, $endDate);

        return $this->actionSuccess('Upcoming lead events retrieved successfully', $data);
    }

    /**
     * Upcoming Client Events
     *
     * Get upcoming events for clients.
     *
     * @queryParam filters object Optional filters in JSON format. Example: {"location": "New York"}
     * @queryParam start_date string Date format: Y-m-d. Example: 2025-07-01
     * @queryParam end_date string Date format: Y-m-d. Example: 2025-07-31
     */
    public function upcomingClientEvents(Request $request)
    {
        $clientModel = 'Modules\\Clients\\Models\\Client';
        if (!class_exists($clientModel)) return $this->actionSuccess('Upcoming client events retrieved successfully', ['birthdays' => [], 'anniversaries' => []]);
        $filters = $request->get('filters', []);
        if (is_string($filters)) {
            $filters = json_decode($filters, true) ?? [];
        }
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $data = $this->dashboardService->getUpcomingEvents($clientModel,  $filters, ['status' => ['!=', CommonConst::IN_ACTIVE]], $startDate, $endDate);

        return $this->actionSuccess('Upcoming client events retrieved successfully', $data);
    }

    /**
     * Calendar Events
     *
     * Get calendar events for the specified calendars.
     *
     * @bodyParam calendars array required List of calendar IDs. Example: [1,2,3]
     * @bodyParam start_date string required Date format: Y-m-d. Example: 2025-07-01
     * @bodyParam end_date string required Date format: Y-m-d. Example: 2025-07-31
     */
    public function calendarEvents(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'calendars' => 'required|array|min:1',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);

        # If validation fails, return the errors
        if ($validator->fails()) return $this->validationFailed(true, $validator->errors()->first());

        try {
            # Parse start and end dates directly with Carbon
            $start_date = $request->start_date ?? Carbon::now()->startOfMonth()->toDateTimeString();
            $end_date = $request->end_date ?? Carbon::now()->endOfMonth()->toDateTimeString();

            # Get the calendars as an array
            $calendars = $request->calendars;

            # Fetch calendar events from the service
            $events = $this->dashboardService->getCalendarEvents($calendars, $start_date, $end_date);

            return $this->actionSuccess('Calendar Events List retrieved successfully', $events);
        } catch (\Throwable $e) {
            # Log the error and return the failure message
            er('DashboardController : Calendar Events Error: ' . $e->getMessage());
            return $this->actionFailure($e->getMessage());
        }
    }

    /**
     * Upcoming Site Risk Management
     *
     * Get upcoming site risk management events for the next 14 days.
     */
    public function upcomingSiteRiskManagement()
    {
        $today = Carbon::now();
        $start = $today->subYear()->startOfDay();
        $end = $today->copy()->addDays(14)->endOfDay();

        # Eager load site_visit, filter only installation visit_type
        $siteRiskManagementData = SiteRiskManagement::with('site_visit')
            ->whereHas('site_visit', fn($q) => $q->where('visit_type', 'installation'))
            ->whereBetween('visit_datetime', [$start, $end])
            ->orderBy('visit_datetime')
            ->get();

        $results = $siteRiskManagementData->transform(function ($risk) {
            $visit = $risk->site_visit;
            return [
                'id'             => $visit->id,
                'visit_type'     => $visit->visit_type,
                'visit_time'     => $risk->visit_datetime,
                'visit_assignee' => $risk->visit_assignee_id,
                'status'         => $visit->status,
                'visit_notes'    => $visit->visit_notes,
                'upcoming_time'  => $risk->visit_datetime->copy()->addYear()->toDateTimeString(),
                'items'          => $this->fetchingProductNames($visit),
                'route_name'     => $visit->lead_id ? 'lead-details-id' : 'client-details-id',
                'route_id'       => $visit->lead_id ?? $visit->client_id
            ];
        });

        return $this->actionSuccess('Filtered Site Risk Management list from last year range retrieved', $results);
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
    public function fetchingProductNames($siteVisit)
    {
        $productIds = $siteVisit->products;

        return is_array($productIds)
            ? ProductService::whereIn('id', $productIds)->pluck('name')->toArray()
            : [];
    }

    /**
     * @group Dashboard
     * 
     * User Information Chart List
     * 
     * Retrieves the monthly count of users by status (excluding Super Admins).
     *
     * @authenticated
     */
    public function userInformationChartList(Request $request)
    {
        $months = collect(range(1, 12));

        # Fetch dynamic statuses for Users
        $statusList = AdminControlConfig::where('status_for', CommonConst::MODULE_USER)
            ->select('slug', 'status_text', 'status_color')
            ->orderBy('position', 'asc')
            ->get();

        $statuses = [];
        foreach ($statusList as $status) {
            $data = [];
            foreach ($months as $month) {
                $count = User::whereHas('roles', fn($q) => $q->whereNotIn('slug', [RolePermissionConst::SLUG_SUPER_ADMIN]))
                    ->where('status', $status->slug)
                    ->whereMonth('created_at', $month)
                    ->count();

                $data[] = $count; # Always push 12 months
            }

            $statuses[] = [
                'slug'         => $status->slug,
                'status_text'  => $status->status_text,
                'status_color' => $status->status_color,
                'data'         => $data,
            ];
        }

        return $this->actionSuccess('User Information Chart List retrieved successfully', [
            'months'   => $months->map(fn($m) => Carbon::create()->month($m)->format('M')),
            'statuses' => $statuses,
        ]);
    }

    /**
     * @group Dashboard
     *
     * Get Lead Information Chart List
     *
     * This API retrieves the monthly lead data categorized by statuses.
     *
     * @authenticated
     *
     * @queryParam start_year int optional Filter results starting from this year. Example: 2023
     * @queryParam end_year int optional Filter results up to this year. Example: 2024
     */
    public function leadInfoChartList(Request $request)
    {
        $months = collect(range(1, 12));
        $start_year =  $request->start_year ?? null;
        $end_year = $request->end_year ?? null;

        # Fetch dynamic statuses for Users
        $statusList = AdminControlConfig::where('status_for', CommonConst::MODULE_LEAD)
            ->select('slug', 'status_text', 'status_color')
            ->orderBy('position', 'asc')
            ->get();

        $statuses = [];
        foreach ($statusList as $status) {
            $data = [];
            foreach ($months as $month) {
                $query = Lead::query()->whereMonth('created_at', $month)->where('status', $status->slug);

                if ($start_year) $query->whereYear('created_at', '>=', $start_year);
                if ($start_year) $query->whereYear('created_at', '<=', $end_year);

                $count = $query->count();

                $data[] = $count;
            }

            $statuses[] = [
                'slug'         => $status->slug,
                'status_text'  => $status->status_text,
                'status_color' => $status->status_color,
                'data'         => $data,
            ];
        }

        return $this->actionSuccess('Lead Information Chart List retrieved successfully', [
            'months'   => $months->map(fn($m) => Carbon::create()->month($m)->format('M')),
            'statuses' => $statuses,
        ]);
    }

    /**
     * @group Dashboard
     *
     * Get Client Information Chart List
     *
     * This API retrieves the monthly client data categorized by statuses.
     *
     * @authenticated
     *
     * @queryParam start_year int optional Filter results starting from this year. Example: 2023
     * @queryParam end_year int optional Filter results up to this year. Example: 2024
     */
    public function clientInfoChartList(Request $request)
    {
        $months = collect(range(1, 12));

        $start_year =  $request->start_year ?? null;
        $end_year = $request->end_year ?? null;

        # Fetch dynamic statuses for Users
        $statusList = AdminControlConfig::where('status_for', CommonConst::MODULE_USER)
            ->select('slug', 'status_text', 'status_color')
            ->orderBy('position', 'asc')
            ->get();

        $statuses = [];
        foreach ($statusList as $status) {
            $data = [];
            foreach ($months as $month) {
                $query = Client::query()->whereMonth('created_at', $month)->where('status', $status->slug);

                if ($start_year) $query->whereYear('created_at', '>=', $start_year);
                if ($start_year) $query->whereYear('created_at', '<=', $end_year);

                $count = $query->count();

                $data[] = $count;
            }

            $statuses[] = [
                'slug'         => $status->slug,
                'status_text'  => $status->status_text,
                'status_color' => $status->status_color,
                'data'         => $data,
            ];
        }

        return $this->actionSuccess('Client Information Chart List retrieved successfully', [
            'months'   => $months->map(fn($m) => Carbon::create()->month($m)->format('M')),
            'statuses' => $statuses,
        ]);
    }
}
