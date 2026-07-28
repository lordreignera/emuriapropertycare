<?php

namespace App\Jobs;

use App\Models\SpatialModel;
use App\Services\PointCloudConversionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ConvertSpatialModelPointCloud implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout;

    public function __construct(public int $spatialModelId)
    {
        $this->timeout = (int) config('digital_twin.conversion_timeout', 3600);
        $this->onQueue('digital-twin');
    }

    public function handle(PointCloudConversionService $converter): void
    {
        $sourceModel = SpatialModel::find($this->spatialModelId);

        if (!$sourceModel) {
            return;
        }

        $sourceModel->update([
            'processing_status' => 'processing',
            'metadata' => array_merge($sourceModel->metadata ?? [], [
                'conversion_started_at' => now()->toIso8601String(),
                'conversion_error' => null,
            ]),
        ]);

        try {
            $convertedModel = $converter->convertToPotree($sourceModel->fresh());

            $sourceModel->fresh()->update([
                'processing_status' => 'ready',
                'processed_at' => now(),
                'metadata' => array_merge($sourceModel->fresh()->metadata ?? [], [
                    'conversion_completed_at' => now()->toIso8601String(),
                    'converted_spatial_model_id' => $convertedModel->id,
                    'conversion_error' => null,
                ]),
            ]);
        } catch (\Throwable $e) {
            $sourceModel->fresh()->update([
                'processing_status' => 'failed',
                'metadata' => array_merge($sourceModel->fresh()->metadata ?? [], [
                    'conversion_failed_at' => now()->toIso8601String(),
                    'conversion_error' => $e->getMessage(),
                ]),
            ]);

            Log::warning('Digital twin point-cloud conversion failed', [
                'spatial_model_id' => $this->spatialModelId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
