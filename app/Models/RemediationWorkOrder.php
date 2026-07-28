<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RemediationWorkOrder extends Model
{
    protected $fillable = [
        'work_order_number',
        'property_id',
        'inspection_id',
        'inspection_quotation_id',
        'remediation_roadmap_id',
        'created_by',
        'assigned_user_id',
        'assigned_trade_partner_id',
        'title',
        'scope_of_work',
        'status',
        'scheduled_start_date',
        'scheduled_end_date',
        'budget_amount',
        'materials',
        'evidence_requirements',
        'completion_requirements',
        'verification_requirements',
        'notes',
    ];

    protected $casts = [
        'scheduled_start_date' => 'date',
        'scheduled_end_date' => 'date',
        'budget_amount' => 'decimal:2',
        'materials' => 'array',
        'evidence_requirements' => 'array',
        'completion_requirements' => 'array',
        'verification_requirements' => 'array',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(Inspection::class);
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(InspectionQuotation::class, 'inspection_quotation_id');
    }

    public function roadmap(): BelongsTo
    {
        return $this->belongsTo(RemediationRoadmap::class, 'remediation_roadmap_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function tradePartner(): BelongsTo
    {
        return $this->belongsTo(TradePartner::class, 'assigned_trade_partner_id');
    }

    public function findings(): BelongsToMany
    {
        return $this->belongsToMany(PHARFinding::class, 'remediation_work_order_findings', 'remediation_work_order_id', 'phar_finding_id')
            ->withTimestamps();
    }

    public function events(): HasMany
    {
        return $this->hasMany(WorkOrderEvent::class);
    }

    public function verificationRecords(): HasMany
    {
        return $this->hasMany(VerificationRecord::class);
    }
}
