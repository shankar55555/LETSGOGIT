<?php

namespace Modules\Targets\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\Auth;
use Modules\Targets\Http\Requests\{IncentiveStoreRequest};
use Modules\Targets\Services\IncentiveService;
use Modules\Targets\Services\TargetService;
use Modules\Targets\Transformers\IncentiveResource;
use Symfony\Component\HttpFoundation\Response;

class IncentiveController extends Controller
{
    protected $incentiveService;
    protected $targetService;

    public function __construct(IncentiveService $incentiveService, TargetService $targetService)
    {
        $this->targetService = $targetService;
        $this->incentiveService = $incentiveService;
    }

    public function index(Request $request): JsonResponse
    {
        $paginated = $this->incentiveService->getPaginatedIncentive(
            $request->integer('per_page', 15),
            $request->boolean('with_trashed'),
            $request->input('search'),
            $request->input('user_id'),
        );

        return response()->json([
            'data' => IncentiveResource::collection($paginated->items()),
            'totalIncentives' => $this->incentiveService->calculateTotalIncentives($request->user_id ?? Auth::user()->uuid),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'last_page' => $paginated->lastPage(),
            ],
            'message' => 'Incentive retrieved successfully'
        ]);
    }

    public function store(IncentiveStoreRequest $request): JsonResponse
    {

        $target = $this->targetService->getTargetById($request->target_id);
        $amount = $target->target_value * 0.10; // Your calculation logic

        $incentive = $this->incentiveService->createIncentive(
            array_merge($request->validated(), [
                'amount' => $amount,
                'status' => 'pending'
            ])
        );
        return response()->json([
            'data' => new IncentiveResource($incentive),
            'message' => 'Incentive created successfully'
        ], Response::HTTP_CREATED);
    }

    public function show(string $id): JsonResponse
    {

        $target = $this->incentiveService->getIncentiveById($id);
        return response()->json([
            'data' => new IncentiveResource($target),
            'message' => 'Incentive retrieved successfully'
        ]);
    }

    public function approve(Request $request)
    {
        $this->incentiveService->updateIncentive(
            $request->id,
            ['status' => 'approved']
        );
        return response()->json([
            'message' => 'Incentive approved successfully'
        ]);
    }

    public function markAsPaid(Request $request)
    {
        $this->incentiveService->updateIncentive(
            $request->id,
            [
                'status' => 'paid',
                'payment_date' => now()
            ]
        );
        return response()->json([
            'message' => 'Incentive marked as paid successfully'
        ]);
    }
}
