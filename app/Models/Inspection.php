<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inspection extends Model
{
    protected $fillable = [
        'project_id',
        'property_id',
        'inspector_id',
        'assigned_by',
        'scheduled_date',
        'completed_date',
        'summary',
        'findings',
        'notes',
        'report_file',
        'photos',
        'status',
        'approved_by_client',
        'client_approved_at',
        'inspection_fee_amount',
        'inspection_fee_status',
        'inspection_fee_paid_at',
    ];

    protected $casts = [
        'scheduled_date' => 'datetime',
        'completed_date' => 'datetime',
        'findings' => 'array',
        'photos' => 'array',
        'approved_by_client' => 'boolean',
        'client_approved_at' => 'datetime',
        'inspection_fee_amount' => 'decimal:2',
        'inspection_fee_paid_at' => 'datetime',
    ];

    // Relationships
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function scopeOfWorks(): HasMany
    {
        return $this->hasMany(ScopeOfWork::class);
    }

    // Helper methods
    public function isScheduled(): bool
    {
        return $this->status === 'scheduled';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isApproved(): bool
    {
        return $this->approved_by_client && $this->status === 'approved';
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeByInspector($query, $inspectorId)
    {
        return $query->where('inspector_id', $inspectorId);
    }
}
