<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Accommodation extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'region',
        'address',
        'latitude',
        'longitude',
        'price_per_night',
        'capacity',
        'rating',
        'amenities',
        'is_active',
        'source_provider',
        'source_id',
        'source_url',
        'is_verified',
        'price_verified_at',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'price_per_night' => 'integer',
        'capacity' => 'integer',
        'rating' => 'float',
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
        'price_verified_at' => 'datetime',
    ];
}
