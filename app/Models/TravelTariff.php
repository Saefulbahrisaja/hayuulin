<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TravelTariff extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'label', 'amount', 'unit'];

    protected $casts = ['amount' => 'integer'];
}
