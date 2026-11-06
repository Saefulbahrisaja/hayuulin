<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransportRoute extends Model
{
    use HasFactory;

    protected $fillable = [
        'transport_option_id',
        'name',
        'origin_region',
        'destination_region',
        'fare',
        'duration_minutes',
        'instructions',
        'is_active',
    ];

    public function transportOption(): BelongsTo
    {
        return $this->belongsTo(TransportOption::class);
    }
}
