<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClientCustomProduct extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'client_id',
        'property_id',
        'base_product_id',
        'inspection_id',
        'custom_product_name',
        'custom_description',
        'customized_components',
        'total_price',
        'pricing_model',
        'monthly_price',
        'annual_price',
        'valid_from',
        'valid_until',
        'status',
        'offered_at',
        'accepted_at',
        'created_by',
    ];

    protected $casts = [
        'customized_components' => 'array',
        'total_price' => 'decimal:2',
        'monthly_price' => 'decimal:2',
        'annual_price' => 'decimal:2',
        'valid_from' => 'date',
        'valid_until' => 'date',
        'offered_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];

    /**
     * Get the client that owns this custom product.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    /**
     * Get the property this product is for.
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Get the base product.
     */
    public function baseProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'base_product_id');
    }

    /**
     * Get the inspection that generated this product.
     */
    public function inspection(): BelongsTo
    {
        return $this->belongsTo(Inspection::class);
    }

    /**
     * Get the user who created this custom product.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Calculate total price from customized components
     */
    public function calculateTotalPrice(): float
    {
        $total = 0;

        if (is_array($this->customized_components)) {
            foreach ($this->customized_components as $component) {
                $total += $component['calculated_cost'] ?? 0;
            }
        }

        $this->total_price = $total;
        return $total;
    }

    /**
     * Mark product as offered to client
     */
    public function markAsOffered(): void
    {
        $this->update([
            'status' => 'offered',
            'offered_at' => now(),
        ]);
    }

    /**
     * Mark product as accepted by client
     */
    public function markAsAccepted(): void
    {
        $this->update([
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);
    }

    /**
     * Check if product is still valid
     */
    public function isValid(): bool
    {
        if (!$this->valid_until) {
            return true;
        }

        return now()->lte($this->valid_until);
    }

    /**
     * Scope to get active products
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get offered products
     */
    public function scopeOffered($query)
    {
        return $query->where('status', 'offered');
    }
}
