<?php

namespace Database\Seeders;

use App\Models\BuildingComponent;
use App\Models\BuildingSubsystem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BuildingComponentSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $subsystemsByCode = BuildingSubsystem::query()->pluck('id', 'code');

            foreach (config('building_taxonomy', []) as $systemRow) {
                foreach ($systemRow['subsystems'] ?? [] as $subsystemRow) {
                    $subsystemId = $subsystemsByCode[$subsystemRow['code']] ?? null;

                    if (!$subsystemId) {
                        continue;
                    }

                    foreach ($subsystemRow['components'] ?? [] as $componentRow) {
                        BuildingComponent::updateOrCreate(
                            ['code' => $componentRow['code']],
                            [
                                'building_subsystem_id' => $subsystemId,
                                'name' => $componentRow['name'],
                                'slug' => $componentRow['slug'],
                                'description' => $componentRow['description'] ?? null,
                                'default_trade' => $componentRow['default_trade'] ?? null,
                                'aliases' => $componentRow['aliases'] ?? [],
                                'sort_order' => $componentRow['sort_order'] ?? 0,
                                'is_active' => true,
                                'metadata' => array_merge($componentRow['metadata'] ?? [], ['seeded' => true]),
                            ]
                        );
                    }
                }
            }
        });
    }
}
