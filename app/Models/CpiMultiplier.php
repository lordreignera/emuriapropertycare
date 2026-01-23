<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CpiMultiplier extends Model
{
    protected $fillable = [
        'band_code',
        'multiplier',
        'description',
        'is_active',
    ];

    protected $casts = [
        'multiplier' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function bandRange()
    {
        return $this->belongsTo(CpiBandRange::class, 'band_code', 'band_code');
    }
}
