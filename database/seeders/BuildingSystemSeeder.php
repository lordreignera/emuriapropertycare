<?php

namespace Database\Seeders;

use App\Models\BuildingSystem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BuildingSystemSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            foreach (config('building_taxonomy', []) as $systemRow) {
                BuildingSystem::updateOrCreate(
                    ['code' => $systemRow['code']],
                    [
                        'name' => $systemRow['name'],
                        'slug' => $systemRow['slug'],
                        'description' => $systemRow['description'] ?? null,
                        'sort_order' => $systemRow['sort_order'] ?? 0,
                        'is_core' => (bool) ($systemRow['is_core'] ?? false),
                        'is_active' => true,
                        'metadata' => array_merge($systemRow['metadata'] ?? [], ['seeded' => true]),
                    ]
                );
            }
        });
    }
}
