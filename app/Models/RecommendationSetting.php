<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecommendationSetting extends Model
{
    protected $fillable = [
        'recommendation',
        'building_system_id',
        'building_subsystem_id',
        'building_component_id',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'building_system_id' => 'integer',
        'building_subsystem_id' => 'integer',
        'building_component_id' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
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
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('recommendation');
    }
}
