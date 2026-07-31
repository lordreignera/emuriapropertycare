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

    public function test_repeated_diagnosis_saves_reuse_the_current_property_diagnosis(): void
    {
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckActiveSubscription::class,
        ]);

        Role::firstOrCreate(['name' => 'Administrator', 'guard_name' => 'web']);

        $admin = User::factory()->create();
        $admin->assignRole('Administrator');

        $client = User::factory()->create([
            'name' => 'Property Owner',
            'email' => 'owner@example.test',
        ]);

        $property = Property::create([
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
        ]);

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
}
