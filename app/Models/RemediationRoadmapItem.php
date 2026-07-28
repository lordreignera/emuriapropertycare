<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RemediationRoadmapItem extends Model
{
    protected $fillable = [
        'remediation_roadmap_id',
        'phar_finding_id',
        'finding_client_decision_id',
        'plan_type',
        'target_date',
        'priority_order',
        'status',
        'notes',
    ];

    protected $casts = [
        'target_date' => 'date',
        'priority_order' => 'integer',
    ];

    public function roadmap(): BelongsTo
    {
        return $this->belongsTo(RemediationRoadmap::class, 'remediation_roadmap_id');
    }

    public function finding(): BelongsTo
    {
        return $this->belongsTo(PHARFinding::class, 'phar_finding_id');
    }

    public function clientDecision(): BelongsTo
    {
        return $this->belongsTo(FindingClientDecision::class, 'finding_client_decision_id');
    }
}
