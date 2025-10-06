<?php

namespace Modules\Leads\Models;

use App\Constants\CommonConst;
use App\Models\AdminControlConfig;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Clients\Models\Client;
use Modules\FollowUp\Models\FollowUp;
use Modules\Quotations\Models\Quotation;
use Modules\SiteVisit\Models\SiteVisit;

class Lead extends Model
{
    use HasFactory, SoftDeletes, HasUuids;
    protected $table = 'leads';

    protected $fillable = [
        "name",
        "contact_person",
        "contact_person_role",
        "email",
        "country_code",
        "phone",
        'secondary_phone',
        "address",
        "status",
        "source",
        "referral_detail",
        "assigned_user",
        "note",
        "created_by",
        "last_updated_by",
        "client_id",
        "quotation_id",
        "contract_id",
        "invoice_id",
        "anniversary_date",
        "date_of_birth",
        "city_id",
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'secondary_phone' => 'array',
    ];

    /**
     * Boot the model and register model events
     */
    protected static function boot()
    {
        parent::boot();

        // Before updating, check if source is being changed from "Referral" to something else
        static::updating(function ($lead) {
            // Check if source is being changed and the new source is not "Referral"
            if ($lead->isDirty('source') && $lead->source !== 'Referral') {
                // Clear referral_detail when source is changed from "Referral" to something else
                $lead->referral_detail = null;
            }
        });
    }

    public function scopeSearch($query, $searchTerm)
    {
        $term = strtolower($searchTerm);
        return $query->where(function ($q) use ($term) {
            $q->whereRaw('LOWER(name) LIKE ?', ["%{$term}%"]);
        });
    }

    public function scopeFilterByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeWhereAssignedUser($query, $userId)
    {
        return $query->where('assigned_user', $userId);
    }

    public static function createWithAttributes(array $attributes)
    {
        return static::create(array_merge([
            'id' => Str::orderedUuid(),
            'created_at' => now(),
        ], $attributes));
    }

    public function updateWithAttributes(array $attributes)
    {
        return $this->update(array_merge($attributes, [
            'updated_at' => now(),
        ]));
    }

    public function softDelete()
    {
        return $this->update([
            'last_updated_by' => Auth::user()->uuid,
            'updated_at' => now(),
        ]);
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

    public function status_info()
    {
        return $this->hasOne(AdminControlConfig::class, 'slug', 'status')->where('status_for', CommonConst::MODULE_LEAD)->select('id', 'status_for', 'status_text', 'slug', 'status_color');
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

    public function client()
    {
        return $this->hasOne(Client::class, 'id', 'client_id');
    }
    public function city()
    {
        return $this->belongsTo(\App\Models\City::class, 'city_id', 'id');
    }

    public static function getMatchingIdsFromRule($rule, ?array $lead_ids = [], ?string $type = 'All')
    {
        i(" Lead :{$type}: Running getMatchingIdsFromRule | Rule ID: {$rule->id}, Lead IDs count: " . count($lead_ids));

        $conditions = collect(json_decode($rule->conditions ?? '[]', true))
            ->filter(fn($c) => !empty($c['allow_condition']))
            ->values();

        if ($conditions->isEmpty() && empty($lead_ids)) {
            i(" Lead :{$type}: No conditions and no lead IDs provided. Returning empty result.");
            return [];
        }

        $logic = strtoupper($rule->condition_type ?? 'AND');

        try {
            $matchedIds = static::query()
                ->when(!empty($lead_ids), fn($q) => $q->whereIn('id', $lead_ids))
                ->when($conditions->isNotEmpty(), function ($query) use ($conditions, $logic, $type) {
                    $query->where(function ($q) use ($conditions, $logic, $type) {
                        foreach ($conditions as $index => $condition) {
                            $field = $condition['field'] ?? 'updated_at';
                            $operator = $condition['operator'] ?? '==';
                            $datatype = $condition['datatype'] ?? 'date';
                            $value = $condition['value'] ?? null;

                            if ($value === null || $value === '') {
                                i(" Lead :{$type}: Skipping empty condition: " . json_encode($condition));
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
                                        i(" Lead :{$type}: Applied DATE condition: $field $mappedOperator $evaluatedDate using method [{$method}Date]");
                                    } else {
                                        i(" Lead :{$type}: Invalid date operator [$operator] — skipping condition.");
                                    }

                                    break;
                                case 'numeric':
                                    $q->{$method}($field, $operator, (float)$value);
                                    i(" Lead :{$type}: Applied NUMERIC condition: $field $operator $value using method [$method]");
                                    break;

                                case 'string':
                                    if ($operator === 'like') {
                                        $q->{$method}($field, 'like', "%{$value}%");
                                        i(" Lead :{$type}: Applied STRING condition: $field LIKE %{$value}% using method [$method]");
                                    } elseif (in_array($operator, ['==', '!='])) {
                                        $mappedOperator = $operator === '==' ? '=' : '!=';
                                        $q->{$method}($field, $mappedOperator, $value);
                                        i(" Lead :{$type}: Applied STRING condition: $field $mappedOperator $value using method [$method]");
                                    }
                                    break;

                                default:
                                    $q->{$method}($field, $operator, $value);
                                    i(" Lead :{$type}: Applied DEFAULT condition: $field $operator $value using method [$method]");
                                    break;
                            }
                        }
                    });
                })
                ->pluck('id')
                ->toArray();

            i(" Lead :{$type}: Matched Lead IDs count: " . count($matchedIds));

            if (!empty($matchedIds)) {
                i(" Lead :{$type}: Sample IDs: " . implode(', ', array_slice($matchedIds, 0, 5)));
            }

            return $matchedIds;
        } catch (\Throwable $e) {
            er(" Lead : {$type}: Error in getMatchingIdsFromRule: " . $e->getMessage());
            return [];
        }
    }
}
