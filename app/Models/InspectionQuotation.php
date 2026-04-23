<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InspectionQuotation extends Model
{
    protected $fillable = [
        'inspection_id',
        'property_id',
        'project_id',
        'created_by',
        'quote_number',
        'status',
        'findings_snapshot',
        'approved_finding_ids',
        'deferred_finding_ids',
        'client_notes',
        'client_responded_at',
        'approved_labour_cost',
        'approved_material_cost',
        'approved_bdc_cost',
        'approved_total',
        'admin_notes',
        'shared_at',
        'expires_at',
        'valid_until',
    ];

    protected $casts = [
        'findings_snapshot'    => 'array',
        'approved_finding_ids' => 'array',
        'deferred_finding_ids' => 'array',
        'approved_labour_cost' => 'decimal:2',
        'approved_material_cost' => 'decimal:2',
        'approved_bdc_cost'    => 'decimal:2',
        'approved_total'       => 'decimal:2',
        'client_responded_at'  => 'datetime',
        'shared_at'            => 'datetime',
        'expires_at'           => 'datetime',
        'valid_until'          => 'date',
    ];

    // ── Relationships ──────────────────────────────────────────────────────

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(Inspection::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Status Helpers ─────────────────────────────────────────────────────

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isShared(): bool
    {
        return in_array($this->status, ['shared', 'client_reviewing']);
    }

    public function hasClientResponded(): bool
    {
        return $this->status === 'client_responded';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired'
            || ($this->expires_at && $this->expires_at->isPast() && !$this->isApproved());
    }

    // ── Scopes ─────────────────────────────────────────────────────────────

    public function scopeForInspection($query, int $inspectionId)
    {
        return $query->where('inspection_id', $inspectionId);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['shared', 'client_reviewing', 'client_responded']);
    }
}
