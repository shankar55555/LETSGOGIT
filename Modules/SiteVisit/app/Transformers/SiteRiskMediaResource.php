<?php

namespace Modules\SiteVisit\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class SiteRiskMediaResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'site_visit_id' => $this->site_visit_id,
            'type' => $this->type,
            'filename' => $this->filename ?? "na.png",
            'path' => $this->path ? Storage::disk('public')->url($this->path) : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // If you want to include additional file information:
            'file_size' => $this->path ? Storage::disk('public')->size($this->path) : 0,
        ];
    }
}
