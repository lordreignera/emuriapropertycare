<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIssueMarkerRequest;
use App\Http\Requests\StoreSpatialModelRequest;
use App\Jobs\ProcessMatterPakToGlb;
use App\Models\CaptureSession;
use App\Models\Inspection;
use App\Models\IssueMarker;
use App\Models\MatterportModel;
use App\Models\PHARFinding;
use App\Models\Property;
use App\Models\SpatialModel;
use App\Models\TwinProcessingJob;
use App\Models\TwinSourceFile;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class DigitalTwinController extends Controller
{
    public function show(Inspection $inspection)
    {
        $this->authorizeInspectionAccess($inspection);

        $inspection->loadMissing([
            'property.user',
            'property.projectManager',
            'inspector',
            'activeMatterportModel',
            'captureSessions.capturedBy',
            'activeSpatialModels.creator',
            'activeSpatialModels.captureSession',
            'twinSourceFiles.uploader',
            'twinSourceFiles.childSourceFiles',
            'twinProcessingJobs.sourceFile',
            'issueMarkers.spatialModel',
            'issueMarkers.pharFinding',
            'pharFindings',
        ]);

        $user = Auth::user();
        $layout = $user->hasRole('Client') ? 'client.layout' : 'admin.layout';

        return view('inspections.digital-twin.show', [
            'inspection' => $inspection,
            'property' => $inspection->property,
            'layout' => $layout,
            'captureSessions' => $inspection->captureSessions->sortByDesc('id'),
            'spatialModels' => $inspection->activeSpatialModels,
            'sourceFiles' => $inspection->twinSourceFiles->sortByDesc('id'),
            'processingJobs' => $inspection->twinProcessingJobs->sortByDesc('id'),
            'legacyMatterportModel' => $inspection->activeMatterportModel
                ?: $inspection->matterportModels()->latest('id')->first(),
            'issueMarkers' => $inspection->issueMarkers->sortByDesc('id'),
            'pharFindings' => $inspection->pharFindings->sortByDesc('id'),
            'providers' => CaptureSession::PROVIDERS,
            'captureTypes' => CaptureSession::CAPTURE_TYPES,
            'sourceTypes' => SpatialModel::SOURCE_TYPES,
            'canManageDigitalTwin' => $this->canManageDigitalTwin($inspection),
            'canCreateIssueMarkers' => $this->canCreateIssueMarkers($inspection),
            'backUrl' => $user->hasRole('Client')
                ? route('client.inspections.index')
                : route('inspections.index'),
        ]);
    }

    public function showProperty(Request $request, Property $property)
    {
        $this->authorizePropertyTwinAccess($property);

        $inspection = $this->resolvePropertyTwinInspection($property, $request->integer('inspection_id') ?: null);

        if ($inspection instanceof Inspection) {
            $inspection->loadMissing('property');

            return $this->show($inspection);
        }

        $user = Auth::user();
        $validInspections = $this->validPropertyTwinInspections($property)->get();
        $layout = $user->hasRole('Client') ? 'client.layout' : 'admin.layout';

        return view('inspections.digital-twin.select-inspection', [
            'property' => $property,
            'inspections' => $validInspections,
            'layout' => $layout,
            'selectedInspectionId' => $request->integer('inspection_id') ?: null,
            'canStartInspection' => $user && !$user->hasRole('Client'),
            'startInspectionUrl' => route('inspections.create', ['property_id' => $property->id]),
            'backUrl' => $user->hasRole('Client')
                ? route('client.properties.show', $property)
                : route('properties.show', $property),
        ]);
    }

    public function storeSpatialModel(StoreSpatialModelRequest $request, Inspection $inspection): RedirectResponse
    {
        $inspection->loadMissing('property');

        if (!$inspection->property) {
            return back()->with('error', 'Attach a property to this inspection before adding digital twin data.');
        }

        $validated = $request->validated();
        $sourceFile = $request->file('source_file');
        $classification = $this->classifyTwinSource($validated, $sourceFile);
        $disk = config('digital_twin.disk', config('filesystems.default', 'local'));
        $sourceFilePath = null;
        $storedFilename = null;
        $fileMetadata = [];

        if ($sourceFile) {
            $storedFilename = Str::random(40) . '.' . $classification['extension'];
            $sourceFilePath = $sourceFile->storeAs(
                "properties/{$inspection->property_id}/twins/inspections/{$inspection->id}/source",
                $storedFilename,
                $disk
            );

            $fileMetadata = [
                'original_filename' => $sourceFile->getClientOriginalName(),
                'stored_filename' => $storedFilename,
                'mime_type' => $sourceFile->getMimeType(),
                'file_size' => $sourceFile->getSize(),
                'checksum_sha256' => hash_file('sha256', $sourceFile->getRealPath()),
            ];
        }

        $thumbnailPath = $request->hasFile('thumbnail_file')
            ? $request->file('thumbnail_file')->store("properties/{$inspection->property_id}/twins/inspections/{$inspection->id}/thumbnails", $disk)
            : null;

        $result = DB::transaction(function () use ($inspection, $validated, $sourceFile, $sourceFilePath, $storedFilename, $thumbnailPath, $disk, $classification, $fileMetadata) {
            if (!empty($validated['is_primary']) && $classification['creates_spatial_model']) {
                SpatialModel::where('inspection_id', $inspection->id)->update(['is_primary' => false]);
            }

            $captureSession = CaptureSession::create([
                'property_id' => $inspection->property_id,
                'inspection_id' => $inspection->id,
                'captured_by' => Auth::id(),
                'provider' => $validated['provider'],
                'capture_type' => $classification['capture_type'] ?: $validated['capture_type'],
                'device_name' => $validated['device_name'] ?? null,
                'device_serial' => $validated['device_serial'] ?? null,
                'status' => $classification['processing_status'],
                'accuracy_class' => $validated['accuracy_class'] ?? null,
                'captured_at' => $validated['captured_at'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'metadata' => [
                    'source_type' => $classification['source_type'],
                    'original_format' => $classification['original_format'] ?: ($validated['original_format'] ?? null),
                    'runtime_format' => $classification['runtime_format'] ?: ($validated['runtime_format'] ?? null),
                    'external_url' => $validated['external_url'] ?? null,
                    'classification_message' => $classification['message'],
                ],
            ]);

            $spatialModel = null;
            $twinSourceFile = null;
            $processingJob = null;

            if ($classification['creates_spatial_model']) {
                $spatialModel = SpatialModel::create([
                    'property_id' => $inspection->property_id,
                    'inspection_id' => $inspection->id,
                    'capture_session_id' => $captureSession->id,
                    'created_by' => Auth::id(),
                    'provider' => $validated['provider'],
                    'source_type' => $classification['spatial_source_type'] ?: $validated['source_type'],
                    'display_name' => $validated['display_name'] ?? null,
                    'runtime_format' => $classification['runtime_format'] ?: ($validated['runtime_format'] ?? null),
                    'original_format' => $classification['original_format'] ?: ($validated['original_format'] ?? null),
                    'provider_model_id' => $validated['provider_model_id'] ?? null,
                    'external_url' => $validated['external_url'] ?? null,
                    'file_path' => $sourceFilePath,
                    'thumbnail_path' => $thumbnailPath,
                    'status' => $validated['status'],
                    'processing_status' => $classification['processing_status'],
                    'is_primary' => !empty($validated['is_primary']),
                    'accuracy_class' => $validated['accuracy_class'] ?? null,
                    'processed_at' => $classification['processing_status'] === 'ready' ? now() : null,
                    'metadata' => [
                        'notes' => $validated['notes'] ?? null,
                        'stored_as_vendor_neutral_twin_source' => true,
                        'storage_owner' => $sourceFilePath ? 'laravel_storage' : 'external',
                        'storage_disk' => $sourceFilePath ? $disk : null,
                        'source_file_type' => $classification['source_type'],
                        'classification_message' => $classification['message'],
                        'cloud_reference_url' => $validated['external_url'] ?? null,
                    ],
                ]);
            }

            if ($sourceFile || $classification['records_source_file']) {
                $twinSourceFile = TwinSourceFile::create([
                    'property_id' => $inspection->property_id,
                    'inspection_id' => $inspection->id,
                    'capture_session_id' => $captureSession->id,
                    'spatial_model_id' => $spatialModel?->id,
                    'uploaded_by' => Auth::id(),
                    'storage_disk' => $sourceFilePath ? $disk : null,
                    'storage_path' => $sourceFilePath,
                    'original_filename' => $fileMetadata['original_filename'] ?? $classification['original_filename'],
                    'stored_filename' => $storedFilename,
                    'relative_path' => null,
                    'extension' => $classification['extension'] ?: 'other',
                    'mime_type' => $fileMetadata['mime_type'] ?? null,
                    'file_size' => $fileMetadata['file_size'] ?? null,
                    'checksum_sha256' => $fileMetadata['checksum_sha256'] ?? null,
                    'source_type' => $classification['source_type'],
                    'file_role' => $classification['file_role'] ?? null,
                    'processing_status' => $classification['source_processing_status'],
                    'metadata' => [
                        'external_url' => $validated['external_url'] ?? null,
                        'provider_model_id' => $validated['provider_model_id'] ?? null,
                        'display_name' => $validated['display_name'] ?? null,
                        'is_primary' => !empty($validated['is_primary']),
                        'selected_capture_type' => $validated['capture_type'],
                        'selected_source_type' => $validated['source_type'],
                        'package_type' => $classification['package_type'] ?? null,
                        'classification_message' => $classification['message'],
                    ],
                ]);
            }

            if (($classification['queues_matterpak_conversion'] ?? false) && $twinSourceFile) {
                $processingJob = TwinProcessingJob::create([
                    'property_id' => $inspection->property_id,
                    'inspection_id' => $inspection->id,
                    'capture_session_id' => $captureSession->id,
                    'source_file_id' => $twinSourceFile->id,
                    'created_by' => Auth::id(),
                    'processor' => 'blender',
                    'job_type' => 'matterpak_obj_to_glb',
                    'queue_name' => config('digital_twin.processing.queue', 'digital-twin'),
                    'status' => 'queued',
                    'input_storage_disk' => $disk,
                    'input_storage_path' => $sourceFilePath,
                    'timeout_seconds' => config('digital_twin.processing.timeout_seconds', 3600),
                    'metadata' => [
                        'original_filename' => $fileMetadata['original_filename'] ?? $classification['original_filename'],
                        'matterpak_source_file_id' => $twinSourceFile->id,
                        'classification_message' => $classification['message'],
                    ],
                ]);
            }

            if ($spatialModel && $spatialModel->provider === 'matterport' && $spatialModel->provider_model_id) {
                MatterportModel::updateOrCreate(
                    ['inspection_id' => $inspection->id],
                    [
                        'property_id' => $inspection->property_id,
                        'spatial_model_id' => $spatialModel->id,
                        'created_by' => Auth::id(),
                        'model_sid' => $spatialModel->provider_model_id,
                        'model_name' => $spatialModel->display_name,
                        'model_url' => $spatialModel->external_url,
                        'status' => $spatialModel->status,
                        'scanned_at' => $validated['captured_at'] ?? null,
                        'notes' => $validated['notes'] ?? null,
                    ]
                );
            }

            return [
                'spatial_model' => $spatialModel,
                'source_file' => $twinSourceFile,
                'processing_job' => $processingJob,
            ];
        });

        if ($result['processing_job'] instanceof TwinProcessingJob) {
            try {
                ProcessMatterPakToGlb::dispatch($result['processing_job']->id)
                    ->onQueue(config('digital_twin.processing.queue', 'digital-twin'));
            } catch (Throwable $exception) {
                $result['processing_job']->update([
                    'status' => 'failed',
                    'processing_error' => $exception->getMessage(),
                    'completed_at' => now(),
                ]);

                $result['source_file']?->update([
                    'processing_status' => 'failed',
                    'processing_error' => $exception->getMessage(),
                ]);

                return redirect()
                    ->route('inspections.digital-twin', $inspection)
                    ->with('error', 'MatterPak was stored, but conversion could not be queued: ' . $exception->getMessage());
            }
        }

        return redirect()
            ->route('inspections.digital-twin', $inspection)
            ->with($result['spatial_model'] ? 'success' : 'info', $classification['message']);
    }

    public function storeIssueMarker(StoreIssueMarkerRequest $request, Inspection $inspection): RedirectResponse
    {
        $inspection->loadMissing('property');

        if (!$inspection->property) {
            return back()->with('error', 'Attach a property to this inspection before adding issue markers.');
        }

        $validated = $request->validated();

        if (!empty($validated['spatial_model_id'])) {
            $belongsToInspection = SpatialModel::where('id', $validated['spatial_model_id'])
                ->where('inspection_id', $inspection->id)
                ->exists();

            if (!$belongsToInspection) {
                return back()->withErrors([
                    'spatial_model_id' => 'Choose a spatial model from this inspection.',
                ]);
            }
        }

        if (!empty($validated['capture_session_id'])) {
            $belongsToInspection = CaptureSession::where('id', $validated['capture_session_id'])
                ->where('inspection_id', $inspection->id)
                ->exists();

            if (!$belongsToInspection) {
                return back()->withErrors([
                    'capture_session_id' => 'Choose a capture session from this inspection.',
                ]);
            }
        }

        if (!empty($validated['phar_finding_id'])) {
            $belongsToInspection = PHARFinding::where('id', $validated['phar_finding_id'])
                ->where('inspection_id', $inspection->id)
                ->exists();

            if (!$belongsToInspection) {
                return back()->withErrors([
                    'phar_finding_id' => 'Choose a finding from this inspection.',
                ]);
            }
        }

        $marker = IssueMarker::create([
            'property_id' => $inspection->property_id,
            'inspection_id' => $inspection->id,
            'spatial_model_id' => $validated['spatial_model_id'] ?? null,
            'capture_session_id' => $validated['capture_session_id'] ?? null,
            'phar_finding_id' => $validated['phar_finding_id'] ?? null,
            'created_by' => Auth::id(),
            'source_provider' => $validated['source_provider'],
            'marker_type' => $validated['marker_type'],
            'title' => $validated['title'],
            'severity' => $validated['severity'],
            'status' => $validated['status'],
            'position_x' => $validated['position_x'] ?? null,
            'position_y' => $validated['position_y'] ?? null,
            'position_z' => $validated['position_z'] ?? null,
            'normal_x' => $validated['normal_x'] ?? null,
            'normal_y' => $validated['normal_y'] ?? null,
            'normal_z' => $validated['normal_z'] ?? null,
            'camera_position' => $validated['camera_position'] ?? null,
            'camera_target' => $validated['camera_target'] ?? null,
            'object_uuid' => $validated['object_uuid'] ?? null,
            'room_name' => $validated['room_name'] ?? null,
            'surface_label' => $validated['surface_label'] ?? null,
            'source_reference' => $validated['source_reference'] ?? null,
            'confidence' => $validated['confidence'] ?? null,
            'description' => $validated['description'] ?? null,
            'metadata' => [
                'created_from' => 'digital_twin_marker_form',
            ],
            'provenance' => $validated['provenance'] ?? [
                'created_from' => 'digital_twin_marker_form',
                'source_provider' => $validated['source_provider'],
            ],
        ]);

        return redirect()
            ->route('inspections.digital-twin', $inspection)
            ->with('success', "Issue marker {$marker->id} added to the property digital twin.");
    }

    public function spatialModelFile(Inspection $inspection, SpatialModel $spatialModel)
    {
        $this->authorizeInspectionAccess($inspection);

        if ((int) $spatialModel->inspection_id !== (int) $inspection->id) {
            abort(404);
        }

        if (!$spatialModel->file_path) {
            abort(404, 'This spatial model does not have a stored file.');
        }

        return $this->authorizedStorageResponse(
            $spatialModel->file_path,
            $spatialModel->metadata['storage_disk'] ?? config('digital_twin.disk', config('filesystems.default', 'local')),
            $spatialModel->display_name ?: basename($spatialModel->file_path)
        );
    }

    public function sourceFileDownload(Inspection $inspection, TwinSourceFile $twinSourceFile)
    {
        $this->authorizeInspectionAccess($inspection);

        if ((int) $twinSourceFile->inspection_id !== (int) $inspection->id) {
            abort(404);
        }

        if (!$twinSourceFile->storage_path) {
            abort(404, 'This source file is stored outside Laravel storage.');
        }

        return $this->authorizedStorageResponse(
            $twinSourceFile->storage_path,
            $twinSourceFile->storage_disk ?: config('digital_twin.disk', config('filesystems.default', 'local')),
            $twinSourceFile->original_filename
        );
    }

    private function authorizeInspectionAccess(Inspection $inspection): void
    {
        $user = Auth::user();

        if (!$user) {
            abort(403);
        }

        $inspection->loadMissing(['property', 'project']);
        $property = $inspection->property;

        if (!$property) {
            abort(404, 'This inspection is not connected to a property.');
        }

        if ($user->hasAnyRole(['Super Admin', 'Administrator'])) {
            return;
        }

        if ($user->hasRole('Client') && (int) $property->user_id === (int) $user->id) {
            return;
        }

        if (
            $this->hasTwinPermission('view digital twin inspections')
            && $this->isAssignedStaff($inspection, (int) $user->id)
        ) {
            return;
        }

        if ($this->hasTwinPermission('view matterport inspections') && $this->isAssignedStaff($inspection, (int) $user->id)) {
            return;
        }

        if ($user->hasAnyRole(['Project Manager', 'Inspector']) && $this->isAssignedStaff($inspection, (int) $user->id)) {
            return;
        }

        abort(403, 'You do not have access to this digital twin inspection.');
    }

    private function authorizePropertyTwinAccess(Property $property): void
    {
        $user = Auth::user();

        if (!$user) {
            abort(403);
        }

        if ($user->hasAnyRole(['Super Admin', 'Administrator'])) {
            return;
        }

        if ($user->hasRole('Client') && (int) $property->user_id === (int) $user->id) {
            return;
        }

        if (
            $user->hasAnyRole(['Project Manager', 'Inspector'])
            && (
                (int) ($property->project_manager_id ?? 0) === (int) $user->id
                || (int) ($property->inspector_id ?? 0) === (int) $user->id
            )
        ) {
            return;
        }

        abort(403, 'You do not have access to this property twin.');
    }

    private function resolvePropertyTwinInspection(Property $property, ?int $selectedInspectionId = null): ?Inspection
    {
        $query = $this->validPropertyTwinInspections($property);

        if ($selectedInspectionId) {
            return (clone $query)->whereKey($selectedInspectionId)->first();
        }

        $validInspectionIds = (clone $query)->limit(2)->pluck('id');

        if ($validInspectionIds->count() === 1) {
            return (clone $query)->whereKey($validInspectionIds->first())->first();
        }

        return null;
    }

    private function validPropertyTwinInspections(Property $property)
    {
        return $property->inspections()
            ->with('inspector')
            ->whereIn('status', $this->validPropertyTwinInspectionStatuses())
            ->latest('id');
    }

    private function validPropertyTwinInspectionStatuses(): array
    {
        return [
            'scheduled',
            'in_progress',
            'findings_captured',
            'findings_shared',
            'client_committed',
            'estimation_in_progress',
            'estimation_completed',
            'quotation_shared',
            'quotation_approved',
            'completed',
        ];
    }

    private function classifyTwinSource(array $validated, ?UploadedFile $file): array
    {
        $extension = $this->detectTwinExtension($validated, $file);
        $mappedSourceType = config("digital_twin.extension_source_types.{$extension}", 'other');
        $hasHostedReference = !empty($validated['external_url']) || !empty($validated['provider_model_id']);
        $isPanorama = $mappedSourceType === 'image'
            && (
                ($validated['capture_type'] ?? null) === 'panorama'
                || ($validated['source_type'] ?? null) === 'panorama_set'
                || ($validated['provider'] ?? null) === 'camera_360'
            );

        $classification = [
            'extension' => $extension,
            'source_type' => $isPanorama ? 'panorama' : $mappedSourceType,
            'spatial_source_type' => $validated['source_type'] ?? null,
            'capture_type' => $validated['capture_type'] ?? null,
            'runtime_format' => $validated['runtime_format'] ?? null,
            'original_format' => $validated['original_format'] ?? null,
            'processing_status' => 'ready',
            'source_processing_status' => 'uploaded',
            'creates_spatial_model' => true,
            'records_source_file' => false,
            'queues_matterpak_conversion' => false,
            'file_role' => null,
            'package_type' => null,
            'original_filename' => $this->externalSourceName($validated, $extension),
            'message' => 'Capture source added to the property digital twin.',
        ];

        if (($validated['provider'] ?? null) === 'matterport' && $file && $extension === 'zip') {
            return array_merge($classification, [
                'source_type' => config('digital_twin.matterpak.archive_source_type', 'obj_bundle'),
                'spatial_source_type' => 'runtime_3d_model',
                'capture_type' => 'obj_mesh',
                'runtime_format' => null,
                'original_format' => 'matterpak_zip',
                'processing_status' => 'awaiting_processing',
                'source_processing_status' => 'awaiting_processing',
                'creates_spatial_model' => false,
                'records_source_file' => true,
                'queues_matterpak_conversion' => true,
                'file_role' => 'matterpak_archive',
                'package_type' => 'matterpak',
                'message' => 'MatterPak ZIP stored privately. OBJ/MTL/textures will be converted to GLB by the queued Blender worker; XYZ, JPG and PDF files are preserved as source records.',
            ]);
        }

        if (($validated['provider'] ?? null) === 'matterport' && !$file) {
            return array_merge($classification, [
                'source_type' => 'other',
                'spatial_source_type' => 'hosted_tour',
                'capture_type' => 'hosted_tour',
                'runtime_format' => 'hosted',
                'original_format' => 'matterport_sid',
                'processing_status' => 'ready',
                'source_processing_status' => 'ready',
                'creates_spatial_model' => true,
                'records_source_file' => false,
                'message' => 'Matterport hosted source added to the property digital twin.',
            ]);
        }

        if (!$file && !empty($validated['provider_model_id']) && empty($validated['external_url'])) {
            return array_merge($classification, [
                'processing_status' => 'ready',
                'source_processing_status' => 'ready',
                'creates_spatial_model' => true,
                'records_source_file' => false,
                'message' => 'Provider-hosted capture source added to the property digital twin.',
            ]);
        }

        if (in_array($mappedSourceType, ['glb', 'gltf'], true)) {
            return array_merge($classification, [
                'spatial_source_type' => 'runtime_3d_model',
                'capture_type' => 'glb_model',
                'runtime_format' => $mappedSourceType,
                'original_format' => $mappedSourceType,
                'processing_status' => 'ready',
                'source_processing_status' => 'ready',
                'creates_spatial_model' => true,
                'records_source_file' => (bool) $hasHostedReference && !$file,
                'message' => 'GLB/glTF source uploaded and is ready for the Three.js viewer.',
            ]);
        }

        if ($mappedSourceType === 'obj_bundle') {
            return array_merge($classification, [
                'spatial_source_type' => 'runtime_3d_model',
                'capture_type' => 'obj_mesh',
                'runtime_format' => null,
                'original_format' => $extension ?: 'obj',
                'processing_status' => 'awaiting_processing',
                'source_processing_status' => 'awaiting_processing',
                'creates_spatial_model' => false,
                'records_source_file' => true,
                'message' => 'OBJ source preserved. Browser-ready GLB conversion is awaiting processing and Blender integration is not configured yet.',
            ]);
        }

        if (in_array($mappedSourceType, ['e57', 'las', 'laz'], true)) {
            return array_merge($classification, [
                'spatial_source_type' => 'master_point_cloud',
                'capture_type' => 'point_cloud',
                'runtime_format' => null,
                'original_format' => $mappedSourceType,
                'processing_status' => 'awaiting_processing',
                'source_processing_status' => 'awaiting_processing',
                'creates_spatial_model' => false,
                'records_source_file' => true,
                'message' => 'Point-cloud source preserved. Potree/Cesium processing is not configured yet, so this file is awaiting processing.',
            ]);
        }

        if ($isPanorama) {
            return array_merge($classification, [
                'source_type' => 'panorama',
                'spatial_source_type' => 'panorama_set',
                'capture_type' => 'panorama',
                'runtime_format' => null,
                'original_format' => $extension,
                'processing_status' => 'ready',
                'source_processing_status' => 'uploaded',
                'creates_spatial_model' => true,
                'message' => '360 image stored as a source capture. It is viewable as a panorama but no automatic 3D reconstruction was performed.',
            ]);
        }

        if ($mappedSourceType === 'image') {
            return array_merge($classification, [
                'spatial_source_type' => $validated['source_type'] ?? 'document_reference',
                'capture_type' => $validated['capture_type'] ?? 'photo_set',
                'runtime_format' => null,
                'original_format' => $extension,
                'processing_status' => 'ready',
                'source_processing_status' => 'uploaded',
                'creates_spatial_model' => true,
                'message' => 'Image source stored as evidence. No automatic 3D reconstruction was performed.',
            ]);
        }

        if ($mappedSourceType === 'pdf') {
            return array_merge($classification, [
                'spatial_source_type' => null,
                'capture_type' => 'document',
                'runtime_format' => null,
                'original_format' => 'pdf',
                'processing_status' => 'uploaded',
                'source_processing_status' => 'uploaded',
                'creates_spatial_model' => false,
                'records_source_file' => true,
                'message' => 'PDF stored as supporting source documentation. It was not treated as a spatial model.',
            ]);
        }

        if (!$file && $hasHostedReference) {
            return array_merge($classification, [
                'processing_status' => 'ready',
                'source_processing_status' => 'ready',
                'creates_spatial_model' => true,
                'records_source_file' => false,
                'message' => 'Hosted capture source added to the property digital twin.',
            ]);
        }

        return array_merge($classification, [
            'processing_status' => 'uploaded',
            'source_processing_status' => 'uploaded',
            'creates_spatial_model' => false,
            'records_source_file' => true,
            'message' => 'Source file stored for the property twin.',
        ]);
    }

    private function detectTwinExtension(array $validated, ?UploadedFile $file): string
    {
        if ($file) {
            return strtolower((string) $file->getClientOriginalExtension());
        }

        $externalPath = parse_url((string) ($validated['external_url'] ?? ''), PHP_URL_PATH);
        $extension = strtolower((string) pathinfo((string) $externalPath, PATHINFO_EXTENSION));

        if ($extension !== '') {
            return $extension;
        }

        return strtolower(trim((string) ($validated['original_format'] ?? 'other'))) ?: 'other';
    }

    private function externalSourceName(array $validated, string $extension): string
    {
        $externalPath = parse_url((string) ($validated['external_url'] ?? ''), PHP_URL_PATH);
        $basename = $externalPath ? basename($externalPath) : null;

        if ($basename) {
            return $basename;
        }

        if (!empty($validated['provider_model_id'])) {
            return (string) $validated['provider_model_id'];
        }

        return 'external-source.' . ($extension ?: 'txt');
    }

    private function authorizedStorageResponse(string $path, ?string $disk, string $downloadName)
    {
        $disk = $disk ?: config('digital_twin.disk', config('filesystems.default', 'local'));
        $storage = Storage::disk($disk);

        if (!$storage->exists($path)) {
            abort(404, 'The requested twin file could not be found.');
        }

        $driver = config("filesystems.disks.{$disk}.driver");

        if ($driver !== 'local' && method_exists($storage, 'temporaryUrl')) {
            return redirect()->away($storage->temporaryUrl($path, now()->addMinutes(30)));
        }

        return $storage->response($path, $downloadName);
    }

    private function canManageDigitalTwin(Inspection $inspection): bool
    {
        $user = Auth::user();

        if (!$user || $user->hasRole('Client')) {
            return false;
        }

        if ($user->hasAnyRole(['Super Admin', 'Administrator'])) {
            return true;
        }

        $canManage = $this->hasTwinPermission('manage digital twin models')
            || $this->hasTwinPermission('attach matterport models')
            || $user->hasRole('Project Manager');

        return $canManage && $this->isAssignedStaff($inspection, (int) $user->id);
    }

    private function canCreateIssueMarkers(Inspection $inspection): bool
    {
        $user = Auth::user();

        if (!$user || $user->hasRole('Client')) {
            return false;
        }

        if ($user->hasAnyRole(['Super Admin', 'Administrator'])) {
            return true;
        }

        $canCreate = $this->hasTwinPermission('create digital twin issue markers')
            || $this->hasTwinPermission('create inspection findings')
            || $user->hasAnyRole(['Project Manager', 'Inspector']);

        return $canCreate && $this->isAssignedStaff($inspection, (int) $user->id);
    }

    private function hasTwinPermission(string $permission): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        try {
            return $user->can($permission);
        } catch (\Throwable) {
            return false;
        }
    }

    private function isAssignedStaff(Inspection $inspection, int $userId): bool
    {
        $inspection->loadMissing(['property', 'project']);
        $property = $inspection->property;
        $project = $inspection->project;

        return (int) ($inspection->inspector_id ?? 0) === $userId
            || (int) ($property?->inspector_id ?? 0) === $userId
            || (int) ($property?->project_manager_id ?? 0) === $userId
            || (int) ($project?->managed_by ?? 0) === $userId;
    }
}
