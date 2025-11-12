<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Client extends Model
{
    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'phone',
        'address',
        'city',
        'province',
        'postal_code',
        'country',
        'registered_at',
        'account_status',
        'current_subscription_id',
        'current_tier_id',
        'stripe_customer_id',
        'payment_methods',
        'email_notifications',
        'sms_notifications',
        'preferred_contact_method',
        'lifetime_value',
        'total_savings',
        'total_projects',
        'total_properties',
    ];

    protected $casts = [
        'payment_methods' => 'array',
        'email_notifications' => 'boolean',
        'sms_notifications' => 'boolean',
        'registered_at' => 'datetime',
        'lifetime_value' => 'decimal:2',
        'total_savings' => 'decimal:2',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function currentSubscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class, 'current_subscription_id');
    }

    public function currentTier(): BelongsTo
    {
        return $this->belongsTo(Tier::class, 'current_tier_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'user_id', 'user_id');
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class, 'user_id', 'user_id');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'user_id', 'user_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'user_id', 'user_id');
    }

    public function savings(): HasMany
    {
        return $this->hasMany(Saving::class, 'user_id', 'user_id');
    }

    // Helper methods
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function hasActiveSubscription(): bool
    {
        return $this->account_status === 'active' && $this->current_subscription_id !== null;
    }

    public function canAddProperty(): bool
    {
        return $this->hasActiveSubscription();
    }
}
