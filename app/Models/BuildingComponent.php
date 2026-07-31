<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class BuildingComponent extends Model
{
    protected $fillable = [
        'building_subsystem_id',
        'code',
        'name',
        'slug',
        'description',
        'default_trade',
        'aliases',
        'sort_order',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'metadata' => 'array',
            'aliases' => 'array',
        ];
    }

    public function subsystem(): BelongsTo
    {
        return $this->belongsTo(
            BuildingSubsystem::class,
            'building_subsystem_id'
        );
    }

    public function system(): HasOneThrough
    {
        return $this->hasOneThrough(
            BuildingSystem::class,
            BuildingSubsystem::class,
            'id',
            'id',
            'building_subsystem_id',
            'building_system_id'
        );
    }
}
