<?php

namespace Modules\Product\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Product\Models\Product;

class ProductService
{
    public function getPaginatedProducts(
        int $perPage = 15,
        bool $withTrashed = false,
        ?string $search = null
    ): LengthAwarePaginator {
        return Product::query()
            ->when($withTrashed, fn($q) => $q->withTrashed())
            ->when($search, fn($q) => $q->search($search))
            ->with(['creator', 'updater', 'variants.images'])
            ->latest()
            ->paginate($perPage);
    }

    public function getProductById(string $id): Product
    {
        return Product::with(['creator', 'updater', 'variants.images'])
            ->findOrFail($id);
    }

    public function createProduct(array $data): Product
    {
        // Extract variants data before creating product
        $variants = $data['variants'] ?? [];
        unset($data['variants']);

        // Create the product
        $product = Product::create($data);

        // Create variants if provided
        if (!empty($variants)) {
            foreach ($variants as $variantData) {
                $variant = $product->variants()->create([
                    'sku' => $variantData['sku'],
                    'mrp' => $variantData['mrp'],
                    'stock_quantity' => $variantData['stock_quantity'] ?? 0,
                    'low_stock_alert' => $variantData['low_stock_alert'] ?? null,
                ]);

                // Create variant images
                $this->updateVariantImages($variant, $variantData['images'] ?? []);
            }
        }

        return $product->load('variants.images');
    }

    public function updateProduct(string $id, array $data): Product
    {
        $Product = $this->getProductById($id);

        // Extract variants data before updating product
        $variants = $data['variants'] ?? [];
        unset($data['variants']);

        // Update the product
        $Product->update($data);

        // Update variants if provided
        if (!empty($variants)) {
            $this->updateProductVariants($Product, $variants);
        }

        return $Product->fresh(['variants.images']);
    }

    private function updateProductVariants(Product $product, array $variants): void
    {
        // Get existing variants
        $existingVariants = $product->variants->keyBy('id');
        $existingVariantsBySku = $product->variants->keyBy('sku');
        $updatedVariantIds = [];

        foreach ($variants as $variantData) {
            $variant = null;

            // First try to find by ID if provided
            if (isset($variantData['id']) && $existingVariants->has($variantData['id'])) {
                $variant = $existingVariants->get($variantData['id']);
            }
            // If no ID or variant not found by ID, try to find by SKU
            elseif (isset($variantData['sku']) && $existingVariantsBySku->has($variantData['sku'])) {
                $variant = $existingVariantsBySku->get($variantData['sku']);
            }

            if ($variant) {
                // Update existing variant
                $variant->update([
                    'sku' => $variantData['sku'],
                    'mrp' => $variantData['mrp'],
                    'stock_quantity' => $variantData['stock_quantity'] ?? 0,
                    'low_stock_alert' => $variantData['low_stock_alert'] ?? null,
                    'image' => $variantData['images'][0]['url'] ?? null, // Use first image as main image
                ]);

                // Update variant images
                $this->updateVariantImages($variant, $variantData['images'] ?? []);
                $updatedVariantIds[] = $variant->id;
            } else {
                // Create new variant
                $variant = $product->variants()->create([
                    'sku' => $variantData['sku'],
                    'mrp' => $variantData['mrp'],
                    'stock_quantity' => $variantData['stock_quantity'] ?? 0,
                    'low_stock_alert' => $variantData['low_stock_alert'] ?? null,
                    'image' => $variantData['images'][0]['url'] ?? null, // Use first image as main image
                ]);

                // Create variant images
                $this->updateVariantImages($variant, $variantData['images'] ?? []);
                $updatedVariantIds[] = $variant->id;
            }
        }

        // Delete variants that are no longer present
        $existingVariantIds = $existingVariants->keys()->toArray();
        $variantsToDelete = array_diff($existingVariantIds, $updatedVariantIds);
        if (!empty($variantsToDelete)) {
            $product->variants()->whereIn('id', $variantsToDelete)->delete();
        }
    }

    private function updateVariantImages($variant, array $images): void
    {
        // Delete existing images
        $variant->images()->delete();

        // Create new images
        foreach ($images as $index => $imageData) {
            $variant->images()->create([
                'url' => $imageData['url'],
                'name' => $imageData['name'] ?? null,
                'sort_order' => $index,
                'is_primary' => $index === 0, // First image is primary
            ]);
        }
    }

    public function deleteProduct(string $id): void
    {
        $Product = $this->getProductById($id);
        $Product->delete();
    }

    public function getLastPurchaseNumber(): array
    {
        $lastProduct = Product::orderBy('purchase_no', 'desc')->first();

        if (!$lastProduct || !$lastProduct->purchase_no) {
            return ['last_number' => 0];
        }
        // Extract numeric part from purchase number (assuming format PUR-0001)
        $purchaseNo = $lastProduct->purchase_no;
        if (preg_match('/PUR-(\d+)/', $purchaseNo, $matches)) {
            $lastNumber = (int) $matches[1];
        } else {
            // Fallback: try to extract any number from the string
            preg_match('/\d+/', $purchaseNo, $matches);
            $lastNumber = isset($matches[0]) ? (int) $matches[0] : 0;
        }

        return ['last_number' => $lastNumber];
    }
}
