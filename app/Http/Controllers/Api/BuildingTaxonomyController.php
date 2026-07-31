<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BuildingComponentResource;
use App\Http\Resources\BuildingSubsystemResource;
use App\Http\Resources\BuildingSystemResource;
use App\Models\BuildingSubsystem;
use App\Models\BuildingSystem;

class BuildingTaxonomyController extends Controller
{
    public function taxonomy()
    {
        $systems = BuildingSystem::query()
            ->where('is_active', true)
            ->with([
                'subsystems' => fn ($query) => $query
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('name'),
                'subsystems.components' => fn ($query) => $query
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('name'),
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return BuildingSystemResource::collection($systems);
    }

    public function systems()
    {
        $systems = BuildingSystem::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return BuildingSystemResource::collection($systems);
    }

    public function subsystems(BuildingSystem $buildingSystem)
    {
        abort_unless($buildingSystem->is_active, 404);

        $subsystems = $buildingSystem->subsystems()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return BuildingSubsystemResource::collection($subsystems);
    }

    public function components(BuildingSubsystem $buildingSubsystem)
    {
        abort_unless($buildingSubsystem->is_active, 404);

        $components = $buildingSubsystem->components()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return BuildingComponentResource::collection($components);
    }
}
