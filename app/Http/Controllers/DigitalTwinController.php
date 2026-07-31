<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIssueMarkerRequest;
use App\Http\Requests\StoreSpatialModelRequest;
use App\Models\CaptureSession;
use App\Models\Inspection;
use App\Models\IssueMarker;
use App\Models\MatterportModel;
use App\Models\PHARFinding;
use App\Models\Project;
use App\Models\Property;
use App\Models\SpatialModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

    public function showProperty(Property $property)
    {
        $this->authorizePropertyTwinAccess($property);

        $inspection = $this->resolvePropertyTwinInspection($property);

        if (!$inspection) {
            return redirect()
                ->route('client.properties.show', $property)
                ->with('info', 'The property twin will appear after ETOGO captures the first property facts source.');
        }

        $inspection->loadMissing('property');

        return $this->show($inspection);
    }

    public function storeSpatialModel(StoreSpatialModelRequest $request, Inspection $inspection): RedirectResponse
    {
        $inspection->loadMissing('property');

        if (!$inspection->property) {
            return back()->with('error', 'Attach a property to this inspection before adding digital twin data.');
        }

        $validated = $request->validated();
        $disk = config('filesystems.default', 'public');

        $sourceFilePath = $request->hasFile('source_file')
            ? $request->file('source_file')->storeAs(
                "digital-twins/{$inspection->property_id}/sources",
                Str::random(40) . '.' . strtolower($request->file('source_file')->getClientOriginalExtension()),
                $disk
            )
            : null;

        $thumbnailPath = $request->hasFile('thumbnail_file')
            ? $request->file('thumbnail_file')->store("digital-twins/{$inspection->property_id}/thumbnails", $disk)
            : null;

        $spatialModel = DB::transaction(function () use ($inspection, $validated, $sourceFilePath, $thumbnailPath, $disk) {
            if (!empty($validated['is_primary'])) {
                SpatialModel::where('inspection_id', $inspection->id)->update(['is_primary' => false]);
            }

            $captureSession = CaptureSession::create([
                'property_id' => $inspection->property_id,
                'inspection_id' => $inspection->id,
                'captured_by' => Auth::id(),
                'provider' => $validated['provider'],
                'capture_type' => $validated['capture_type'],
                'device_name' => $validated['device_name'] ?? null,
                'device_serial' => $validated['device_serial'] ?? null,
                'status' => 'ready',
                'accuracy_class' => $validated['accuracy_class'] ?? null,
                'captured_at' => $validated['captured_at'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'metadata' => [
                    'original_format' => $validated['original_format'] ?? null,
                    'runtime_format' => $validated['runtime_format'] ?? null,
                    'external_url' => $validated['external_url'] ?? null,
                ],
            ]);

            $spatialModel = SpatialModel::create([
                'property_id' => $inspection->property_id,
                'inspection_id' => $inspection->id,
                'capture_session_id' => $captureSession->id,
                'created_by' => Auth::id(),
                'provider' => $validated['provider'],
                'source_type' => $validated['source_type'],
                'display_name' => $validated['display_name'] ?? null,
                'runtime_format' => $validated['runtime_format'] ?? null,
                'original_format' => $validated['original_format'] ?? null,
                'provider_model_id' => $validated['provider_model_id'] ?? null,
                'external_url' => $validated['external_url'] ?? null,
                'file_path' => $sourceFilePath,
                'thumbnail_path' => $thumbnailPath,
                'status' => $validated['status'],
                'processing_status' => 'ready',
                'is_primary' => !empty($validated['is_primary']),
                'accuracy_class' => $validated['accuracy_class'] ?? null,
                'processed_at' => now(),
                'metadata' => [
                    'notes' => $validated['notes'] ?? null,
                    'stored_as_vendor_neutral_twin_source' => true,
                    'storage_owner' => 'cloud',
                    'storage_disk' => $sourceFilePath ? $disk : null,
                    'cloud_reference_url' => $validated['external_url'] ?? null,
                ],
            ]);

            if ($spatialModel->provider === 'matterport' && $spatialModel->provider_model_id) {
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

            return $spatialModel;
        });

        return redirect()
            ->route('inspections.digital-twin', $inspection)
            ->with('success', 'Cloud-hosted capture source added to the property digital twin.');
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
            'room_name' => $validated['room_name'] ?? null,
            'surface_label' => $validated['surface_label'] ?? null,
            'source_reference' => $validated['source_reference'] ?? null,
            'confidence' => $validated['confidence'] ?? null,
            'description' => $validated['description'] ?? null,
            'metadata' => [
                'created_from' => 'digital_twin_marker_form',
            ],
        ]);

        return redirect()
            ->route('inspections.digital-twin', $inspection)
            ->with('success', "Issue marker {$marker->id} added to the property digital twin.");
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

    private function resolvePropertyTwinInspection(Property $property): ?Inspection
    {
        $property->loadMissing('user');

        $inspection = $property->inspections()
            ->whereIn('status', [
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
            ])
            ->latest('id')
            ->first();

        if ($inspection) {
            return $inspection;
        }

        $user = Auth::user();
        if (!$user || $user->hasRole('Client')) {
            return null;
        }

        return DB::transaction(function () use ($property) {
            $project = Project::firstOrCreate(
                ['property_id' => $property->id],
                [
                    'title' => 'Property Facts & Diagnosis - ' . $property->property_name,
                    'description' => 'Property facts and diagnosis workflow for ' . $property->property_name,
                    'status' => 'pending',
                    'user_id' => $property->user_id,
                    'managed_by' => $property->project_manager_id,
                    'created_by' => Auth::id(),
                    'project_number' => 'PRJ-' . strtoupper(Str::random(8)),
                ]
            );

            if (($property->status ?? null) === 'registered') {
                $property->update(['status' => 'awaiting_inspection']);
            }

            return Inspection::create([
                'property_id' => $property->id,
                'project_id' => $project->id,
                'inspector_id' => $property->inspector_id,
                'assigned_by' => Auth::id(),
                'status' => 'scheduled',
                'inspection_fee_status' => 'pending',
                'property_code' => $property->property_code,
                'property_name' => $property->property_name,
                'property_address_snapshot' => trim(($property->property_address ?? '') . ', ' . ($property->city ?? '')),
                'property_type_snapshot' => $property->type,
                'residential_units_snapshot' => (int) ($property->number_of_units ?: $property->residential_units ?: 0),
                'commercial_sqft_snapshot' => $property->square_footage_interior,
                'mixed_use_weight_snapshot' => $property->mixed_use_commercial_weight,
            ]);
        });
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
