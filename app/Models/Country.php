<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'countries';
    protected $fillable = [
        'country_id',
        'name',
        'iso3',
        'iso2',
        'numeric_code',
        'phone_code',
        'capital',
        'currency',
        'currency_name',
        'currency_symbol',
        'region',
        'nationality',
        'timezones',
        'latitude',
        'longitude',
        'emoji',
        'emojiU',
        'country_type',
    ];

    protected $casts = [
        'timezones' => 'array',
    ];

    // Optional if you still want to use this elsewhere
    public function getPhoneCodeAttribute($value)
    {
        return  preg_split('/,\s*|and\s*/i', $value);
    }

    public function getPhoneCodeList()
    {
        return collect(preg_split('/,\s*|and\s*/i', $this->attributes['phone_code']))
            ->map(function ($code) {
                return [
                    'phone_code' => trim($code),
                    'emojiU' => $this->emojiU,
                    'emoji' => $this->emojiU,
                ];
            })
            ->toArray();
    }
}
