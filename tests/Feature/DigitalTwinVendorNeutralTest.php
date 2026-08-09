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
use Illuminate\Support\Facades\Crypt;
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

    public function test_completed_direct_matterpak_upload_is_recorded_and_queued_for_conversion(): void
    {
        $this->useTwinTestDisk();
        Queue::fake();

        $staff = $this->createUserWithRole('Project Manager');
        [, $inspection] = $this->createInspectionForClient();
        $inspection->property->update(['project_manager_id' => $staff->id]);

        $storagePath = "properties/{$inspection->property_id}/twins/inspections/{$inspection->id}/source/direct-matterpak.zip";
        Storage::disk('twin_test')->put($storagePath, 'direct-uploaded-matterpak');

        $token = Crypt::encryptString(json_encode([
            'inspection_id' => $inspection->id,
            'property_id' => $inspection->property_id,
            'user_id' => $staff->id,
            'storage_disk' => 'twin_test',
            'storage_path' => $storagePath,
            'stored_filename' => 'direct-matterpak.zip',
            'original_filename' => 'Direct MatterPak.zip',
            'extension' => 'zip',
            'mime_type' => 'application/zip',
            'file_size' => 24,
            'expires_at' => now()->addMinutes(30)->toIso8601String(),
        ], JSON_THROW_ON_ERROR));

        $response = $this->actingAs($staff, 'sanctum')
            ->post(route('inspections.digital-twin.models.store', $inspection), [
                'provider' => 'matterport',
                'capture_type' => 'obj_mesh',
                'source_type' => 'runtime_3d_model',
                'display_name' => 'Direct MatterPak',
                'direct_upload_token' => $token,
                'status' => 'active',
                'is_primary' => '1',
            ]);

        $response->assertRedirect(route('inspections.digital-twin', $inspection));

        $sourceFile = TwinSourceFile::where('inspection_id', $inspection->id)
            ->where('file_role', 'matterpak_archive')
            ->firstOrFail();

        $this->assertSame($storagePath, $sourceFile->storage_path);
        $this->assertSame('twin_test', $sourceFile->storage_disk);
        $this->assertSame('Direct MatterPak.zip', $sourceFile->original_filename);
        $this->assertSame('direct_to_private_bucket', $sourceFile->metadata['upload_strategy'] ?? null);

        $processingJob = TwinProcessingJob::where('source_file_id', $sourceFile->id)->firstOrFail();

        $this->assertSame('matterpak_obj_to_glb', $processingJob->job_type);
        $this->assertSame('queued', $processingJob->status);

        Queue::assertPushed(ProcessMatterPakToGlb::class, function (ProcessMatterPakToGlb $job) use ($processingJob) {
            return $job->processingJobId === $processingJob->id;
        });
    }

    public function test_staff_can_start_matterpak_reconversion_from_ready_archive(): void
    {
        $this->useTwinTestDisk();
        Queue::fake();

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

        $sourceFile = TwinSourceFile::where('inspection_id', $inspection->id)
            ->where('file_role', 'matterpak_archive')
            ->firstOrFail();
        $firstJob = TwinProcessingJob::where('source_file_id', $sourceFile->id)->firstOrFail();

        $spatialModel = SpatialModel::create([
            'property_id' => $inspection->property_id,
            'inspection_id' => $inspection->id,
            'capture_session_id' => $sourceFile->capture_session_id,
            'created_by' => $staff->id,
            'provider' => 'matterport',
            'source_type' => 'runtime_3d_model',
            'display_name' => 'MatterPak browser-ready GLB',
            'runtime_format' => 'glb',
            'original_format' => 'matterpak_zip',
            'file_path' => 'properties/test/twins/processed/model.glb',
            'status' => 'active',
            'processing_status' => 'ready',
            'processed_at' => now(),
        ]);

        $sourceFile->update([
            'spatial_model_id' => $spatialModel->id,
            'processing_status' => 'ready',
            'processing_error' => null,
        ]);
        $sourceFile->captureSession->update(['status' => 'ready']);
        $firstJob->update([
            'spatial_model_id' => $spatialModel->id,
            'status' => 'ready',
            'completed_at' => now(),
        ]);

        $viewer = $this->actingAs($staff, 'sanctum')
            ->get(route('inspections.digital-twin', $inspection));

        $viewer->assertOk();
        $viewer->assertSee('Reconvert GLB');
        $viewer->assertSee(route('inspections.digital-twin.source-files.convert', [$inspection, $sourceFile]), false);

        $response = $this->actingAs($staff, 'sanctum')
            ->post(route('inspections.digital-twin.source-files.convert', [$inspection, $sourceFile]));

        $response->assertRedirect(route('inspections.digital-twin', [$inspection, 'capture' => $sourceFile->capture_session_id]));
        $response->assertSessionHas('success');

        $newJob = TwinProcessingJob::where('source_file_id', $sourceFile->id)
            ->latest('id')
            ->firstOrFail();

        $this->assertNotSame($firstJob->id, $newJob->id);
        $this->assertSame('queued', $newJob->status);
        $this->assertSame('manual_digital_twin_convert_button', $newJob->metadata['triggered_from'] ?? null);

        $sourceFile->refresh();
        $this->assertSame('queued', $sourceFile->processing_status);
        $this->assertSame('queued', $sourceFile->captureSession->fresh()->status);

        Queue::assertPushed(ProcessMatterPakToGlb::class, 2);
        Queue::assertPushed(ProcessMatterPakToGlb::class, function (ProcessMatterPakToGlb $job) use ($newJob) {
            return $job->processingJobId === $newJob->id;
        });
    }

    public function test_matterpak_upload_with_hosted_matterport_url_adds_walkthrough_layer(): void
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
                'display_name' => 'Office MatterPak',
                'source_file' => $this->fakeMatterPakUpload(),
                'external_url' => 'https://my.matterport.com/show/?m=office123',
                'status' => 'active',
                'is_primary' => '1',
            ]);

        $response->assertRedirect(route('inspections.digital-twin', $inspection));

        $sourceFile = TwinSourceFile::where('inspection_id', $inspection->id)
            ->where('file_role', 'matterpak_archive')
            ->firstOrFail();

        $this->assertDatabaseHas('spatial_models', [
            'inspection_id' => $inspection->id,
            'capture_session_id' => $sourceFile->capture_session_id,
            'provider' => 'matterport',
            'source_type' => 'hosted_tour',
            'runtime_format' => 'hosted',
            'provider_model_id' => 'office123',
            'external_url' => 'https://my.matterport.com/show/?m=office123',
            'processing_status' => 'ready',
            'is_primary' => true,
        ]);

        $this->assertDatabaseHas('matterport_models', [
            'inspection_id' => $inspection->id,
            'model_sid' => 'office123',
            'model_name' => 'Office MatterPak walkthrough',
            'status' => 'active',
        ]);

        $viewer = $this->actingAs($staff, 'sanctum')
            ->get(route('inspections.digital-twin', $inspection));

        $viewer->assertOk();
        $viewer->assertSee('Office MatterPak walkthrough');
        $viewer->assertSee('"viewerType":"hosted_tour"', false);
        $viewer->assertSee('"viewerType":"awaiting_processing"', false);
        $viewer->assertSee('Conversion queued');

        Queue::assertPushed(ProcessMatterPakToGlb::class, 1);
    }

    public function test_matterpak_convert_button_does_not_duplicate_active_conversion_job(): void
    {
        $this->useTwinTestDisk();
        Queue::fake();

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

        $sourceFile = TwinSourceFile::where('inspection_id', $inspection->id)
            ->where('file_role', 'matterpak_archive')
            ->firstOrFail();

        $viewer = $this->actingAs($staff, 'sanctum')
            ->get(route('inspections.digital-twin', $inspection));

        $viewer->assertOk();
        $viewer->assertSee('Conversion queued');

        $response = $this->actingAs($staff, 'sanctum')
            ->post(route('inspections.digital-twin.source-files.convert', [$inspection, $sourceFile]));

        $response->assertRedirect(route('inspections.digital-twin', [$inspection, 'capture' => $sourceFile->capture_session_id]));
        $response->assertSessionHas('info');

        $this->assertSame(1, TwinProcessingJob::where('source_file_id', $sourceFile->id)->count());
        Queue::assertPushed(ProcessMatterPakToGlb::class, 1);
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
            'relative_path' => 'matterpak-demo/colorplan_000.jpg',
            'file_role' => 'floor_plan',
            'source_type' => 'image',
        ]);

        $this->assertDatabaseHas('twin_source_files', [
            'parent_source_file_id' => $sourceFile->id,
            'relative_path' => 'matterpak-demo/colorplan.pdf',
            'file_role' => 'floor_plan',
            'source_type' => 'pdf',
        ]);

        $this->assertDatabaseHas('twin_source_files', [
            'parent_source_file_id' => $sourceFile->id,
            'relative_path' => 'matterpak-demo/ceilingcolorplan_000.jpg',
            'file_role' => 'reflected_ceiling_plan',
            'source_type' => 'image',
        ]);

        $this->assertDatabaseHas('twin_source_files', [
            'parent_source_file_id' => $sourceFile->id,
            'relative_path' => 'matterpak-demo/matterpak_readme.pdf',
            'file_role' => 'supporting_source',
            'source_type' => 'pdf',
        ]);

        $this->assertDatabaseHas('twin_source_files', [
            'parent_source_file_id' => $sourceFile->id,
            'relative_path' => 'pointcloud/house.xyz',
            'file_role' => 'colour_point_cloud',
            'source_type' => 'other',
            'processing_status' => 'uploaded',
        ]);

        $this->assertDatabaseHas('twin_source_files', [
            'parent_source_file_id' => $sourceFile->id,
            'relative_path' => 'generated/point-cloud-preview.json',
            'file_role' => 'point_cloud_preview',
            'source_type' => 'other',
            'extension' => 'json',
            'processing_status' => 'ready',
        ]);

        $pointCloudPreview = TwinSourceFile::where('inspection_id', $inspection->id)
            ->where('file_role', 'point_cloud_preview')
            ->firstOrFail();

        Storage::disk('twin_test')->assertExists($pointCloudPreview->storage_path);
        $previewPayload = json_decode(Storage::disk('twin_test')->get($pointCloudPreview->storage_path), true);
        $this->assertSame('matterpak_xyz_preview', $previewPayload['format']);
        $this->assertSame(1, $previewPayload['point_count']);
        $this->assertSame(1, $previewPayload['source_point_count']);

        $processingJob->refresh();
        $jobDiagnostics = $processingJob->metadata['conversion_diagnostics'] ?? [];
        $this->assertSame('matterpak_visual_preserve', $jobDiagnostics['quality_profile'] ?? null);
        $this->assertSame(1, $jobDiagnostics['texture_sources']['texture_file_count'] ?? null);
        $this->assertSame(strlen('fake-jpg-texture'), $jobDiagnostics['texture_sources']['texture_total_bytes'] ?? null);
        $this->assertSame(1, $jobDiagnostics['material_textures']['texture_reference_count'] ?? null);
        $this->assertSame(1, $jobDiagnostics['material_textures']['resolved_texture_count'] ?? null);
        $this->assertSame(0, $jobDiagnostics['material_textures']['missing_texture_count'] ?? null);

        $this->assertDatabaseHas('twin_processing_jobs', [
            'id' => $processingJob->id,
            'status' => 'failed',
        ]);

        $this->assertDatabaseHas('twin_source_files', [
            'id' => $sourceFile->id,
            'processing_status' => 'failed',
        ]);

        $viewer = $this->actingAs($staff, 'sanctum')
            ->get(route('inspections.digital-twin', $inspection));

        $viewer->assertOk();
        $viewer->assertSee('MatterPak floor plan - colorplan_000.jpg');
        $viewer->assertSee('MatterPak floor plan - colorplan.pdf');
        $viewer->assertSee('MatterPak ceiling plan - ceilingcolorplan_000.jpg');
        $viewer->assertSee('MatterPak document - matterpak_readme.pdf');
        $viewer->assertSee('MatterPak point cloud preview');
        $viewer->assertSee('MatterPak texture maps');
        $viewer->assertSee('Texture coverage');
        $viewer->assertSee('1 mapped / 0 missing');
        $viewer->assertSee('Source textures');
        $viewer->assertSee('"viewerType":"image"', false);
        $viewer->assertSee('"viewerType":"pdf"', false);
        $viewer->assertSee('"viewerType":"point_cloud_preview"', false);
        $viewer->assertSee('"viewerType":"media_gallery"', false);
        $viewer->assertSee('"mediaItems":[', false);
        $viewer->assertSee('"mediaCount":1', false);

        $this->assertDatabaseCount('spatial_models', 0);
    }

    public function test_matterpak_blender_script_preserves_original_texture_quality(): void
    {
        $scriptMethod = new \ReflectionMethod(ProcessMatterPakToGlb::class, 'blenderConversionScript');
        $scriptMethod->setAccessible(true);

        $script = $scriptMethod->invoke(new ProcessMatterPakToGlb(1));

        $this->assertStringContainsString('"export_image_format": "AUTO"', $script);
        $this->assertStringContainsString('"export_keep_originals": True', $script);
        $this->assertStringContainsString('"export_image_quality": 100', $script);
        $this->assertStringContainsString('"export_draco_mesh_compression_enable": False', $script);
        $this->assertStringContainsString('os.chdir(obj_directory)', $script);
        $this->assertStringContainsString('material.use_backface_culling = False', $script);
        $this->assertStringContainsString('set_image_colorspace(node.image, "sRGB"', $script);
    }

    public function test_matterpak_blender_binary_path_can_be_relative_to_project_root(): void
    {
        config(['digital_twin.blender.binary' => 'tools/blender/blender']);

        $binaryPathMethod = new \ReflectionMethod(ProcessMatterPakToGlb::class, 'blenderBinaryPath');
        $binaryPathMethod->setAccessible(true);

        $this->assertSame(
            base_path('tools/blender/blender'),
            $binaryPathMethod->invoke(new ProcessMatterPakToGlb(1))
        );
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

        $viewer = $this->actingAs($staff, 'sanctum')
            ->get(route('inspections.digital-twin', $inspection));

        $viewer->assertOk();
        $viewer->assertSee('data-twin-markers', false);
        $viewer->assertSee('data-twin-marker-card', false);
        $viewer->assertSee('data-twin-action-view', false);
        $viewer->assertSee('data-twin-action-add-finding', false);
        $viewer->assertSee('data-twin-marker-filter="high"', false);
        $viewer->assertSee('title="1 issue marker"', false);
        $viewer->assertSee('"title":"Roof leak at north wall"', false);
        $viewer->assertSee('"position":{"x":1.25,"y":2.5,"z":3.75}', false);
        $viewer->assertSee('"cameraPosition":{"x":9.1,"y":8.2,"z":7.3}', false);
        $viewer->assertSee('"cameraTarget":{"x":1,"y":2,"z":3}', false);
        $viewer->assertSee('PHAR #' . $finding->id);
    }

    public function test_issue_marker_can_create_phar_finding_for_same_property_diagnosis(): void
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
            'display_name' => 'Dining room capture',
            'runtime_format' => 'glb',
            'original_format' => 'glb',
            'status' => 'active',
            'processing_status' => 'ready',
        ]);

        $system = \App\Models\BuildingSystem::create([
            'code' => 'INT',
            'name' => 'Interior',
            'slug' => 'interior',
            'is_active' => true,
        ]);
        $subsystem = \App\Models\BuildingSubsystem::create([
            'building_system_id' => $system->id,
            'code' => 'INT-CEIL',
            'name' => 'Ceilings',
            'slug' => 'ceilings',
            'is_active' => true,
        ]);
        $component = \App\Models\BuildingComponent::create([
            'building_subsystem_id' => $subsystem->id,
            'code' => 'INT-CEIL-DRY',
            'name' => 'Drywall ceiling',
            'slug' => 'drywall-ceiling',
            'is_active' => true,
        ]);

        $response = $this->actingAs($staff, 'sanctum')
            ->post(route('inspections.digital-twin.markers.store', $inspection), [
                'spatial_model_id' => $spatialModel->id,
                'capture_session_id' => $captureSession->id,
                'create_phar_finding' => '1',
                'building_system_id' => $system->id,
                'building_subsystem_id' => $subsystem->id,
                'building_component_id' => $component->id,
                'source_provider' => 'manual_upload',
                'marker_type' => 'issue',
                'title' => 'Dining room ceiling stain',
                'severity' => 'medium',
                'status' => 'open',
                'position_x' => '1.0000',
                'position_y' => '2.0000',
                'position_z' => '3.0000',
                'room_name' => 'Dining room',
                'surface_label' => 'Ceiling',
                'source_reference' => 'Dining room capture',
                'description' => 'Brown stain visible near the light fixture.',
            ]);

        $response->assertRedirect(route('inspections.digital-twin', $inspection));

        $finding = PHARFinding::where('inspection_id', $inspection->id)->firstOrFail();
        $marker = \App\Models\IssueMarker::where('inspection_id', $inspection->id)->firstOrFail();

        $this->assertSame($inspection->property_id, $finding->property_id);
        $this->assertSame('Dining room ceiling stain', $finding->task_question);
        $this->assertSame('Interior', $finding->category);
        $this->assertSame($system->id, $finding->building_system_id);
        $this->assertSame($subsystem->id, $finding->building_subsystem_id);
        $this->assertSame($component->id, $finding->building_component_id);
        $this->assertSame($finding->id, $marker->phar_finding_id);
        $this->assertSame($captureSession->id, $marker->capture_session_id);
        $this->assertSame($spatialModel->id, $marker->spatial_model_id);

        $inspection->refresh();
        $jsonFinding = collect($inspection->findings)->firstWhere('issue', 'Dining room ceiling stain');
        $this->assertSame('Dining room', $jsonFinding['location']);
        $this->assertSame('Interior', $jsonFinding['system']);
        $this->assertSame('Ceilings', $jsonFinding['subsystem']);
        $this->assertSame('Drywall ceiling', $jsonFinding['component']);
        $this->assertSame($captureSession->id, $jsonFinding['digital_twin']['capture_session_id']);
        $this->assertSame($spatialModel->id, $jsonFinding['digital_twin']['spatial_model_id']);
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
        $viewer->assertSee('Capture Sessions');
        $viewer->assertSee('View Capture');
        $viewer->assertSee('Add Finding');
        $viewer->assertSee('Create PHAR finding from this marker');
        $viewer->assertSee('data-twin-add-marker', false);
        $viewer->assertSee('data-twin-markers', false);
        $viewer->assertSee('data-twin-marker-card', false);
        $viewer->assertSee('data-twin-action-view', false);
        $viewer->assertSee('data-twin-action-add-finding', false);
        $viewer->assertSee('data-twin-action-open-source', false);
        $viewer->assertDontSee('twin-capture-actions', false);
        $viewer->assertSee('data-twin-marker-filter="high"', false);
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

    public function test_digital_twin_uses_property_diagnosis_number_instead_of_raw_record_id(): void
    {
        $staff = $this->createUserWithRole('Project Manager');
        $this->createInspectionForClient();
        [, $inspection] = $this->createInspectionForClient();
        $inspection->property->update(['project_manager_id' => $staff->id]);

        $this->assertGreaterThan(1, $inspection->id);

        $response = $this->actingAs($staff, 'sanctum')
            ->get(route('inspections.digital-twin', $inspection));

        $response->assertOk();
        $response->assertSee('Diagnosis #1');
        $response->assertSee('Inspection record #' . $inspection->id);
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
        $zip->addFromString('matterpak-demo/colorplan_000.jpg', 'fake-color-plan-image');
        $zip->addFromString('matterpak-demo/colorplan.pdf', '%PDF-1.4 fake');
        $zip->addFromString('matterpak-demo/ceilingcolorplan_000.jpg', 'fake-ceiling-color-plan-image');
        $zip->addFromString('matterpak-demo/ceilingcolorplan.pdf', '%PDF-1.4 fake');
        $zip->addFromString('matterpak-demo/matterpak_readme.pdf', '%PDF-1.4 fake readme');
        $zip->addFromString('pointcloud/house.xyz', "0 0 0 255 255 255\n");
        $zip->addFromString('matterpak-demo', 'root package marker');
        $zip->addFromString('matterpak-demo/package-manifest.txt', 'folder with same normalized name');
        $zip->addFromString('__MACOSX/._matterpak-demo', 'macos-resource-fork');
        $zip->addFromString('.DS_Store', 'macos-folder-metadata');
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
