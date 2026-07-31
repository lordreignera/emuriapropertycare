<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PHARFindingAffectedArea extends Model
{
    protected $table = 'phar_finding_affected_areas';

    protected $fillable = [
        'phar_finding_id',
        'building_system_id',
        'building_subsystem_id',
        'building_component_id',
        'location',
        'impact_description',
        'severity',
        'sort_order',
    ];

    public function finding(): BelongsTo
    {
        return $this->belongsTo(PHARFinding::class, 'phar_finding_id');
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
