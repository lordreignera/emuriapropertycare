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

            $extracted = $this->extractMatterPak($archivePath, $extractDirectory, $sourceFile, $diskName);
            $objCandidate = collect($extracted)->firstWhere('role', 'obj_mesh');

            if (!$objCandidate) {
                throw new RuntimeException('MatterPak ZIP was preserved, but no OBJ mesh was found for GLB conversion.');
            }

            $glbPath = $workDirectory . DIRECTORY_SEPARATOR . 'matterpak-model.glb';
            $this->convertObjToGlb((string) $objCandidate['local_path'], $glbPath, $workDirectory);

            if (!File::exists($glbPath) || File::size($glbPath) < 1) {
                throw new RuntimeException('Blender finished without producing a GLB file.');
            }

            $outputPath = $this->processedModelStoragePath($sourceFile);
            $glbStream = fopen($glbPath, 'rb');
            $disk->put($outputPath, $glbStream);
            if (is_resource($glbStream)) {
                fclose($glbStream);
            }

            $spatialModel = $this->createReadySpatialModel($sourceFile, $processingJob, $outputPath, $diskName, $extracted);

            DB::transaction(function () use ($processingJob, $sourceFile, $spatialModel, $diskName, $outputPath) {
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
    private function extractMatterPak(string $archivePath, string $extractDirectory, TwinSourceFile $parentSourceFile, string $diskName): array
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
                $role = $this->classifyMatterPakFile($relativePath, $extension);
                $storagePath = $this->extractedSourceStoragePath($parentSourceFile, $relativePath);
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

    private function convertObjToGlb(string $objPath, string $glbPath, string $workingDirectory): void
    {
        $blenderBinary = trim((string) config('digital_twin.blender.binary', 'blender'));

        if ($blenderBinary === '') {
            throw new RuntimeException('Blender binary is not configured. Set DIGITAL_TWIN_BLENDER_BINARY on the queue worker.');
        }

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

        $process->run();

        if (!$process->isSuccessful()) {
            $error = trim($process->getErrorOutput()) ?: trim($process->getOutput());

            throw new RuntimeException('Blender OBJ-to-GLB conversion failed. ' . Str::limit($error, 3000, ''));
        }
    }

    private function createReadySpatialModel(
        TwinSourceFile $sourceFile,
        TwinProcessingJob $processingJob,
        string $outputPath,
        string $diskName,
        array $extracted
    ): SpatialModel {
        $metadata = $sourceFile->metadata ?: [];
        $isPrimary = (bool) ($metadata['is_primary'] ?? false);

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

        if (preg_match('/(^|\/)\.\.(\/|$)/', $path) || preg_match('/^[A-Za-z]:\//', $path)) {
            throw new RuntimeException('MatterPak ZIP contains an unsafe file path.');
        }

        return $path;
    }

    private function classifyMatterPakFile(string $relativePath, string $extension): string
    {
        $path = strtolower($relativePath);

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
            return str_contains($path, 'ceiling') || str_contains($path, 'rcp')
                ? 'reflected_ceiling_plan'
                : 'floor_plan';
        }

        if (in_array($extension, config('digital_twin.matterpak.texture_extensions', []), true)) {
            foreach (config('digital_twin.matterpak.plan_keywords', []) as $keyword) {
                if (str_contains($path, (string) $keyword)) {
                    return str_contains($path, 'ceiling') || str_contains($path, 'rcp')
                        ? 'reflected_ceiling_plan'
                        : 'floor_plan';
                }
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
            default => 'other',
        };
    }

    private function initialStatusForMatterPakFile(string $role): string
    {
        return in_array($role, ['obj_mesh', 'material_library', 'texture'], true)
            ? 'awaiting_processing'
            : 'uploaded';
    }

    private function extractedSourceStoragePath(TwinSourceFile $sourceFile, string $relativePath): string
    {
        $relativePath = collect(explode('/', $relativePath))
            ->map(fn (string $segment) => Str::slug(pathinfo($segment, PATHINFO_FILENAME)) . $this->extensionSuffix($segment))
            ->filter()
            ->implode('/');

        return "properties/{$sourceFile->property_id}/twins/inspections/{$sourceFile->inspection_id}/source/matterpak-{$sourceFile->id}/{$relativePath}";
    }

    private function processedModelStoragePath(TwinSourceFile $sourceFile): string
    {
        return "properties/{$sourceFile->property_id}/twins/inspections/{$sourceFile->inspection_id}/processed/matterpak-{$sourceFile->id}/model.glb";
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
if "--" not in args:
    raise RuntimeError("Missing OBJ and GLB paths")

obj_path, glb_path = args[args.index("--") + 1:args.index("--") + 3]

bpy.ops.object.select_all(action="SELECT")
bpy.ops.object.delete()

try:
    bpy.ops.wm.obj_import(filepath=obj_path)
except Exception:
    bpy.ops.import_scene.obj(filepath=obj_path)

os.makedirs(os.path.dirname(glb_path), exist_ok=True)
bpy.ops.export_scene.gltf(filepath=glb_path, export_format="GLB")
PY;
    }
}
