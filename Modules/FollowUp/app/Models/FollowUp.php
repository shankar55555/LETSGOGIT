<?php

namespace Modules\FollowUp\Models;

use App\Constants\CommonConst;
use App\Models\AdminControlConfig;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Clients\Models\Client;
use Modules\Leads\Models\Lead;
use Modules\SiteVisit\Models\SiteVisit;

class FollowUp extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'call_status',
        'lead_prospect',
        'call_summary',
        'created_by',
        'last_updated_by',
        'lead_id',
        'client_id',
        'next_call_datetime',
        'need_site_visit',
        'site_visit_datetime',
        'site_visit_user_id',
    ];

    protected $casts = [
        'updated_at' => 'datetime',
        'lead_id' => 'string',
        'client_id' => 'string',
        'next_call_datetime' => 'datetime',
        'site_visit_datetime' => 'datetime',
        'need_site_visit' => 'boolean'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->id = \Illuminate\Support\Str::uuid();
            $model->created_by = Auth::user()->uuid;
        });

        static::updating(function ($model) {
            $model->last_updated_by = Auth::user()->uuid;
            $model->updated_at = now();
        });
    }

    public function scopeSearch($query, $searchTerm)
    {
        $term = strtolower($searchTerm);
        return $query->where(function ($q) use ($term) {
            $q->whereRaw('LOWER(call_summary) LIKE ?', ["%{$term}%"]);
        });
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by', 'uuid');
    }

    public function updater()
    {
        return $this->belongsTo(\App\Models\User::class, 'last_updated_by', 'uuid');
    }

    public function status_info()
    {
        return $this->hasOne(AdminControlConfig::class, 'slug', 'call_status')->where('status_for', CommonConst::MODULE_FOLLOW_UP)->select('id', 'status_for', 'status_text', 'slug', 'status_color');
    }

    public function client()
    {
        return $this->hasOne(Client::class, 'id', 'client_id');
    }

    public function lead()
    {
        return $this->hasOne(Lead::class, 'id',  'lead_id');
    }

    public function site_visit_user()
    {
        return $this->hasOne(User::class, 'uuid',  'site_visit_user_id');
    }

    # FollowUp Model function getMatchingIdsFromRule 
    public static function getMatchingIdsFromRule($rule, ?array $ids = [], ?string $type = 'All')
    {
        i("FollowUp :{$type}: Running getMatchingIdsFromRule for FollowUp | Rule ID: {$rule->id}, Input IDs count: " . count($ids));

        $conditions = collect(json_decode($rule->conditions ?? '[]', true))
            ->filter(fn($c) => !empty($c['allow_condition']))
            ->values();

        if ($conditions->isEmpty() && empty($ids)) {
            i("FollowUp :{$type}: No conditions and no follow-up IDs provided. Returning empty result.");
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
                            $operator = $condition['operator'] ?? '==';
                            $datatype = $condition['datatype'] ?? 'date';
                            $value = $condition['value'] ?? null;

                            if ($value === null || $value === '') {
                                i("FollowUp :{$type}: Skipping empty condition: " . json_encode($condition));
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
                                        i("FollowUp :{$type}: Applied DATE condition: $field $mappedOperator $evaluatedDate using method [{$method}Date]");
                                    } else {
                                        i("FollowUp :{$type}: Invalid date operator [$operator] — skipping condition.");
                                    }

                                    break;

                                case 'numeric':
                                    $q->{$method}($field, $operator, (float)$value);
                                    i("FollowUp :{$type}: Applied NUMERIC condition: $field $operator $value using method [$method]");
                                    break;

                                case 'string':
                                    if ($operator === 'like') {
                                        $q->{$method}($field, 'like', "%{$value}%");
                                        i("FollowUp :{$type}: Applied STRING condition: $field LIKE %{$value}% using method [$method]");
                                    } elseif (in_array($operator, ['==', '!='])) {
                                        $mappedOperator = $operator === '==' ? '=' : '!=';
                                        $q->{$method}($field, $mappedOperator, $value);
                                        i("FollowUp :{$type}: Applied STRING condition: $field $mappedOperator $value using method [$method]");
                                    }
                                    break;

                                default:
                                    $q->{$method}($field, $operator, $value);
                                    i("FollowUp :{$type}: Applied DEFAULT condition: $field $operator $value using method [$method]");
                                    break;
                            }
                        }
                    });
                })
                ->pluck('id')
                ->toArray();

            i("FollowUp :{$type}: Matched FollowUp IDs count: " . count($matchedIds));

            if (!empty($matchedIds)) {
                i("FollowUp :{$type}: Sample FollowUp IDs: " . implode(', ', array_slice($matchedIds, 0, 5)));
            }

            return $matchedIds;
        } catch (\Throwable $e) {
            er("FollowUp : {$type}: Error in FollowUp::getMatchingIdsFromRule: " . $e->getMessage());
            return [];
        }
    }
}
