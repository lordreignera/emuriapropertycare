<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'component_name',
        'description',
        'calculation_type',
        'parameter_name',
        'parameter_value',
        'unit_cost',
        'calculated_cost',
        'sort_order',
        'is_required',
        'is_customizable',
        'metadata',
    ];

    protected $casts = [
        'parameter_value' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'calculated_cost' => 'decimal:2',
        'is_required' => 'boolean',
        'is_customizable' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Get the product that owns the component.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get all parameters for this component.
     */
    public function parameters(): HasMany
    {
        return $this->hasMany(ComponentParameter::class, 'component_id')->orderBy('sort_order');
    }

    /**
     * Calculate component cost based on calculation_type
     * Now includes parameter-level costs
     */
    public function calculateCost(): float
    {
        $cost = 0;

        // If component has parameters, calculate from them
        if ($this->parameters()->count() > 0) {
            foreach ($this->parameters as $parameter) {
                $cost += $parameter->calculateCost();
            }
            $this->calculated_cost = $cost;
            return $cost;
        }

        // Otherwise, use the old calculation method
        switch ($this->calculation_type) {
            case 'fixed':
                // Fixed cost (e.g., $150)
                $cost = $this->unit_cost;
                break;

            case 'multiply':
                // Quantity × Unit Cost (e.g., 4 filters × $25 = $100)
                $cost = $this->parameter_value * $this->unit_cost;
                break;

            case 'hourly':
                // Hours × Hourly Rate (e.g., 2 hours × $100/hr = $200)
                $cost = $this->parameter_value * $this->unit_cost;
                break;

            case 'add':
                // Sum of sub-items (could be expanded for nested components)
                $cost = $this->unit_cost;
                break;

            case 'percentage':
                // Percentage of another value (e.g., 10% markup)
                $cost = ($this->parameter_value / 100) * $this->unit_cost;
                break;

            default:
                $cost = $this->unit_cost;
        }

        $this->calculated_cost = $cost;
        return $cost;
    }

    /**
     * Recalculate all parameter costs
     */
    public function recalculateParameters(): void
    {
        foreach ($this->parameters as $parameter) {
            $parameter->calculateCost();
            $parameter->save();
        }
    }

    /**
     * Auto-calculate cost on save
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($component) {
            $component->calculated_cost = $component->calculateCost();
        });
    }

    /**
     * Get formatted cost display
     */
    public function getFormattedCostAttribute(): string
    {
        switch ($this->calculation_type) {
            case 'multiply':
            case 'hourly':
                return "{$this->parameter_value} × \${$this->unit_cost} = \${$this->calculated_cost}";
            
            case 'percentage':
                return "{$this->parameter_value}% of \${$this->unit_cost} = \${$this->calculated_cost}";
            
            case 'fixed':
            default:
                return "\${$this->calculated_cost}";
        }
    }
}
