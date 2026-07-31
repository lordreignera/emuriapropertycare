<?php

namespace App\Models;

use App\Support\PharCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FindingTemplateSetting extends Model
{
    protected $table = 'finding_template_settings';

    protected $fillable = [
        'task_question',
        'building_system_id',
        'building_subsystem_id',
        'building_component_id',
        'category',
        'default_included',
        'default_notes',
        'default_recommendations',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'building_system_id' => 'integer',
        'building_subsystem_id' => 'integer',
        'building_component_id' => 'integer',
        'default_included' => 'boolean',
        'default_recommendations' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public static function defaults(): array
    {
        return PharCatalog::findingTemplates();
    }

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
}
