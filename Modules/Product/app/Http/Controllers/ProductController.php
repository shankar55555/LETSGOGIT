<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Auth, Storage};
use Illuminate\Http\UploadedFile;
use Modules\Product\Http\Requests\{ProductStoreRequest, ProductUpdateRequest};
use Modules\Product\Services\ProductService;
use Modules\Product\Transformers\ProductResource;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends Controller
{
    protected $ProductService;

    public function __construct(ProductService $ProductService)
    {
        $this->ProductService = $ProductService;
    }

    public function index(Request $request): JsonResponse
    {
        $paginated = $this->ProductService->getPaginatedProducts(
            $request->integer('per_page', 15),
            $request->boolean('with_trashed'),
            $request->input('search')
        );

        return response()->json([
            'data' => ProductResource::collection($paginated->items()),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'last_page' => $paginated->lastPage(),
            ],
            'message' => 'Products retrieved successfully'
        ]);
    }

    public function store(ProductStoreRequest $request): JsonResponse
    {
        $Product = $this->ProductService->createProduct(
            array_merge($request->validated(), ['created_by' => Auth::user()->uuid])
        );

        return response()->json([
            'data' => new ProductResource($Product),
            'message' => 'Productcreated successfully'
        ], Response::HTTP_CREATED);
    }

    public function show(string $id): JsonResponse
    {
        $Product = $this->ProductService->getProductById($id);
        return response()->json([
            'data' => new ProductResource($Product),
            'message' => 'Product retrieved successfully'
        ]);
    }

    public function update(ProductUpdateRequest $request, string $id): JsonResponse
    {
        $Product = $this->ProductService->updateProduct(
            $id,
            array_merge($request->validated(), ['last_updated_by' => Auth::user()->uuid])
        );

        return response()->json([
            'data' => new ProductResource($Product),
            'message' => 'Product updated successfully'
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->ProductService->deleteProduct($id);
        return response()->json([
            'message' => 'Product deleted successfully'
        ]);
    }

    public function lastPurchaseNumber()
    {
        $lastPurchaseNumber = $this->ProductService->getLastPurchaseNumber();
        return response()->json([
            'data' => $lastPurchaseNumber,
            'message' => 'Last purchase number retrieved successfully'
        ]);
    }

    public function uploadImage(Request $request): JsonResponse
    {
        // dd($request->all());
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'filename' => 'required|string',
            'original_name' => 'required|string'
        ]);

        try {
            $image = $request->file('image');
            $filename = $request->input('filename');

            // Store the image in the public/uploads/products directory
            $path = $image->storeAs('uploads/products', $filename, 'public');

            // Generate the full URL for the image
            $url = Storage::url($path);

            return response()->json([
                'success' => true,
                'path' => $path,
                'url' => $url,
                'filename' => $filename,
                'original_name' => $request->input('original_name'),
                'message' => 'Image uploaded successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload image: ' . $e->getMessage()
            ], 500);
        }
    }

    public function deleteImage(Request $request): JsonResponse
    {
        $request->validate([
            'path' => 'required|string'
        ]);

        try {
            $path = $request->input('path');

            // Check if file exists and delete it
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);

                return response()->json([
                    'success' => true,
                    'message' => 'Image deleted successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Image not found'
                ], 404);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete image: ' . $e->getMessage()
            ], 500);
        }
    }
}
