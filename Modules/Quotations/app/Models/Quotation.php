<?php

namespace Modules\Quotations\Models;

use App\Constants\CommonConst;
use App\Models\AdminControlConfig;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Nwidart\Modules\Facades\Module;

class Quotation extends Model
{
    use SoftDeletes, HasUuids;

    // quotations
    protected $fillable = [
        'quotation_number',
        'valid_uptil',
        'quotation_type',
        'title',
        'sub_total',
        'discount',
        'tax',
        'total',
        'amount_due',
        'status',
        'items',
        'custom_header_text',
        'payment_terms',
        'terms_conditions',
        'lead_id',
        'client_id',
        'contract_id',
        'created_by',
        'last_updated_by'
    ];

    protected $casts = [
        'valid_uptil' => 'date',
        'sub_total' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'items' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function scopeSearch($query, $searchTerm)
    {
        $term = strtolower($searchTerm);
        $query->where(function ($q) use ($term) {
            $q->whereRaw('LOWER(title) LIKE ?', ["%{$term}%"]);

            if (Module::has(CommonConst::MODULE_CLIENT)) {
                $q->orWhereHas('clientDetail', function ($subQuery) use ($term) {
                    $subQuery->whereRaw('LOWER(name) LIKE ?', ["%{$term}%"])
                        ->orWhereRaw('LOWER(phone) LIKE ?', ["%{$term}%"]);
                });
            }
            if (Module::has(CommonConst::MODULE_LEAD)) {
                $q->orWhereHas('leadDetail', function ($subQuery) use ($term) {
                    $subQuery->whereRaw('LOWER(name) LIKE ?', ["%{$term}%"])
                        ->orWhereRaw('LOWER(phone) LIKE ?', ["%{$term}%"]);
                });
            }
        });

        return $query;
    }

    public static function createWithAttributes(array $attributes)
    {
        return static::create(array_merge([
            'id' => Str::orderedUuid(),
        ], $attributes));
    }

    public function updateWithAttributes(array $attributes)
    {
        return $this->update(array_merge($attributes, [
            'last_updated_by' => Auth::user()->uuid,
        ]));
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by', 'uuid');
    }

    public function updater()
    {
        return $this->belongsTo(\App\Models\User::class, 'last_updated_by', 'uuid');
    }

    public function clientDetail()
    {
        if (Module::has(CommonConst::MODULE_CLIENT)) {
            return $this->belongsTo(\Modules\Clients\Models\Client::class, 'client_id', 'id');
        }
        return null;
    }

    public function leadDetail()
    {
        if (Module::has(CommonConst::MODULE_LEAD)) {
            return $this->belongsTo(\Modules\Leads\Models\Lead::class, 'lead_id', 'id');
        }
        return null;
    }
    public function invoices()
    {
        if (Module::has(CommonConst::MODULE_INVOICE)) {
            return $this->hasMany(\Modules\Invoices\Models\Invoice::class, 'quotation_id', 'id');
        }
        return null;
    }

    public function status_info()
    {
        return $this->hasOne(AdminControlConfig::class, 'slug', 'status')->where('status_for', CommonConst::MODULE_QUOTATION)->select('id', 'status_for', 'status_text', 'slug', 'status_color');
    }

    # Quotation Model function getMatchingIdsFromRule 
    public static function getMatchingIdsFromRule($rule, ?array $ids = [], ?string $type = 'All')
    {
        i(" Quotation :{$type}: Running getMatchingIdsFromRule for Quotation | Rule ID: {$rule->id}, Input IDs count: " . count($ids));

        $conditions = collect(json_decode($rule->conditions ?? '[]', true))
            ->filter(fn($c) => !empty($c['allow_condition']))
            ->values();

        if ($conditions->isEmpty() && empty($ids)) {
            i(" Quotation :{$type}: No conditions and no quotation IDs provided. Returning empty result.");
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
                                i(" Quotation :{$type}: Skipping empty condition: " . json_encode($condition));
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
                                        i(" Quotation :{$type}: Applied DATE condition: $field $mappedOperator $evaluatedDate using method [{$method}Date]");
                                    } else {
                                        i(" Quotation :{$type}: Invalid date operator [$operator] — skipping condition.");
                                    }

                                    break;

                                case 'numeric':
                                    $q->{$method}($field, $operator, (float)$value);
                                    i(" Quotation :{$type}: Applied NUMERIC condition: $field $operator $value using method [$method]");
                                    break;

                                case 'string':
                                    if ($operator === 'like') {
                                        $q->{$method}($field, 'like', "%{$value}%");
                                        i(" Quotation :{$type}: Applied STRING condition: $field LIKE %{$value}% using method [$method]");
                                    } elseif (in_array($operator, ['==', '!='])) {
                                        $sqlOperator = $operator === '==' ? '=' : '!=';
                                        $q->{$method}($field, $sqlOperator, $value);
                                        i(" Quotation :{$type}: Applied STRING condition: $field $sqlOperator $value using method [$method]");
                                    } else {
                                        $q->{$method}($field, $operator, $value);
                                        i(" Quotation :{$type}: Applied STRING condition: $field $operator $value using method [$method]");
                                    }
                                    break;

                                default:
                                    $q->{$method}($field, $operator, $value);
                                    i(" Quotation :{$type}: Applied DEFAULT condition: $field $operator $value using method [$method]");
                                    break;
                            }
                        }
                    });
                })
                ->pluck('id')
                ->toArray();

            i(" Quotation :{$type}: Matched Quotation IDs count: " . count($matchedIds));
            if (!empty($matchedIds)) {
                i(" Quotation :{$type}: Sample Quotation IDs: " . implode(', ', array_slice($matchedIds, 0, 5)));
            }

            return $matchedIds;
        } catch (\Throwable $e) {
            er("Quotation : {$type}: Error in Quotation::getMatchingIdsFromRule: " . $e->getMessage());
            return [];
        }
    }
}
