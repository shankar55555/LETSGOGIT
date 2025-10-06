<?php

namespace Modules\Invoices\Transformers;

use App\Constants\CommonConst;
use App\Models\AdminControlConfig;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request)
    {
        $data = parent::toArray($request);
        $data['self_status'] =  $this->formatStatus(CommonConst::MODULE_INVOICE, $data['status']);
        return $data;
    }

    private function formatStatus(string $module, ?string $status): array
    {
        if (!$status) {
            return [];
        }

        $config = AdminControlConfig::where('status_for', $module)
            ->where('slug', $status)
            ->first(['status_text', 'status_color']);

        return $config ? [
            'title' => $config->status_text,
            'color' => $config->status_color,
        ] : [];
    }
}
