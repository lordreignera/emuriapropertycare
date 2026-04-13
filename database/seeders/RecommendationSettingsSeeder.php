<?php

namespace Database\Seeders;

use App\Models\FindingTemplateSetting;
use App\Models\InspectionSubsystem;
use App\Models\InspectionSystem;
use App\Models\RecommendationSetting;
use Illuminate\Database\Seeder;

class RecommendationSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $entries = [];

        $systems = InspectionSystem::query()->get(['id', 'recommended_actions']);
        foreach ($systems as $system) {
            foreach ((array) ($system->recommended_actions ?? []) as $recommendation) {
                $entries[] = [
                    'recommendation' => $recommendation,
                    'system_id' => $system->id,
                    'subsystem_id' => null,
                ];
            }
        }

        $subsystems = InspectionSubsystem::query()->get(['id', 'system_id', 'recommended_actions']);
        foreach ($subsystems as $subsystem) {
            foreach ((array) ($subsystem->recommended_actions ?? []) as $recommendation) {
                $entries[] = [
                    'recommendation' => $recommendation,
                    'system_id' => $subsystem->system_id,
                    'subsystem_id' => $subsystem->id,
                ];
            }
        }

        $findingTemplates = FindingTemplateSetting::query()
            ->where('is_active', true)
            ->get(['system_id', 'subsystem_id', 'default_recommendations']);

        foreach ($findingTemplates as $findingTemplate) {
            foreach ((array) ($findingTemplate->default_recommendations ?? []) as $recommendation) {
                $entries[] = [
                    'recommendation' => $recommendation,
                    'system_id' => $findingTemplate->system_id,
                    'subsystem_id' => $findingTemplate->subsystem_id,
                ];
            }
        }

        $uniqueEntries = [];
        foreach ($entries as $entry) {
            $recommendationText = trim((string) ($entry['recommendation'] ?? ''));
            if ($recommendationText === '') {
                continue;
            }

            $systemId = $entry['system_id'] !== null ? (int) $entry['system_id'] : null;
            $subsystemId = $entry['subsystem_id'] !== null ? (int) $entry['subsystem_id'] : null;
            $key = ($systemId ?? 'g') . '|' . ($subsystemId ?? 'g') . '|' . strtolower($recommendationText);

            $uniqueEntries[$key] = [
                'recommendation' => $recommendationText,
                'system_id' => $systemId,
                'subsystem_id' => $subsystemId,
            ];
        }

        RecommendationSetting::query()->update(['is_active' => false]);

        $sortOrder = 0;
        foreach (array_values($uniqueEntries) as $entry) {
            RecommendationSetting::updateOrCreate(
                [
                    'recommendation' => $entry['recommendation'],
                    'system_id' => $entry['system_id'],
                    'subsystem_id' => $entry['subsystem_id'],
                ],
                [
                    'sort_order' => $sortOrder++,
                    'is_active' => true,
                ]
            );
        }
    }
}
