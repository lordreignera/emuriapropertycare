<?php

namespace Database\Seeders;

use App\Models\FindingTemplateSetting;
use App\Models\BuildingSubsystem;
use App\Models\BuildingSystem;
use App\Models\RecommendationSetting;
use Illuminate\Database\Seeder;

class RecommendationSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $entries = [];

        $systems = BuildingSystem::query()->get(['id', 'metadata']);
        foreach ($systems as $system) {
            foreach ((array) ($system->recommended_actions ?? []) as $recommendation) {
                $entries[] = [
                    'recommendation' => $recommendation,
                    'building_system_id' => $system->id,
                    'building_subsystem_id' => null,
                    'building_component_id' => null,
                ];
            }
        }

        $subsystems = BuildingSubsystem::query()->get(['id', 'building_system_id', 'metadata']);
        foreach ($subsystems as $subsystem) {
            foreach ((array) ($subsystem->recommended_actions ?? []) as $recommendation) {
                $entries[] = [
                    'recommendation' => $recommendation,
                    'building_system_id' => $subsystem->building_system_id,
                    'building_subsystem_id' => $subsystem->id,
                    'building_component_id' => null,
                ];
            }
        }

        $findingTemplates = FindingTemplateSetting::query()
            ->where('is_active', true)
            ->get(['building_system_id', 'building_subsystem_id', 'building_component_id', 'default_recommendations']);

        foreach ($findingTemplates as $findingTemplate) {
            foreach ((array) ($findingTemplate->default_recommendations ?? []) as $recommendation) {
                $entries[] = [
                    'recommendation' => $recommendation,
                    'building_system_id' => $findingTemplate->building_system_id,
                    'building_subsystem_id' => $findingTemplate->building_subsystem_id,
                    'building_component_id' => $findingTemplate->building_component_id,
                ];
            }
        }

        $uniqueEntries = [];
        foreach ($entries as $entry) {
            $recommendationText = $this->sanitizeRecommendationText($entry['recommendation'] ?? '');
            if ($recommendationText === '') {
                continue;
            }

            $systemId = $entry['building_system_id'] !== null ? (int) $entry['building_system_id'] : null;
            $subsystemId = $entry['building_subsystem_id'] !== null ? (int) $entry['building_subsystem_id'] : null;
            $componentId = $entry['building_component_id'] !== null ? (int) $entry['building_component_id'] : null;
            $key = ($systemId ?? 'g') . '|' . ($subsystemId ?? 'g') . '|' . ($componentId ?? 'g') . '|' . strtolower($recommendationText);

            $uniqueEntries[$key] = [
                'recommendation' => $recommendationText,
                'building_system_id' => $systemId,
                'building_subsystem_id' => $subsystemId,
                'building_component_id' => $componentId,
            ];
        }

        RecommendationSetting::query()->update(['is_active' => false]);

        $sortOrder = 0;
        foreach (array_values($uniqueEntries) as $entry) {
            RecommendationSetting::updateOrCreate(
                    [
                        'recommendation' => $entry['recommendation'],
                        'building_system_id' => $entry['building_system_id'],
                        'building_subsystem_id' => $entry['building_subsystem_id'],
                        'building_component_id' => $entry['building_component_id'],
                    ],
                [
                    'sort_order' => $sortOrder++,
                    'is_active' => true,
                ]
            );
        }
    }

    private function sanitizeRecommendationText($value): string
    {
        $text = preg_replace('/\s+/', ' ', trim((string) $value)) ?? '';
        if ($text === '') {
            return '';
        }

        foreach ([
            'an error occurred while processing your inspection request',
            'an error occurred while processing your diagnosis request',
            'please try again',
            'an unexpected error occurred',
        ] as $fragment) {
            $text = preg_replace('/' . preg_quote($fragment, '/') . '[\.\s]*/i', '', $text) ?? '';
        }

        return trim(preg_replace('/\s+/', ' ', $text) ?? '');
    }
}
