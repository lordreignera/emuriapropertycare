<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MixedUseCalculationSetting extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'setting_name',
        'setting_value',
        'description',
    ];

    protected $casts = [
        'setting_value' => 'decimal:2',
        'updated_at' => 'datetime',
    ];
}
