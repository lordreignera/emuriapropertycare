<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIssueMarkerRequest;
use App\Http\Requests\StoreSpatialModelRequest;
use App\Jobs\ProcessMatterPakToGlb;
use App\Models\BuildingComponent;
use App\Models\BuildingSubsystem;
use App\Models\BuildingSystem;
use App\Models\CaptureSession;
use App\Models\Inspection;
use App\Models\IssueMarker;
use App\Models\PHARFinding;
use App\Models\Property;
use App\Models\SpatialModel;
use App\Models\TwinProcessingJob;
use App\Models\TwinSourceFile;
use App\Services\MatterportHostedTourService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
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
            'twinSourceFiles.captureSession',
            'twinSourceFiles.uploader',
            'twinSourceFiles.childSourceFiles',
            'twinProcessingJobs.sourceFile',
            'issueMarkers.spatialModel',
            'issueMarkers.captureSession',
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
            'viewerMarkers' => $this->viewerMarkers($inspection->issueMarkers),
            'pharFindings' => $inspection->pharFindings->sortByDesc('id'),
            'propertyDiagnosisNumber' => $this->propertyDiagnosisNumber($inspection),
            'providers' => CaptureSession::PROVIDERS,
            'captureTypes' => CaptureSession::CAPTURE_TYPES,
            'sourceTypes' => SpatialModel::SOURCE_TYPES,
            'buildingSystems' => $this->activeBuildingSystems(),
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
            if (
                !empty($validated['is_primary'])
                && $classification['creates_spatial_model']
                && !$this->isMatterportHostedClassification($validated, $classification)
            ) {
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

            if ($classification['creates_spatial_model'] && $this->isMatterportHostedClassification($validated, $classification)) {
                $spatialModel = $this->attachMatterportHostedTour(
                    $inspection,
                    $captureSession,
                    $validated,
                    $thumbnailPath,
                    [
                        'storage_owner' => 'external',
                        'source_file_type' => 'hosted_matterport_walkthrough',
                        'classification_message' => $classification['message'],
                    ]
                );
            } elseif ($classification['creates_spatial_model']) {
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
                $processingJob = $this->createMatterPakProcessingJob($twinSourceFile, [
                    'original_filename' => $fileMetadata['original_filename'] ?? $classification['original_filename'],
                    'classification_message' => $classification['message'],
                    'triggered_from' => 'matterpak_upload',
                ]);
            }

            if (($classification['queues_matterpak_conversion'] ?? false) && $twinSourceFile && $this->hasMatterportHostedReference($validated)) {
                $spatialModel = $this->attachMatterportHostedTour(
                    $inspection,
                    $captureSession,
                    $validated,
                    $thumbnailPath,
                    [
                        'storage_owner' => 'external',
                        'paired_matterpak_source_file_id' => $twinSourceFile->id,
                        'source_file_type' => 'hosted_matterport_walkthrough',
                        'classification_message' => 'Hosted Matterport walkthrough added alongside MatterPak ZIP source.',
                    ],
                    $this->matterportHostedDisplayName($validated)
                );
            }

            return [
                'spatial_model' => $spatialModel,
                'source_file' => $twinSourceFile,
                'processing_job' => $processingJob,
                'capture_session' => $captureSession,
            ];
        });

        if ($result['processing_job'] instanceof TwinProcessingJob) {
            try {
                $this->dispatchMatterPakProcessingJob($result['processing_job']);
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
            ->with('digital_twin_capture_id', $result['capture_session']?->id)
            ->with($result['spatial_model'] ? 'success' : 'info', $classification['message']);
    }

    public function convertMatterPakSource(Request $request, Inspection $inspection, TwinSourceFile $twinSourceFile): RedirectResponse
    {
        $this->authorizeInspectionAccess($inspection);

        if (!$this->canManageDigitalTwin($inspection)) {
            abort(403, 'You do not have permission to convert MatterPak sources for this diagnosis.');
        }

        $inspection->loadMissing('property');

        if (
            (int) $twinSourceFile->inspection_id !== (int) $inspection->id
            || (int) $twinSourceFile->property_id !== (int) $inspection->property_id
        ) {
            abort(404);
        }

        if (!$this->isMatterPakArchive($twinSourceFile)) {
            return redirect()
                ->route('inspections.digital-twin', $inspection)
                ->with('error', 'Only MatterPak ZIP archive source files can be converted with Blender.');
        }

        if (!$twinSourceFile->storage_path) {
            return redirect()
                ->route('inspections.digital-twin', $inspection)
                ->with('error', 'This MatterPak archive is missing its private storage path.');
        }

        $activeJob = $this->activeMatterPakProcessingJob($twinSourceFile);

        if ($activeJob) {
            return redirect()
                ->route('inspections.digital-twin', [$inspection, 'capture' => $twinSourceFile->capture_session_id])
                ->with('info', 'MatterPak conversion is already ' . str_replace('_', ' ', (string) $activeJob->status) . ' for this capture.');
        }

        $processingJob = DB::transaction(function () use ($twinSourceFile) {
            $twinSourceFile->update([
                'processing_status' => 'queued',
                'processing_error' => null,
            ]);

            CaptureSession::whereKey($twinSourceFile->capture_session_id)->update([
                'status' => 'queued',
            ]);

            return $this->createMatterPakProcessingJob($twinSourceFile, [
                'original_filename' => $twinSourceFile->original_filename,
                'triggered_from' => 'manual_digital_twin_convert_button',
                'requested_at' => now()->toIso8601String(),
                'previous_spatial_model_id' => $twinSourceFile->spatial_model_id,
            ]);
        });

        try {
            $this->dispatchMatterPakProcessingJob($processingJob);
        } catch (Throwable $exception) {
            $processingJob->update([
                'status' => 'failed',
                'processing_error' => $exception->getMessage(),
                'completed_at' => now(),
            ]);

            $twinSourceFile->update([
                'processing_status' => 'failed',
                'processing_error' => $exception->getMessage(),
            ]);

            CaptureSession::whereKey($twinSourceFile->capture_session_id)->update([
                'status' => 'failed',
            ]);

            return redirect()
                ->route('inspections.digital-twin', [$inspection, 'capture' => $twinSourceFile->capture_session_id])
                ->with('error', 'MatterPak conversion could not be queued: ' . $exception->getMessage());
        }

        return redirect()
            ->route('inspections.digital-twin', [$inspection, 'capture' => $twinSourceFile->capture_session_id])
            ->with('success', 'MatterPak conversion started. Keep the digital-twin queue worker running to finish the GLB.');
    }

    public function storeIssueMarker(StoreIssueMarkerRequest $request, Inspection $inspection): RedirectResponse
    {
        $inspection->loadMissing('property');

        if (!$inspection->property) {
            return back()->with('error', 'Attach a property to this inspection before adding issue markers.');
        }

        $validated = $request->validated();
        $validated = $this->normalizeMarkerFindingContext($validated);

        if (
            !empty($validated['create_phar_finding'])
            && empty($validated['phar_finding_id'])
            && !$this->canCreatePharFindingFromMarker($inspection)
        ) {
            return back()
                ->withErrors(['create_phar_finding' => 'New PHAR findings cannot be created from markers after the findings report has been shared. Link this marker to an existing finding instead.'])
                ->withInput();
        }

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

        $createdFinding = null;
        $marker = DB::transaction(function () use ($inspection, $validated, &$createdFinding) {
            $pharFindingId = $validated['phar_finding_id'] ?? null;

            if (!empty($validated['create_phar_finding']) && !$pharFindingId) {
                $createdFinding = $this->createPharFindingFromMarker($inspection, $validated);
                $pharFindingId = $createdFinding->id;
            }

            return IssueMarker::create([
                'property_id' => $inspection->property_id,
                'inspection_id' => $inspection->id,
                'spatial_model_id' => $validated['spatial_model_id'] ?? null,
                'capture_session_id' => $validated['capture_session_id'] ?? null,
                'phar_finding_id' => $pharFindingId,
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
                    'created_phar_finding_id' => $createdFinding?->id,
                ],
                'provenance' => $validated['provenance'] ?? [
                    'created_from' => 'digital_twin_marker_form',
                    'source_provider' => $validated['source_provider'],
                ],
            ]);
        });

        return redirect()
            ->route('inspections.digital-twin', $inspection)
            ->with('success', $createdFinding
                ? "Issue marker {$marker->id} added and PHAR finding {$createdFinding->id} created for this diagnosis."
                : "Issue marker {$marker->id} added to the property digital twin.");
    }

    private function createPharFindingFromMarker(Inspection $inspection, array $markerData): PHARFinding
    {
        $inspection->refresh();

        $jsonSeverity = in_array($markerData['severity'], ['critical', 'high', 'medium', 'low'], true)
            ? $markerData['severity']
            : 'medium';
        $pharSeverity = $jsonSeverity === 'medium' ? 'moderate' : $jsonSeverity;
        $priority = match ($jsonSeverity) {
            'critical', 'high' => '1',
            'medium' => '2',
            default => '3',
        };
        $location = trim((string) ($markerData['room_name'] ?? ''));
        $spot = trim((string) ($markerData['surface_label'] ?? ''));
        $sourceReference = trim((string) ($markerData['source_reference'] ?? ''));
        $description = trim((string) ($markerData['description'] ?? ''));
        $issue = trim((string) $markerData['title']);
        $systemId = $markerData['building_system_id'] ?? null;
        $subsystemId = $markerData['building_subsystem_id'] ?? null;
        $componentId = $markerData['building_component_id'] ?? null;
        $systemName = $markerData['building_system_name'] ?? null;
        $systemSlug = $markerData['building_system_slug'] ?? null;
        $subsystemName = $markerData['building_subsystem_name'] ?? null;
        $componentName = $markerData['building_component_name'] ?? null;

        $finding = PHARFinding::create([
            'inspection_id' => $inspection->id,
            'property_id' => $inspection->property_id,
            'building_system_id' => $systemId,
            'building_subsystem_id' => $subsystemId,
            'building_component_id' => $componentId,
            'task_question' => $issue,
            'category' => $systemName ?: 'Digital Twin',
            'finding_type' => 'stand_alone',
            'severity' => $pharSeverity,
            'priority' => $priority,
            'included_yn' => true,
            'labour_hours' => 0,
            'material_cost' => 0,
            'notes' => $sourceReference !== '' ? 'Digital twin source: ' . $sourceReference : null,
            'observed_condition' => $description !== '' ? $description : $issue,
            'consequence_if_ignored' => null,
            'remediation_strategy' => null,
            'workflow_status' => 'observed',
        ]);

        $findings = collect($inspection->findings ?? [])->values();
        $findings->push([
            'building_system_id' => $systemId,
            'system' => $systemName,
            'system_slug' => $systemSlug,
            'building_subsystem_id' => $subsystemId,
            'building_component_id' => $componentId,
            'subsystem' => $subsystemName,
            'component' => $componentName,
            'issue' => $issue,
            'issue_description' => $description !== '' ? $description : $issue,
            'location' => $location,
            'spot' => $spot,
            'severity' => $jsonSeverity,
            'notes' => $sourceReference !== '' ? 'Digital twin source: ' . $sourceReference : '',
            'recommendations' => [],
            'recommendation_details' => '',
            'affected_areas' => [[
                'building_system_id' => $systemId,
                'building_subsystem_id' => $subsystemId,
                'building_component_id' => $componentId,
                'location' => trim(implode(' / ', array_filter([$location, $spot]))) ?: null,
                'impact_description' => $description !== '' ? $description : null,
                'severity' => $pharSeverity,
            ]],
            'type' => $systemSlug,
            'finding_photos' => [],
            'risk_impact' => '',
            'phar_labour_hours' => 0,
            'phar_category' => $systemName ?: 'Digital Twin',
            'phar_included_yn' => true,
            'phar_notes' => $sourceReference !== '' ? 'Digital twin source: ' . $sourceReference : '',
            'fulfillment_type' => 'decide_later',
            'trade_application_id' => null,
            'trade_quantity' => 1,
            'trade_unit' => '',
            'trade_scope_area' => $location,
            'trade_duration_hours' => null,
            'trade_materials_included' => false,
            'trade_notes' => '',
            'phar_materials' => [],
            'digital_twin' => [
                'capture_session_id' => $markerData['capture_session_id'] ?? null,
                'spatial_model_id' => $markerData['spatial_model_id'] ?? null,
                'source_reference' => $sourceReference ?: null,
                'created_from_marker' => true,
            ],
        ]);

        $inspection->forceFill(['findings' => $findings->all()])->save();

        return $finding;
    }

    private function normalizeMarkerFindingContext(array $validated): array
    {
        $systemId = !empty($validated['building_system_id']) ? (int) $validated['building_system_id'] : null;
        $subsystemId = !empty($validated['building_subsystem_id']) ? (int) $validated['building_subsystem_id'] : null;
        $componentId = !empty($validated['building_component_id']) ? (int) $validated['building_component_id'] : null;

        $system = $systemId ? BuildingSystem::find($systemId) : null;
        $subsystem = $subsystemId ? BuildingSubsystem::find($subsystemId) : null;
        $component = $componentId ? BuildingComponent::with('subsystem')->find($componentId) : null;

        if ($systemId && !$system) {
            throw ValidationException::withMessages([
                'building_system_id' => 'Choose a valid building system.',
            ]);
        }

        if ($subsystemId && !$subsystem) {
            throw ValidationException::withMessages([
                'building_subsystem_id' => 'Choose a valid building subsystem.',
            ]);
        }

        if ($componentId && !$component) {
            throw ValidationException::withMessages([
                'building_component_id' => 'Choose a valid building component.',
            ]);
        }

        if ($subsystem && $systemId && (int) $subsystem->building_system_id !== $systemId) {
            throw ValidationException::withMessages([
                'building_subsystem_id' => 'Selected subsystem does not belong to the selected building system.',
            ]);
        }

        if ($component) {
            if ($subsystemId && (int) $component->building_subsystem_id !== $subsystemId) {
                throw ValidationException::withMessages([
                    'building_component_id' => 'Selected component does not belong to the selected subsystem.',
                ]);
            }

            if (!$subsystem) {
                $subsystem = $component->subsystem;
                $subsystemId = $subsystem?->id;
            }
        }

        if ($subsystem && !$system) {
            $system = BuildingSystem::find($subsystem->building_system_id);
            $systemId = $system?->id;
        }

        if ($component && $subsystem && $systemId && (int) $subsystem->building_system_id !== $systemId) {
            throw ValidationException::withMessages([
                'building_component_id' => 'Selected component does not belong to the selected building system.',
            ]);
        }

        return array_merge($validated, [
            'building_system_id' => $systemId,
            'building_subsystem_id' => $subsystemId,
            'building_component_id' => $componentId,
            'building_system_name' => $system?->name,
            'building_system_slug' => $system?->slug,
            'building_subsystem_name' => $subsystem?->name,
            'building_component_name' => $component?->name,
        ]);
    }

    private function viewerMarkers($markers)
    {
        return $markers
            ->filter(fn (IssueMarker $marker) => $marker->position_x !== null && $marker->position_y !== null && $marker->position_z !== null)
            ->map(function (IssueMarker $marker) {
                $captureLabel = $marker->captureSession
                    ? $marker->captureSession->provider_label . ' / ' . $marker->captureSession->capture_type_label
                    : null;
                $modelTitle = $marker->spatialModel
                    ? ($marker->spatialModel->display_name ?: $marker->spatialModel->source_type_label)
                    : null;

                return [
                    'id' => (int) $marker->id,
                    'title' => $marker->title,
                    'description' => $marker->description,
                    'severity' => $marker->severity,
                    'status' => $marker->status,
                    'sourceProvider' => $marker->source_provider,
                    'sourceProviderLabel' => CaptureSession::PROVIDERS[$marker->source_provider] ?? ucfirst(str_replace('_', ' ', (string) $marker->source_provider)),
                    'markerType' => $marker->marker_type,
                    'roomName' => $marker->room_name,
                    'surfaceLabel' => $marker->surface_label,
                    'sourceReference' => $marker->source_reference,
                    'confidence' => $marker->confidence !== null ? (float) $marker->confidence : null,
                    'spatialModelId' => $marker->spatial_model_id ? (int) $marker->spatial_model_id : null,
                    'captureSessionId' => $marker->capture_session_id ? (int) $marker->capture_session_id : null,
                    'pharFindingId' => $marker->phar_finding_id ? (int) $marker->phar_finding_id : null,
                    'pharFindingLabel' => $this->pharFindingLabel($marker->pharFinding),
                    'captureLabel' => $captureLabel,
                    'modelTitle' => $modelTitle,
                    'position' => [
                        'x' => (float) $marker->position_x,
                        'y' => (float) $marker->position_y,
                        'z' => (float) $marker->position_z,
                    ],
                    'normal' => $this->viewerVector(
                        $marker->normal_x,
                        $marker->normal_y,
                        $marker->normal_z
                    ),
                    'cameraPosition' => $this->viewerVectorFromPayload($marker->camera_position),
                    'cameraTarget' => $this->viewerVectorFromPayload($marker->camera_target),
                ];
            })
            ->values();
    }

    private function viewerVector($x, $y, $z): ?array
    {
        if ($x === null || $y === null || $z === null) {
            return null;
        }

        return [
            'x' => (float) $x,
            'y' => (float) $y,
            'z' => (float) $z,
        ];
    }

    private function viewerVectorFromPayload($payload): ?array
    {
        if (is_string($payload)) {
            $payload = json_decode($payload, true);
        }

        if (!is_array($payload)) {
            return null;
        }

        return $this->viewerVector(
            $payload['x'] ?? null,
            $payload['y'] ?? null,
            $payload['z'] ?? null
        );
    }

    private function pharFindingLabel(?PHARFinding $finding): ?string
    {
        if (!$finding) {
            return null;
        }

        return $finding->task_question
            ?: $finding->observed_condition
            ?: $finding->category
            ?: 'Finding #' . $finding->id;
    }

    private function propertyDiagnosisNumber(Inspection $inspection): int
    {
        return max(1, Inspection::where('property_id', $inspection->property_id)
            ->where('id', '<=', $inspection->id)
            ->count());
    }

    private function activeBuildingSystems()
    {
        return BuildingSystem::with([
            'subsystems' => fn ($query) => $query
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name'),
            'subsystems.components' => fn ($query) => $query
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name'),
        ])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    private function canCreatePharFindingFromMarker(Inspection $inspection): bool
    {
        if ($inspection->findings_report_shared_at) {
            return false;
        }

        return !in_array($inspection->status, [
            'findings_shared',
            'client_committed',
            'estimation_in_progress',
            'estimation_completed',
            'quotation_shared',
            'quotation_approved',
            'completed',
            'approved',
        ], true);
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

    private function createMatterPakProcessingJob(TwinSourceFile $sourceFile, array $metadata = []): TwinProcessingJob
    {
        return TwinProcessingJob::create([
            'property_id' => $sourceFile->property_id,
            'inspection_id' => $sourceFile->inspection_id,
            'capture_session_id' => $sourceFile->capture_session_id,
            'source_file_id' => $sourceFile->id,
            'spatial_model_id' => $sourceFile->spatial_model_id,
            'created_by' => Auth::id(),
            'processor' => 'blender',
            'job_type' => 'matterpak_obj_to_glb',
            'queue_name' => config('digital_twin.processing.queue', 'digital-twin'),
            'status' => 'queued',
            'input_storage_disk' => $sourceFile->storage_disk ?: config('digital_twin.disk', config('filesystems.default', 'local')),
            'input_storage_path' => $sourceFile->storage_path,
            'timeout_seconds' => config('digital_twin.processing.timeout_seconds', 3600),
            'metadata' => array_merge([
                'matterpak_source_file_id' => $sourceFile->id,
            ], $metadata),
        ]);
    }

    private function dispatchMatterPakProcessingJob(TwinProcessingJob $processingJob): void
    {
        ProcessMatterPakToGlb::dispatch($processingJob->id)
            ->onQueue(config('digital_twin.processing.queue', 'digital-twin'));
    }

    private function activeMatterPakProcessingJob(TwinSourceFile $sourceFile): ?TwinProcessingJob
    {
        return TwinProcessingJob::query()
            ->where('source_file_id', $sourceFile->id)
            ->where('job_type', 'matterpak_obj_to_glb')
            ->whereIn('status', ['queued', 'processing'])
            ->latest('id')
            ->first();
    }

    private function isMatterPakArchive(TwinSourceFile $sourceFile): bool
    {
        return $sourceFile->file_role === 'matterpak_archive'
            && strtolower((string) $sourceFile->extension) === 'zip'
            && ($sourceFile->metadata['package_type'] ?? null) === 'matterpak';
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

    private function hasMatterportHostedReference(array $validated): bool
    {
        if (($validated['provider'] ?? null) !== 'matterport') {
            return false;
        }

        return !empty($validated['provider_model_id']) || !empty($validated['external_url']);
    }

    private function attachMatterportHostedTour(
        Inspection $inspection,
        CaptureSession $captureSession,
        array $validated,
        ?string $thumbnailPath,
        array $metadata,
        ?string $displayName = null
    ): SpatialModel {
        return app(MatterportHostedTourService::class)->attachToCaptureSession($inspection, $captureSession, [
            'provider_model_id' => $validated['provider_model_id'] ?? null,
            'external_url' => $validated['external_url'] ?? null,
            'display_name' => $displayName ?? ($validated['display_name'] ?? null),
            'status' => $validated['status'],
            'is_primary' => !empty($validated['is_primary']),
            'accuracy_class' => $validated['accuracy_class'] ?? null,
            'thumbnail_path' => $thumbnailPath,
            'captured_at' => $validated['captured_at'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'created_by' => Auth::id(),
            'metadata' => $metadata,
        ]);
    }

    private function isMatterportHostedClassification(array $validated, array $classification): bool
    {
        return ($validated['provider'] ?? null) === 'matterport'
            && ($classification['spatial_source_type'] ?? null) === 'hosted_tour';
    }

    private function matterportHostedDisplayName(array $validated): string
    {
        $displayName = trim((string) ($validated['display_name'] ?? ''));

        if ($displayName === '') {
            return 'Matterport hosted walkthrough';
        }

        if (preg_match('/\b(tour|walkthrough|showcase)\b/i', $displayName)) {
            return $displayName;
        }

        return $displayName . ' walkthrough';
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
