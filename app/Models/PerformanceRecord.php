<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformanceRecord extends Model
{
    protected $fillable = [
        'property_id',
        'verified_property_fact_id',
        'phar_finding_id',
        'remediation_work_order_id',
        'recorded_by',
        'recurrence_status',
        'system_improved',
        'risk_reduced',
        'trade_score',
        'measurements',
        'notes',
        'recorded_at',
    ];

    protected $casts = [
        'system_improved' => 'boolean',
        'risk_reduced' => 'boolean',
        'trade_score' => 'integer',
        'measurements' => 'array',
        'recorded_at' => 'datetime',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function verifiedPropertyFact(): BelongsTo
    {
        return $this->belongsTo(VerifiedPropertyFact::class);
    }

    public function finding(): BelongsTo
    {
        return $this->belongsTo(PHARFinding::class, 'phar_finding_id');
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(RemediationWorkOrder::class, 'remediation_work_order_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
