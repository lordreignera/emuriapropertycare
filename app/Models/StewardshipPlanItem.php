<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StewardshipPlanItem extends Model
{
    protected $fillable = [
        'stewardship_plan_id',
        'verified_property_fact_id',
        'phar_finding_id',
        'activity_type',
        'title',
        'description',
        'frequency',
        'next_due_date',
        'status',
        'metadata',
    ];

    protected $casts = [
        'next_due_date' => 'date',
        'metadata' => 'array',
    ];

    public function stewardshipPlan(): BelongsTo
    {
        return $this->belongsTo(StewardshipPlan::class);
    }

    public function verifiedPropertyFact(): BelongsTo
    {
        return $this->belongsTo(VerifiedPropertyFact::class);
    }

    public function finding(): BelongsTo
    {
        return $this->belongsTo(PHARFinding::class, 'phar_finding_id');
    }
}
