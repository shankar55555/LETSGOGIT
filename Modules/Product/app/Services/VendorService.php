<?php

namespace Modules\Product\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Product\Models\Vendor;

class VendorService
{
    /**
     * Get paginated vendors with optional filters
     */
    public function getPaginatedVendors(
        int $perPage = 15,
        bool $withTrashed = false,
        ?string $search = null
    ): LengthAwarePaginator {
        return Vendor::query()
            ->when($withTrashed, fn($q) => $q->withTrashed())
            ->when($search, function ($query) use ($search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->with(['creator', 'updater'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get vendor by ID
     */
    public function getVendorById(string $id): Vendor
    {
        return Vendor::with(['creator', 'updater'])
            ->findOrFail($id);
    }

    /**
     * Create a new vendor
     */
    public function createVendor(array $data): Vendor
    {
        Log::debug('Data received in createVendor:', $data);

        // Check what the actual data looks like before create
        logger()->debug('Data before Vendor::create:', $data);

        $vendor = Vendor::create($data);

        Log::debug('Vendor created:', $vendor->toArray());

        return $vendor;
    }

    /**
     * Update an existing vendor
     */
    public function updateVendor(string $id, array $data): Vendor
    {
        $vendor = $this->getVendorById($id);
        $vendor->update($data);
        return $vendor->fresh();
    }

    /**
     * Delete a vendor
     */
    public function deleteVendor(string $id): void
    {
        $vendor = $this->getVendorById($id);
        $vendor->delete();
    }
}
