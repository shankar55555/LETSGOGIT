<?php

namespace Modules\SiteVisit\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SiteRiskManagement extends Model
{
    use SoftDeletes, HasUuids;

    protected $table = 'site_risk_management';

    protected $fillable = [
        'site_visit_id',
        'customer_name',
        'phone',
        'email',
        'address',
        'building_type',
        'roof_type',
        'height_of_roof',
        'service',
        'visit_datetime',
        'solution_recommended',
        'visit_assignee_id',
        'created_by',
        'last_updated_by',
    ];

    protected $casts = [
        'visit_datetime' => 'datetime'
    ];


    /**
     * Get all risk management records for a site visit
     */
    public static function getBySiteVisitId($siteVisitId)
    {
        return self::where('site_visit_id', $siteVisitId)
            ->with(['assignedUser', 'creator', 'updater', 'site_visit'])
            ->first();
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by', 'uuid');
    }

    public function updater()
    {
        return $this->belongsTo(\App\Models\User::class, 'last_updated_by', 'uuid');
    }

    public function site_visit()
    {
        return $this->belongsTo(SiteVisit::class, 'site_visit_id'); // Correct reference
    }

    public function assignedUser()
    {
        return $this->belongsTo(\App\Models\User::class, 'visit_assignee_id', 'uuid');
    }
}
