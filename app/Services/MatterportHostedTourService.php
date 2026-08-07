<?php

namespace App\Services;

use App\Models\CaptureSession;
use App\Models\Inspection;
use App\Models\MatterportModel;
use App\Models\SpatialModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MatterportHostedTourService
{
    public function createWithCaptureSession(Inspection $inspection, array $data): SpatialModel
    {
        return DB::transaction(function () use ($inspection, $data) {
            $modelSid = $this->modelSid($data);
            $modelUrl = $this->modelUrl($data);

            $captureSession = CaptureSession::create([
                'property_id' => $inspection->property_id,
                'inspection_id' => $inspection->id,
                'captured_by' => $data['created_by'] ?? Auth::id(),
                'provider' => 'matterport',
                'capture_type' => 'hosted_tour',
                'device_name' => $data['device_name'] ?? 'Matterport',
                'device_serial' => $data['device_serial'] ?? null,
                'status' => 'ready',
                'accuracy_class' => $data['accuracy_class'] ?? null,
                'captured_at' => $data['captured_at'] ?? ($data['scanned_at'] ?? null),
                'notes' => $data['notes'] ?? null,
                'metadata' => [
                    'provider_model_id' => $modelSid,
                    'reference_url' => $modelUrl,
                ],
            ]);

            return $this->attachToCaptureSession($inspection, $captureSession, $data);
        });
    }

    public function attachToCaptureSession(Inspection $inspection, CaptureSession $captureSession, array $data): SpatialModel
    {
        $modelSid = $this->modelSid($data);
        $modelUrl = $this->modelUrl($data);
        $displayName = $this->displayName($data);
        $isPrimary = (bool) ($data['is_primary'] ?? true);

        if ($isPrimary) {
            SpatialModel::where('inspection_id', $inspection->id)->update(['is_primary' => false]);
        }

        $spatialModel = SpatialModel::create([
            'property_id' => $inspection->property_id,
            'inspection_id' => $inspection->id,
            'capture_session_id' => $captureSession->id,
            'created_by' => $data['created_by'] ?? Auth::id(),
            'provider' => 'matterport',
            'source_type' => 'hosted_tour',
            'display_name' => $displayName,
            'runtime_format' => 'hosted',
            'original_format' => 'matterport_sid',
            'provider_model_id' => $modelSid,
            'external_url' => $modelUrl,
            'thumbnail_path' => $data['thumbnail_path'] ?? null,
            'status' => $data['status'] ?? 'active',
            'processing_status' => 'ready',
            'is_primary' => $isPrimary,
            'accuracy_class' => $data['accuracy_class'] ?? null,
            'processed_at' => now(),
            'metadata' => array_filter(array_merge([
                'legacy_matterport_record' => true,
                'thumbnail_url' => $data['thumbnail_url'] ?? null,
            ], $data['metadata'] ?? []), fn ($value) => $value !== null),
        ]);

        $captureSession->update([
            'metadata' => array_merge($captureSession->metadata ?: [], [
                'hosted_walkthrough_spatial_model_id' => $spatialModel->id,
                'provider_model_id' => $modelSid,
                'reference_url' => $modelUrl,
            ]),
        ]);

        if ($modelSid) {
            MatterportModel::updateOrCreate(
                ['inspection_id' => $inspection->id],
                [
                    'property_id' => $inspection->property_id,
                    'spatial_model_id' => $spatialModel->id,
                    'created_by' => $data['created_by'] ?? Auth::id(),
                    'model_sid' => $modelSid,
                    'model_name' => $displayName,
                    'model_url' => $modelUrl,
                    'thumbnail_url' => $data['thumbnail_url'] ?? null,
                    'status' => $data['status'] ?? 'active',
                    'scanned_at' => $data['scanned_at'] ?? ($data['captured_at'] ?? null),
                    'notes' => $data['notes'] ?? null,
                ]
            );
        }

        return $spatialModel;
    }

    private function modelSid(array $data): ?string
    {
        return $data['model_sid'] ?? ($data['provider_model_id'] ?? null);
    }

    private function modelUrl(array $data): ?string
    {
        return $data['model_url'] ?? ($data['external_url'] ?? null);
    }

    private function displayName(array $data): string
    {
        $displayName = trim((string) ($data['display_name'] ?? ($data['model_name'] ?? '')));

        return $displayName !== '' ? $displayName : 'Matterport hosted walkthrough';
    }
}
