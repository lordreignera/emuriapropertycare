<?php

namespace Database\Seeders;

use App\Models\FindingTemplateSetting;
use App\Models\InspectionSubsystem;
use App\Models\InspectionSystem;
use Illuminate\Database\Seeder;

class FindingTemplateSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $systemMap = InspectionSystem::query()->pluck('id', 'name');
        $subsystemMap = InspectionSubsystem::query()->get()->keyBy(function ($subsystem) {
            return $subsystem->system_id . '|' . $subsystem->name;
        });

        $activeReferences = [];

        foreach (FindingTemplateSetting::defaults() as $row) {
            $systemId = $systemMap[$row['system_name']] ?? null;
            $subsystemId = null;

            if ($systemId !== null) {
                $subsystemKey = $systemId . '|' . $row['subsystem_name'];
                $subsystemId = optional($subsystemMap->get($subsystemKey))->id;
            }

            $activeReferences[] = $row['task_question'];

            FindingTemplateSetting::updateOrCreate(
                ['task_question' => $row['task_question']],
                [
                    'system_id'               => $systemId,
                    'subsystem_id'            => $subsystemId,
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
