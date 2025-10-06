<?php

namespace Modules\Contracts\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Contracts\Models\Contract;

class ContractResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Get status data for this contract
        $statusData = Contract::getStatusList($this->status);

        return array_merge(parent::toArray($request), [
            'status_data' => $statusData, // Include full status data
        ]);
    }
}
