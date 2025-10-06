<?php

namespace Modules\Targets\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Targets\Models\Target;

class TargetService
{
    public function getPaginatedTargets(
        int $perPage = 15,
        bool $withTrashed = false,
        ?string $search = null,
        ?string $createdBy = null,
        ?string $lastUpdatedBy = null,
    ): LengthAwarePaginator {
        $query = Target::query()->when($withTrashed, fn($q) => $q->withTrashed());
        return $query->when($search, fn($q) => $q->search($search))
            ->when($createdBy, fn($q) => $q->where('created_by', $createdBy))
            ->when($lastUpdatedBy, fn($q) => $q->where('last_updated_by', $lastUpdatedBy))
            ->with(['creator', 'updater'])
            ->latest()
            ->paginate($perPage);
    }
    public function getTargetById(string $id): Target
    {
        return Target::with(['creator', 'updater'])
            ->findOrFail($id);
    }
    public function createTarget(array $data): Target
    {
        return Target::create($data);
    }
    public function updateTarget(string $id, array $data): Target
    {
        $lead = $this->getTargetById($id);
        $lead->update($data);
        return $lead->fresh();
    }
    public function deleteTarget(string $id): void
    {
        $lead = $this->getTargetById($id);
        $lead->delete();
    }
}
