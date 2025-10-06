<?php

namespace Modules\Targets\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\Auth;
use Modules\Targets\Http\Requests\{TargetStoreRequest, TargetUpdateRequest};
use Modules\Targets\Services\TargetService;
use Modules\Targets\Transformers\TargetResource;
use Symfony\Component\HttpFoundation\Response;

class TargetController extends Controller
{
    protected $targetService;

    public function __construct(TargetService $targetService)
    {
        $this->targetService = $targetService;
    }

    public function index(Request $request): JsonResponse
    {

        $paginated = $this->targetService->getPaginatedTargets(
            $request->integer('per_page', 15),
            $request->boolean('with_trashed'),
            $request->input('search'),
            $request->input('created_by'),
            $request->input('last_updated_by')
        );

        return response()->json([
            'data' => TargetResource::collection($paginated->items()),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'last_page' => $paginated->lastPage(),
            ],
            'message' => 'Targets retrieved successfully'
        ]);
    }

    public function store(TargetStoreRequest $request): JsonResponse
    {
        $target = $this->targetService->createTarget(
            array_merge($request->validated(), [
                'created_by' => Auth::user()->uuid

            ])
        );
        return response()->json([
            'data' => new TargetResource($target),
            'message' => 'Target created successfully'
        ], Response::HTTP_CREATED);
    }

    public function show(string $id): JsonResponse
    {

        $target = $this->targetService->getTargetById($id);
        return response()->json([
            'data' => new TargetResource($target),
            'message' => 'Target retrieved successfully'
        ]);
    }

    public function update(TargetUpdateRequest $request, string $id): JsonResponse
    {
        $target = $this->targetService->updateTarget(
            $id,
            array_merge($request->validated(), ['last_updated_by' => Auth::user()->uuid])
        );

        return response()->json([
            'data' => new TargetResource($target),
            'message' => 'Target updated successfully'
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->targetService->deleteTarget($id);
        return response()->json([
            'message' => 'Target deleted successfully'
        ]);
    }
}
