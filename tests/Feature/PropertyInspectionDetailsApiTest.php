<?php

namespace Tests\Feature;

use App\Models\Inspection;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PropertyInspectionDetailsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_property_owner_can_view_their_inspection_details(): void
    {
        Role::firstOrCreate(['name' => 'Client', 'guard_name' => 'web']);

        $client = User::factory()->create();
        $client->assignRole('Client');

        $property = $this->createPropertyFor($client);

        Inspection::create([
            'property_id' => $property->id,
            'status' => 'scheduled',
            'scheduled_date' => now()->addDay(),
            'inspection_fee_amount' => 299,
            'summary' => 'Owner-visible scheduling note.',
        ]);

        Sanctum::actingAs($client);

        $this->getJson("/api/properties/{$property->id}/inspection-details")
            ->assertOk()
            ->assertJsonPath('inspection.fee_amount', '299.00')
            ->assertJsonPath('inspection.notes', 'Owner-visible scheduling note.');
    }

    public function test_another_client_cannot_view_property_inspection_details(): void
    {
        Role::firstOrCreate(['name' => 'Client', 'guard_name' => 'web']);

        $owner = User::factory()->create();
        $owner->assignRole('Client');

        $otherClient = User::factory()->create();
        $otherClient->assignRole('Client');

        $property = $this->createPropertyFor($owner);

        Inspection::create([
            'property_id' => $property->id,
            'status' => 'scheduled',
            'inspection_fee_amount' => 299,
            'summary' => 'Private scheduling note.',
        ]);

        Sanctum::actingAs($otherClient);

        $this->getJson("/api/properties/{$property->id}/inspection-details")
            ->assertForbidden();
    }

    public function test_guest_cannot_view_property_inspection_details(): void
    {
        $owner = User::factory()->create();
        $property = $this->createPropertyFor($owner);

        $this->getJson("/api/properties/{$property->id}/inspection-details")
            ->assertUnauthorized();
    }

    private function createPropertyFor(User $user): Property
    {
        return Property::create([
            'property_code' => 'TES-' . $user->id,
            'user_id' => $user->id,
            'owner_first_name' => 'Demo',
            'owner_phone' => '0780000000',
            'owner_email' => $user->email,
            'property_name' => 'API Test Home',
            'property_address' => 'Acacia Avenue',
            'city' => 'Kampala',
            'province' => 'Central',
            'country' => 'Uganda',
            'type' => 'residential',
            'residential_units' => 1,
            'number_of_units' => 1,
            'status' => 'registered',
        ]);
    }
}
