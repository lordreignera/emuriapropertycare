<?php

namespace Tests\Feature;

use App\Models\Inspection;
use App\Models\PHARFinding;
use App\Models\BuildingComponent;
use App\Models\BuildingSubsystem;
use App\Models\BuildingSystem;
use App\Models\PHARFindingAffectedArea;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ClientDashboardFindingsReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_dashboard_points_shared_findings_to_the_review_report(): void
    {
        [$client, $inspection] = $this->createClientWithSharedFindings();

        $response = $this->actingAs($client, 'sanctum')
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('PHAR Findings Ready for Review');
        $response->assertSee('Review PHAR Findings');
        $response->assertSee(route('client.inspections.findings-report', $inspection), false);
    }

    public function test_client_inspections_page_shows_a_clear_phar_report_button(): void
    {
        [$client, $inspection] = $this->createClientWithSharedFindings();

        $response = $this->actingAs($client, 'sanctum')
            ->get(route('client.inspections.index'));

        $response->assertOk();
        $response->assertSee('Your PHAR report is ready');
        $response->assertSee('Open PHAR Report');
        $response->assertSee(route('client.inspections.findings-report', $inspection), false);
    }

    public function test_findings_report_separates_the_report_from_client_decisions(): void
    {
        [$client, $inspection] = $this->createClientWithSharedFindings();

        $response = $this->actingAs($client, 'sanctum')
            ->get(route('client.inspections.findings-report', $inspection));

        $response->assertOk();
        $response->assertSee('Diagnosis Report');
        $response->assertSee('Property &amp; Finding Photos', false);
        $response->assertDontSee('Open Full Twin');
        $response->assertSee('Client reported under Gutters: gutters broken');
        $response->assertSee('Why does it matter?');
        $response->assertSee('Gutters and downspouts control how rainwater leaves the roof edge.');
        $response->assertSee('What should be done next?');
        $response->assertSee('Recommended action');
        $response->assertSee('Repair or replace damaged gutter sections');
        $response->assertSee('Cascading / affected areas');
        $response->assertSee('Building Envelope');
        $response->assertSee('Exterior Wall Assemblies');
        $response->assertSee('Brick Veneer');
        $response->assertSee('Water staining below the roof edge');
        $response->assertSee('Evidence photos');
        $response->assertSee('demo-gutter.jpg');
        $response->assertSee('Client Decisions');
        $response->assertSee('Do now - include in proposal');
        $response->assertDontSee('An error occurred while processing your inspection request');
    }

    public function test_admin_cannot_start_deliverable_costing_before_client_commits_findings(): void
    {
        [, $inspection] = $this->createClientWithSharedFindings();

        Role::firstOrCreate([
            'name' => 'Administrator',
            'guard_name' => 'web',
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('Administrator');

        $this->actingAs($admin)
            ->get(route('inspections.estimation', $inspection))
            ->assertRedirect(route('inspections.findings-preview', $inspection));

        $this->actingAs($admin)
            ->post(route('inspections.share-quotation', $inspection))
            ->assertRedirect(route('inspections.findings-preview', $inspection));
    }

    /**
     * @return array{0: \App\Models\User, 1: \App\Models\Inspection}
     */
    private function createClientWithSharedFindings(): array
    {
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckActiveSubscription::class,
        ]);

        $clientRole = Role::firstOrCreate([
            'name' => 'Client',
            'guard_name' => 'web',
        ]);

        $client = User::factory()->create([
            'name' => 'Demo Client',
            'email' => 'client@example.test',
        ]);
        $client->assignRole($clientRole);

        $property = Property::create([
            'property_code' => 'TES-2001',
            'user_id' => $client->id,
            'owner_first_name' => 'Demo',
            'owner_phone' => '0780000000',
            'owner_email' => $client->email,
            'property_name' => 'Review Ready Home',
            'property_address' => 'Makerere Hill Road',
            'city' => 'Kampala',
            'province' => 'Central',
            'postal_code' => '256',
            'country' => 'Uganda',
            'type' => 'residential',
            'residential_units' => 1,
            'number_of_units' => 1,
            'blueprint_file' => 'properties/blueprints/demo-floor-plan.pdf',
            'status' => 'awaiting_inspection',
        ]);

        $inspection = Inspection::create([
            'property_id' => $property->id,
            'scheduled_date' => now()->subDay(),
            'status' => 'findings_shared',
            'inspection_fee_status' => 'paid',
            'findings_report_shared_at' => now(),
            'property_code' => $property->property_code,
            'property_name' => $property->property_name,
            'property_address_snapshot' => $property->property_address,
            'property_type_snapshot' => $property->type,
        ]);

        $system = BuildingSystem::create([
            'code' => 'ENV',
            'name' => 'Building Envelope',
            'slug' => 'building-envelope',
            'sort_order' => 10,
            'is_core' => true,
            'is_active' => true,
        ]);

        $subsystem = BuildingSubsystem::create([
            'building_system_id' => $system->id,
            'code' => 'ENV-WALL',
            'name' => 'Exterior Wall Assemblies',
            'slug' => 'exterior-wall-assemblies',
            'sort_order' => 20,
            'is_active' => true,
        ]);

        $component = BuildingComponent::create([
            'building_subsystem_id' => $subsystem->id,
            'code' => 'ENV-WALL-001',
            'name' => 'Brick Veneer',
            'slug' => 'brick-veneer',
            'sort_order' => 10,
            'is_active' => true,
        ]);

        $finding = PHARFinding::create([
            'inspection_id' => $inspection->id,
            'property_id' => $property->id,
            'task_question' => 'gutters broken',
            'category' => 'Gutters',
            'severity' => 'critical',
            'priority' => '1',
            'plain_language_definition' => 'Gutters move roof water away from the building.',
            'observed_condition' => 'Client reported under Gutters: gutters broken',
            'consequence_if_ignored' => 'An error occurred while processing your inspection request. Please try again.',
            'photo_ids' => ['inspections/finding-photos/demo-gutter.jpg'],
            'workflow_status' => 'decision_pending',
        ]);

        PHARFindingAffectedArea::create([
            'phar_finding_id' => $finding->id,
            'building_system_id' => $system->id,
            'building_subsystem_id' => $subsystem->id,
            'building_component_id' => $component->id,
            'location' => 'North elevation',
            'impact_description' => 'Water staining below the roof edge',
            'severity' => 'moderate',
            'sort_order' => 10,
        ]);

        return [$client, $inspection];
    }
}
