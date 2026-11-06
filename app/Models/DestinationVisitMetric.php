<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DestinationVisitMetric extends Model
{
    protected $fillable = [
        'destination_id',
        'recorded_by',
        'visited_on',
        'visitor_count',
        'is_holiday',
        'source',
    ];

    protected $casts = [
        'visited_on' => 'date',
        'visitor_count' => 'integer',
        'is_holiday' => 'boolean',
    ];

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
