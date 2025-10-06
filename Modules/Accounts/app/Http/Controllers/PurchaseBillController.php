<?php

namespace Modules\Accounts\Http\Controllers;

use Illuminate\Http\{JsonResponse, Request};
use App\Http\Controllers\Controller;
use Modules\Accounts\app\Http\Requests\PurchaseBillRequest;
use Modules\Accounts\app\Services\PurchaseBillService;
use Illuminate\Support\Facades\Auth;

class PurchaseBillController extends Controller
{
    protected $purchaseBillService;

    public function __construct(PurchaseBillService $purchaseBillService)
    {
        $this->purchaseBillService = $purchaseBillService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $paginated = $this->purchaseBillService->getPaginatedPurchaseBills(
            $request->integer('per_page', 15),
            $request->boolean('with_trashed'),
            $request->input('search')
        );

        return response()->json([
            'data' => $paginated->items(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'last_page' => $paginated->lastPage(),
            ],
            'message' => 'Products/Services retrieved successfully'
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PurchaseBillRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['created_by'] = Auth::user()->uuid;
        $data['last_updated_by'] = Auth::user()->uuid;

        $purchaseBill = $this->purchaseBillService->createPurchaseBill($data);

        return response()->json([
            'data' => $purchaseBill,
            'message' => 'Purchase bill created successfully'
        ], 201);
    }

    /**
     * Show the specified resource.
     */
    public function show($id): JsonResponse
    {
        $purchaseBill = $this->purchaseBillService->getPurchaseBillById($id);

        return response()->json([
            'data' => $purchaseBill,
            'message' => 'Purchase bill retrieved successfully'
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PurchaseBillRequest $request, $id): JsonResponse
    {
        $data = $request->validated();
        $data['last_updated_by'] = Auth::user()->uuid;

        $purchaseBill = $this->purchaseBillService->updatePurchaseBill($id, $data);

        return response()->json([
            'data' => $purchaseBill,
            'message' => 'Purchase bill updated successfully'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id): JsonResponse
    {
        $this->purchaseBillService->deletePurchaseBill($id);

        return response()->json([
            'message' => 'Purchase bill deleted successfully'
        ]);
    }

    /**
     * Get all vendors for dropdown
     */
    public function getVendors(): JsonResponse
    {
        $vendors = $this->purchaseBillService->getVendors();

        return response()->json([
            'data' => $vendors,
            'message' => 'Vendors retrieved successfully'
        ]);
    }

    /**
     * Get all products for dropdown
     */
    public function getProducts(): JsonResponse
    {
        $products = $this->purchaseBillService->getProducts();

        return response()->json([
            'data' => $products,
            'message' => 'Products retrieved successfully'
        ]);
    }

    /**
     * Get all states for dropdown
     */
    public function getStates(): JsonResponse
    {
        $states = $this->purchaseBillService->getStates();

        return response()->json([
            'data' => $states,
            'message' => 'States retrieved successfully'
        ]);
    }
}
