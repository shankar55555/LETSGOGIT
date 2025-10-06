<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\Auth;
use Modules\Product\Services\VendorService;
use Modules\Product\Transformers\VendorResource;
use Symfony\Component\HttpFoundation\Response;

class VendorController extends Controller
{
    protected $vendorService;

    public function __construct(VendorService $vendorService)
    {
        $this->vendorService = $vendorService;
    }

    /**
     * Display a listing of vendors.
     */
    public function index(Request $request): JsonResponse
    {
        $paginated = $this->vendorService->getPaginatedVendors(
            $request->integer('per_page', 15),
            $request->boolean('with_trashed'),
            $request->input('search')
        );

        return response()->json([
            'data' => VendorResource::collection($paginated->items()),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'last_page' => $paginated->lastPage(),
            ],
            'message' => 'Vendors retrieved successfully'
        ]);
    }

    /**
     * Store a newly created vendor.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'email' => 'required|email|unique:vendors,email',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|max:20',
            'gstin' => 'nullable|string|max:15',
        ]);
        $vendor = $this->vendorService->createVendor(
            array_merge($validated, ['created_by' => Auth::user()->uuid])
        );

        return response()->json([
            'data' => new VendorResource($vendor),
            'message' => 'Vendor created successfully'
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified vendor.
     */
    public function show(string $id): JsonResponse
    {
        $vendor = $this->vendorService->getVendorById($id);
        return response()->json([
            'data' => new VendorResource($vendor),
            'message' => 'Vendor retrieved successfully'
        ]);
    }

    /**
     * Update the specified vendor.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'email' => 'required|email|unique:vendors,email,' . $id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|max:20',
            'gstin' => 'nullable|string|max:15',
        ]);

        $vendor = $this->vendorService->updateVendor(
            $id,
            array_merge($validated, ['last_updated_by' => Auth::user()->uuid])
        );

        return response()->json([
            'data' => new VendorResource($vendor),
            'message' => 'Vendor updated successfully'
        ]);
    }

    /**
     * Remove the specified vendor.
     */
    public function destroy(string $id): JsonResponse
    {
        $this->vendorService->deleteVendor($id);
        return response()->json([
            'message' => 'Vendor deleted successfully'
        ]);
    }
}
