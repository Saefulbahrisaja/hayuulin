<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DestinationReview extends Model
{
    protected $fillable = [
        'destination_id',
        'user_id',
        'body',
        'sentiment',
        'sentiment_confidence',
    ];

    protected $casts = [
        'sentiment_confidence' => 'float',
    ];

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
