<?php

namespace Tests\Feature;

use App\Models\Inspection;
use App\Models\MatterportModel;
use App\Models\Property;
use App\Models\SpatialModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MatterportInspectionViewerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckActiveSubscription::class,
        ]);
        $this->withoutVite();

        config(['services.matterport.sdk_key' => 'test-sdk-key']);
    }

    public function test_authorized_staff_can_attach_matterport_as_a_digital_twin_source(): void
    {
        $staff = $this->createUserWithRole('Project Manager');
        [, $inspection] = $this->createInspectionForClient();
        $inspection->property->update(['project_manager_id' => $staff->id]);

        $response = $this->actingAs($staff, 'sanctum')
            ->post(route('inspections.matterport-model.store', $inspection), [
                'model_sid' => 'https://my.matterport.com/show/?m=a1B2c3D4e5F',
                'model_name' => 'Main property scan',
                'status' => 'active',
                'scanned_at' => '2026-07-18',
            ]);

        $response->assertRedirect(route('inspections.digital-twin', $inspection));

        $this->assertDatabaseHas('matterport_models', [
            'inspection_id' => $inspection->id,
            'model_sid' => 'a1B2c3D4e5F',
            'model_name' => 'Main property scan',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('capture_sessions', [
            'inspection_id' => $inspection->id,
            'provider' => 'matterport',
            'capture_type' => 'hosted_tour',
            'status' => 'ready',
        ]);

        $this->assertDatabaseHas('spatial_models', [
            'inspection_id' => $inspection->id,
            'provider' => 'matterport',
            'source_type' => 'hosted_tour',
            'provider_model_id' => 'a1B2c3D4e5F',
            'is_primary' => true,
        ]);

        $viewer = $this->actingAs($staff, 'sanctum')
            ->get(route('inspections.digital-twin', $inspection));

        $viewer->assertOk();
        $viewer->assertSee('Property Digital Twin');
        $viewer->assertSee('Digital Twin Viewer');
        $viewer->assertSee('a1B2c3D4e5F');
        $viewer->assertSee('Add Capture Source');
        $viewer->assertSee('my.matterport.com/show/', false);
    }

    public function test_client_can_view_their_own_digital_twin_without_attach_controls(): void
    {
        [$client, $inspection] = $this->createInspectionForClient();

        $spatialModel = SpatialModel::create([
            'property_id' => $inspection->property_id,
            'inspection_id' => $inspection->id,
            'provider' => 'matterport',
            'source_type' => 'hosted_tour',
            'display_name' => 'Client-visible scan',
            'runtime_format' => 'hosted',
            'original_format' => 'matterport_sid',
            'provider_model_id' => 'z9Y8x7W6v5U',
            'status' => 'active',
            'processing_status' => 'ready',
            'is_primary' => true,
        ]);

        MatterportModel::create([
            'property_id' => $inspection->property_id,
            'inspection_id' => $inspection->id,
            'spatial_model_id' => $spatialModel->id,
            'model_sid' => 'z9Y8x7W6v5U',
            'model_name' => 'Client-visible scan',
            'status' => 'active',
        ]);

        $response = $this->actingAs($client, 'sanctum')
            ->get(route('inspections.digital-twin', $inspection));

        $response->assertOk();
        $response->assertSee('Property Digital Twin');
        $response->assertSee('z9Y8x7W6v5U');
        $response->assertSee('Digital Twin Viewer');
        $response->assertDontSee('Add Capture Source');
        $response->assertDontSee('Add Issue Marker');
    }

    public function test_client_cannot_view_another_clients_digital_twin_inspection(): void
    {
        [, $inspection] = $this->createInspectionForClient();
        $otherClient = $this->createUserWithRole('Client');

        MatterportModel::create([
            'property_id' => $inspection->property_id,
            'inspection_id' => $inspection->id,
            'model_sid' => 'u1V2w3X4y5Z',
            'status' => 'active',
        ]);

        $response = $this->actingAs($otherClient, 'sanctum')
            ->get(route('inspections.digital-twin', $inspection));

        $response->assertForbidden();
    }

    public function test_legacy_matterport_route_redirects_to_digital_twin(): void
    {
        $staff = $this->createUserWithRole('Project Manager');
        [, $inspection] = $this->createInspectionForClient();
        $inspection->property->update(['project_manager_id' => $staff->id]);

        $response = $this->actingAs($staff, 'sanctum')
            ->get(route('inspections.matterport', $inspection));

        $response->assertRedirect(route('inspections.digital-twin', $inspection));
    }

    private function createUserWithRole(string $roleName): User
    {
        $role = Role::firstOrCreate([
            'name' => $roleName,
            'guard_name' => 'web',
        ]);

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    /**
     * @return array{0: \App\Models\User, 1: \App\Models\Inspection}
     */
    private function createInspectionForClient(): array
    {
        $client = $this->createUserWithRole('Client');

        $property = Property::create([
            'property_code' => 'MPT-1001',
            'user_id' => $client->id,
            'owner_first_name' => 'Demo',
            'owner_phone' => '0780000000',
            'owner_email' => $client->email,
            'property_name' => 'Matterport Demo Home',
            'property_address' => 'Makerere Hill Road',
            'city' => 'Kampala',
            'province' => 'Central',
            'postal_code' => '256',
            'country' => 'Uganda',
            'type' => 'residential',
            'residential_units' => 1,
            'number_of_units' => 1,
            'status' => 'awaiting_inspection',
        ]);

        $inspection = Inspection::create([
            'property_id' => $property->id,
            'scheduled_date' => now()->addDay(),
            'status' => 'scheduled',
            'inspection_fee_status' => 'paid',
            'property_code' => $property->property_code,
            'property_name' => $property->property_name,
            'property_address_snapshot' => $property->property_address,
            'property_type_snapshot' => $property->type,
        ]);

        return [$client, $inspection];
    }
}
