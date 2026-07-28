<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FindingClientDecision extends Model
{
    protected $fillable = [
        'phar_finding_id',
        'inspection_id',
        'inspection_quotation_id',
        'decided_by',
        'decision',
        'scheduled_for',
        'client_notes',
        'decided_at',
    ];

    protected $casts = [
        'scheduled_for' => 'date',
        'decided_at' => 'datetime',
    ];

    public function finding(): BelongsTo
    {
        return $this->belongsTo(PHARFinding::class, 'phar_finding_id');
    }

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(Inspection::class);
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(InspectionQuotation::class, 'inspection_quotation_id');
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
