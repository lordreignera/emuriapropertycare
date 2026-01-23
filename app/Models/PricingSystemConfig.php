<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PricingSystemConfig extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'config_key',
        'config_value',
        'data_type',
        'config_group',
        'description',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'updated_at' => 'datetime',
    ];

    public static function get(string $key, $default = null)
    {
        $config = self::where('config_key', $key)->first();
        
        if (!$config) {
            return $default;
        }

        return self::castValue($config->config_value, $config->data_type);
    }

    public static function set(string $key, $value): bool
    {
        $config = self::where('config_key', $key)->first();
        
        if ($config) {
            $config->config_value = $value;
            $config->updated_at = now();
            return $config->save();
        }

        return false;
    }

    protected static function castValue($value, string $dataType)
    {
        return match($dataType) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'decimal' => (float) $value,
            'json' => json_decode($value, true),
            default => $value,
        };
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeGroup($query, string $group)
    {
        return $query->where('config_group', $group);
    }
}
