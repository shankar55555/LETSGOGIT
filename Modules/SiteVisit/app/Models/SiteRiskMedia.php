<?php

namespace Modules\SiteVisit\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

// use Modules\SiteVisit\Database\Factories\SiteRiskMediaFactory;

class SiteRiskMedia extends Model
{
    use HasUuids;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'site_visit_id',
        'type',
        'filename',
        'path',
    ];
}
