<?php

namespace Modules\SiteVisit\Http\Controllers;

use App\Constants\CommonConst;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use Modules\SiteVisit\Services\SiteVisitService;
use Illuminate\Http\{JsonResponse, Request};
use Modules\SiteVisit\Http\Requests\{StoreSiteVisitRequest, UpdateSiteVisitRequest};
use Modules\SiteVisit\Transformers\SiteVisitResource;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Modules\AlertAndNotification\Jobs\NotificationJob;
use Modules\Clients\Models\Client;
use Modules\Leads\Models\Lead;
use Modules\Product\Models\Product;
use Modules\SiteVisit\Constants\SiteVisitConst;
use Modules\SiteVisit\Models\SiteVisit;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use stdClass;

class SiteVisitController extends Controller
{
    protected $siteVisitService;

    public function __construct(SiteVisitService $siteVisitService)
    {
        $this->siteVisitService = $siteVisitService;
    }

    public function index(Request $request): JsonResponse
    {
        $visits = $this->siteVisitService->getAllVisits()
            ->when($request->boolean('with_trashed'), fn($q) => $q->withTrashed())
            ->when($request->search, fn($q) => $q->search($request->search))
            ->when($request->client_id, fn($q) => $q->where('client_id', $request->client_id))
            ->when($request->lead_id, fn($q) => $q->where('lead_id', $request->lead_id))
            ->when($request->visit_type, fn($q) => $q->where('visit_type', $request->visit_type))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'data' => SiteVisitResource::collection($visits),
            'meta' => $this->buildPaginationMeta($visits),
            'message' => 'Site visits retrieved successfully',
            'status' => Response::HTTP_OK
        ], Response::HTTP_OK);
    }

    public function store(StoreSiteVisitRequest $request): JsonResponse
    {
        $visit = $this->siteVisitService->createVisit($request->validated());

        # Site Visit assigned to user
        if ($visit->visit_assignee) {
            NotificationJob::dispatch(SiteVisitConst::RULE_ASSIGNED_TO_USER, SiteVisitRuleNotification($visit->id), null, loginUserId());
        }

        return response()->json([
            'message' => __('Site visit created successfully'),
            'data' => new SiteVisitResource($visit),
            'status' => Response::HTTP_OK
        ], Response::HTTP_OK);
    }

    public function show(string $id): JsonResponse
    {
        $visit = $this->siteVisitService->getVisitById($id);
        return response()->json([
            'data' => new SiteVisitResource($visit),
            'message' => 'Site visit retrieved successfully',
            'status' => Response::HTTP_OK
        ], Response::HTTP_OK);
    }

    public function update(UpdateSiteVisitRequest $request, string $id): JsonResponse
    {
        $assign_user = SiteVisit::where('id', $id)->pluck('visit_assignee')->first();
        $visit = $this->siteVisitService->updateVisit($id, $request->validated());

        # Site Visit assigned to user
        if ($visit->visit_assignee != $assign_user) {
            NotificationJob::dispatch(SiteVisitConst::RULE_ASSIGNED_TO_USER, SiteVisitRuleNotification($id), null, loginUserId());
        }

        return response()->json([
            'data' => new SiteVisitResource($visit),
            'message' => 'Site visit updated successfully',
            'status' => Response::HTTP_OK
        ], Response::HTTP_OK);
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $this->siteVisitService->deleteVisit($id);
            return response()->json([
                'message' => 'Site visit deleted successfully'
            ], Response::HTTP_OK);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Site visit not found',
                'error' => $e->getMessage()
            ], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete site visit',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    protected function buildPaginationMeta($paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
        ];
    }

    public function StatusUpdate(Request $request)
    {
        $record = SiteVisit::findOrFail($request->id);
        $oldStatus =  $record->status;
        $record->status = $request->status;
        $record->save();
        $this->siteVisitService->existTriggerActionSendFile($record->id, $request->status, $oldStatus);
        return $this->actionSuccess('SiteVisit status updated successfully', $record);
    }

    public function generateChallan(Request $request)
    {
        $siteVisit = SiteVisit::findOrFail($request->id);
        if ($siteVisit->lead_id)
            $parentRecord = Lead::find($siteVisit->lead_id);
        else if ($siteVisit->client_id)
            $parentRecord = Client::find($siteVisit->client_id);
        else $parentRecord = new stdClass;
        $siteVisit->contact_person = $parentRecord->contact_person ?? "-";
        $siteVisit->phone = $parentRecord->phone ?? '-';
        $siteVisit->address = $parentRecord->address ?? '-';
        $products = Product::whereIn('id', $siteVisit->products)->get();

        $data = [
            'products' => $products,
            'siteVisit' => $siteVisit,
            'company' => Setting::where('key', 'company_name')->first()->value ?? "-"
        ];

        $pdf = Pdf::loadView('pdf.challan', $data);
        $filename = 'attachment_' . time() . '.pdf';
        return $pdf->download($filename);
    }
}
