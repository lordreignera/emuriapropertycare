<?php

namespace App\Jobs;

use App\Models\CaptureSession;
use App\Models\SpatialModel;
use App\Models\TwinProcessingJob;
use App\Models\TwinSourceFile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;
use ZipArchive;

class ProcessMatterPakToGlb implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $timeout;

    private ?string $storageRunId = null;

    public function __construct(public int $processingJobId)
    {
        $this->timeout = (int) config('digital_twin.processing.timeout_seconds', 3600);
    }

    public function handle(): void
    {
        $processingJob = TwinProcessingJob::with(['sourceFile', 'inspection', 'property'])->findOrFail($this->processingJobId);
        $sourceFile = $processingJob->sourceFile;

        if (!$sourceFile instanceof TwinSourceFile || !$sourceFile->storage_path) {
            throw new RuntimeException('MatterPak source file is missing from Laravel storage metadata.');
        }

        $diskName = $sourceFile->storage_disk ?: config('digital_twin.disk', 'local');
        $disk = Storage::disk($diskName);

        if (!$disk->exists($sourceFile->storage_path)) {
            throw new RuntimeException('MatterPak ZIP could not be found in storage.');
        }

        $workDirectory = $this->makeWorkDirectory($processingJob->id);
        $this->storageRunId = 'run-' . $processingJob->id . '-' . Str::lower(Str::random(8));

        try {
            $this->markProcessing($processingJob, $sourceFile);

            $archivePath = $workDirectory . DIRECTORY_SEPARATOR . 'matterpak.zip';
            $readStream = $disk->readStream($sourceFile->storage_path);

            if (!$readStream) {
                throw new RuntimeException('MatterPak ZIP could not be opened from storage.');
            }

            $writeStream = fopen($archivePath, 'wb');
            if (!$writeStream) {
                fclose($readStream);
                throw new RuntimeException('MatterPak ZIP could not be written to temporary processing storage.');
            }

            stream_copy_to_stream($readStream, $writeStream);
            fclose($readStream);
            fclose($writeStream);

            $extractDirectory = $workDirectory . DIRECTORY_SEPARATOR . 'extracted';
            File::ensureDirectoryExists($extractDirectory);
            $disk->deleteDirectory($this->extractedSourceStorageBaseDirectory($sourceFile));
            $this->ensureLocalStorageDirectory($diskName, $this->extractedSourceStorageDirectory($sourceFile, $processingJob));

            $extracted = $this->extractMatterPak($archivePath, $extractDirectory, $sourceFile, $processingJob, $diskName);
            $objCandidate = collect($extracted)
                ->where('role', 'obj_mesh')
                ->sortByDesc(fn (array $file) => $file['record']->file_size ?? 0)
                ->first();
            $pointCloudPreview = $this->createPointCloudPreview($extracted, $sourceFile, $processingJob, $diskName, $workDirectory);

            if (!$objCandidate) {
                throw new RuntimeException('MatterPak ZIP was preserved, but no OBJ mesh was found for GLB conversion.');
            }

            $conversionDiagnostics = [
                'quality_profile' => 'matterpak_visual_preserve',
                'obj_source' => [
                    'source_file_id' => $objCandidate['record']->id ?? null,
                    'relative_path' => $objCandidate['record']->relative_path ?? null,
                    'size_bytes' => $objCandidate['record']->file_size ?? null,
                ],
                'texture_sources' => $this->sourceTextureDiagnostics($extracted),
                'material_textures' => $this->materialTextureDiagnostics((string) $objCandidate['local_path']),
            ];
            $this->storeConversionDiagnostics($processingJob, $conversionDiagnostics, $pointCloudPreview);

            $glbPath = $workDirectory . DIRECTORY_SEPARATOR . 'matterpak-model.glb';
            $this->convertObjToGlb((string) $objCandidate['local_path'], $glbPath, $workDirectory, $processingJob);

            if (!File::exists($glbPath) || File::size($glbPath) < 1) {
                throw new RuntimeException('Blender finished without producing a GLB file.');
            }

            $conversionDiagnostics['glb_output'] = [
                'format' => 'glb',
                'size_bytes' => File::size($glbPath),
            ];
            $this->storeConversionDiagnostics($processingJob, $conversionDiagnostics, $pointCloudPreview);

            $outputPath = $this->processedModelStoragePath($sourceFile);
            $glbStream = fopen($glbPath, 'rb');
            $disk->put($outputPath, $glbStream);
            if (is_resource($glbStream)) {
                fclose($glbStream);
            }

            $spatialModel = $this->createReadySpatialModel(
                $sourceFile,
                $processingJob,
                $outputPath,
                $diskName,
                $extracted,
                $pointCloudPreview,
                $conversionDiagnostics
            );

            DB::transaction(function () use ($processingJob, $sourceFile, $spatialModel, $diskName, $outputPath, $pointCloudPreview) {
                $sourceFile->update([
                    'spatial_model_id' => $spatialModel->id,
                    'processing_status' => 'ready',
                    'processing_error' => null,
                ]);

                TwinSourceFile::where('parent_source_file_id', $sourceFile->id)
                    ->whereIn('file_role', ['obj_mesh', 'material_library', 'texture'])
                    ->update([
                        'spatial_model_id' => $spatialModel->id,
                        'processing_status' => 'ready',
                        'processing_error' => null,
                    ]);

                $previewRecord = $pointCloudPreview['record'] ?? null;
                if ($previewRecord instanceof TwinSourceFile) {
                    $previewRecord->update([
                        'spatial_model_id' => $spatialModel->id,
                        'processing_status' => 'ready',
                        'processing_error' => null,
                    ]);
                }

                CaptureSession::whereKey($sourceFile->capture_session_id)->update([
                    'status' => 'ready',
                ]);

                $processingJob->update([
                    'spatial_model_id' => $spatialModel->id,
                    'status' => 'ready',
                    'output_storage_disk' => $diskName,
                    'output_storage_path' => $outputPath,
                    'processing_error' => null,
                    'completed_at' => now(),
                ]);
            });
        } catch (Throwable $exception) {
            $this->markFailed($processingJob, $sourceFile, $exception);

            throw $exception;
        } finally {
            File::deleteDirectory($workDirectory);
        }
    }

    public function failed(Throwable $exception): void
    {
        $processingJob = TwinProcessingJob::with('sourceFile')->find($this->processingJobId);

        if (!$processingJob) {
            return;
        }

        $this->markFailed($processingJob, $processingJob->sourceFile, $exception);
    }

    private function markProcessing(TwinProcessingJob $processingJob, TwinSourceFile $sourceFile): void
    {
        DB::transaction(function () use ($processingJob, $sourceFile) {
            $processingJob->update([
                'status' => 'processing',
                'attempts' => ((int) $processingJob->attempts) + 1,
                'started_at' => $processingJob->started_at ?: now(),
                'processing_error' => null,
            ]);

            $sourceFile->update([
                'processing_status' => 'processing',
                'processing_error' => null,
            ]);

            CaptureSession::whereKey($sourceFile->capture_session_id)->update([
                'status' => 'processing',
            ]);
        });
    }

    private function markFailed(TwinProcessingJob $processingJob, ?TwinSourceFile $sourceFile, Throwable $exception): void
    {
        $message = Str::limit($exception->getMessage(), 6000, '');

        DB::transaction(function () use ($processingJob, $sourceFile, $message) {
            $processingJob->update([
                'status' => 'failed',
                'processing_error' => $message,
                'completed_at' => now(),
            ]);

            if ($sourceFile) {
                $sourceFile->update([
                    'processing_status' => 'failed',
                    'processing_error' => $message,
                ]);

                CaptureSession::whereKey($sourceFile->capture_session_id)->update([
                    'status' => 'failed',
                ]);
            }
        });
    }

    /**
     * @return array<int, array{record: TwinSourceFile, local_path: string, role: string}>
     */
    private function extractMatterPak(
        string $archivePath,
        string $extractDirectory,
        TwinSourceFile $parentSourceFile,
        TwinProcessingJob $processingJob,
        string $diskName
    ): array
    {
        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('PHP ZipArchive is required before MatterPak packages can be processed.');
        }

        $zip = new ZipArchive();
        $openResult = $zip->open($archivePath);

        if ($openResult !== true) {
            throw new RuntimeException('MatterPak ZIP could not be opened for extraction.');
        }

        $extracted = [];
        $disk = Storage::disk($diskName);

        try {
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $rawName = (string) $zip->getNameIndex($index);
                $relativePath = $this->normalizeZipPath($rawName);

                if ($relativePath === null) {
                    continue;
                }

                $localPath = $extractDirectory . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
                File::ensureDirectoryExists(dirname($localPath));

                $inputStream = $zip->getStream($rawName);
                if (!$inputStream) {
                    throw new RuntimeException("MatterPak file {$relativePath} could not be read from the ZIP.");
                }

                $outputStream = fopen($localPath, 'wb');
                if (!$outputStream) {
                    fclose($inputStream);
                    throw new RuntimeException("MatterPak file {$relativePath} could not be written to temporary storage.");
                }

                stream_copy_to_stream($inputStream, $outputStream);
                fclose($inputStream);
                fclose($outputStream);

                $extension = strtolower((string) pathinfo($relativePath, PATHINFO_EXTENSION));
                $extension = $this->safeExtension($extension);
                $role = $this->classifyMatterPakFile($relativePath, $extension);
                $storagePath = $this->extractedSourceStoragePath($parentSourceFile, $processingJob, $relativePath);
                $this->deleteStorageParentFileConflicts($disk, $storagePath);
                $storedStream = fopen($localPath, 'rb');
                $disk->put($storagePath, $storedStream);
                if (is_resource($storedStream)) {
                    fclose($storedStream);
                }

                $record = TwinSourceFile::updateOrCreate(
                    [
                        'parent_source_file_id' => $parentSourceFile->id,
                        'relative_path' => $relativePath,
                    ],
                    [
                        'property_id' => $parentSourceFile->property_id,
                        'inspection_id' => $parentSourceFile->inspection_id,
                        'capture_session_id' => $parentSourceFile->capture_session_id,
                        'spatial_model_id' => null,
                        'uploaded_by' => $parentSourceFile->uploaded_by,
                        'storage_disk' => $diskName,
                        'storage_path' => $storagePath,
                        'original_filename' => basename($relativePath),
                        'stored_filename' => basename($storagePath),
                        'extension' => $extension ?: 'other',
                        'mime_type' => $this->detectMimeType($localPath),
                        'file_size' => File::size($localPath),
                        'checksum_sha256' => hash_file('sha256', $localPath),
                        'source_type' => $this->sourceTypeForMatterPakFile($role, $extension),
                        'file_role' => $role,
                        'processing_status' => $this->initialStatusForMatterPakFile($role),
                        'processing_error' => null,
                        'metadata' => [
                            'package_type' => 'matterpak',
                            'parent_archive_id' => $parentSourceFile->id,
                            'extracted_from' => $parentSourceFile->original_filename,
                        ],
                    ]
                );

                $extracted[] = [
                    'record' => $record,
                    'local_path' => $localPath,
                    'role' => $role,
                ];
            }
        } finally {
            $zip->close();
        }

        return $extracted;
    }

    private function convertObjToGlb(string $objPath, string $glbPath, string $workingDirectory, TwinProcessingJob $processingJob): void
    {
        $blenderBinary = $this->blenderBinaryPath();

        $scriptPath = $workingDirectory . DIRECTORY_SEPARATOR . 'convert_obj_to_glb.py';
        File::put($scriptPath, $this->blenderConversionScript());

        $process = new Process([
            $blenderBinary,
            '--background',
            '--python',
            $scriptPath,
            '--',
            $objPath,
            $glbPath,
        ], $workingDirectory, null, null, (float) config('digital_twin.processing.timeout_seconds', 3600));

        $startedAt = microtime(true);

        try {
            $process->run();
        } catch (Throwable $exception) {
            $diagnostics = [
                'status' => 'exception',
                'duration_seconds' => round(microtime(true) - $startedAt, 2),
                'message' => Str::limit($exception->getMessage(), 2000, ''),
                'obj_filename' => basename($objPath),
                'glb_created' => File::exists($glbPath),
                'glb_size_bytes' => File::exists($glbPath) ? File::size($glbPath) : 0,
            ];

            $this->storeBlenderDiagnostics($processingJob, $diagnostics);

            throw new RuntimeException('Blender OBJ-to-GLB conversion could not run. ' . $diagnostics['message'], previous: $exception);
        }

        $diagnostics = $this->blenderProcessDiagnostics($process, $objPath, $glbPath, microtime(true) - $startedAt);
        $this->storeBlenderDiagnostics($processingJob, $diagnostics);

        if (!$process->isSuccessful()) {
            throw new RuntimeException($this->blenderFailureMessage($diagnostics));
        }
    }

    private function blenderProcessDiagnostics(Process $process, string $objPath, string $glbPath, float $durationSeconds): array
    {
        $stderr = trim($process->getErrorOutput());
        $stdout = trim($process->getOutput());
        $exitCode = $process->getExitCode();

        return [
            'status' => $process->isSuccessful() ? 'ready' : 'failed',
            'exit_code' => $exitCode,
            'exit_code_text' => $process->getExitCodeText(),
            'duration_seconds' => round($durationSeconds, 2),
            'stdout' => Str::limit($stdout, 3000, ''),
            'stderr' => Str::limit($stderr, 3000, ''),
            'obj_filename' => basename($objPath),
            'glb_created' => File::exists($glbPath),
            'glb_size_bytes' => File::exists($glbPath) ? File::size($glbPath) : 0,
            'likely_worker_memory_kill' => $this->isLikelyMemoryKill($exitCode, $stderr . "\n" . $stdout),
        ];
    }

    private function blenderFailureMessage(array $diagnostics): string
    {
        $parts = ['Blender OBJ-to-GLB conversion failed.'];
        $exitCode = $diagnostics['exit_code'] ?? null;
        $exitText = trim((string) ($diagnostics['exit_code_text'] ?? ''));
        $output = trim((string) ($diagnostics['stderr'] ?? '')) ?: trim((string) ($diagnostics['stdout'] ?? ''));

        if ($exitCode !== null) {
            $parts[] = 'Exit code ' . $exitCode . ($exitText !== '' ? " ({$exitText})." : '.');
        }

        if (!empty($diagnostics['likely_worker_memory_kill'])) {
            $parts[] = 'Linux likely killed Blender during export, which is usually worker memory pressure. Retry after adding swap or using a larger converter Droplet.';
        }

        if ($output !== '') {
            $parts[] = 'Blender output: ' . $output;
        } else {
            $parts[] = 'Blender returned no stdout/stderr. Check the worker journal and kernel logs for timeout or out-of-memory kill entries.';
        }

        return Str::limit(implode(' ', $parts), 6000, '');
    }

    private function isLikelyMemoryKill(?int $exitCode, string $output): bool
    {
        $output = strtolower($output);

        return in_array($exitCode, [9, 137], true)
            || str_contains($output, 'killed')
            || str_contains($output, 'out of memory')
            || str_contains($output, 'cannot allocate memory')
            || str_contains($output, 'oom');
    }

    private function storeBlenderDiagnostics(TwinProcessingJob $processingJob, array $diagnostics): void
    {
        $metadata = $processingJob->metadata ?: [];
        $conversionDiagnostics = $metadata['conversion_diagnostics'] ?? [];
        $conversionDiagnostics['blender'] = $diagnostics;
        $metadata['conversion_diagnostics'] = $conversionDiagnostics;

        $processingJob->update(['metadata' => $metadata]);
    }

    private function blenderBinaryPath(): string
    {
        $blenderBinary = trim((string) config('digital_twin.blender.binary', 'blender'));

        if ($blenderBinary === '') {
            throw new RuntimeException('Blender binary is not configured. Set DIGITAL_TWIN_BLENDER_BINARY on the queue worker.');
        }

        if ($this->isAbsolutePath($blenderBinary) || $blenderBinary === 'blender') {
            return $blenderBinary;
        }

        return base_path($blenderBinary);
    }

    private function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, '/')
            || str_starts_with($path, '\\\\')
            || preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) === 1;
    }

    private function createPointCloudPreview(
        array $extracted,
        TwinSourceFile $parentSourceFile,
        TwinProcessingJob $processingJob,
        string $diskName,
        string $workDirectory
    ): ?array {
        $pointCloudCandidate = collect($extracted)
            ->where('role', 'colour_point_cloud')
            ->sortByDesc(fn (array $file) => $file['record']->file_size ?? 0)
            ->first();

        $maxPoints = max(0, (int) config('digital_twin.matterpak.point_cloud_preview_points', 30000));

        if (!$pointCloudCandidate || $maxPoints < 1) {
            return null;
        }

        $preview = $this->sampleXyzPointCloud((string) $pointCloudCandidate['local_path'], $maxPoints);

        if (($preview['point_count'] ?? 0) < 1) {
            return null;
        }

        $previewPath = $workDirectory . DIRECTORY_SEPARATOR . 'point-cloud-preview.json';
        $encoded = json_encode($preview, JSON_UNESCAPED_SLASHES);

        if ($encoded === false) {
            throw new RuntimeException('MatterPak point cloud preview could not be encoded.');
        }

        File::put($previewPath, $encoded);

        $outputPath = $this->pointCloudPreviewStoragePath($parentSourceFile);
        $disk = Storage::disk($diskName);
        $previewStream = fopen($previewPath, 'rb');
        $disk->put($outputPath, $previewStream);
        if (is_resource($previewStream)) {
            fclose($previewStream);
        }

        $record = TwinSourceFile::updateOrCreate(
            [
                'parent_source_file_id' => $parentSourceFile->id,
                'relative_path' => 'generated/point-cloud-preview.json',
            ],
            [
                'property_id' => $parentSourceFile->property_id,
                'inspection_id' => $parentSourceFile->inspection_id,
                'capture_session_id' => $parentSourceFile->capture_session_id,
                'spatial_model_id' => null,
                'uploaded_by' => $parentSourceFile->uploaded_by,
                'storage_disk' => $diskName,
                'storage_path' => $outputPath,
                'original_filename' => 'point-cloud-preview.json',
                'stored_filename' => basename($outputPath),
                'extension' => 'json',
                'mime_type' => 'application/json',
                'file_size' => File::size($previewPath),
                'checksum_sha256' => hash_file('sha256', $previewPath),
                'source_type' => 'other',
                'file_role' => 'point_cloud_preview',
                'processing_status' => 'ready',
                'processing_error' => null,
                'metadata' => [
                    'package_type' => 'matterpak',
                    'parent_archive_id' => $parentSourceFile->id,
                    'generated_from' => $pointCloudCandidate['record']->relative_path ?? null,
                    'source_point_count' => $preview['source_point_count'],
                    'sampled_point_count' => $preview['point_count'],
                    'max_preview_points' => $maxPoints,
                    'bounds' => $preview['bounds'],
                    'has_color' => $preview['has_color'],
                ],
            ]
        );

        return [
            'record' => $record,
            'source_file_id' => $pointCloudCandidate['record']->id ?? null,
            'preview_source_file_id' => $record->id,
            'storage_path' => $outputPath,
            'source_point_count' => $preview['source_point_count'],
            'sampled_point_count' => $preview['point_count'],
            'max_preview_points' => $maxPoints,
            'bounds' => $preview['bounds'],
            'has_color' => $preview['has_color'],
        ];
    }

    private function sampleXyzPointCloud(string $xyzPath, int $maxPoints): array
    {
        $handle = fopen($xyzPath, 'rb');

        if (!$handle) {
            throw new RuntimeException('MatterPak XYZ point cloud could not be opened for preview generation.');
        }

        $points = [];
        $sourcePointCount = 0;
        $hasColor = false;
        $boundsMin = [INF, INF, INF];
        $boundsMax = [-INF, -INF, -INF];

        try {
            while (($line = fgets($handle)) !== false) {
                $line = trim($line);

                if ($line === '' || str_starts_with($line, '#')) {
                    continue;
                }

                $parts = preg_split('/[\s,]+/', $line) ?: [];

                if (count($parts) < 3 || !is_numeric($parts[0]) || !is_numeric($parts[1]) || !is_numeric($parts[2])) {
                    continue;
                }

                $x = (float) $parts[0];
                $y = (float) $parts[1];
                $z = (float) $parts[2];

                if (!is_finite($x) || !is_finite($y) || !is_finite($z)) {
                    continue;
                }

                $sourcePointCount++;
                $boundsMin = [
                    min($boundsMin[0], $x),
                    min($boundsMin[1], $y),
                    min($boundsMin[2], $z),
                ];
                $boundsMax = [
                    max($boundsMax[0], $x),
                    max($boundsMax[1], $y),
                    max($boundsMax[2], $z),
                ];

                $r = $this->normalizedRgbValue($parts[3] ?? null);
                $g = $this->normalizedRgbValue($parts[4] ?? null);
                $b = $this->normalizedRgbValue($parts[5] ?? null);
                $hasColor = $hasColor || count($parts) >= 6;
                $point = [round($x, 4), round($y, 4), round($z, 4), $r, $g, $b];

                if (count($points) < $maxPoints) {
                    $points[] = $point;
                    continue;
                }

                $slot = mt_rand(0, $sourcePointCount - 1);
                if ($slot < $maxPoints) {
                    $points[$slot] = $point;
                }
            }
        } finally {
            fclose($handle);
        }

        return [
            'version' => 1,
            'format' => 'matterpak_xyz_preview',
            'sampling' => 'reservoir',
            'source_point_count' => $sourcePointCount,
            'point_count' => count($points),
            'has_color' => $hasColor,
            'bounds' => $sourcePointCount > 0 ? [
                'min' => array_map(fn ($value) => round((float) $value, 4), $boundsMin),
                'max' => array_map(fn ($value) => round((float) $value, 4), $boundsMax),
            ] : null,
            'points' => array_values($points),
        ];
    }

    private function normalizedRgbValue($value): int
    {
        if (!is_numeric($value)) {
            return 255;
        }

        return max(0, min(255, (int) round((float) $value)));
    }

    private function materialTextureDiagnostics(string $objPath): array
    {
        $materialFiles = [];
        $textureReferences = [];
        $resolvedTextures = [];
        $missingTextures = [];

        $objHandle = fopen($objPath, 'rb');
        if (!$objHandle) {
            return [
                'material_file_count' => 0,
                'texture_reference_count' => 0,
                'resolved_texture_count' => 0,
                'resolved_texture_total_bytes' => 0,
                'missing_texture_count' => 0,
                'missing_textures' => [],
            ];
        }

        try {
            while (($line = fgets($objHandle)) !== false) {
                $line = trim($line);

                if (!str_starts_with($line, 'mtllib ')) {
                    continue;
                }

                $materialPath = trim(substr($line, 7));
                if ($materialPath !== '') {
                    $materialFiles[] = $this->resolveSiblingPath($objPath, $materialPath);
                }
            }
        } finally {
            fclose($objHandle);
        }

        foreach (array_unique($materialFiles) as $materialFile) {
            if (!File::exists($materialFile)) {
                continue;
            }

            $mtlHandle = fopen($materialFile, 'rb');
            if (!$mtlHandle) {
                continue;
            }

            try {
                while (($line = fgets($mtlHandle)) !== false) {
                    $line = trim($line);

                    if (!preg_match('/^map_[A-Za-z0-9_]+\s+(.+)$/', $line, $matches)) {
                        continue;
                    }

                    $tokens = preg_split('/\s+/', trim($matches[1])) ?: [];
                    $texturePath = end($tokens) ?: null;

                    if (!$texturePath || str_starts_with($texturePath, '-')) {
                        continue;
                    }

                    $resolvedTexture = $this->resolveSiblingPath($materialFile, $texturePath);
                    $textureReferences[$texturePath] = true;

                    if (!File::exists($resolvedTexture)) {
                        $missingTextures[$texturePath] = true;
                        continue;
                    }

                    $resolvedTextures[$texturePath] = File::size($resolvedTexture);
                }
            } finally {
                fclose($mtlHandle);
            }
        }

        return [
            'material_file_count' => count(array_unique($materialFiles)),
            'texture_reference_count' => count($textureReferences),
            'resolved_texture_count' => count($resolvedTextures),
            'resolved_texture_total_bytes' => array_sum($resolvedTextures),
            'missing_texture_count' => count($missingTextures),
            'missing_textures' => array_slice(array_keys($missingTextures), 0, 25),
        ];
    }

    private function sourceTextureDiagnostics(array $extracted): array
    {
        $textureFiles = collect($extracted)
            ->filter(fn (array $file) => ($file['role'] ?? null) === 'texture')
            ->values();
        $largestTexture = $textureFiles
            ->sortByDesc(fn (array $file) => (int) ($file['record']->file_size ?? 0))
            ->first();

        return [
            'texture_file_count' => $textureFiles->count(),
            'texture_total_bytes' => (int) $textureFiles->sum(fn (array $file) => (int) ($file['record']->file_size ?? 0)),
            'largest_texture_bytes' => $largestTexture ? (int) ($largestTexture['record']->file_size ?? 0) : 0,
            'texture_extensions' => $textureFiles
                ->map(fn (array $file) => strtolower((string) ($file['record']->extension ?? '')))
                ->filter()
                ->countBy()
                ->sortKeys()
                ->all(),
        ];
    }

    private function storeConversionDiagnostics(
        TwinProcessingJob $processingJob,
        array $conversionDiagnostics,
        ?array $pointCloudPreview
    ): void {
        $pointCloudPreviewMetadata = $pointCloudPreview;

        if (is_array($pointCloudPreviewMetadata)) {
            unset($pointCloudPreviewMetadata['record']);
        }

        $metadata = $processingJob->metadata ?: [];
        $metadata['conversion_diagnostics'] = $conversionDiagnostics;

        if (is_array($pointCloudPreviewMetadata)) {
            $metadata['point_cloud_preview'] = $pointCloudPreviewMetadata;
        }

        $processingJob->update(['metadata' => $metadata]);
    }

    private function resolveSiblingPath(string $sourcePath, string $relativePath): string
    {
        $relativePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, trim($relativePath));

        return dirname($sourcePath) . DIRECTORY_SEPARATOR . $relativePath;
    }

    private function createReadySpatialModel(
        TwinSourceFile $sourceFile,
        TwinProcessingJob $processingJob,
        string $outputPath,
        string $diskName,
        array $extracted,
        ?array $pointCloudPreview,
        array $conversionDiagnostics
    ): SpatialModel {
        $metadata = $sourceFile->metadata ?: [];
        $isPrimary = (bool) ($metadata['is_primary'] ?? false);
        $pointCloudPreviewMetadata = $pointCloudPreview;

        if (is_array($pointCloudPreviewMetadata)) {
            unset($pointCloudPreviewMetadata['record']);
        }

        if ($isPrimary) {
            SpatialModel::where('inspection_id', $sourceFile->inspection_id)->update(['is_primary' => false]);
        }

        $spatialModel = SpatialModel::firstOrNew([
            'capture_session_id' => $sourceFile->capture_session_id,
            'provider' => 'matterport',
            'source_type' => 'runtime_3d_model',
            'original_format' => 'matterpak_zip',
        ]);

        $spatialModel->fill([
            'property_id' => $sourceFile->property_id,
            'inspection_id' => $sourceFile->inspection_id,
            'capture_session_id' => $sourceFile->capture_session_id,
            'created_by' => $sourceFile->uploaded_by,
            'provider' => 'matterport',
            'source_type' => 'runtime_3d_model',
            'display_name' => $metadata['display_name']
                ?: config('digital_twin.matterpak.generated_model_name', 'MatterPak browser-ready GLB'),
            'runtime_format' => 'glb',
            'original_format' => 'matterpak_zip',
            'file_path' => $outputPath,
            'status' => 'active',
            'processing_status' => 'ready',
            'is_primary' => $isPrimary,
            'processed_at' => now(),
            'metadata' => [
                'storage_disk' => $diskName,
                'storage_owner' => 'laravel_storage',
                'generated_from' => 'matterpak_zip',
                'source_file_id' => $sourceFile->id,
                'processing_job_id' => $processingJob->id,
                'original_source_path' => $sourceFile->storage_path,
                'extracted_file_count' => count($extracted),
                'extracted_roles' => collect($extracted)->pluck('role')->countBy()->all(),
                'point_cloud_preview' => $pointCloudPreviewMetadata,
                'conversion_diagnostics' => $conversionDiagnostics,
            ],
        ]);

        $spatialModel->save();

        return $spatialModel;
    }

    private function normalizeZipPath(string $path): ?string
    {
        $path = str_replace('\\', '/', trim($path));
        $path = ltrim($path, '/');

        if ($path === '' || str_ends_with($path, '/')) {
            return null;
        }

        $segments = explode('/', $path);
        $basename = basename($path);

        if (($segments[0] ?? '') === '__MACOSX' || $basename === '.DS_Store' || str_starts_with($basename, '._')) {
            return null;
        }

        if (pathinfo($basename, PATHINFO_EXTENSION) === '') {
            return null;
        }

        if (preg_match('/(^|\/)\.\.(\/|$)/', $path) || preg_match('/^[A-Za-z]:\//', $path)) {
            throw new RuntimeException('MatterPak ZIP contains an unsafe file path.');
        }

        return $path;
    }

    private function safeExtension(string $extension): string
    {
        if ($extension === '' || !preg_match('/^[a-z0-9]{1,12}$/', $extension)) {
            return 'other';
        }

        return $extension;
    }

    private function classifyMatterPakFile(string $relativePath, string $extension): string
    {
        $path = strtolower($relativePath);
        $isReadme = str_contains($path, 'readme');
        $isPlan = $this->isMatterPakPlanPath($path);

        if (in_array($extension, config('digital_twin.matterpak.model_extensions', ['obj']), true)) {
            return 'obj_mesh';
        }

        if (in_array($extension, config('digital_twin.matterpak.material_extensions', ['mtl']), true)) {
            return 'material_library';
        }

        if (in_array($extension, config('digital_twin.matterpak.point_cloud_extensions', ['xyz']), true)) {
            return 'colour_point_cloud';
        }

        if (in_array($extension, config('digital_twin.matterpak.document_extensions', ['pdf']), true)) {
            if ($isReadme || !$isPlan) {
                return 'supporting_source';
            }

            return $this->isReflectedCeilingPlanPath($path) ? 'reflected_ceiling_plan' : 'floor_plan';
        }

        if (in_array($extension, config('digital_twin.matterpak.texture_extensions', []), true)) {
            if ($isPlan) {
                return $this->isReflectedCeilingPlanPath($path) ? 'reflected_ceiling_plan' : 'floor_plan';
            }

            return 'texture';
        }

        return 'supporting_source';
    }

    private function sourceTypeForMatterPakFile(string $role, string $extension): string
    {
        return match ($role) {
            'obj_mesh', 'material_library' => 'obj_bundle',
            'texture' => 'image',
            'floor_plan', 'reflected_ceiling_plan' => $extension === 'pdf' ? 'pdf' : 'image',
            'point_cloud_preview' => 'other',
            'supporting_source' => in_array($extension, config('digital_twin.matterpak.document_extensions', ['pdf']), true) ? 'pdf' : 'other',
            default => 'other',
        };
    }

    private function isMatterPakPlanPath(string $path): bool
    {
        foreach (config('digital_twin.matterpak.plan_keywords', []) as $keyword) {
            if (str_contains($path, (string) $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function isReflectedCeilingPlanPath(string $path): bool
    {
        return str_contains($path, 'ceiling') || str_contains($path, 'rcp');
    }

    private function initialStatusForMatterPakFile(string $role): string
    {
        return in_array($role, ['obj_mesh', 'material_library', 'texture'], true)
            ? 'awaiting_processing'
            : 'uploaded';
    }

    private function extractedSourceStoragePath(TwinSourceFile $sourceFile, TwinProcessingJob $processingJob, string $relativePath): string
    {
        $extension = $this->safeExtension(strtolower((string) pathinfo($relativePath, PATHINFO_EXTENSION)));
        $basename = Str::slug(pathinfo($relativePath, PATHINFO_FILENAME)) ?: 'source';
        $storedFilename = $basename . '-' . substr(sha1($relativePath), 0, 16) . '.' . ($extension === 'other' ? 'bin' : $extension);

        return $this->extractedSourceStorageDirectory($sourceFile, $processingJob) . "/{$storedFilename}";
    }

    private function extractedSourceStorageDirectory(TwinSourceFile $sourceFile, TwinProcessingJob $processingJob): string
    {
        $runId = $this->storageRunId ?: 'run-' . $processingJob->id;

        return $this->extractedSourceStorageBaseDirectory($sourceFile) . "/{$runId}";
    }

    private function extractedSourceStorageBaseDirectory(TwinSourceFile $sourceFile): string
    {
        return "properties/{$sourceFile->property_id}/twins/inspections/{$sourceFile->inspection_id}/extracted-source-files/matterpak-{$sourceFile->id}";
    }

    private function deleteStorageParentFileConflicts($disk, string $storagePath): void
    {
        $segments = explode('/', trim($storagePath, '/'));
        array_pop($segments);

        $parent = '';
        foreach ($segments as $segment) {
            $parent = $parent === '' ? $segment : "{$parent}/{$segment}";

            try {
                if ($disk->exists($parent)) {
                    $disk->delete($parent);
                }
            } catch (Throwable) {
                // Some adapters return false for file existence on directories; keep walking.
            }
        }
    }

    private function ensureLocalStorageDirectory(string $diskName, string $directory): void
    {
        if (config("filesystems.disks.{$diskName}.driver") !== 'local') {
            return;
        }

        $root = rtrim((string) config("filesystems.disks.{$diskName}.root"), "\\/");
        if ($root === '') {
            return;
        }

        File::ensureDirectoryExists($root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, trim($directory, '/')));
    }

    private function processedModelStoragePath(TwinSourceFile $sourceFile): string
    {
        return "properties/{$sourceFile->property_id}/twins/inspections/{$sourceFile->inspection_id}/processed/matterpak-{$sourceFile->id}/model.glb";
    }

    private function pointCloudPreviewStoragePath(TwinSourceFile $sourceFile): string
    {
        return "properties/{$sourceFile->property_id}/twins/inspections/{$sourceFile->inspection_id}/processed/matterpak-{$sourceFile->id}/point-cloud-preview.json";
    }

    private function extensionSuffix(string $path): string
    {
        $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));

        return $extension ? '.' . $extension : '';
    }

    private function detectMimeType(string $localPath): ?string
    {
        try {
            return File::mimeType($localPath) ?: null;
        } catch (Throwable) {
            return null;
        }
    }

    private function makeWorkDirectory(int $processingJobId): string
    {
        $basePath = rtrim((string) config('digital_twin.processing.temporary_path'), "\\/");
        $workDirectory = $basePath . DIRECTORY_SEPARATOR . 'matterpak-' . $processingJobId . '-' . Str::random(8);

        File::ensureDirectoryExists($workDirectory);

        return $workDirectory;
    }

    private function blenderConversionScript(): string
    {
        return <<<'PY'
import bpy
import os
import sys

args = sys.argv
if "--" not in args or len(args) < args.index("--") + 3:
    raise RuntimeError("Missing OBJ and GLB paths")

obj_path, glb_path = args[args.index("--") + 1:args.index("--") + 3]
obj_directory = os.path.dirname(obj_path)
if obj_directory:
    os.chdir(obj_directory)

bpy.ops.object.select_all(action="SELECT")
bpy.ops.object.delete()

if hasattr(bpy.ops.wm, "obj_import"):
    bpy.ops.wm.obj_import(filepath=obj_path)
else:
    bpy.ops.import_scene.obj(filepath=obj_path)

mesh_objects = [obj for obj in bpy.context.scene.objects if obj.type == "MESH"]
if not mesh_objects:
    raise RuntimeError("OBJ import produced no mesh objects")

def set_image_colorspace(image, colorspace):
    try:
        image.colorspace_settings.name = colorspace
    except Exception:
        pass

def image_node_is_color(node):
    for output in node.outputs:
        for link in output.links:
            target = (link.to_socket.name + " " + link.to_node.name).lower()
            if any(term in target for term in ["normal", "roughness", "metallic", "occlusion", "bump"]):
                return False
            if any(term in target for term in ["base color", "diffuse", "emission", "color"]):
                return True

    return True

for material in bpy.data.materials:
    material.use_backface_culling = False
    if hasattr(material, "show_transparent_back"):
        material.show_transparent_back = True
    material.use_nodes = True

    if not material.node_tree:
        continue

    for node in material.node_tree.nodes:
        if node.bl_idname != "ShaderNodeTexImage" or not node.image:
            continue

        node.interpolation = "Linear"
        set_image_colorspace(node.image, "sRGB" if image_node_is_color(node) else "Non-Color")

os.makedirs(os.path.dirname(glb_path), exist_ok=True)

supported_export_options = {
    property.identifier
    for property in bpy.ops.export_scene.gltf.get_rna_type().properties
}
export_args = {
    "filepath": glb_path,
    "export_format": "GLB",
}
quality_preserving_args = {
    "export_materials": "EXPORT",
    "export_image_format": "AUTO",
    "export_keep_originals": True,
    "export_image_quality": 100,
    "export_jpeg_quality": 100,
    "export_texcoords": True,
    "export_normals": True,
    "export_tangents": True,
    "export_yup": True,
    "export_apply": False,
    "export_draco_mesh_compression_enable": False,
}

for option, value in quality_preserving_args.items():
    if option in supported_export_options:
        export_args[option] = value

bpy.ops.export_scene.gltf(**export_args)
PY;
    }
}
