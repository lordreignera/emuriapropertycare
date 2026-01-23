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
        'property_code',
        'property_brand',
        'has_tenants',
        'number_of_units',
        'tenant_common_password',
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
        'property_subtype',
        'residential_units',
        'mixed_use_commercial_weight',
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
        'project_manager_id',
        'inspector_id',
        'assigned_at',
        'inspection_scheduled_at',
    ];

    protected $casts = [
        'has_tenants' => 'boolean',
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
        'assigned_at' => 'datetime',
        'inspection_scheduled_at' => 'datetime',
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

    public function projectManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'project_manager_id');
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }

    public function emergencyReports(): HasMany
    {
        return $this->hasMany(TenantEmergencyReport::class);
    }

    public function customProducts(): HasMany
    {
        return $this->hasMany(ClientCustomProduct::class);
    }

    public function complexityScores(): HasMany
    {
        return $this->hasMany(PropertyComplexityScore::class);
    }

    public function inspections(): HasMany
    {
        return $this->hasMany(Inspection::class);
    }

    // Helper methods
    
    /**
     * Generate unique property code with 10-digit timestamp
     */
    public static function generatePropertyCode(?string $brand = null): string
    {
        $prefix = $brand ? strtoupper(substr($brand, 0, 3)) : 'PROP';
        $timestamp = substr(time(), 0, 10); // 10-digit Unix timestamp
        
        $propertyCode = $prefix . '-' . $timestamp;
        
        // Ensure uniqueness (in case of concurrent requests)
        $counter = 1;
        while (self::where('property_code', $propertyCode)->exists()) {
            $propertyCode = $prefix . '-' . $timestamp . '-' . $counter;
            $counter++;
        }
        
        return $propertyCode;
        // Examples: PROP-1732387200, SUN-1732387201, MAP-1732387202
    }

    /**
     * Generate common password for all tenants in this property
     */
    public static function generateTenantPassword(): string
    {
        return strtoupper(substr(md5(uniqid()), 0, 8));
        // Example: 5F4A9C2E
    }

    /**
     * Check if property has tenants
     */
    public function hasTenants(): bool
    {
        return $this->has_tenants && $this->tenants()->count() > 0;
    }

    /**
     * Get active tenants
     */
    public function activeTenants(): HasMany
    {
        return $this->tenants()->where('status', 'active');
    }
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
