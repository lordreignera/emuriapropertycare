<?php

namespace Database\Seeders;

use App\Models\FindingTemplateSetting;
use App\Support\BuildingTaxonomyResolver;
use Illuminate\Database\Seeder;

class FindingTemplateSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $activeReferences = [];

        foreach (FindingTemplateSetting::defaults() as $row) {
            $taxonomy = BuildingTaxonomyResolver::resolve($row['system_name'] ?? null, $row['subsystem_name'] ?? null);

            $activeReferences[] = $row['task_question'];

            FindingTemplateSetting::updateOrCreate(
                ['task_question' => $row['task_question']],
                [
                    'building_system_id' => $taxonomy['building_system_id'],
                    'building_subsystem_id' => $taxonomy['building_subsystem_id'],
                    'building_component_id' => $taxonomy['building_component_id'],
                    'category'                => $row['category'],
                    'default_included'        => $row['default_included'],
                    'default_notes'           => $row['default_notes'],
                    'default_recommendations' => $row['default_recommendations'] ?? [],
                    'is_active'               => true,
                    'sort_order'              => $row['sort_order'],
                ]
            );
        }

        FindingTemplateSetting::query()
            ->whereNotIn('task_question', $activeReferences)
            ->update(['is_active' => false]);
    }
}
