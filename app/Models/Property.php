<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Property extends Model
{
    protected $fillable = [
        'user_id',
        'subscription_id',
        'owner_first_name',
        'owner_phone',
        'owner_email',
        'admin_first_name',
        'admin_last_name',
        'admin_email',
        'admin_phone',
        'property_name',
        'property_address',
        'city',
        'province',
        'postal_code',
        'country',
        'type',
        'year_built',
        'square_footage_interior',
        'square_footage_green',
        'square_footage_paved',
        'square_footage_extra',
        'total_square_footage',
        'occupied_by',
        'has_pets',
        'has_kids',
        'personality',
        'known_problems',
        'sensitivities',
        'care_goals',
        'home_journey',
        'home_feel',
        'planned_projects',
        'needs_design_support',
        'scheduling_style',
        'preferred_days',
        'preferred_times',
        'blueprint_file',
        'property_photos',
        'status',
        'approved_at',
        'approved_by',
    ];

    protected $casts = [
        'has_pets' => 'boolean',
        'has_kids' => 'boolean',
        'needs_design_support' => 'boolean',
        'sensitivities' => 'array',
        'care_goals' => 'array',
        'home_journey' => 'array',
        'home_feel' => 'array',
        'planned_projects' => 'array',
        'scheduling_style' => 'array',
        'preferred_days' => 'array',
        'preferred_times' => 'array',
        'property_photos' => 'array',
        'approved_at' => 'datetime',
        'square_footage_interior' => 'decimal:2',
        'square_footage_green' => 'decimal:2',
        'square_footage_paved' => 'decimal:2',
        'square_footage_extra' => 'decimal:2',
        'total_square_footage' => 'decimal:2',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    // Helper methods
    public function isPendingApproval(): bool
    {
        return $this->status === 'pending_approval';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function getFullAddressAttribute(): string
    {
        return trim("{$this->property_address}, {$this->city}, {$this->province} {$this->postal_code}");
    }

    // Scopes
    public function scopePendingApproval($query)
    {
        return $query->where('status', 'pending_approval');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }
}
