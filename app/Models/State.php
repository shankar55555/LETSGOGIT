<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class State extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'states';
    protected $fillable = [
        'name',
        'country_id',
        'country_name',
        'state_code',
        'type',
        'latitude',
        'longitude',
        'state_type',
    ];

    public function country(){
        return $this->hasOne(Country::class,'id','country_id');
    }
}
