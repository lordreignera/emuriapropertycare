<?php

namespace Tests\Feature;

use App\Models\BuildingComponent;
use App\Models\BuildingSubsystem;
use App\Models\BuildingSystem;
use Database\Seeders\BuildingComponentSeeder;
use Database\Seeders\BuildingSubsystemSeeder;
use Database\Seeders\BuildingSystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuildingTaxonomyTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_systems_are_seeded_in_order_with_unique_codes(): void
    {
        $this->seedBuildingTaxonomy();

        $expected = [
            'Building Envelope',
            'Interiors',
            'Plumbing',
            'HVAC and Mechanical',
            'Electrical',
            'Structure and Substructure',
            'Fire and Life Safety',
            'Communications, Security and Controls',
            'Conveying',
            'Equipment and Furnishings',
            'Site and Civil Works',
            'Special Construction and Amenities',
        ];

        $systems = BuildingSystem::query()->orderBy('sort_order')->orderBy('name')->get();

        $this->assertSame($expected, $systems->pluck('name')->all());
        $this->assertSame($systems->count(), $systems->pluck('code')->unique()->count());
        $this->assertSame(5, BuildingSystem::query()->where('is_core', true)->count());
    }

    public function test_subsystem_and_component_codes_are_unique_and_parented(): void
    {
        $this->seedBuildingTaxonomy();

        $subsystems = BuildingSubsystem::all();
        $components = BuildingComponent::all();

        $this->assertGreaterThan(0, $subsystems->count());
        $this->assertGreaterThan(0, $components->count());
        $this->assertSame($subsystems->count(), $subsystems->pluck('code')->unique()->count());
        $this->assertSame($components->count(), $components->pluck('code')->unique()->count());
        $this->assertSame(0, $subsystems->whereNull('building_system_id')->count());
        $this->assertSame(0, $components->whereNull('building_subsystem_id')->count());
    }

    public function test_api_returns_ordered_taxonomy(): void
    {
        $this->seedBuildingTaxonomy();

        $this->getJson('/api/building-taxonomy')
            ->assertOk()
            ->assertJsonPath('data.0.code', 'ENV')
            ->assertJsonPath('data.0.name', 'Building Envelope')
            ->assertJsonPath('data.0.subsystems.0.code', 'ENV-ROOF')
            ->assertJsonPath('data.0.subsystems.0.components.0.code', 'ENV-ROOF-001');

        $envelope = BuildingSystem::where('code', 'ENV')->firstOrFail();
        $roofing = BuildingSubsystem::where('code', 'ENV-ROOF')->firstOrFail();

        $this->getJson("/api/building-systems/{$envelope->slug}/subsystems")
            ->assertOk()
            ->assertJsonPath('data.0.code', 'ENV-ROOF');

        $this->getJson("/api/building-subsystems/{$roofing->slug}/components")
            ->assertOk()
            ->assertJsonPath('data.25.code', 'ENV-ROOF-026');
    }

    public function test_required_components_have_correct_primary_classification(): void
    {
        $this->seedBuildingTaxonomy();

        $this->assertComponentPath('ENV-ROOF-026', 'Gutters', 'Roofing', 'Building Envelope');
        $this->assertComponentPath('ENV-ROOF-033', 'Fascia', 'Roofing', 'Building Envelope');
        $this->assertComponentPath('STR-ROOF-002', 'Roof Truss', 'Roof Structure', 'Structure and Substructure');
        $this->assertComponentPath('ELE-BRCH-002', 'Electrical Receptacle', 'Branch Wiring and Devices', 'Electrical');
        $this->assertComponentPath('INT-TRIM-001', 'Baseboard', 'Interior Trim and Millwork', 'Interiors');
        $this->assertComponentPath('PLB-STORM-001', 'Internal Roof Drain', 'Storm Drainage and Rainwater Piping', 'Plumbing');
        $this->assertComponentPath('SITE-ROAD-001', 'Asphalt Driveway', 'Roads, Driveways and Parking', 'Site and Civil Works');
    }

    public function test_inactive_components_are_not_returned_for_new_findings(): void
    {
        $this->seedBuildingTaxonomy();

        $gutter = BuildingComponent::where('code', 'ENV-ROOF-026')->firstOrFail();
        $gutter->update(['is_active' => false]);

        $roofing = BuildingSubsystem::where('code', 'ENV-ROOF')->firstOrFail();

        $this->getJson("/api/building-subsystems/{$roofing->slug}/components")
            ->assertOk()
            ->assertJsonMissing(['code' => 'ENV-ROOF-026']);
    }

    public function test_rerunning_seeder_does_not_create_duplicates(): void
    {
        $this->seedBuildingTaxonomy();

        $counts = [
            BuildingSystem::count(),
            BuildingSubsystem::count(),
            BuildingComponent::count(),
        ];

        $this->seedBuildingTaxonomy();

        $this->assertSame($counts, [
            BuildingSystem::count(),
            BuildingSubsystem::count(),
            BuildingComponent::count(),
        ]);
    }

    private function assertComponentPath(string $code, string $component, string $subsystem, string $system): void
    {
        $record = BuildingComponent::where('code', $code)
            ->with('subsystem.system')
            ->firstOrFail();

        $this->assertSame($component, $record->name);
        $this->assertSame($subsystem, $record->subsystem->name);
        $this->assertSame($system, $record->subsystem->system->name);
    }

    private function seedBuildingTaxonomy(): void
    {
        $this->seed([
            BuildingSystemSeeder::class,
            BuildingSubsystemSeeder::class,
            BuildingComponentSeeder::class,
        ]);
    }
}
