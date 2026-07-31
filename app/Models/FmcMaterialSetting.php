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
        'building_system_id',
        'building_subsystem_id',
        'building_component_id',
    ];

    protected $casts = [
        'default_unit_cost' => 'decimal:2',
        'hst_rate'          => 'decimal:2',
        'pst_rate'          => 'decimal:2',
        'is_active'         => 'boolean',
        'sort_order'        => 'integer',
        'building_system_id'         => 'integer',
        'building_subsystem_id'      => 'integer',
        'building_component_id'      => 'integer',
    ];

    public function system(): BelongsTo
    {
        return $this->belongsTo(BuildingSystem::class, 'building_system_id');
    }

    public function subsystem(): BelongsTo
    {
        return $this->belongsTo(BuildingSubsystem::class, 'building_subsystem_id');
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(BuildingComponent::class, 'building_component_id');
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
