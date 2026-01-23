<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackagePricing extends Model
{
    protected $table = 'package_pricing';

    protected $fillable = [
        'pricing_package_id',
        'property_type_id',
        'base_monthly_price',
        'is_active',
    ];

    protected $casts = [
        'base_monthly_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get the package for this pricing
     */
    public function pricingPackage(): BelongsTo
    {
        return $this->belongsTo(PricingPackage::class, 'pricing_package_id');
    }

    /**
     * Get the property type for this pricing
     */
    public function propertyType(): BelongsTo
    {
        return $this->belongsTo(PropertyType::class, 'property_type_id');
    }
}
