<?php

namespace Modules\Clients\Models;

use App\Constants\CommonConst;
use App\Models\AdminControlConfig;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\FollowUp\Models\FollowUp;
use Modules\Leads\Models\Lead;
use Modules\Quotations\Models\Quotation;
use Modules\SiteVisit\Models\SiteVisit;

class Client extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = [
        'lead_id',
        'name',
        'type',
        'contact_person',
        'gst',
        'contact_person_role',
        'email',
        'country_code',
        'phone',
        'secondary_phone',
        'status',
        'assigned_user',
        'avatar',
        'created_by',
        'converted_by',
        'last_updated_by',
        'city_id',
        'date_of_birth',
        'anniversary_date',
    ];

    protected $casts = [
        'secondary_phone' => 'array',
    ];

    public function scopeSearch($query, $searchTerm)
    {
        $term = strtolower($searchTerm);
        return $query->where(function ($q) use ($term) {
            $q->whereRaw('LOWER(name) LIKE ?', ["%{$term}%"]);
        });
    }

    // Custom query scopes
    public function scopeFilterByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeWhereAssignedUser($query, $userId)
    {
        return $query->where('assigned_user', $userId);
    }

    // Custom creation method
    public static function createWithAttributes(array $attributes)
    {
        return static::create(array_merge([
            'id' => Str::orderedUuid(), // More efficient for DB indexing
            'created_at' => now(),
        ], $attributes));
    }

    // Custom update method
    public function updateWithAttributes(array $attributes)
    {
        return $this->update(array_merge($attributes, [
            'updated_at' => now(),
        ]));
    }

    // Soft delete with tracking
    public function softDelete()
    {
        return $this->update([
            'last_updated_by' => Auth::user()->uuid,
            'updated_at' => now(),
        ]);
    }

    // Relationship loading
    public function loadRelations()
    {
        return $this->load(['creator', 'updater', 'assignedUser', 'siteVisits', 'followups', 'quotations']);
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by', 'uuid');
    }

    public function updater()
    {
        return $this->belongsTo(\App\Models\User::class, 'last_updated_by', 'uuid');
    }
    public function assignedUser()
    {
        return $this->belongsTo(\App\Models\User::class, 'assigned_user', 'uuid');
    }

    public function leads()
    {
        return $this->hasMany(Lead::class, 'client_id', 'id');
    }

    public function siteVisits()
    {
        return $this->hasMany(SiteVisit::class);
    }

    public function followups()
    {
        return $this->hasMany(FollowUp::class);
    }

    public function quotations()
    {
        return $this->hasMany(Quotation::class);
    }

    public function status_info()
    {
        return $this->hasOne(AdminControlConfig::class, 'slug', 'status')->where('status_for', CommonConst::MODULE_CLIENT)->select('id', 'status_for', 'status_text', 'slug', 'status_color');
    }
    public function city()
    {
        return $this->belongsTo(\App\Models\City::class, 'city_id', 'id');
    }

    public static function getMatchingIdsFromRuleOld($rule, ?array $ids = [], ?string $type = 'All')
    {
        $conditions = collect(json_decode($rule->conditions ?? '[]', true))
            ->filter(fn($c) => !empty($c['allow_condition']))
            ->values();

        if ($conditions->isEmpty() && empty($ids)) {
            return [];
        }

        $logic = strtoupper($rule->condition_type ?? 'AND');

        return static::query()
            ->when(!empty($ids), fn($q) => $q->whereIn('id', $ids))
            ->when($conditions->isNotEmpty(), function ($query) use ($conditions, $logic) {
                $query->where(function ($q) use ($conditions, $logic) {
                    foreach ($conditions as $index => $condition) {
                        $field = 'updated_at';
                        $operator = $condition['operator'] ?? '>';
                        $datatype = $condition['datatype'] ?? 'date';
                        $value = $condition['value'] ?? null;

                        if ($value === null) {
                            continue;
                        }

                        $method = ($index === 0 || $logic === 'AND') ? 'where' : 'orWhere';

                        if ($datatype === 'date') {
                            $interval = (int)$value;
                            $rawDate = match ($operator) {
                                '>' => "NOW() - INTERVAL '{$interval} days'",
                                '<' => "NOW() + INTERVAL '{$interval} days'",
                                '==' => "CURRENT_DATE - INTERVAL '{$interval} days'",
                                '!=' => "CURRENT_DATE - INTERVAL '{$interval} days'",
                                default => 0,
                            };
                            $operator = match ($operator) {
                                '>' => '<',
                                '<' => '>',
                                '==' => '=',
                                '!=' => '!=',
                                default => "=",
                            };
                            $q->{$method}($field, $operator, DB::raw($rawDate));
                        } else {
                            $q->{$method}($field, $operator, $value);
                        }
                    }
                });
            })
            ->pluck('id')
            ->toArray();
    }

    # Client Model function getMatchingIdsFromRule 
    public static function getMatchingIdsFromRule($rule, ?array $ids = [], ?string $type = 'All')
    {
        i("Client : {$type}: Running getMatchingIdsFromRule for Client | Rule ID: {$rule->id}, Input IDs count: " . count($ids));

        $conditions = collect(json_decode($rule->conditions ?? '[]', true))
            ->filter(fn($c) => !empty($c['allow_condition']))
            ->values();

        if ($conditions->isEmpty() && empty($ids)) {
            i("Client : {$type}: No conditions and no client IDs provided. Returning empty result.");
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
                                i("Client : {$type}: Skipping empty condition: " . json_encode($condition));
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
                                        i("Client : {$type}: Applied DATE condition: $field $mappedOperator $evaluatedDate using method [{$method}Date]");
                                    } else {
                                        i("Client : {$type}: Invalid date operator [$operator] — skipping condition.");
                                    }

                                    break;

                                case 'numeric':
                                    $q->{$method}($field, $operator, (float)$value);
                                    i("Client : {$type}: Applied NUMERIC condition: $field $operator $value using method [$method]");
                                    break;

                                case 'string':
                                    if (in_array($operator, ['==', '!='])) {
                                        $mappedOperator = $operator === '==' ? '=' : '!=';
                                        $q->{$method}($field, $mappedOperator, $value);
                                        i("Client : {$type}: Applied STRING condition: $field $mappedOperator $value using method [$method]");
                                    } elseif ($operator === 'like') {
                                        $q->{$method}($field, 'like', "%{$value}%");
                                        i("Client : {$type}: Applied STRING condition: $field LIKE %{$value}% using method [$method]");
                                    }
                                    break;

                                default:
                                    $q->{$method}($field, $operator, $value);
                                    i("Client : {$type}: Applied DEFAULT condition: $field $operator $value using method [$method]");
                                    break;
                            }
                        }
                    });
                })
                ->pluck('id')
                ->toArray();

            i("Client : {$type}: Matched Client IDs count: " . count($matchedIds));
            if (!empty($matchedIds)) {
                i("Client : {$type}: Sample Client IDs: " . implode(', ', array_slice($matchedIds, 0, 5)));
            }

            return $matchedIds;
        } catch (\Throwable $e) {
            er("Client : {$type}: Error in Client::getMatchingIdsFromRule: " . $e->getMessage());
            return [];
        }
    }
}
