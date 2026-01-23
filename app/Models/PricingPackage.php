<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PricingPackage extends Model
{
    protected $fillable = [
        'package_name',
        'description',
        'features',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'features' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    /**
     * Get all pricing records for this package
     */
    public function packagePricing(): HasMany
    {
        return $this->hasMany(PackagePricing::class, 'pricing_package_id');
    }

    /**
     * Get price for a specific property type
     */
    public function getPriceForPropertyType($propertyTypeId): ?float
    {
        $pricing = $this->packagePricing()
            ->where('property_type_id', $propertyTypeId)
            ->where('is_active', true)
            ->first();

        return $pricing ? $pricing->base_monthly_price : null;
    }
}
