<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BDCSetting extends Model
{
    protected $table = 'bdc_settings';
    
    protected $fillable = [
        'setting_key',
        'setting_label',
        'setting_description',
        'setting_value',
        'unit',
        'setting_type',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'setting_value' => 'decimal:2',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get setting value by key
     */
    public static function getValue(string $key, $default = null)
    {
        $setting = self::where('setting_key', $key)
            ->where('is_active', true)
            ->first();
            
        return $setting ? $setting->setting_value : $default;
    }

    /**
     * Update setting value by key
     */
    public static function updateValue(string $key, $value): bool
    {
        return self::where('setting_key', $key)
            ->update(['setting_value' => $value]);
    }

    /**
     * Get all active settings as key-value pairs
     */
    public static function getAll(): array
    {
        return self::where('is_active', true)
            ->orderBy('sort_order')
            ->pluck('setting_value', 'setting_key')
            ->toArray();
    }

    /**
     * Get all active settings with full details
     */
    public static function getAllWithDetails()
    {
        return self::where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }
}
