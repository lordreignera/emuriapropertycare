<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommercialSizeSetting extends Model
{
    protected $fillable = [
        'setting_name',
        'setting_value',
        'data_type',
        'description',
    ];

    protected $casts = [
        'setting_value' => 'decimal:2',
        'updated_at' => 'datetime',
    ];

    public static function getValue(string $settingName, $default = null)
    {
        $setting = self::where('setting_name', $settingName)->first();
        return $setting ? $setting->setting_value : $default;
    }

    public static function calculateSizeFactor(float $squareFootage): float
    {
        $baseDivisor = (float) self::getValue('base_sqft_divisor', 10000);
        $minFactor = (float) self::getValue('min_factor', 1.0);
        $maxFactor = self::getValue('max_factor');

        $calculatedFactor = max($minFactor, $squareFootage / $baseDivisor);

        if ($maxFactor !== null) {
            $calculatedFactor = min($calculatedFactor, (float) $maxFactor);
        }

        return round($calculatedFactor, 2);
    }
}
