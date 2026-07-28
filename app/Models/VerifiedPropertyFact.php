<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VerifiedPropertyFact extends Model
{
    protected $fillable = [
        'property_id',
        'phar_finding_id',
        'remediation_work_order_id',
        'verification_record_id',
        'fact_type',
        'title',
        'fact_summary',
        'source_type',
        'reliability_level',
        'effective_date',
        'materials',
        'warranty',
        'monitoring_requirements',
        'metadata',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'materials' => 'array',
        'warranty' => 'array',
        'monitoring_requirements' => 'array',
        'metadata' => 'array',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function finding(): BelongsTo
    {
        return $this->belongsTo(PHARFinding::class, 'phar_finding_id');
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(RemediationWorkOrder::class, 'remediation_work_order_id');
    }

    public function verificationRecord(): BelongsTo
    {
        return $this->belongsTo(VerificationRecord::class);
    }

    public function performanceRecords(): HasMany
    {
        return $this->hasMany(PerformanceRecord::class);
    }
}
