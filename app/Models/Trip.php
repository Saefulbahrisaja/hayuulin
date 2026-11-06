<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Trip extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'origin_name',
        'origin_latitude',
        'origin_longitude',
        'days',
        'travelers',
        'budget_per_person',
        'total_cost',
        'actual_cost',
        'start_date',
        'total_distance_km',
        'total_duration_minutes',
        'transport_name',
        'accommodation_name',
        'cost_breakdown',
        'preferences',
    ];

    protected $casts = [
        'cost_breakdown' => 'array',
        'preferences' => 'array',
        'origin_latitude' => 'float',
        'origin_longitude' => 'float',
        'total_distance_km' => 'float',
        'total_duration_minutes' => 'integer',
        'actual_cost' => 'integer',
        'start_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function stops(): HasMany
    {
        return $this->hasMany(TripStop::class)->orderBy('day')->orderBy('sequence');
    }
}
