<?php

namespace Database\Seeders;

use App\Models\InspectionSystem;
use App\Models\InspectionSubsystem;
use App\Support\PharCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class InspectionSystemsSeeder extends Seeder
{
    public function run(): void
    {
        $systems = PharCatalog::systemMap();
        $weights = PharCatalog::systemWeights();
        $activeSystemSlugs = [];
        $activeSubsystemSlugs = [];

        $systemOrder = 1;

        foreach ($systems as $systemName => $subsystems) {
            $systemRecommendations = $this->getSystemRecommendations($systemName);
            $systemSlug = Str::slug($systemName);
            $activeSystemSlugs[] = $systemSlug;

            $system = InspectionSystem::updateOrCreate(
                ['slug' => $systemSlug],
                [
                    'name'                => $systemName,
                    'description'         => 'Inspection system for ' . $systemName,
                    'recommended_actions' => $systemRecommendations,
                    'sort_order'          => $systemOrder,
                    'weight'              => $weights[$systemName] ?? 0,
                    'is_active'           => true,
                ]
            );

            foreach (array_values($subsystems) as $subOrder => $subName) {
                $slug = Str::slug($systemName . ' ' . $subName);
                $activeSubsystemSlugs[] = $slug;

                InspectionSubsystem::updateOrCreate(
                    ['slug' => $slug],
                    [
                        'system_id' => $system->id,
                        'name' => $subName,
                        'description' => $subName . ' checks within ' . $systemName,
                        'recommended_actions' => $this->getSubsystemRecommendations($systemName, $subName, $systemRecommendations),
                        'sort_order' => $subOrder + 1,
                        'is_active' => true,
                    ]
                );
            }

            $systemOrder++;
        }

        InspectionSubsystem::query()
            ->when($activeSubsystemSlugs !== [], function ($query) use ($activeSubsystemSlugs) {
                $query->whereNotIn('slug', $activeSubsystemSlugs);
            })
            ->update(['is_active' => false]);

        InspectionSystem::query()
            ->when($activeSystemSlugs !== [], function ($query) use ($activeSystemSlugs) {
                $query->whereNotIn('slug', $activeSystemSlugs);
            })
            ->update(['is_active' => false]);
    }

    private function getSystemRecommendations(string $systemName): array
    {
        return PharCatalog::recommendedActionsForSystem($systemName) ?: ['Inspect and diagnose', 'Repair damaged area', 'Preventive maintenance', 'Schedule follow-up'];
    }

    private function getSubsystemRecommendations(string $systemName, string $subsystemName, array $fallback): array
    {
        return PharCatalog::recommendedActionsForSubsystem($systemName, $subsystemName) ?: $fallback;
    }
}
