<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_code',
        'product_name',
        'description',
        'category',
        'pricing_type',
        'base_price',
        'is_active',
        'is_customizable',
        'created_by',
        'metadata',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'is_active' => 'boolean',
        'is_customizable' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Get the user who created this product.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all components for this product.
     */
    public function components(): HasMany
    {
        return $this->hasMany(ProductComponent::class)->orderBy('sort_order');
    }

    /**
     * Get all custom products based on this product.
     */
    public function customProducts(): HasMany
    {
        return $this->hasMany(ClientCustomProduct::class, 'base_product_id');
    }

    /**
     * Calculate total price based on components
     */
    public function calculateTotalPrice(): float
    {
        $total = $this->base_price;
        
        foreach ($this->components as $component) {
            $total += $component->calculated_cost;
        }
        
        return $total;
    }

    /**
     * Recalculate all component costs
     */
    public function recalculateComponents(): void
    {
        foreach ($this->components as $component) {
            $component->calculateCost();
            $component->save();
        }
    }

    /**
     * Scope to get only active products
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by category
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}
