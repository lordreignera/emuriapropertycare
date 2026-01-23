<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CpiDomain extends Model
{
    protected $fillable = [
        'domain_number',
        'domain_name',
        'domain_code',
        'max_possible_points',
        'description',
        'calculation_method',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'domain_number' => 'integer',
        'max_possible_points' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public function factors(): HasMany
    {
        return $this->hasMany(CpiScoringFactor::class, 'domain_id');
    }

    public function activeFactors(): HasMany
    {
        return $this->hasMany(CpiScoringFactor::class, 'domain_id')
            ->where('is_active', true)
            ->orderBy('sort_order');
    }
}
