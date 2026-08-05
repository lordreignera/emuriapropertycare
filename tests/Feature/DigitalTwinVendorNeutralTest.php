<?php

namespace Tests\Feature;

use App\Jobs\ProcessMatterPakToGlb;
use App\Models\Inspection;
use App\Models\PHARFinding;
use App\Models\Property;
use App\Models\SpatialModel;
use App\Models\TwinProcessingJob;
use App\Models\TwinSourceFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use ZipArchive;

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

    public function test_property_twin_does_not_silently_create_duplicate_inspection_when_none_exists(): void
    {
        $staff = $this->createUserWithRole('Project Manager');
        $client = $this->createUserWithRole('Client');

        $property = Property::create([
            'property_code' => 'TWN-NONE-001',
            'user_id' => $client->id,
            'project_manager_id' => $staff->id,
            'owner_first_name' => 'Demo',
            'owner_phone' => '0780000000',
            'owner_email' => $client->email,
            'property_name' => 'No Diagnosis Home',
            'property_address' => 'Makerere Hill Road',
            'city' => 'Kampala',
            'province' => 'Central',
            'postal_code' => '256',
            'country' => 'Uganda',
            'type' => 'residential',
            'residential_units' => 1,
            'number_of_units' => 1,
            'status' => 'registered',
        ]);

        $this->assertDatabaseCount('inspections', 0);

        $response = $this->actingAs($staff, 'sanctum')
            ->get(route('properties.digital-twin', $property));

        $response->assertOk();
        $response->assertSee('does not have a diagnosis inspection yet');
        $response->assertSee('Start diagnosis');
        $this->assertDatabaseCount('inspections', 0);
    }

    public function test_property_twin_reuses_single_existing_diagnosis_inspection(): void
    {
        $staff = $this->createUserWithRole('Project Manager');
        [, $inspection] = $this->createInspectionForClient();
        $inspection->property->update(['project_manager_id' => $staff->id]);

        $this->assertDatabaseCount('inspections', 1);

        $response = $this->actingAs($staff, 'sanctum')
            ->get(route('properties.digital-twin', $inspection->property));

        $response->assertOk();
        $response->assertSee('Property Digital Twin');
        $response->assertSee('Diagnosis #' . $inspection->id);
        $this->assertDatabaseCount('inspections', 1);
    }

    public function test_property_twin_requires_selection_when_multiple_diagnosis_inspections_exist(): void
    {
        $staff = $this->createUserWithRole('Project Manager');
        [, $inspection] = $this->createInspectionForClient();
        $inspection->property->update(['project_manager_id' => $staff->id]);

        $secondInspection = Inspection::create([
            'property_id' => $inspection->property_id,
            'scheduled_date' => now()->addDays(2),
            'status' => 'in_progress',
            'inspection_fee_status' => 'paid',
            'property_code' => $inspection->property_code,
            'property_name' => $inspection->property_name,
            'property_address_snapshot' => $inspection->property_address_snapshot,
            'property_type_snapshot' => $inspection->property_type_snapshot,
        ]);

        $response = $this->actingAs($staff, 'sanctum')
            ->get(route('properties.digital-twin', $inspection->property));

        $response->assertOk();
        $response->assertSee('more than one diagnosis inspection');
        $response->assertSee('Diagnosis #' . $inspection->id);
        $response->assertSee('Diagnosis #' . $secondInspection->id);
        $this->assertDatabaseCount('inspections', 2);

        $selected = $this->actingAs($staff, 'sanctum')
            ->get(route('properties.digital-twin', [$inspection->property, 'inspection_id' => $inspection->id]));

        $selected->assertOk();
        $selected->assertSee('Property Digital Twin');
        $selected->assertSee('Diagnosis #' . $inspection->id);
        $this->assertDatabaseCount('inspections', 2);
    }

    public function test_glb_upload_becomes_ready_and_uses_source_metadata(): void
    {
        $this->useTwinTestDisk();

        $staff = $this->createUserWithRole('Project Manager');
        [, $inspection] = $this->createInspectionForClient();
        $inspection->property->update(['project_manager_id' => $staff->id]);

        $response = $this->actingAs($staff, 'sanctum')
            ->post(route('inspections.digital-twin.models.store', $inspection), [
                'provider' => 'manual_upload',
                'capture_type' => 'photo_set',
                'source_type' => 'document_reference',
                'display_name' => 'Main GLB model',
                'source_file' => UploadedFile::fake()->create('main-model.glb', 64, 'model/gltf-binary'),
                'status' => 'active',
                'is_primary' => '1',
            ]);

        $response->assertRedirect(route('inspections.digital-twin', $inspection));

        $this->assertDatabaseHas('spatial_models', [
            'inspection_id' => $inspection->id,
            'source_type' => 'runtime_3d_model',
            'runtime_format' => 'glb',
            'processing_status' => 'ready',
        ]);

        $this->assertDatabaseHas('twin_source_files', [
            'inspection_id' => $inspection->id,
            'source_type' => 'glb',
            'processing_status' => 'ready',
            'original_filename' => 'main-model.glb',
        ]);
    }

    public function test_obj_upload_is_preserved_and_awaits_processing(): void
    {
        $this->useTwinTestDisk();

        $staff = $this->createUserWithRole('Project Manager');
        [, $inspection] = $this->createInspectionForClient();
        $inspection->property->update(['project_manager_id' => $staff->id]);

        $response = $this->actingAs($staff, 'sanctum')
            ->post(route('inspections.digital-twin.models.store', $inspection), [
                'provider' => 'manual_upload',
                'capture_type' => 'obj_mesh',
                'source_type' => 'runtime_3d_model',
                'display_name' => 'OBJ source package',
                'source_file' => UploadedFile::fake()->create('scan.obj', 64, 'text/plain'),
                'status' => 'active',
            ]);

        $response->assertRedirect(route('inspections.digital-twin', $inspection));

        $this->assertDatabaseHas('capture_sessions', [
            'inspection_id' => $inspection->id,
            'capture_type' => 'obj_mesh',
            'status' => 'awaiting_processing',
        ]);

        $this->assertDatabaseHas('twin_source_files', [
            'inspection_id' => $inspection->id,
            'source_type' => 'obj_bundle',
            'processing_status' => 'awaiting_processing',
            'original_filename' => 'scan.obj',
        ]);

        $this->assertDatabaseCount('spatial_models', 0);
    }

    public function test_e57_upload_is_preserved_and_awaits_point_cloud_processing(): void
    {
        $this->useTwinTestDisk();

        $staff = $this->createUserWithRole('Project Manager');
        [, $inspection] = $this->createInspectionForClient();
        $inspection->property->update(['project_manager_id' => $staff->id]);

        $response = $this->actingAs($staff, 'sanctum')
            ->post(route('inspections.digital-twin.models.store', $inspection), [
                'provider' => 'lidar',
                'capture_type' => 'point_cloud',
                'source_type' => 'master_point_cloud',
                'display_name' => 'Basement E57 cloud',
                'source_file' => UploadedFile::fake()->create('basement.e57', 64, 'application/octet-stream'),
                'status' => 'active',
            ]);

        $response->assertRedirect(route('inspections.digital-twin', $inspection));

        $this->assertDatabaseHas('twin_source_files', [
            'inspection_id' => $inspection->id,
            'source_type' => 'e57',
            'processing_status' => 'awaiting_processing',
            'original_filename' => 'basement.e57',
        ]);

        $this->assertDatabaseCount('spatial_models', 0);
    }

    public function test_matterpak_zip_upload_is_preserved_and_queued_for_blender_conversion(): void
    {
        $this->useTwinTestDisk();
        Queue::fake();

        $staff = $this->createUserWithRole('Project Manager');
        [, $inspection] = $this->createInspectionForClient();
        $inspection->property->update(['project_manager_id' => $staff->id]);

        $response = $this->actingAs($staff, 'sanctum')
            ->post(route('inspections.digital-twin.models.store', $inspection), [
                'provider' => 'matterport',
                'capture_type' => 'obj_mesh',
                'source_type' => 'runtime_3d_model',
                'display_name' => 'MatterPak package',
                'source_file' => $this->fakeMatterPakUpload(),
                'status' => 'active',
                'is_primary' => '1',
            ]);

        $response->assertRedirect(route('inspections.digital-twin', $inspection));

        $processingJob = TwinProcessingJob::where('inspection_id', $inspection->id)->firstOrFail();

        Queue::assertPushed(ProcessMatterPakToGlb::class, function (ProcessMatterPakToGlb $job) use ($processingJob) {
            return $job->processingJobId === $processingJob->id;
        });

        $this->assertDatabaseHas('capture_sessions', [
            'inspection_id' => $inspection->id,
            'provider' => 'matterport',
            'capture_type' => 'obj_mesh',
            'status' => 'awaiting_processing',
        ]);

        $this->assertDatabaseHas('twin_source_files', [
            'inspection_id' => $inspection->id,
            'source_type' => 'obj_bundle',
            'file_role' => 'matterpak_archive',
            'processing_status' => 'awaiting_processing',
            'original_filename' => 'MatterPak Demo.zip',
        ]);

        $this->assertDatabaseHas('twin_processing_jobs', [
            'inspection_id' => $inspection->id,
            'job_type' => 'matterpak_obj_to_glb',
            'processor' => 'blender',
            'status' => 'queued',
        ]);

        $this->assertDatabaseCount('spatial_models', 0);

        $preview = $this->actingAs($staff, 'sanctum')
            ->get(route('inspections.findings-preview', $inspection));

        $preview->assertOk();
        $preview->assertSee('Digital Twin Evidence');
        $preview->assertSee('Uploaded twin sources');
        $preview->assertSee('MatterPak Demo.zip');
        $preview->assertSee('Twin processing');
        $preview->assertSee('Queued');
    }

    public function test_matterpak_processing_extracts_files_and_fails_cleanly_without_blender(): void
    {
        $this->useTwinTestDisk();
        Queue::fake();
        config(['digital_twin.blender.binary' => '']);

        $staff = $this->createUserWithRole('Project Manager');
        [, $inspection] = $this->createInspectionForClient();
        $inspection->property->update(['project_manager_id' => $staff->id]);

        $this->actingAs($staff, 'sanctum')
            ->post(route('inspections.digital-twin.models.store', $inspection), [
                'provider' => 'matterport',
                'capture_type' => 'obj_mesh',
                'source_type' => 'runtime_3d_model',
                'display_name' => 'MatterPak package',
                'source_file' => $this->fakeMatterPakUpload(),
                'status' => 'active',
                'is_primary' => '1',
            ])
            ->assertRedirect(route('inspections.digital-twin', $inspection));

        $processingJob = TwinProcessingJob::where('inspection_id', $inspection->id)->firstOrFail();
        $sourceFile = TwinSourceFile::where('inspection_id', $inspection->id)
            ->where('file_role', 'matterpak_archive')
            ->firstOrFail();

        try {
            (new ProcessMatterPakToGlb($processingJob->id))->handle();
            $this->fail('MatterPak processing should fail when Blender is not configured.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('Blender binary is not configured', $exception->getMessage());
        }

        $this->assertDatabaseHas('twin_source_files', [
            'parent_source_file_id' => $sourceFile->id,
            'relative_path' => 'mesh/model.obj',
            'file_role' => 'obj_mesh',
            'source_type' => 'obj_bundle',
            'processing_status' => 'awaiting_processing',
        ]);

        $this->assertDatabaseHas('twin_source_files', [
            'parent_source_file_id' => $sourceFile->id,
            'relative_path' => 'mesh/model.mtl',
            'file_role' => 'material_library',
        ]);

        $this->assertDatabaseHas('twin_source_files', [
            'parent_source_file_id' => $sourceFile->id,
            'relative_path' => 'mesh/textures/wall.jpg',
            'file_role' => 'texture',
            'source_type' => 'image',
        ]);

        $this->assertDatabaseHas('twin_source_files', [
            'parent_source_file_id' => $sourceFile->id,
            'relative_path' => 'pointcloud/house.xyz',
            'file_role' => 'colour_point_cloud',
            'source_type' => 'other',
            'processing_status' => 'uploaded',
        ]);

        $this->assertDatabaseHas('twin_processing_jobs', [
            'id' => $processingJob->id,
            'status' => 'failed',
        ]);

        $this->assertDatabaseHas('twin_source_files', [
            'id' => $sourceFile->id,
            'processing_status' => 'failed',
        ]);

        $this->assertDatabaseCount('spatial_models', 0);
    }

    public function test_unsupported_twin_source_extension_is_rejected(): void
    {
        $this->useTwinTestDisk();

        $staff = $this->createUserWithRole('Project Manager');
        [, $inspection] = $this->createInspectionForClient();
        $inspection->property->update(['project_manager_id' => $staff->id]);

        $response = $this->actingAs($staff, 'sanctum')
            ->from(route('inspections.digital-twin', $inspection))
            ->post(route('inspections.digital-twin.models.store', $inspection), [
                'provider' => 'manual_upload',
                'capture_type' => 'glb_model',
                'source_type' => 'runtime_3d_model',
                'display_name' => 'Unsupported source',
                'source_file' => UploadedFile::fake()->create('malware.exe', 1, 'application/octet-stream'),
                'status' => 'active',
            ]);

        $response->assertRedirect(route('inspections.digital-twin', $inspection));
        $response->assertSessionHasErrors('source_file');
        $this->assertDatabaseCount('twin_source_files', 0);
        $this->assertDatabaseCount('spatial_models', 0);
    }

    public function test_unauthorized_user_cannot_access_private_spatial_model_file(): void
    {
        $this->useTwinTestDisk();

        [$client, $inspection] = $this->createInspectionForClient();
        $otherClient = $this->createUserWithRole('Client');

        Storage::disk('twin_test')->put('properties/demo/twins/model.glb', 'fake-glb-data');

        $spatialModel = SpatialModel::create([
            'property_id' => $inspection->property_id,
            'inspection_id' => $inspection->id,
            'provider' => 'manual_upload',
            'source_type' => 'runtime_3d_model',
            'display_name' => 'Private GLB',
            'runtime_format' => 'glb',
            'original_format' => 'glb',
            'file_path' => 'properties/demo/twins/model.glb',
            'status' => 'active',
            'processing_status' => 'ready',
            'metadata' => ['storage_disk' => 'twin_test'],
        ]);

        $response = $this->actingAs($otherClient, 'sanctum')
            ->get(route('inspections.digital-twin.models.file', [$inspection, $spatialModel]));

        $response->assertForbidden();
        $this->assertNotSame($client->id, $otherClient->id);
    }

    public function test_issue_marker_saves_spatial_coordinates_camera_data_and_phar_link(): void
    {
        $staff = $this->createUserWithRole('Project Manager');
        [, $inspection] = $this->createInspectionForClient();
        $inspection->property->update(['project_manager_id' => $staff->id]);

        $captureSession = \App\Models\CaptureSession::create([
            'property_id' => $inspection->property_id,
            'inspection_id' => $inspection->id,
            'captured_by' => $staff->id,
            'provider' => 'manual_upload',
            'capture_type' => 'glb_model',
            'status' => 'ready',
        ]);

        $spatialModel = SpatialModel::create([
            'property_id' => $inspection->property_id,
            'inspection_id' => $inspection->id,
            'capture_session_id' => $captureSession->id,
            'created_by' => $staff->id,
            'provider' => 'manual_upload',
            'source_type' => 'runtime_3d_model',
            'display_name' => 'Marker model',
            'runtime_format' => 'glb',
            'original_format' => 'glb',
            'status' => 'active',
            'processing_status' => 'ready',
        ]);

        $finding = PHARFinding::create([
            'inspection_id' => $inspection->id,
            'property_id' => $inspection->property_id,
            'task_question' => 'Roof leak marker',
            'category' => 'Moisture',
            'severity' => 'high',
        ]);

        $response = $this->actingAs($staff, 'sanctum')
            ->post(route('inspections.digital-twin.markers.store', $inspection), [
                'spatial_model_id' => $spatialModel->id,
                'capture_session_id' => $captureSession->id,
                'phar_finding_id' => $finding->id,
                'source_provider' => 'manual_upload',
                'marker_type' => 'issue',
                'title' => 'Roof leak at north wall',
                'severity' => 'high',
                'status' => 'open',
                'position_x' => '1.2500',
                'position_y' => '2.5000',
                'position_z' => '3.7500',
                'normal_x' => '0.000000',
                'normal_y' => '1.000000',
                'normal_z' => '0.000000',
                'camera_position' => json_encode(['x' => 9.1, 'y' => 8.2, 'z' => 7.3]),
                'camera_target' => json_encode(['x' => 1.0, 'y' => 2.0, 'z' => 3.0]),
                'object_uuid' => 'mesh-uuid-123',
            ]);

        $response->assertRedirect(route('inspections.digital-twin', $inspection));

        $marker = \App\Models\IssueMarker::where('inspection_id', $inspection->id)->firstOrFail();

        $this->assertSame($finding->id, $marker->phar_finding_id);
        $this->assertSame('mesh-uuid-123', $marker->object_uuid);
        $this->assertSame(['x' => 9.1, 'y' => 8.2, 'z' => 7.3], $marker->camera_position);
        $this->assertEquals(['x' => 1.0, 'y' => 2.0, 'z' => 3.0], $marker->camera_target);
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

        $this->assertDatabaseHas('capture_sessions', [
            'inspection_id' => $inspection->id,
            'capture_type' => 'point_cloud',
            'status' => 'awaiting_processing',
        ]);

        $this->assertDatabaseHas('twin_source_files', [
            'inspection_id' => $inspection->id,
            'source_type' => 'e57',
            'processing_status' => 'awaiting_processing',
            'original_filename' => 'basement-point-cloud.e57',
        ]);

        $this->assertDatabaseCount('spatial_models', 0);
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

    private function useTwinTestDisk(): void
    {
        config([
            'digital_twin.disk' => 'twin_test',
            'filesystems.disks.twin_test' => [
                'driver' => 'local',
                'root' => storage_path('app/twin-test'),
                'throw' => true,
            ],
        ]);
    }

    private function fakeMatterPakUpload(string $name = 'MatterPak Demo.zip'): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'matterpak_');

        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('mesh/model.obj', "mtllib model.mtl\nv 0 0 0\nv 1 0 0\nv 0 1 0\nf 1 2 3\n");
        $zip->addFromString('mesh/model.mtl', "newmtl wall\nmap_Kd textures/wall.jpg\n");
        $zip->addFromString('mesh/textures/wall.jpg', 'fake-jpg-texture');
        $zip->addFromString('floorplans/floor-plan.jpg', 'fake-floor-plan-image');
        $zip->addFromString('floorplans/reflected-ceiling-plan.pdf', '%PDF-1.4 fake');
        $zip->addFromString('pointcloud/house.xyz', "0 0 0 255 255 255\n");
        $zip->close();

        return new UploadedFile($path, $name, 'application/zip', null, true);
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
