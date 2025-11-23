<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantEmergencyReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'property_id',
        'report_number',
        'emergency_type',
        'urgency',
        'description',
        'location_in_property',
        'photos',
        'floor_plan_pin',
        'reported_at',
        'acknowledged_at',
        'assigned_at',
        'resolved_at',
        'status',
        'assigned_to',
        'resolution_notes',
        'resolution_cost',
    ];

    protected $casts = [
        'photos' => 'array',
        'floor_plan_pin' => 'array',
        'reported_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'assigned_at' => 'datetime',
        'resolved_at' => 'datetime',
        'resolution_cost' => 'decimal:2',
    ];

    /**
     * Get the tenant who reported this emergency.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the property where the emergency occurred.
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Get the user assigned to handle this emergency.
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Generate unique report number
     */
    public static function generateReportNumber(): string
    {
        $prefix = 'EMR';
        $date = now()->format('Ymd');
        $count = self::whereDate('created_at', today())->count() + 1;
        
        return $prefix . '-' . $date . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
        // Example: EMR-20251115-0001
    }

    /**
     * Mark report as acknowledged
     */
    public function acknowledge(): void
    {
        $this->update([
            'status' => 'acknowledged',
            'acknowledged_at' => now(),
        ]);
    }

    /**
     * Assign to technician/inspector
     */
    public function assignTo(int $userId): void
    {
        $this->update([
            'status' => 'assigned',
            'assigned_to' => $userId,
            'assigned_at' => now(),
        ]);
    }

    /**
     * Mark as resolved
     */
    public function markResolved(string $notes = null, float $cost = null): void
    {
        $this->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolution_notes' => $notes,
            'resolution_cost' => $cost,
        ]);
    }

    /**
     * Check if report is critical
     */
    public function isCritical(): bool
    {
        return $this->urgency === 'critical';
    }

    /**
     * Scope for open reports
     */
    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['reported', 'acknowledged', 'assigned', 'in_progress']);
    }

    /**
     * Scope for critical reports
     */
    public function scopeCritical($query)
    {
        return $query->where('urgency', 'critical');
    }
}
