<?php

namespace App\Models;

use App\Support\PharCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FmcMaterialSetting extends Model
{
    protected $fillable = [
        'material_name',
        'default_unit',
        'default_unit_cost',
        'hst_rate',
        'pst_rate',
        'description',
        'is_active',
        'sort_order',
        'system_id',
        'subsystem_id',
    ];

    protected $casts = [
        'default_unit_cost' => 'decimal:2',
        'hst_rate'          => 'decimal:2',
        'pst_rate'          => 'decimal:2',
        'is_active'         => 'boolean',
        'sort_order'        => 'integer',
        'system_id'         => 'integer',
        'subsystem_id'      => 'integer',
    ];

    public function system(): BelongsTo
    {
        return $this->belongsTo(InspectionSystem::class, 'system_id');
    }

    public function subsystem(): BelongsTo
    {
        return $this->belongsTo(InspectionSubsystem::class, 'subsystem_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public static function defaults(): array
    {
        return PharCatalog::materials();
    }
}
