<?php

namespace App\Services;

use App\Models\SpatialModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\Process\Process;

class PointCloudConversionService
{
    private const PDAL_REQUIRED_EXTENSIONS = ['e57', 'pts', 'ptx', 'xyz'];
    private const DIRECT_POTREE_EXTENSIONS = ['las', 'laz'];

    public function convertToPotree(SpatialModel $sourceModel): SpatialModel
    {
        if (!$sourceModel->file_path) {
            throw new RuntimeException('This point cloud source does not have an uploaded file.');
        }

        if (!$sourceModel->isRawPointCloud()) {
            throw new RuntimeException('Only raw point-cloud sources can be converted to Potree tiles.');
        }

        $diskName = config('filesystems.default', 'public');
        $disk = Storage::disk($diskName);
        $extension = strtolower((string) $sourceModel->detected_extension);

        if (!in_array($extension, array_merge(self::PDAL_REQUIRED_EXTENSIONS, self::DIRECT_POTREE_EXTENSIONS), true)) {
            throw new RuntimeException("The .{$extension} format is not supported by this point-cloud converter yet.");
        }

        if (!$this->localPathIsAvailable($disk)) {
            throw new RuntimeException('Point-cloud conversion currently requires a local filesystem disk. Use local/public storage or add cloud download staging first.');
        }

        $sourcePath = $disk->path($sourceModel->file_path);
        if (!is_file($sourcePath)) {
            throw new RuntimeException('The uploaded point-cloud source file could not be found in storage.');
        }

        $pdalBinary = (string) config('digital_twin.pdal_binary', 'pdal');
        $potreeBinary = (string) config('digital_twin.potree_converter_binary', 'PotreeConverter');
        $timeout = (int) config('digital_twin.conversion_timeout', 3600);

        if (in_array($extension, self::PDAL_REQUIRED_EXTENSIONS, true)) {
            $this->assertCommandAvailable($pdalBinary, 'PDAL', 'DIGITAL_TWIN_PDAL_BINARY');
        }

        $this->assertCommandAvailable($potreeBinary, 'PotreeConverter', 'DIGITAL_TWIN_POTREE_CONVERTER_BINARY');

        $workingRelative = "digital-twins/{$sourceModel->property_id}/converted/spatial-model-{$sourceModel->id}";
        $workingAbsolute = $disk->path($workingRelative);
        $stagingRelative = "{$workingRelative}/staging";
        $stagingAbsolute = $disk->path($stagingRelative);
        $potreeRelative = "{$workingRelative}/potree";
        $potreeAbsolute = $disk->path($potreeRelative);

        $disk->makeDirectory($stagingRelative);
        $disk->makeDirectory($potreeRelative);

        $potreeInput = $sourcePath;
        if (in_array($extension, self::PDAL_REQUIRED_EXTENSIONS, true)) {
            $normalizedRelative = "{$stagingRelative}/normalized-{$sourceModel->id}.laz";
            $normalizedAbsolute = $disk->path($normalizedRelative);

            $this->runProcess(
                [$pdalBinary, 'translate', $sourcePath, $normalizedAbsolute],
                $timeout,
                'PDAL could not normalize the point cloud.'
            );

            $potreeInput = $normalizedAbsolute;
        }

        $this->runProcess(
            [$potreeBinary, $potreeInput, '-o', $potreeAbsolute, '--generate-page', 'viewer'],
            $timeout,
            'PotreeConverter could not create browser tiles.'
        );

        $viewerRelative = $this->findGeneratedViewer($disk, $potreeRelative)
            ?: $this->writeFallbackViewer($disk, $potreeRelative, $sourceModel);

        return SpatialModel::updateOrCreate(
            [
                'property_id' => $sourceModel->property_id,
                'inspection_id' => $sourceModel->inspection_id,
                'capture_session_id' => $sourceModel->capture_session_id,
                'source_type' => 'point_cloud_tiles',
                'runtime_format' => 'potree',
                'provider_model_id' => 'converted-from-spatial-model-' . $sourceModel->id,
            ],
            [
                'created_by' => Auth::id() ?: $sourceModel->created_by,
                'provider' => $sourceModel->provider,
                'display_name' => ($sourceModel->display_name ?: 'Point cloud') . ' - Potree tiles',
                'original_format' => 'potree',
                'external_url' => null,
                'file_path' => $viewerRelative,
                'thumbnail_path' => $sourceModel->thumbnail_path,
                'status' => 'active',
                'processing_status' => 'ready',
                'is_primary' => false,
                'accuracy_class' => $sourceModel->accuracy_class,
                'coordinate_transform' => $sourceModel->coordinate_transform,
                'processed_at' => now(),
                'metadata' => [
                    'source_spatial_model_id' => $sourceModel->id,
                    'source_file_path' => $sourceModel->file_path,
                    'potree_output_path' => $potreeRelative,
                    'converter' => [
                        'pdal' => $pdalBinary,
                        'potree_converter' => $potreeBinary,
                    ],
                ],
            ]
        );
    }

    private function assertCommandAvailable(string $binary, string $label, string $envKey): void
    {
        try {
            $process = new Process([$binary, '--version']);
            $process->setTimeout(15);
            $process->run();
        } catch (\Throwable $e) {
            throw new RuntimeException("{$label} is not installed or not available on PATH. Configure {$envKey} if it is installed elsewhere.");
        }
    }

    private function runProcess(array $command, int $timeout, string $failureMessage): void
    {
        $process = new Process($command);
        $process->setTimeout($timeout);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new RuntimeException($failureMessage . ' ' . trim($process->getErrorOutput() ?: $process->getOutput()));
        }
    }

    private function findGeneratedViewer($disk, string $potreeRelative): ?string
    {
        foreach ($disk->files($potreeRelative) as $file) {
            if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'html') {
                return $file;
            }
        }

        return null;
    }

    private function writeFallbackViewer($disk, string $potreeRelative, SpatialModel $sourceModel): string
    {
        $metadataFile = collect($disk->allFiles($potreeRelative))
            ->first(fn ($file) => strtolower(pathinfo($file, PATHINFO_BASENAME)) === 'metadata.json');

        $viewerRelative = "{$potreeRelative}/viewer.html";
        $title = htmlspecialchars(($sourceModel->display_name ?: 'Converted point cloud'), ENT_QUOTES, 'UTF-8');
        $metadataUrl = $metadataFile ? Storage::disk(config('filesystems.default', 'public'))->url($metadataFile) : '#';

        $disk->put($viewerRelative, <<<HTML
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$title}</title>
    <style>
        body { margin: 0; font-family: Arial, sans-serif; background: #07111f; color: #e5eefb; }
        main { min-height: 100vh; display: grid; place-items: center; padding: 32px; box-sizing: border-box; text-align: center; }
        section { max-width: 720px; }
        a { color: #8ec5ff; }
    </style>
</head>
<body>
    <main>
        <section>
            <h1>{$title}</h1>
            <p>Potree conversion output is ready. Add the Potree viewer library to render this dataset interactively.</p>
            <p><a href="{$metadataUrl}">Open converted metadata</a></p>
        </section>
    </main>
</body>
</html>
HTML);

        return $viewerRelative;
    }

    private function localPathIsAvailable($disk): bool
    {
        return method_exists($disk, 'path');
    }
}
