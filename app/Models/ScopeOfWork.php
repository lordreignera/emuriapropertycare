<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScopeOfWork extends Model
{
    protected $fillable = [
        'project_id', 'inspection_id', 'created_by', 'description', 'work_items',
        'estimated_hours', 'estimated_cost', 'tier_covered_amount', 'client_billable_amount',
        'coverage_status', 'approved_by_pm', 'pm_approved_by', 'pm_approved_at'
    ];

    protected $casts = [
        'work_items' => 'array',
        'estimated_hours' => 'decimal:2',
        'estimated_cost' => 'decimal:2',
        'tier_covered_amount' => 'decimal:2',
        'client_billable_amount' => 'decimal:2',
        'approved_by_pm' => 'boolean',
        'pm_approved_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(Inspection::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function isFullyCovered(): bool
    {
        return $this->coverage_status === 'fully_covered';
    }
}
