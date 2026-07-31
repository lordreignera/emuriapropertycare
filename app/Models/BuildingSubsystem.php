<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BuildingSubsystem extends Model
{
    protected $fillable = [
        'building_system_id',
        'code',
        'name',
        'slug',
        'description',
        'sort_order',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function system(): BelongsTo
    {
        return $this->belongsTo(BuildingSystem::class, 'building_system_id');
    }

    public function components(): HasMany
    {
        return $this->hasMany(BuildingComponent::class)
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    public function getRecommendedActionsAttribute(): array
    {
        return $this->metadata['recommended_actions'] ?? [];
    }
}
