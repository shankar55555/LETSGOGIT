<?php

namespace Modules\Invoices\Models;

use App\Constants\CommonConst;
use App\Models\AdminControlConfig;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Quotations\Models\Quotation;
use Nwidart\Modules\Facades\Module;

class Invoice extends Model
{
    use SoftDeletes, HasUuids;
    // protected $table = "invoices";
    protected $fillable = [
        'invoice_number',
        'title',
        'description',
        'items',
        'amount_paid',
        'sub_total',
        'tax',
        'discount',
        'total',
        'status',
        'due_date',
        'client_id',
        'contract_id',
        'quotation_id',
        'created_by',
        'last_updated_by'
    ];

    protected $casts = [
        'items' => 'array',
        'sub_total' => 'decimal:2',
        'tax' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];


    public function scopeSearch($query, $searchTerm)
    {
        $term = strtolower($searchTerm);
        $query->where(function ($q) use ($term) {
            $q->whereRaw('LOWER(title) LIKE ?', ["%{$term}%"]);
            # TODO: confirm add client id to un commit code and after code remove
            // if (Module::has(CommonConst::MODULE_CLIENT)) {
            //     $q->orWhereHas('client', function ($subQuery) use ($term) {
            //         $subQuery->whereRaw('LOWER(name) LIKE ?', ["%{$term}%"])
            //                  ->orWhereRaw('LOWER(phone) LIKE ?', ["%{$term}%"]);
            //     });
            // }

            if (Module::has(CommonConst::MODULE_QUOTATION) && Module::has(CommonConst::MODULE_CLIENT)) {
                $q->orWhereHas('quotation.clientDetail', function ($subQuery) use ($term) {
                    $subQuery->whereRaw('LOWER(name) LIKE ?', ["%{$term}%"])
                        ->orWhereRaw('LOWER(phone) LIKE ?', ["%{$term}%"]);
                });
            }

            if (Module::has(CommonConst::MODULE_QUOTATION) && Module::has(CommonConst::MODULE_LEAD)) {
                $q->orWhereHas('quotation.leadDetail', function ($subQuery) use ($term) {
                    $subQuery->whereRaw('LOWER(name) LIKE ?', ["%{$term}%"])
                        ->orWhereRaw('LOWER(phone) LIKE ?', ["%{$term}%"]);
                });
            }
        });

        return $query;
    }

    public function scopeFilterByStatus($query, $status)
    {
        return $query->where('status', $status);
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

    public function quotationNumber()
    {
        return $this->belongsTo(\Modules\Quotations\Models\Quotation::class, 'quotation_id', 'id');
    }

    public function contractNumber()
    {
        return $this->belongsTo(\Modules\Contracts\Models\Contract::class, 'contract_id', 'id');
    }

    public function quotation()
    {
        if (Module::has(CommonConst::MODULE_QUOTATION)) {
            return $this->hasOne(Quotation::class, 'id', 'quotation_id');
        }
        return null;
    }

    public function client()
    {
        if (Module::has(CommonConst::MODULE_CLIENT)) {
            return $this->hasOne(\Modules\Clients\Models\Client::class, 'id', 'client_id');
        }
        return null;
    }
    public function status_info()
    {
        return $this->hasOne(AdminControlConfig::class, 'slug', 'status')->where('status_for', CommonConst::MODULE_INVOICE)->select('id', 'status_for', 'status_text', 'slug', 'status_color');
    }

    # Invoice Model function getMatchingIdsFromRule 
    public static function getMatchingIdsFromRule($rule, $field_name, ?array $ids = [], ?string $type = 'All')
    {
        i(" Invoice :{$type}: Running getMatchingIdsFromRule for Invoice | Rule ID: {$rule->id}, Input IDs count: " . count($ids));

        $conditions = collect(json_decode($rule->conditions ?? '[]', true))
            ->filter(fn($c) => !empty($c['allow_condition']))
            ->values();

        if ($conditions->isEmpty() && empty($ids)) {
            i(" Invoice :{$type}: No conditions and no invoice IDs provided. Returning empty result.");
            return [];
        }

        $logic = strtoupper($rule->condition_type ?? 'AND');

        try {
            $matchedIds = static::query()
                ->when(!empty($ids), fn($q) => $q->whereIn('id', $ids))
                ->when($conditions->isNotEmpty(), function ($query) use ($conditions, $logic, $field_name, $type) {
                    $query->where(function ($q) use ($conditions, $logic, $field_name, $type) {
                        foreach ($conditions as $index => $condition) {
                            $field = $condition['field'] ?? $field_name ?? 'due_date';
                            $operator = $condition['operator'] ?? '>';
                            $datatype = $condition['datatype'] ?? 'date';
                            $value = $condition['value'] ?? null;

                            if ($value === null || $value === '') {
                                i(" Invoice :{$type}: Skipping empty condition: " . json_encode($condition));
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
                                        i(" Invoice :{$type}: Applied DATE condition: $field $mappedOperator $evaluatedDate using method [{$method}Date]");
                                    } else {
                                        i(" Invoice :{$type}: Invalid date operator [$operator] — skipping condition.");
                                    }

                                    break;

                                case 'numeric':
                                    $q->{$method}($field, $operator, (float)$value);
                                    i(" Invoice :{$type}: Applied NUMERIC condition: $field $operator $value using method [$method]");
                                    break;

                                case 'string':
                                    if (in_array($operator, ['==', '!='])) {
                                        $realOp = $operator === '==' ? '=' : '!=';
                                        $q->{$method}($field, $realOp, $value);
                                        i(" Invoice :{$type}: Applied STRING condition: $field $realOp $value using method [$method]");
                                    }
                                    break;

                                default:
                                    $q->{$method}($field, '=', $value);
                                    i(" Invoice :{$type}: Applied DEFAULT condition: $field = $value using method [$method]");
                                    break;
                            }
                        }
                    });
                })
                ->pluck('id')
                ->toArray();

            i(" Invoice :{$type}: Matched Invoice IDs count: " . count($matchedIds));

            if (!empty($matchedIds)) {
                i(" Invoice :{$type}: Sample Invoice IDs: " . implode(', ', array_slice($matchedIds, 0, 5)));
            }

            return $matchedIds;
        } catch (\Throwable $e) {
            er(" Invoice : {$type}: Error in Invoice::getMatchingIdsFromRule: " . $e->getMessage());
            return [];
        }
    }
}
