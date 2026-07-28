<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMatterportModelRequest;
use App\Models\CaptureSession;
use App\Models\Inspection;
use App\Models\MatterportModel;
use App\Models\SpatialModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MatterportModelController extends Controller
{
    public function store(StoreMatterportModelRequest $request, Inspection $inspection)
    {
        $inspection->loadMissing('property');

        if (!$inspection->property) {
            return back()->with('error', 'Attach a property to this inspection before adding a Matterport model.');
        }

        $validated = $request->validated();

        DB::transaction(function () use ($inspection, $validated) {
            $captureSession = CaptureSession::create([
                'property_id' => $inspection->property_id,
                'inspection_id' => $inspection->id,
                'captured_by' => Auth::id(),
                'provider' => 'matterport',
                'capture_type' => 'hosted_tour',
                'device_name' => 'Matterport',
                'status' => 'ready',
                'captured_at' => $validated['scanned_at'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'metadata' => [
                    'provider_model_id' => $validated['model_sid'],
                    'reference_url' => $validated['model_url'] ?? null,
                ],
            ]);

            SpatialModel::where('inspection_id', $inspection->id)->update(['is_primary' => false]);

            $spatialModel = SpatialModel::create([
                'property_id' => $inspection->property_id,
                'inspection_id' => $inspection->id,
                'capture_session_id' => $captureSession->id,
                'created_by' => Auth::id(),
                'provider' => 'matterport',
                'source_type' => 'hosted_tour',
                'display_name' => $validated['model_name'] ?? 'Matterport hosted walkthrough',
                'runtime_format' => 'hosted',
                'original_format' => 'matterport_sid',
                'provider_model_id' => $validated['model_sid'],
                'external_url' => $validated['model_url'] ?? null,
                'status' => $validated['status'] ?? 'active',
                'processing_status' => 'ready',
                'is_primary' => true,
                'processed_at' => now(),
                'metadata' => [
                    'legacy_matterport_record' => true,
                    'thumbnail_url' => $validated['thumbnail_url'] ?? null,
                ],
            ]);

            MatterportModel::updateOrCreate(
                ['inspection_id' => $inspection->id],
                [
                    'property_id' => $inspection->property_id,
                    'spatial_model_id' => $spatialModel->id,
                    'created_by' => Auth::id(),
                    'model_sid' => $validated['model_sid'],
                    'model_name' => $validated['model_name'] ?? null,
                    'model_url' => $validated['model_url'] ?? null,
                    'thumbnail_url' => $validated['thumbnail_url'] ?? null,
                    'status' => $validated['status'] ?? 'active',
                    'scanned_at' => $validated['scanned_at'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                ]
            );
        });

        return redirect()
            ->route('inspections.digital-twin', $inspection)
            ->with('success', 'Matterport source added to the vendor-neutral digital twin.');
    }
}
