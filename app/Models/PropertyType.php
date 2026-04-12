<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
