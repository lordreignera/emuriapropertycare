<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PHARFinding extends Model
{
    protected $table = 'phar_findings';
    
    protected $fillable = [
        'inspection_id',
        'property_id',
        'system_id',
        'subsystem_id',
        'parent_finding_id',
        'task_question',
        'category',
        'finding_type',
        'severity',
        'impact_categories',
        'priority',
        'included_yn',
        'labour_hours',
        'material_cost',
        'notes',
        'photo_ids',
        'observed_condition',
        'plain_language_definition',
        'plain_language_meaning',
        'why_it_matters',
        'consequence_if_ignored',
        'remediation_strategy',
        'stewardship_strategy',
        'management_strategy',
        'workflow_status',
    ];

    protected $casts = [
        'labour_hours' => 'decimal:2',
        'material_cost' => 'decimal:2',
        'included_yn' => 'boolean',
        'photo_ids' => 'array',
        'impact_categories' => 'array',
    ];

    /**
     * Get the inspection this finding belongs to
     */
    public function inspection(): BelongsTo
    {
        return $this->belongsTo(Inspection::class);
    }

    /**
     * Get the property this finding belongs to
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function system(): BelongsTo
    {
        return $this->belongsTo(InspectionSystem::class, 'system_id');
    }

    public function subsystem(): BelongsTo
    {
        return $this->belongsTo(InspectionSubsystem::class, 'subsystem_id');
    }

    public function parentFinding(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_finding_id');
    }

    public function childFindings(): HasMany
    {
        return $this->hasMany(self::class, 'parent_finding_id');
    }

    public function tradePricingItems(): HasMany
    {
        return $this->hasMany(InspectionTradePricingItem::class, 'phar_finding_id');
    }

    public function evidence(): HasMany
    {
        return $this->hasMany(FindingEvidence::class, 'phar_finding_id');
    }

    public function issueMarkers(): HasMany
    {
        return $this->hasMany(IssueMarker::class, 'phar_finding_id');
    }

    public function clientDecisions(): HasMany
    {
        return $this->hasMany(FindingClientDecision::class, 'phar_finding_id');
    }

    public function roadmapItems(): HasMany
    {
        return $this->hasMany(RemediationRoadmapItem::class, 'phar_finding_id');
    }

    public function workOrders(): BelongsToMany
    {
        return $this->belongsToMany(RemediationWorkOrder::class, 'remediation_work_order_findings', 'phar_finding_id', 'remediation_work_order_id')
            ->withTimestamps();
    }

    public function verificationRecords(): HasMany
    {
        return $this->hasMany(VerificationRecord::class, 'phar_finding_id');
    }

    public function verifiedFacts(): HasMany
    {
        return $this->hasMany(VerifiedPropertyFact::class, 'phar_finding_id');
    }

    /**
     * Calculate labour cost for this finding
     */
    public function getLabourCostAttribute(): float
    {
        // Use a fixed rate of $165 if inspection relationship is not loaded
        $hourlyRate = 165;
        
        // Try to get rate from inspection if relationship is loaded
        if ($this->relationLoaded('inspection') && $this->inspection) {
            $hourlyRate = $this->inspection->labour_hourly_rate ?? 165;
        }
        
        return $this->labour_hours * $hourlyRate;
    }
}
