<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class VerificationRecord extends Model
{
    protected $fillable = [
        'remediation_work_order_id',
        'phar_finding_id',
        'verified_by',
        'status',
        'before_review',
        'after_review',
        'quality_notes',
        'tests_performed',
        'remaining_concerns',
        'verified_at',
    ];

    protected $casts = [
        'before_review' => 'array',
        'after_review' => 'array',
        'tests_performed' => 'array',
        'verified_at' => 'datetime',
    ];

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(RemediationWorkOrder::class, 'remediation_work_order_id');
    }

    public function finding(): BelongsTo
    {
        return $this->belongsTo(PHARFinding::class, 'phar_finding_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function verifiedPropertyFact(): HasOne
    {
        return $this->hasOne(VerifiedPropertyFact::class);
    }
}
