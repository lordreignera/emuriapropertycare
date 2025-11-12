<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Quote extends Model
{
    protected $fillable = [
        'quote_number', 'project_id', 'scope_of_work_id', 'created_by', 'line_items',
        'covered_total', 'billable_total', 'tax_amount', 'grand_total', 'approval_status',
        'client_approved_at', 'client_notes', 'valid_until', 'notes', 'terms_conditions',
        'scheduled_days', 'total_hours', 'subscription_savings'
    ];

    protected $casts = [
        'line_items' => 'array',
        'scheduled_days' => 'array',
        'covered_total' => 'decimal:2',
        'billable_total' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'subscription_savings' => 'decimal:2',
        'total_hours' => 'decimal:2',
        'client_approved_at' => 'datetime',
        'valid_until' => 'date',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function scopeOfWork(): BelongsTo
    {
        return $this->belongsTo(ScopeOfWork::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isApproved(): bool
    {
        return $this->approval_status === 'approved';
    }

    public function isPending(): bool
    {
        return $this->approval_status === 'pending';
    }
}
