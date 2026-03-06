<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Parameter extends Model
{
    protected $fillable = [
        'parameter_key',
        'parameter_value',
        'group_name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'parameter_value' => 'decimal:6',
        'is_active' => 'boolean',
    ];

    public static function getValue(string $key, ?float $default = null): ?float
    {
        $value = static::query()
            ->where('parameter_key', $key)
            ->where('is_active', true)
            ->value('parameter_value');

        return $value !== null ? (float) $value : $default;
    }

    public static function defaultBaseServiceParameters(): array
    {
        return [
            ['parameter_key' => 'RES_PREMIUM_BASE_FACTOR', 'parameter_value' => 1.000000, 'description' => 'Residential premium baseline factor'],
            ['parameter_key' => 'RES_ESS_FACTOR', 'parameter_value' => 0.665000, 'description' => 'Residential essentials factor from premium baseline'],
            ['parameter_key' => 'RES_WHITE_FACTOR', 'parameter_value' => 1.335000, 'description' => 'Residential white-glove factor from premium baseline'],
            ['parameter_key' => 'COM_PREMIUM_BASE_FACTOR', 'parameter_value' => 1.000000, 'description' => 'Commercial premium baseline factor'],
            ['parameter_key' => 'COM_ESS_FACTOR', 'parameter_value' => 0.541667, 'description' => 'Commercial essentials factor from premium baseline'],
            ['parameter_key' => 'COM_WHITE_FACTOR', 'parameter_value' => 1.666667, 'description' => 'Commercial white-glove factor from premium baseline'],
        ];
    }
}
