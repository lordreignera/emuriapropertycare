<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PropertyType extends Model
{
    protected $fillable = [
        'type_code',
        'type_name',
        'uses_unit_count',
        'uses_square_footage',
        'is_active',
    ];

    protected $casts = [
        'uses_unit_count' => 'boolean',
        'uses_square_footage' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get all package pricing records for this property type
     */
    public function packagePricing(): HasMany
    {
        return $this->hasMany(PackagePricing::class, 'property_type_id');
    }
}
