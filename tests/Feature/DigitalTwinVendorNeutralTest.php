<?php

namespace Tests\Feature;

use App\Models\Inspection;
use App\Models\PHARFinding;
use App\Models\Property;
use App\Models\SpatialModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DigitalTwinVendorNeutralTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckActiveSubscription::class,
        ]);
        $this->withoutVite();
    }

    public function test_staff_can_add_non_matterport_capture_sources_and_issue_markers(): void
    {
        $staff = $this->createUserWithRole('Project Manager');
        [$client, $inspection] = $this->createInspectionForClient();
        $inspection->property->update(['project_manager_id' => $staff->id]);

        $response = $this->actingAs($staff, 'sanctum')
            ->post(route('inspections.digital-twin.models.store', $inspection), [
                'provider' => 'resolv',
                'capture_type' => 'wall_scan',
                'source_type' => 'wall_scan_evidence',
                'display_name' => 'Kitchen north wall scan',
                'device_name' => 'RESOLV wall scanner',
                'original_format' => 'PDF',
                'runtime_format' => 'evidence',
                'provider_model_id' => 'RSV-00045',
                'status' => 'active',
                'processing_status' => 'ready',
                'is_primary' => '1',
                'accuracy_class' => 'wall-local depth estimate',
            ]);

        $response->assertRedirect(route('inspections.digital-twin', $inspection));

        $this->assertDatabaseHas('capture_sessions', [
            'inspection_id' => $inspection->id,
            'provider' => 'resolv',
            'capture_type' => 'wall_scan',
            'device_name' => 'RESOLV wall scanner',
        ]);

        $this->assertDatabaseHas('spatial_models', [
            'inspection_id' => $inspection->id,
            'provider' => 'resolv',
            'source_type' => 'wall_scan_evidence',
            'display_name' => 'Kitchen north wall scan',
            'provider_model_id' => 'RSV-00045',
        ]);

        $spatialModelId = \App\Models\SpatialModel::where('inspection_id', $inspection->id)->value('id');
        $captureSessionId = \App\Models\CaptureSession::where('inspection_id', $inspection->id)->value('id');
        $finding = PHARFinding::create([
            'inspection_id' => $inspection->id,
            'property_id' => $inspection->property_id,
            'task_question' => 'Trace kitchen wall moisture',
            'category' => 'Moisture',
            'severity' => 'high',
        ]);

        $markerResponse = $this->actingAs($staff, 'sanctum')
            ->post(route('inspections.digital-twin.markers.store', $inspection), [
                'spatial_model_id' => $spatialModelId,
                'capture_session_id' => $captureSessionId,
                'phar_finding_id' => $finding->id,
                'source_provider' => 'resolv',
                'marker_type' => 'hidden_condition',
                'title' => 'Possible concealed pipe leak',
                'severity' => 'high',
                'status' => 'open',
                'position_x' => '4.3000',
                'position_y' => '6.1000',
                'position_z' => '1.2000',
                'normal_x' => '0.000000',
                'normal_y' => '1.000000',
                'normal_z' => '0.000000',
                'room_name' => 'Kitchen',
                'surface_label' => 'North wall',
                'source_reference' => 'resolv-kitchen-wall.pdf',
                'confidence' => '82.50',
            ]);

        $markerResponse->assertRedirect(route('inspections.digital-twin', $inspection));

        $this->assertDatabaseHas('issue_markers', [
            'inspection_id' => $inspection->id,
            'spatial_model_id' => $spatialModelId,
            'capture_session_id' => $captureSessionId,
            'phar_finding_id' => $finding->id,
            'source_provider' => 'resolv',
            'title' => 'Possible concealed pipe leak',
            'severity' => 'high',
            'normal_x' => '0.000000',
            'normal_y' => '1.000000',
            'normal_z' => '0.000000',
        ]);

        $viewer = $this->actingAs($staff, 'sanctum')
            ->get(route('inspections.digital-twin', $inspection));

        $viewer->assertOk();
        $viewer->assertSee('Property Digital Twin');
        $viewer->assertSee('Vendor neutral');
        $viewer->assertSee('Kitchen north wall scan');
        $viewer->assertSee('Possible concealed pipe leak');
        $viewer->assertSee('data-issue-marker-form', false);
        $viewer->assertSee('data-marker-field="position_x"', false);
        $viewer->assertSee('name="normal_x"', false);

        $preview = $this->actingAs($staff, 'sanctum')
            ->get(route('inspections.findings-preview', $inspection));

        $preview->assertOk();
        $preview->assertSee('Digital Twin Evidence');
        $preview->assertSee('Kitchen north wall scan');
        $preview->assertSee('Possible concealed pipe leak');
        $preview->assertSee('Linked finding #' . $finding->id);

        $inspection->update([
            'status' => 'findings_shared',
            'findings_report_shared_at' => now(),
        ]);

        $clientReport = $this->actingAs($client, 'sanctum')
            ->get(route('client.inspections.findings-report', $inspection));

        $clientReport->assertOk();
        $clientReport->assertSee('Digital Twin Evidence');
        $clientReport->assertSee('Kitchen north wall scan');
        $clientReport->assertSee('Possible concealed pipe leak');
    }

    public function test_issue_marker_cannot_link_to_phar_finding_from_another_inspection(): void
    {
        $staff = $this->createUserWithRole('Project Manager');
        [, $inspection] = $this->createInspectionForClient();
        [, $otherInspection] = $this->createInspectionForClient();
        $inspection->property->update(['project_manager_id' => $staff->id]);

        $otherFinding = PHARFinding::create([
            'inspection_id' => $otherInspection->id,
            'property_id' => $otherInspection->property_id,
            'task_question' => 'Other inspection finding',
            'category' => 'Moisture',
            'severity' => 'high',
        ]);

        $response = $this->actingAs($staff, 'sanctum')
            ->from(route('inspections.digital-twin', $inspection))
            ->post(route('inspections.digital-twin.markers.store', $inspection), [
                'phar_finding_id' => $otherFinding->id,
                'source_provider' => 'manual',
                'marker_type' => 'issue',
                'title' => 'Wrong inspection link',
                'severity' => 'high',
                'status' => 'open',
            ]);

        $response->assertRedirect(route('inspections.digital-twin', $inspection));
        $response->assertSessionHasErrors('phar_finding_id');

        $this->assertDatabaseMissing('issue_markers', [
            'inspection_id' => $inspection->id,
            'phar_finding_id' => $otherFinding->id,
        ]);
    }

    public function test_digital_twin_viewer_classifies_uploaded_camera_outputs(): void
    {
        $staff = $this->createUserWithRole('Project Manager');
        [, $inspection] = $this->createInspectionForClient();
        $inspection->property->update(['project_manager_id' => $staff->id]);

        $captureSession = \App\Models\CaptureSession::create([
            'property_id' => $inspection->property_id,
            'inspection_id' => $inspection->id,
            'captured_by' => $staff->id,
            'provider' => 'manual_upload',
            'capture_type' => 'photo_set',
            'status' => 'ready',
        ]);

        $sources = [
            ['display_name' => 'Phone GLB room model', 'source_type' => 'runtime_3d_model', 'runtime_format' => 'glb', 'file_path' => 'digital-twins/test/room.glb'],
            ['display_name' => 'Thermal image evidence', 'source_type' => 'thermal_evidence', 'runtime_format' => null, 'file_path' => 'digital-twins/test/thermal.jpg'],
            ['display_name' => 'RESOLV PDF report', 'source_type' => 'wall_scan_evidence', 'runtime_format' => null, 'file_path' => 'digital-twins/test/resolv.pdf'],
            ['display_name' => '360 panorama node', 'source_type' => 'panorama_set', 'runtime_format' => null, 'file_path' => 'digital-twins/test/pano.jpg'],
            ['display_name' => 'OBJ mesh package', 'source_type' => 'runtime_3d_model', 'runtime_format' => 'obj', 'file_path' => 'digital-twins/test/model.obj'],
        ];

        foreach ($sources as $index => $source) {
            SpatialModel::create(array_merge([
                'property_id' => $inspection->property_id,
                'inspection_id' => $inspection->id,
                'capture_session_id' => $captureSession->id,
                'created_by' => $staff->id,
                'provider' => 'manual_upload',
                'original_format' => pathinfo($source['file_path'], PATHINFO_EXTENSION),
                'status' => 'active',
                'processing_status' => 'ready',
                'is_primary' => $index === 0,
            ], $source));
        }

        $response = $this->actingAs($staff, 'sanctum')
            ->get(route('inspections.digital-twin', $inspection));

        $response->assertOk();
        $response->assertSee('Phone GLB room model');
        $response->assertSee('Thermal image evidence');
        $response->assertSee('RESOLV PDF report');
        $response->assertSee('360 panorama node');
        $response->assertSee('OBJ mesh package');
        $response->assertSee('"viewerType":"three_model"', false);
        $response->assertSee('"viewerType":"image"', false);
        $response->assertSee('"viewerType":"pdf"', false);
        $response->assertSee('"viewerType":"panorama"', false);
        $response->assertSee('"viewerType":"stored_evidence"', false);
    }

    public function test_capture_source_requires_a_file_url_or_provider_identifier(): void
    {
        $staff = $this->createUserWithRole('Project Manager');
        [, $inspection] = $this->createInspectionForClient();
        $inspection->property->update(['project_manager_id' => $staff->id]);

        $response = $this->actingAs($staff, 'sanctum')
            ->from(route('inspections.digital-twin', $inspection))
            ->post(route('inspections.digital-twin.models.store', $inspection), [
                'provider' => 'manual_upload',
                'capture_type' => 'photo_set',
                'source_type' => 'document_reference',
                'status' => 'active',
                'processing_status' => 'ready',
            ]);

        $response->assertRedirect(route('inspections.digital-twin', $inspection));
        $response->assertSessionHasErrors('source_file');
        $this->assertDatabaseCount('spatial_models', 0);
    }

    public function test_raw_point_cloud_source_is_stored_as_cloud_managed_reference(): void
    {
        $staff = $this->createUserWithRole('Project Manager');
        [, $inspection] = $this->createInspectionForClient();
        $inspection->property->update(['project_manager_id' => $staff->id]);

        $response = $this->actingAs($staff, 'sanctum')
            ->post(route('inspections.digital-twin.models.store', $inspection), [
                'provider' => 'lidar',
                'capture_type' => 'point_cloud',
                'source_type' => 'master_point_cloud',
                'display_name' => 'Basement LiDAR point cloud',
                'external_url' => 'https://cdn.example.test/twins/basement-point-cloud.e57',
                'status' => 'active',
                'processing_status' => 'ready',
                'is_primary' => '1',
            ]);

        $response->assertRedirect(route('inspections.digital-twin', $inspection));

        $this->assertDatabaseHas('spatial_models', [
            'inspection_id' => $inspection->id,
            'source_type' => 'master_point_cloud',
            'processing_status' => 'ready',
        ]);

        $model = SpatialModel::where('inspection_id', $inspection->id)->firstOrFail();

        $this->assertSame('external_link', $model->viewer_type);
        $this->assertSame('https://cdn.example.test/twins/basement-point-cloud.e57', $model->external_url);
        $this->assertNull($model->file_path);
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
        $propertyCode = 'DGT-' . str_pad((string) $client->id, 4, '0', STR_PAD_LEFT);

        $property = Property::create([
            'property_code' => $propertyCode,
            'user_id' => $client->id,
            'owner_first_name' => 'Demo',
            'owner_phone' => '0780000000',
            'owner_email' => $client->email,
            'property_name' => 'Vendor Neutral Demo Home',
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
            'property_code' => $propertyCode,
            'property_name' => $property->property_name,
            'property_address_snapshot' => $property->property_address,
            'property_type_snapshot' => $property->type,
        ]);

        return [$client, $inspection];
    }
}
