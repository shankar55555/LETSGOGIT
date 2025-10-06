<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminControlConfig extends Model
{
    use HasFactory, HasUuids;
    protected $table = "admin_control_configs";
    protected $fillable = [
        'invoice_footer_text',
        'contract_footer_text',
        'status_for',
        'status_text',
        'slug',
        'action',
        'status_color',
        'position',
        'is_predefined',
        'trigger_action',
        'send_plat_forms'
    ];

    /**
     * Get the attributes that should be cast.
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['trigger_action' => 'array', 'send_plat_forms' => 'array'];
    }

    protected $appends = ['trigger_actions'];

    public function getTriggerActionsAttribute()
    {
        $params = ["name" => "TRIGGER_ACTION", "list" => [], "position" => false];
        $allActions = readConstFileList(...$params);

        $selectedValues = $this->trigger_action ?? [];

        // Filter only matched actions
        $matchedActions = collect($allActions)->filter(function ($item) use ($selectedValues) {
            return in_array($item['value'], $selectedValues);
        })->values()->all();

        return $matchedActions;
    }

    public function scopeSearch($query, $searchTerm)
    {
        $term = strtolower($searchTerm);
        return $query->where(function ($q) use ($term) {
            $q->whereRaw('LOWER(status_text) LIKE ?', ["%{$term}%"]);
        });
    }
}
