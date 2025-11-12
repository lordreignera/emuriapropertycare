<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Budget extends Model
{
    protected $fillable = [
        'project_id', 'estimated_revenue', 'estimated_costs', 'actual_revenue', 'actual_costs',
        'variance_notes', 'updated_by'
    ];

    protected $casts = [
        'estimated_revenue' => 'decimal:2',
        'estimated_costs' => 'decimal:2',
        'actual_revenue' => 'decimal:2',
        'actual_costs' => 'decimal:2',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getEstimatedProfitAttribute(): float
    {
        return $this->estimated_revenue - $this->estimated_costs;
    }

    public function getActualProfitAttribute(): float
    {
        return $this->actual_revenue - $this->actual_costs;
    }

    public function getRevenueVarianceAttribute(): float
    {
        return $this->actual_revenue - $this->estimated_revenue;
    }

    public function getCostVarianceAttribute(): float
    {
        return $this->actual_costs - $this->estimated_costs;
    }

    public function getProfitMarginAttribute(): float
    {
        if ($this->actual_revenue == 0) {
            return 0;
        }
        return ($this->actual_profit / $this->actual_revenue) * 100;
    }

    public function isOverBudget(): bool
    {
        return $this->actual_costs > $this->estimated_costs;
    }
}
