<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    protected $fillable = [
        'user_id',
        'tier_id',
        'payment_cadence',
        'start_date',
        'end_date',
        'next_billing_date',
        'status',
        'stripe_subscription_id',
        'stripe_customer_id',
        'auto_renew',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'next_billing_date' => 'date',
        'auto_renew' => 'boolean',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tier(): BelongsTo
    {
        return $this->belongsTo(Tier::class);
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    // Helper methods
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired' || 
               ($this->end_date && $this->end_date->isPast());
    }

    public function canUpgrade(): bool
    {
        return $this->isActive() && $this->tier && $this->tier->sort_order < 5;
    }

    public function canDowngrade(): bool
    {
        return $this->isActive() && $this->tier && $this->tier->sort_order > 1;
    }

    public function getAmountAttribute(): float
    {
        if (!$this->tier) return 0;
        return $this->payment_cadence === 'annual' 
            ? $this->tier->annual_price 
            : $this->tier->monthly_price;
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeExpiring($query, $days = 30)
    {
        return $query->where('status', 'active')
                     ->whereDate('end_date', '<=', now()->addDays($days));
    }
}
