<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CpiBandRange extends Model
{
    protected $fillable = [
        'band_code',
        'band_name',
        'min_score',
        'max_score',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'min_score' => 'integer',
        'max_score' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public function multiplier()
    {
        return $this->hasOne(CpiMultiplier::class, 'band_code', 'band_code');
    }
}
