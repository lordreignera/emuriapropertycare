<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FmcMaterialSetting extends Model
{
    protected $fillable = [
        'material_name',
        'default_unit',
        'default_unit_cost',
        'description',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'default_unit_cost' => 'decimal:2',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public static function defaults(): array
    {
        return [
            ['material_name' => 'Bathroom sealant', 'default_unit' => 'tube', 'default_unit_cost' => 12.50, 'sort_order' => 1],
            ['material_name' => 'GFCI outlet', 'default_unit' => 'ea', 'default_unit_cost' => 28.00, 'sort_order' => 2],
            ['material_name' => 'Paint (touch-up)', 'default_unit' => 'gal', 'default_unit_cost' => 48.00, 'sort_order' => 3],
            ['material_name' => 'Roller/brush', 'default_unit' => 'lot', 'default_unit_cost' => 18.00, 'sort_order' => 4],
        ];
    }
}
