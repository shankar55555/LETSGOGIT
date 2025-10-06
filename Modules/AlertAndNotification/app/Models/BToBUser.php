<?php

namespace Modules\AlertAndNotification\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class BToBUser extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'b_to_b_users';

    protected $fillable = [
        'name',
        'company',
        'role',
        'country_code',
        'contact_no',
        'email',
        'address',
        'created_by',
        'avatar',
        'status',
        'last_updated_by',
    ];

    /**
     * Apply a flexible search filter to the query.
     */
        public function scopeSearch($query, ?string $searchTerm)
        {
            if (empty($searchTerm)) {
                return $query; // Return the unfiltered query if searchTerm is null or empty
            }
        
            $term = strtolower($searchTerm);
        
            return $query->where(function ($q) use ($term) {
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$term}%"])
                  ->orWhereRaw('LOWER(company) LIKE ?', ["%{$term}%"])
                  ->orWhereRaw('LOWER(email) LIKE ?', ["%{$term}%"])
                  ->orWhereRaw('LOWER(contact_no) LIKE ?', ["%{$term}%"])
                  ->orWhereRaw('LOWER(address) LIKE ?', ["%{$term}%"])
                  ->orWhereRaw('LOWER(role) LIKE ?', ["%{$term}%"])
                  ->orWhereHas('creator', fn($sub) =>
                      $sub->whereRaw('LOWER(name) LIKE ?', ["%{$term}%"])
                  )
                  ->orWhereHas('updater', fn($sub) =>
                      $sub->whereRaw('LOWER(name) LIKE ?', ["%{$term}%"])
                  );
            });
        }

    /**
     * Get the creator of the B2B user.
     */
    public function creator()
    {
        return $this->hasOne(User::class, 'uuid', 'created_by');
    }

    /**
     * Get the last user who updated this B2B user.
     */
    public function updater()
    {
        return $this->hasOne(User::class, 'uuid', 'last_updated_by');
    }
}
