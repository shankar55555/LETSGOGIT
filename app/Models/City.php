<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'cities';
    protected $fillable = [
        'name',
        'country_id',
        'country_name',
        'state_id',
        'state_name',
        'latitude',
        'longitude',
        'city_type',
    ];

    public function country(){
        return $this->hasOne(Country::class,'id','country_id');
    }

    public function state(){
        return $this->hasOne(Country::class,'id','state_id');
    }
}
