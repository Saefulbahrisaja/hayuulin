<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Destination extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'address',
        'region',
        'latitude',
        'longitude',
        'rating',
        'review_count',
        'admission_fee',
        'visit_duration_minutes',
        'source_provider',
        'source_id',
        'source_url',
        'is_verified',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'rating' => 'float',
        'review_count' => 'integer',
        'admission_fee' => 'integer',
        'visit_duration_minutes' => 'integer',
        'is_verified' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(DestinationReview::class);
    }

    public function visitMetrics(): HasMany
    {
        return $this->hasMany(DestinationVisitMetric::class);
    }
}
