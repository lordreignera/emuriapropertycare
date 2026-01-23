<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResidentialSizeTier extends Model
{
    protected $fillable = [
        'tier_name',
        'min_units',
        'max_units',
        'size_factor',
        'description',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'min_units' => 'integer',
        'max_units' => 'integer',
        'size_factor' => 'decimal:2',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public static function getSizeFactorForUnits(int $units): float
    {
        $tier = self::active()
            ->where('min_units', '<=', $units)
            ->where(function ($query) use ($units) {
                $query->where('max_units', '>=', $units)
                    ->orWhereNull('max_units');
            })
            ->first();

        return $tier ? (float) $tier->size_factor : 1.00;
    }
}
