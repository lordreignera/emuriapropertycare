<?php

namespace Tests\Feature;

use App\Models\Inspection;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InspectionDiagnosisDuplicateTest extends TestCase
{
    use RefreshDatabase;

    private function actingAdministrator(): User
    {
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckActiveSubscription::class,
        ]);

        foreach (['Administrator', 'Inspector', 'Project Manager', 'Technician'] as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        $admin = User::factory()->create();
        $admin->assignRole('Administrator');

        return $admin;
    }

    private function propertyFor(User $client, array $overrides = []): Property
    {
        return Property::create(array_merge([
            'property_code' => 'PROP-DUP-001',
            'user_id' => $client->id,
            'owner_first_name' => 'Property',
            'owner_phone' => '0780000000',
            'owner_email' => $client->email,
            'property_name' => 'No Duplicate Villa',
            'property_address' => 'Test Street',
            'city' => 'Kampala',
            'province' => 'Central',
            'postal_code' => '256',
            'country' => 'Uganda',
            'type' => 'residential',
            'residential_units' => 1,
            'number_of_units' => 1,
            'status' => 'awaiting_inspection',
        ], $overrides));
    }

    public function test_repeated_diagnosis_saves_reuse_the_current_property_diagnosis(): void
    {
        $admin = $this->actingAdministrator();

        $client = User::factory()->create([
            'name' => 'Property Owner',
            'email' => 'owner@example.test',
        ]);

        $property = $this->propertyFor($client);

        $payload = [
            'property_id' => $property->id,
            'status' => 'in_progress',
            'inspection_date' => now()->format('Y-m-d H:i:s'),
            'overall_condition' => 'good',
            'inspector_notes' => 'Draft diagnosis notes.',
            'recommendations' => 'Draft recommendation.',
            'risk_summary' => 'Draft risk summary.',
        ];

        $this->actingAs($admin)
            ->post(route('inspections.store'), $payload)
            ->assertRedirect();

        $this->actingAs($admin)
            ->post(route('inspections.store'), array_replace($payload, [
                'inspector_notes' => 'Updated diagnosis notes.',
            ]))
            ->assertRedirect();

        $this->assertSame(1, Inspection::where('property_id', $property->id)->count());
        $this->assertSame(
            'Updated diagnosis notes.',
            Inspection::where('property_id', $property->id)->first()->inspector_notes
        );
    }

    public function test_autosave_reuses_report_ready_diagnosis_without_creating_a_duplicate(): void
    {
        $admin = $this->actingAdministrator();
        $client = User::factory()->create(['email' => 'owner-autosave@example.test']);
        $property = $this->propertyFor($client, [
            'property_code' => 'PROP-DUP-002',
            'property_name' => 'Previewed Villa',
            'owner_email' => $client->email,
        ]);

        $inspection = Inspection::create([
            'property_id' => $property->id,
            'inspector_id' => $admin->id,
            'scheduled_date' => now(),
            'status' => 'findings_captured',
            'overall_condition' => 'good',
            'findings' => [
                ['building_system_id' => 1, 'issue' => 'Leaking gutter joint'],
            ],
        ]);

        $this->actingAs($admin)
            ->postJson(route('inspections.autosave-draft'), [
                'property_id' => $property->id,
                'inspection_date' => now()->format('Y-m-d H:i:s'),
                'overall_condition' => 'good',
                'inspector_notes' => 'Continued after preview without sharing.',
            ])
            ->assertOk()
            ->assertJsonPath('inspection_id', $inspection->id);

        $this->assertSame(1, Inspection::where('property_id', $property->id)->count());
        $this->assertSame('findings_captured', $inspection->fresh()->status);
        $this->assertSame('Continued after preview without sharing.', $inspection->fresh()->inspector_notes);
    }

    public function test_diagnosed_reports_queue_uses_latest_diagnosis_per_property(): void
    {
        $admin = $this->actingAdministrator();
        $client = User::factory()->create(['email' => 'owner-queue@example.test']);
        $property = $this->propertyFor($client, [
            'property_code' => 'PROP-DUP-003',
            'property_name' => 'Queue Ready Villa',
            'owner_email' => $client->email,
        ]);

        Inspection::create([
            'property_id' => $property->id,
            'inspector_id' => $admin->id,
            'scheduled_date' => now()->subDay(),
            'status' => 'in_progress',
            'overall_condition' => 'good',
        ]);

        Inspection::create([
            'property_id' => $property->id,
            'inspector_id' => $admin->id,
            'scheduled_date' => now(),
            'status' => 'findings_captured',
            'overall_condition' => 'good',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('inspections.index', ['view' => 'awaiting-quotation']))
            ->assertOk();

        $html = $response->getContent();

        $this->assertStringContainsString('Queue Ready Villa', $html);
        $this->assertSame(1, substr_count($html, 'Ready to Share'));
    }
}
