<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tier extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'icon',
        'experience',
        'description',
        'features',
        'monthly_price',
        'annual_price',
        'stripe_price_id_monthly',
        'stripe_price_id_annual',
        'coverage_limit',
        'designed_for',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'features' => 'array',
        'monthly_price' => 'decimal:2',
        'annual_price' => 'decimal:2',
        'coverage_limit' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class, 'current_tier_id');
    }

    // Helper methods
    public function getAnnualSavingsAttribute(): float
    {
        return ($this->monthly_price * 12) - $this->annual_price;
    }

    public function getAnnualSavingsPercentageAttribute(): float
    {
        $monthlyTotal = $this->monthly_price * 12;
        if ($monthlyTotal == 0) return 0;
        return round((($monthlyTotal - $this->annual_price) / $monthlyTotal) * 100, 2);
    }

    // Scope queries
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}
