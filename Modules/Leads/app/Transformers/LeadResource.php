<?php

namespace Modules\Leads\Transformers;

use App\Constants\CommonConst;
use App\Models\AdminControlConfig;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);

        $data['self_status'] =  $this->formatStatus(CommonConst::MODULE_LEAD, $data['status']);
        $data['last_site_visit_status'] = $this->whenLoaded('siteVisits', function () {
            $status = optional($this->siteVisits->sortByDesc('created_at')->first())->status;
            return $this->formatStatus(CommonConst::MODULE_SITE_VISIT, $status);
        });

        $data['last_followup_status'] = $this->whenLoaded('followups', function () {
            $status = optional($this->followups->sortByDesc('created_at')->first())->call_status;
            return $this->formatStatus(CommonConst::MODULE_FOLLOW_UP, $status);
        });

        $data['last_quotation_status'] = $this->whenLoaded('quotations', function () {
            $status = optional($this->quotations->sortByDesc('created_at')->first())->status;
            return $this->formatStatus(CommonConst::MODULE_QUOTATION, $status);
        });


        $data['city_id'] = $this->city_id;
        $data['city_name'] = optional($this->city)->name;
        

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
