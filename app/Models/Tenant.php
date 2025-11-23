<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'client_id',
        'tenant_number',
        'tenant_login',
        'first_name',
        'last_name',
        'email',
        'phone',
        'unit_number',
        'can_report_emergency',
        'status',
        'move_in_date',
        'move_out_date',
        'last_login_at',
    ];

    protected $casts = [
        'can_report_emergency' => 'boolean',
        'move_in_date' => 'date',
        'move_out_date' => 'date',
        'last_login_at' => 'datetime',
    ];

    /**
     * Get the property that owns the tenant.
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Get the client that owns the tenant.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    /**
     * Get all emergency reports for this tenant.
     */
    public function emergencyReports(): HasMany
    {
        return $this->hasMany(TenantEmergencyReport::class);
    }

    /**
     * Generate tenant login (e.g., APP12-1)
     */
    public static function generateTenantLogin(string $propertyCode, int $tenantNumber): string
    {
        return $propertyCode . '-' . $tenantNumber;
    }

    /**
     * Check if tenant is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Get full name
     */
    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }
}
