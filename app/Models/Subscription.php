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
        'property_id',
        'custom_product_id',
        'payment_cadence',
        'payment_model',
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

    // ========================================
    // RELATIONSHIPS
    // ========================================
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tier(): BelongsTo
    {
        return $this->belongsTo(Tier::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Get the custom product this subscription is for
     */
    public function customProduct(): BelongsTo
    {
        return $this->belongsTo(ClientCustomProduct::class, 'custom_product_id');
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
        // If using custom product
        if ($this->customProduct) {
            return $this->payment_cadence === 'annual' 
                ? ($this->customProduct->annual_price ?? $this->customProduct->total_price * 10)
                : ($this->customProduct->monthly_price ?? $this->customProduct->total_price);
        }
        
        // Fallback to tier (legacy)
        if (!$this->tier) return 0;
        return $this->payment_cadence === 'annual' 
            ? $this->tier->annual_price 
            : $this->tier->monthly_price;
    }

    /**
     * Check if this is a pay-as-you-go subscription
     */
    public function isPayAsYouGo(): bool
    {
        return $this->payment_model === 'pay_as_you_go';
    }

    /**
     * Check if this is a hybrid subscription
     */
    public function isHybrid(): bool
    {
        return $this->payment_model === 'hybrid';
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
