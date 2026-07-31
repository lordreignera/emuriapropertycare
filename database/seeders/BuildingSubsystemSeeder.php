<?php

namespace Database\Seeders;

use App\Models\BuildingSubsystem;
use App\Models\BuildingSystem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BuildingSubsystemSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $systemsByCode = BuildingSystem::query()->pluck('id', 'code');

            foreach (config('building_taxonomy', []) as $systemRow) {
                $systemId = $systemsByCode[$systemRow['code']] ?? null;

                if (!$systemId) {
                    continue;
                }

                foreach ($systemRow['subsystems'] ?? [] as $subsystemRow) {
                    BuildingSubsystem::updateOrCreate(
                        ['code' => $subsystemRow['code']],
                        [
                            'building_system_id' => $systemId,
                            'name' => $subsystemRow['name'],
                            'slug' => $subsystemRow['slug'],
                            'description' => $subsystemRow['description'] ?? null,
                            'sort_order' => $subsystemRow['sort_order'] ?? 0,
                            'is_active' => true,
                            'metadata' => array_merge($subsystemRow['metadata'] ?? [], ['seeded' => true]),
                        ]
                    );
                }
            }
        });
    }
}
