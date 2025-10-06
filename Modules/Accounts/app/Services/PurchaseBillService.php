<?php

namespace Modules\Accounts\app\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Accounts\Models\PurchaseBill;
use Modules\Accounts\Models\PurchaseBillItem;
use Modules\Product\Models\Vendor;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductVariant;

class PurchaseBillService
{
    public function getPaginatedPurchaseBills(
        int $perPage = 15,
        bool $withTrashed = false,
        ?string $search = null
    ): LengthAwarePaginator {
        return PurchaseBill::query()
            ->when($withTrashed, fn($q) => $q->withTrashed())
            ->when($search, function ($query) use ($search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('bill_number', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhere('total_amount', 'like', "%{$search}%");
                });
            })
            ->with(['vendor'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get a purchase bill by ID
     */
    public function getPurchaseBillById(string $id)
    {
        return PurchaseBill::with(['vendor', 'items', 'creator', 'updater'])
            ->findOrFail($id);
    }

    /**
     * Create a new purchase bill
     */
    public function createPurchaseBill(array $data)
    {
        DB::beginTransaction();

        try {
            // Handle bill image upload if present
            if (isset($data['bill_image']) && $data['bill_image']) {
                $data['bill_image'] = $this->uploadBillImage($data['bill_image']);
            }

            // Extract items from the data
            $items = $data['items'] ?? [];
            unset($data['items']);

            // Create the purchase bill
            $purchaseBill = PurchaseBill::create($data);

            // Create the purchase bill items and update variant stock
            foreach ($items as $item) {
                $item['purchase_bill_id'] = $purchaseBill->id;
                $item['created_by'] = $data['created_by'];
                PurchaseBillItem::create($item);

                // Update product variant stock if variant_id is provided
                if (isset($item['variant_id']) && $item['variant_id']) {
                    $this->updateVariantStock($item['variant_id'], $item['quantity']);
                }
            }

            DB::commit();
            return $purchaseBill;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update an existing purchase bill
     */
    public function updatePurchaseBill(string $id, array $data)
    {
        DB::beginTransaction();

        try {
            $purchaseBill = PurchaseBill::findOrFail($id);

            // Handle bill image upload if present
            if (isset($data['bill_image']) && $data['bill_image']) {
                // Delete old image if exists
                if ($purchaseBill->bill_image) {
                    Storage::delete($purchaseBill->bill_image);
                }

                $data['bill_image'] = $this->uploadBillImage($data['bill_image']);
            }

            // Extract items from the data
            $items = $data['items'] ?? [];
            unset($data['items']);

            // Update the purchase bill
            $purchaseBill->update($data);

            // Delete existing items
            $purchaseBill->items()->delete();

            // Create new items and update variant stock
            foreach ($items as $item) {
                $item['purchase_bill_id'] = $purchaseBill->id;
                $item['created_by'] = $data['last_updated_by'];
                PurchaseBillItem::create($item);

                // Update product variant stock if variant_id is provided
                if (isset($item['variant_id']) && $item['variant_id']) {
                    $this->updateVariantStock($item['variant_id'], $item['quantity']);
                }
            }

            DB::commit();
            return $purchaseBill;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete a purchase bill
     */
    public function deletePurchaseBill(string $id)
    {
        $purchaseBill = PurchaseBill::findOrFail($id);
        $purchaseBill->delete();
        return $purchaseBill;
    }

    /**
     * Get all vendors for dropdown
     */
    public function getVendors()
    {
        return Vendor::select('id', 'first_name', 'last_name', 'company_name', 'gstin', 'state')
            ->get()
            ->map(function ($vendor) {
                return [
                    'id' => $vendor->id,
                    'name' => $vendor->company_name ?: $vendor->first_name . ' ' . $vendor->last_name,
                    'gstin' => $vendor->gstin,
                    'state' => $vendor->state
                ];
            });
    }

    /**
     * Get all products for dropdown
     */
    public function getProducts()
    {
        return Product::select('id', 'name', 'sku', 'price', 'gst')
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'price' => $product->price,
                    'gst' => $product->gst
                ];
            });
    }

    /**
     * Get all states for dropdown
     */
    public function getStates()
    {
        return [
            ['id' => 'AN', 'name' => 'Andaman and Nicobar Islands'],
            ['id' => 'AP', 'name' => 'Andhra Pradesh'],
            ['id' => 'AR', 'name' => 'Arunachal Pradesh'],
            ['id' => 'AS', 'name' => 'Assam'],
            ['id' => 'BR', 'name' => 'Bihar'],
            ['id' => 'CH', 'name' => 'Chandigarh'],
            ['id' => 'CT', 'name' => 'Chhattisgarh'],
            ['id' => 'DN', 'name' => 'Dadra and Nagar Haveli'],
            ['id' => 'DD', 'name' => 'Daman and Diu'],
            ['id' => 'DL', 'name' => 'Delhi'],
            ['id' => 'GA', 'name' => 'Goa'],
            ['id' => 'GJ', 'name' => 'Gujarat'],
            ['id' => 'HR', 'name' => 'Haryana'],
            ['id' => 'HP', 'name' => 'Himachal Pradesh'],
            ['id' => 'JK', 'name' => 'Jammu and Kashmir'],
            ['id' => 'JH', 'name' => 'Jharkhand'],
            ['id' => 'KA', 'name' => 'Karnataka'],
            ['id' => 'KL', 'name' => 'Kerala'],
            ['id' => 'LA', 'name' => 'Ladakh'],
            ['id' => 'LD', 'name' => 'Lakshadweep'],
            ['id' => 'MP', 'name' => 'Madhya Pradesh'],
            ['id' => 'MH', 'name' => 'Maharashtra'],
            ['id' => 'MN', 'name' => 'Manipur'],
            ['id' => 'ML', 'name' => 'Meghalaya'],
            ['id' => 'MZ', 'name' => 'Mizoram'],
            ['id' => 'NL', 'name' => 'Nagaland'],
            ['id' => 'OR', 'name' => 'Odisha'],
            ['id' => 'PY', 'name' => 'Puducherry'],
            ['id' => 'PB', 'name' => 'Punjab'],
            ['id' => 'RJ', 'name' => 'Rajasthan'],
            ['id' => 'SK', 'name' => 'Sikkim'],
            ['id' => 'TN', 'name' => 'Tamil Nadu'],
            ['id' => 'TG', 'name' => 'Telangana'],
            ['id' => 'TR', 'name' => 'Tripura'],
            ['id' => 'UP', 'name' => 'Uttar Pradesh'],
            ['id' => 'UT', 'name' => 'Uttarakhand'],
            ['id' => 'WB', 'name' => 'West Bengal']
        ];
    }

    /**
     * Update product variant stock quantity
     */
    private function updateVariantStock(string $variantId, int $quantity)
    {
        $variant = ProductVariant::find($variantId);
        if ($variant) {
            $variant->increment('stock_quantity', $quantity);
        }
    }

    /**
     * Upload bill image
     */
    private function uploadBillImage($image)
    {
        $path = $image->store('purchase-bills', 'public');
        return $path;
    }
}
