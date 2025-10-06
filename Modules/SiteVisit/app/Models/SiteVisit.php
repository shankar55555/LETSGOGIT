<?php

namespace Modules\SiteVisit\Models;

use App\Constants\CommonConst;
use App\Models\AdminControlConfig;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Facades\DB;

class SiteVisit extends Model
{
    use HasFactory, SoftDeletes, HasUuids;
    protected $fillable = ['visit_type', 'visit_time', 'visit_assignee', 'status', 'visit_notes', 'products', 'lead_id', 'client_id', 'created_by', 'last_updated_by'];
    protected $casts = [
        'visit_time' => 'datetime',
        'id' => 'string',
        'lead_id' => 'string',
        'products' => 'array'
    ];
    public function scopeSearch($query, $searchTerm)
    {
        $term = strtolower($searchTerm);
        return $query->where(function ($q) use ($term) {
            $q->whereRaw('LOWER(visit_notes) LIKE ?', ["%{$term}%"]);
        });
    }
    public function assignee()
    {
        return $this->belongsTo(\App\Models\User::class, 'visit_assignee', 'uuid');
    }
    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by', 'uuid');
    }
    public function updater()
    {
        return $this->belongsTo(\App\Models\User::class, 'last_updated_by', 'uuid');
    }

    /**
     * Get status data based on the current contract's status
     * 
     * @param string|null $status The status to get data for. If null, returns all statuses
     * @return \Illuminate\Database\Eloquent\Collection|\App\Models\AdminControlConfig|null
     */
    public static function getStatusList(?string $status = null)
    {
        $query = \App\Models\AdminControlConfig::where('status_for', 'Site Visit')
            ->orderBy('position', 'asc');
        if ($status) {
            return $query->where('slug', $status)
                ->first(['status_text', 'status_color', 'position', 'is_predefined']);
        }
        return $query->get(['status_text', 'status_color', 'position', 'is_predefined']);
    }

    public function status_info()
    {
        return $this->hasOne(AdminControlConfig::class, 'slug', 'status')->where('status_for', CommonConst::MODULE_SITE_VISIT)->select('id', 'status_for', 'status_text', 'slug', 'status_color');
    }

    public function lead()
    {
        return $this->belongsTo(\Modules\Leads\Models\Lead::class, 'lead_id');
    }

    public function client()
    {
        return $this->belongsTo(\Modules\Clients\Models\Client::class, 'client_id');
    }

    public function site_risk()
    {
        return $this->hasOne(\Modules\SiteVisit\Models\SiteRiskManagement::class, 'site_visit_id');
    }

    # SiteVisit Model function getMatchingIdsFromRule 
    public static function getMatchingIdsFromRule($rule, ?array $ids = [], ?string $type = 'All')
    {
        i("Site Visit : {$type}: Running getMatchingIdsFromRule for SiteVisit | Rule ID: {$rule->id}, Input IDs count: " . count($ids));

        $conditions = collect(json_decode($rule->conditions ?? '[]', true))
            ->filter(fn($c) => !empty($c['allow_condition']))
            ->values();

        if ($conditions->isEmpty() && empty($ids)) {
            i("Site Visit : {$type}: No conditions and no site visit IDs provided. Returning empty result.");
            return [];
        }

        $logic = strtoupper($rule->condition_type ?? 'AND');

        try {
            $matchedIds = static::query()
                ->when(!empty($ids), fn($q) => $q->whereIn('id', $ids))
                ->when($conditions->isNotEmpty(), function ($query) use ($conditions, $logic, $type) {
                    $query->where(function ($q) use ($conditions, $logic, $type) {
                        foreach ($conditions as $index => $condition) {
                            $field = $condition['field'] ?? 'updated_at';
                            $operator = $condition['operator'] ?? '>';
                            $datatype = $condition['datatype'] ?? 'date';
                            $value = $condition['value'] ?? null;

                            if ($value === null || $value === '') {
                                i("Site Visit : {$type}: Skipping empty condition: " . json_encode($condition));
                                continue;
                            }

                            $method = ($index === 0 || $logic === 'AND') ? 'where' : 'orWhere';

                            switch ($datatype) {
                                case 'date':
                                    $interval = (int)$value;

                                    $evaluatedDate = match ($operator) {
                                        '>', '>=' => now()->subDays($interval)->toDateString(),
                                        '<', '<=' => now()->addDays($interval)->toDateString(),
                                        '==', '!=' => now()->subDays($interval)->toDateString(),
                                        default => now()->toDateString(),
                                    };

                                    $mappedOperator = match ($operator) {
                                        '>'  => '<',
                                        '>=' => '<=',
                                        '<'  => '>',
                                        '<=' => '>=',
                                        '==' => '=',
                                        '!=' => '!=',
                                        default => '=',
                                    };

                                    if (in_array($mappedOperator, ['=', '!=', '<', '<=', '>', '>='])) {
                                        $q->{$method . 'Date'}($field, $mappedOperator, $evaluatedDate);
                                        i("Site Visit : {$type}: Applied DATE condition: $field $mappedOperator $evaluatedDate using method [{$method}Date]");
                                    } else {
                                        i("Site Visit : {$type}: Invalid date operator [$operator] — skipping condition.");
                                    }

                                    break;

                                case 'numeric':
                                    $q->{$method}($field, $operator, (float)$value);
                                    i("Site Visit : {$type}: Applied NUMERIC condition: $field $operator $value using method [$method]");
                                    break;

                                case 'string':
                                    if ($operator === 'like') {
                                        $q->{$method}($field, 'like', "%{$value}%");
                                        i("Site Visit : {$type}: Applied STRING condition: $field LIKE %{$value}% using method [$method]");
                                    } else {
                                        $mappedOperator = match ($operator) {
                                            '==' => '=',
                                            '!=' => '!=',
                                            default => $operator,
                                        };
                                        $q->{$method}($field, $mappedOperator, $value);
                                        i("Site Visit : {$type}: Applied STRING condition: $field $mappedOperator $value using method [$method]");
                                    }
                                    break;

                                default:
                                    $q->{$method}($field, $operator, $value);
                                    i("Site Visit : {$type}: Applied DEFAULT condition: $field $operator $value using method [$method]");
                                    break;
                            }
                        }
                    });
                })
                ->pluck('id')
                ->toArray();

            i("Site Visit : {$type}: Matched SiteVisit IDs count: " . count($matchedIds));

            if (!empty($matchedIds)) {
                i("Site Visit : {$type}: Sample SiteVisit IDs: " . implode(', ', array_slice($matchedIds, 0, 5)));
            }

            return $matchedIds;
        } catch (\Throwable $e) {
            er("Site Visit : {$type}: Error in SiteVisit::getMatchingIdsFromRule: " . $e->getMessage());
            return [];
        }
    }
}
