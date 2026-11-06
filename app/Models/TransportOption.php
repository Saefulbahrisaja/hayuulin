<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransportOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'cost_per_km',
        'capacity',
        'average_speed_kmh',
        'description',
        'is_active',
    ];

    protected $casts = [
        'cost_per_km' => 'integer',
        'capacity' => 'integer',
        'average_speed_kmh' => 'integer',
        'is_active' => 'boolean',
    ];

    public function routes(): HasMany
    {
        return $this->hasMany(TransportRoute::class);
    }
}
