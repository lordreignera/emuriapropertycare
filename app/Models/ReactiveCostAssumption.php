<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReactiveCostAssumption extends Model
{
    protected $fillable = [
        'severity_level',
        'typical_cost',
        'annual_probability',
        'claimable_fraction',
        'description',
        'sort_order',
    ];

    protected $casts = [
        'typical_cost' => 'decimal:2',
        'annual_probability' => 'decimal:2',
        'claimable_fraction' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    public static function getBySeverity(string $severity)
    {
        return self::where('severity_level', $severity)->first();
    }

    public function getFormattedCostAttribute(): string
    {
        return '$' . number_format($this->typical_cost, 2);
    }

    public function getFormattedProbabilityAttribute(): string
    {
        return number_format($this->annual_probability * 100, 0) . '%';
    }

    public function getFormattedClaimableFractionAttribute(): string
    {
        return number_format($this->claimable_fraction * 100, 0) . '%';
    }
}
