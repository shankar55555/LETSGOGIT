<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;
use Modules\RolePermission\Models\Role;
use Modules\RolePermission\Models\UserRole;
use Laravel\Sanctum\PersonalAccessToken;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, SoftDeletes;

    const USER_IMAGE = 'user_image';
    const SUPER_ADMIN = 'Super Admin';
    const ADMIN = 'Admin';
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        "phone",
        'user_name',
        'password',
        'avatar',
        'status',
        "country_code",
        'email_verified_at',
        'uuid',
        'salary',
        'mark_attendance',
        'date_of_birth',
        'anniversary_date',
        'expire_at',
    ];

    // Boot method to generate UUID
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            do {
                $uuid = (string) Str::uuid();
            } while (User::where('uuid', $uuid)->exists());

            $user->uuid = $uuid;
        });
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'mark_attendance' => 'boolean'
        ];
    }


    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles', 'user_id', 'role_id', 'uuid', 'id');
    }

    public function hasRole($role)
    {
        return $this->roles->contains('name', $role);
    }

    public function user_role()
    {
        return $this->hasMany(UserRole::class, 'user_id', 'uuid');
    }

    public function getPermissionsViaRoles()
    {
        return $this->roles()
            ->with('permissions')
            ->get()
            ->pluck('permissions')   # get Collection of Collections
            ->flatten()              # merge all permissions into one
            ->unique('id')           # remove duplicates
            ->values()               # ✅ reset keys to ensure array structure (indexed array)
            ->all();                 # ✅ convert to raw array (not Collection) if needed
    }

    public function getPermissionsViaRolesOld()
    {
        return $this->roles()
            ->with('permissions')
            ->get()
            ->pluck('permissions')
            ->flatten()
            ->unique('id');
    }

    public function attendances()
    {
        return $this->hasMany(UserAttendance::class, 'user_id', 'uuid');
    }

    // // Laravel Sanctum relationship
    // public function tokens()
    // {
    //     return $this->hasMany(PersonalAccessToken::class, 'tokenable_id');
    // }
}
