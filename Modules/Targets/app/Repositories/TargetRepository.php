<?php

namespace Modules\Targets\Repositories;

use Modules\Targets\Models\Target;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class TargetRepository
{
    protected Builder $query;

    public function __construct()
    {
        $this->query = Target::query();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->query->latest()->paginate($perPage);
    }

    public function withTrashed(): self
    {
        $this->query->withTrashed();
        return $this;
    }
}
