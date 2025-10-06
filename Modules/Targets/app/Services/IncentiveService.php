<?php

namespace Modules\Targets\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Targets\Models\Incentive;

class IncentiveService
{
    public function getPaginatedIncentive(
        int $perPage = 15,
        bool $withTrashed = false,
        ?string $search = null,
        ?string $userId = null
    ): LengthAwarePaginator {
        return Incentive::query()->when($withTrashed, fn($q) => $q->withTrashed())
            ->when($search, fn($q) => $q->search($search))
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->with(['user'])
            ->latest()
            ->paginate($perPage);
    }
    public function getIncentiveById(string $id): Incentive
    {
        return Incentive::with(['user'])
            ->findOrFail($id);
    }
    public function createIncentive(array $data): Incentive
    {
        return Incentive::create($data);
    }
    public function updateIncentive(string $id, array $data): Incentive
    {
        $lead = $this->getIncentiveById($id);
        $lead->update($data);
        return $lead->fresh();
    }
    public function calculateTotalIncentives($userId)
    {
        return Incentive::where('user_id', $userId)
            ->where('status', 'paid')
            ->sum('amount');
    }
}
