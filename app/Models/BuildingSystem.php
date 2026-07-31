<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BuildingSystem extends Model
{
    protected $fillable = [
        'code',
        'name',
        'slug',
        'description',
        'sort_order',
        'is_core',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_core' => 'boolean',
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function subsystems(): HasMany
    {
        return $this->hasMany(BuildingSubsystem::class)
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    public function getRecommendedActionsAttribute(): array
    {
        return $this->metadata['recommended_actions'] ?? [];
    }

    public function getWeightAttribute(): int
    {
        return (int) ($this->metadata['cpi_weight'] ?? 10);
    }
}
