<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripStop extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id',
        'destination_id',
        'day',
        'sequence',
        'destination_name',
        'region',
        'latitude',
        'longitude',
        'admission_fee',
        'visit_duration_minutes',
        'rating',
        'leg_distance_km',
        'leg_minutes',
    ];

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class);
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }
}
