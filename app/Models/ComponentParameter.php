<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComponentParameter extends Model
{
    use HasFactory;

    protected $fillable = [
        'component_id',
        'parameter_name',
        'description',
        'value_type',
        'default_value',
        'min_value',
        'max_value',
        'unit',
        'cost_per_unit',
        'calculated_cost',
        'sort_order',
        'is_required',
        'is_user_editable',
        'calculation_formula',
        'validation_rules',
        'metadata',
    ];

    protected $casts = [
        'default_value' => 'decimal:2',
        'min_value' => 'decimal:2',
        'max_value' => 'decimal:2',
        'cost_per_unit' => 'decimal:2',
        'calculated_cost' => 'decimal:2',
        'is_required' => 'boolean',
        'is_user_editable' => 'boolean',
        'calculation_formula' => 'array',
        'validation_rules' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Get the component that owns this parameter.
     */
    public function component(): BelongsTo
    {
        return $this->belongsTo(ProductComponent::class, 'component_id');
    }

    /**
     * Calculate parameter cost
     * 
     * @param float|null $customValue Override default value
     * @return float
     */
    public function calculateCost(?float $customValue = null): float
    {
        $value = $customValue ?? $this->default_value ?? 0;
        
        // Basic calculation: value × cost_per_unit
        $cost = $value * $this->cost_per_unit;
        
        // If there's a complex formula, apply it
        if ($this->calculation_formula) {
            $cost = $this->applyFormula($value);
        }
        
        $this->calculated_cost = $cost;
        return $cost;
    }

    /**
     * Apply complex calculation formula
     * 
     * @param float $value
     * @return float
     */
    protected function applyFormula(float $value): float
    {
        $formula = $this->calculation_formula;
        
        if (!$formula || !isset($formula['type'])) {
            return $value * $this->cost_per_unit;
        }
        
        switch ($formula['type']) {
            case 'linear':
                // y = mx + b
                return ($formula['slope'] ?? 1) * $value + ($formula['intercept'] ?? 0);
            
            case 'tiered':
                // Different rates for different ranges
                foreach ($formula['tiers'] ?? [] as $tier) {
                    if ($value >= $tier['min'] && $value <= $tier['max']) {
                        return $value * $tier['rate'];
                    }
                }
                return $value * $this->cost_per_unit;
            
            case 'percentage':
                // Percentage of another value
                $baseValue = $formula['base_value'] ?? $this->cost_per_unit;
                return ($value / 100) * $baseValue;
            
            case 'exponential':
                // For compound increases
                return $this->cost_per_unit * pow($value, $formula['exponent'] ?? 1);
            
            default:
                return $value * $this->cost_per_unit;
        }
    }

    /**
     * Validate parameter value
     * 
     * @param mixed $value
     * @return bool
     */
    public function validateValue($value): bool
    {
        // Check min/max if numeric
        if ($this->value_type === 'numeric') {
            if ($this->min_value !== null && $value < $this->min_value) {
                return false;
            }
            if ($this->max_value !== null && $value > $this->max_value) {
                return false;
            }
        }
        
        // Apply custom validation rules
        if ($this->validation_rules) {
            // Add custom validation logic here
        }
        
        return true;
    }

    /**
     * Get formatted display
     */
    public function getFormattedCostAttribute(): string
    {
        $value = $this->default_value ?? 0;
        
        if ($this->unit) {
            return "{$value} {$this->unit} × \${$this->cost_per_unit}/{$this->unit} = \${$this->calculated_cost}";
        }
        
        return "\${$this->calculated_cost}";
    }
}
