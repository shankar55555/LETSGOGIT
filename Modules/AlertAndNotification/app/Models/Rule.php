<?php

namespace Modules\AlertAndNotification\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Rule extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'rules';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'rule',
        'rule_slug',
        'condition_type',
        'conditions',
        'actions',
        'status',
        'created_by',
        'last_updated_by',
    ];

    protected $casts = [
        'conditions' => 'array',
        'actions' => 'array',
    ];

    /**
     * Scope a query to apply a flexible search across key fields.
     */
    public function scopeSearch($query, $searchTerm)
    {
        $term = strtolower($searchTerm);

        return $query->where(function ($q) use ($term) {
            $q->where('rule', 'ILIKE', "%{$term}%")
                ->orWhereRaw("LOWER(actions::text) LIKE ?", ["%{$term}%"])
                ->orWhereHas(
                    'creator',
                    fn($sub) =>
                    $sub->whereRaw("LOWER(name) LIKE ?", ["%{$term}%"])
                )
                ->orWhereHas(
                    'updater',
                    fn($sub) =>
                    $sub->whereRaw("LOWER(name) LIKE ?", ["%{$term}%"])
                );
        });
    }

    # Custom creation method
    public static function createWithAttributes(array $attributes)
    {
        return static::create(array_merge([
            'created_at' => now(),
        ], $attributes));
    }

    public function creator()
    {
        return $this->hasOne(User::class, 'uuid', 'created_by');
    }

    public function updater()
    {
        return $this->hasOne(User::class, 'uuid', 'last_updated_by');
    }
}


// "conditions" => json_encode([
//     "last_client_response_at" => [
//       "operator" => "<=",
//       "datatype" => "int",
//       "value" => "3 days ago"
//     ]
//   ]),
