@extends($layout)

@section('title', 'Property Digital Twin')
@section('header', 'Property Digital Twin')

@section('content')
@php
    $propertyName = $property?->property_name ?: 'Property';
    $propertyDiagnosisNumber = (int) ($propertyDiagnosisNumber ?? 0);
    $inspectionReference = 'Diagnosis #' . ($propertyDiagnosisNumber ?: $inspection->id);
    $inspectionRecordReference = 'Inspection record #' . $inspection->id;
    $processingJobs = $processingJobs ?? collect();
    $buildingSystems = $buildingSystems ?? collect();
    $viewerMarkers = collect($viewerMarkers ?? []);
    $supportedUploadExtensions = collect(config('digital_twin.supported_extensions', []))
        ->map(fn ($extension) => '.' . ltrim($extension, '.'))
        ->implode(',');
    $primaryModel = $spatialModels->firstWhere('is_primary', true) ?? $spatialModels->first();
    $legacyMatterportModel = $legacyMatterportModel ?? null;
    $statusLabel = str_replace('_', ' ', ucfirst((string) ($inspection->status ?? 'scheduled')));
    $viewerSources = $spatialModels->map(function ($model) use ($inspection) {
        $externalUrl = $model->external_url;
        $storedFileUrl = $model->file_path
            ? route('inspections.digital-twin.models.file', [$inspection, $model])
            : null;

        if ($model->provider === 'matterport' && $model->provider_model_id) {
            $externalUrl = $externalUrl ?: 'https://my.matterport.com/show/?' . http_build_query([
                'm' => $model->provider_model_id,
                'play' => '1',
            ]);
        }

        return [
            'id' => 'model-' . $model->id,
            'title' => $model->display_name ?: $model->viewer_label,
            'spatialModelId' => $model->id,
            'captureSessionId' => $model->capture_session_id,
            'provider' => $model->provider,
            'providerLabel' => $model->provider_label,
            'sourceTypeLabel' => $model->source_type_label,
            'viewerType' => $model->viewer_type,
            'fileUrl' => $storedFileUrl,
            'thumbnailUrl' => null,
            'externalUrl' => $externalUrl,
            'downloadUrl' => $storedFileUrl ?: $externalUrl,
            'runtimeFormat' => $model->runtime_format,
            'originalFormat' => $model->original_format,
            'accuracyClass' => $model->accuracy_class,
            'isPrimary' => (bool) $model->is_primary,
            'extension' => $model->detected_extension,
            'processingStatus' => $model->processing_status,
            'icon' => match ($model->viewer_type) {
                'hosted_tour' => 'mdi-home-analytics',
                'three_model' => 'mdi-cube-outline',
                'potree' => 'mdi-dots-triangle',
                'point_cloud_preview' => 'mdi-dots-triangle',
                'image', 'panorama' => 'mdi-image-outline',
                'pdf' => 'mdi-file-pdf-box',
                default => 'mdi-layers-outline',
            },
        ];
    })->values();

    $sourceViewerType = function ($sourceFile) {
        if (!in_array($sourceFile->processing_status, ['ready', 'uploaded'], true)) {
            return 'awaiting_processing';
        }

        $extension = strtolower((string) $sourceFile->extension);

        if ($sourceFile->file_role === 'point_cloud_preview' && $extension === 'json') {
            return 'point_cloud_preview';
        }

        if ($sourceFile->source_type === 'panorama' && in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            return 'panorama';
        }

        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            return 'image';
        }

        if ($extension === 'pdf') {
            return 'pdf';
        }

        return 'stored_evidence';
    };

    $sourceRoleLabel = function (?string $role) {
        return match ($role) {
            'floor_plan' => 'Floor plan',
            'reflected_ceiling_plan' => 'Ceiling plan',
            'texture' => 'Texture map',
            'point_cloud_preview' => 'Point cloud preview',
            'colour_point_cloud' => 'Point cloud',
            'material_library' => 'Material library',
            'obj_mesh' => 'OBJ mesh',
            'matterpak_archive' => 'MatterPak package',
            'supporting_source' => 'Supporting source',
            default => 'Source file',
        };
    };

    $sourceIcon = function (string $viewerType, ?string $role = null) {
        if ($role === 'floor_plan') {
            return 'mdi-floor-plan';
        }

        if ($role === 'reflected_ceiling_plan') {
            return 'mdi-file-image-outline';
        }

        if ($role === 'texture') {
            return 'mdi-image-multiple-outline';
        }

        if ($role === 'point_cloud_preview') {
            return 'mdi-dots-triangle';
        }

        return match ($viewerType) {
            'three_model' => 'mdi-cube-outline',
            'hosted_tour' => 'mdi-home-analytics',
            'image', 'panorama' => 'mdi-image-outline',
            'pdf' => 'mdi-file-pdf-box',
            'media_gallery' => 'mdi-image-multiple-outline',
            'point_cloud_preview' => 'mdi-dots-triangle',
            'potree' => 'mdi-dots-triangle',
            default => 'mdi-layers-outline',
        };
    };

    $sourceFileTitle = function ($sourceFile) use ($sourceRoleLabel) {
        $basename = basename((string) ($sourceFile->relative_path ?: $sourceFile->original_filename));
        $label = $sourceRoleLabel($sourceFile->file_role);

        if (in_array($sourceFile->file_role, ['floor_plan', 'reflected_ceiling_plan'], true)) {
            return 'MatterPak ' . strtolower($label) . ' - ' . $basename;
        }

        if ($sourceFile->file_role === 'supporting_source') {
            return 'MatterPak document - ' . $basename;
        }

        if ($sourceFile->file_role === 'point_cloud_preview') {
            return 'MatterPak point cloud preview';
        }

        return $sourceFile->relative_path ?: $sourceFile->original_filename;
    };

    $sourceViewerSources = $sourceFiles
        ->filter(fn ($sourceFile) => !$sourceFile->spatial_model_id && !$sourceFile->parent_source_file_id)
        ->map(function ($sourceFile) use ($inspection, $sourceViewerType) {
            $viewerType = $sourceViewerType($sourceFile);
            $downloadUrl = $sourceFile->storage_path
                ? route('inspections.digital-twin.source-files.download', [$inspection, $sourceFile])
                : ($sourceFile->metadata['external_url'] ?? null);

            return [
                'id' => 'source-' . $sourceFile->id,
                'title' => $sourceFile->relative_path ?: $sourceFile->original_filename,
                'spatialModelId' => null,
                'sourceFileId' => $sourceFile->id,
                'captureSessionId' => $sourceFile->capture_session_id,
                'provider' => $sourceFile->metadata['selected_provider'] ?? $sourceFile->captureSession?->provider ?? 'manual_upload',
                'providerLabel' => $sourceFile->captureSession?->provider_label ?? 'Source file',
                'sourceTypeLabel' => $sourceFile->source_type_label,
                'viewerType' => $viewerType,
                'fileUrl' => $downloadUrl,
                'thumbnailUrl' => null,
                'externalUrl' => $sourceFile->metadata['external_url'] ?? null,
                'downloadUrl' => $downloadUrl,
                'runtimeFormat' => null,
                'originalFormat' => $sourceFile->extension,
                'accuracyClass' => $sourceFile->captureSession?->accuracy_class,
                'isPrimary' => false,
                'extension' => $sourceFile->extension,
                'processingStatus' => $sourceFile->processing_status,
                'icon' => 'mdi-layers-outline',
            ];
        })
        ->values();

    $matterPakViewableMediaRoles = ['floor_plan', 'reflected_ceiling_plan', 'point_cloud_preview', 'supporting_source'];
    $matterPakRoleOrder = [
        'floor_plan' => 0,
        'reflected_ceiling_plan' => 1,
        'point_cloud_preview' => 2,
        'supporting_source' => 3,
    ];
    $matterPakMediaSources = $sourceFiles
        ->filter(function ($sourceFile) use ($matterPakViewableMediaRoles, $sourceViewerType) {
            return $sourceFile->parent_source_file_id
                && in_array($sourceFile->file_role, $matterPakViewableMediaRoles, true)
                && in_array($sourceViewerType($sourceFile), ['image', 'pdf', 'point_cloud_preview'], true);
        })
        ->sortBy(fn ($sourceFile) => sprintf(
            '%02d-%s',
            $matterPakRoleOrder[$sourceFile->file_role] ?? 9,
            $sourceFile->relative_path ?: $sourceFile->original_filename
        ))
        ->map(function ($sourceFile) use ($inspection, $sourceViewerType, $sourceRoleLabel, $sourceIcon, $sourceFileTitle) {
            $viewerType = $sourceViewerType($sourceFile);
            $downloadUrl = route('inspections.digital-twin.source-files.download', [$inspection, $sourceFile]);

            return [
                'id' => 'source-' . $sourceFile->id,
                'title' => $sourceFileTitle($sourceFile),
                'spatialModelId' => null,
                'sourceFileId' => $sourceFile->id,
                'captureSessionId' => $sourceFile->capture_session_id,
                'provider' => $sourceFile->captureSession?->provider ?? 'matterport',
                'providerLabel' => $sourceFile->captureSession?->provider_label ?? 'Matterport',
                'sourceTypeLabel' => 'MatterPak ' . $sourceRoleLabel($sourceFile->file_role),
                'viewerType' => $viewerType,
                'fileUrl' => $downloadUrl,
                'thumbnailUrl' => $viewerType === 'image' ? $downloadUrl : null,
                'externalUrl' => null,
                'downloadUrl' => $downloadUrl,
                'runtimeFormat' => null,
                'originalFormat' => $sourceFile->extension,
                'accuracyClass' => $sourceFile->captureSession?->accuracy_class,
                'isPrimary' => false,
                'extension' => $sourceFile->extension,
                'processingStatus' => $sourceFile->processing_status,
                'matterPakMediaRole' => $sourceFile->file_role,
                'icon' => $sourceIcon($viewerType, $sourceFile->file_role),
            ];
        })
        ->values();

    $matterPakTextureGallerySources = $sourceFiles
        ->filter(fn ($sourceFile) => $sourceFile->parent_source_file_id
            && $sourceFile->file_role === 'texture'
            && $sourceFile->storage_path
            && in_array(strtolower((string) $sourceFile->extension), ['jpg', 'jpeg', 'png', 'gif', 'webp'], true))
        ->groupBy(fn ($sourceFile) => (int) $sourceFile->capture_session_id)
        ->map(function ($textureFiles, $captureId) use ($inspection) {
            $firstTexture = $textureFiles->sortBy('relative_path')->first();
            $items = $textureFiles
                ->sortBy(fn ($sourceFile) => $sourceFile->relative_path ?: $sourceFile->original_filename)
                ->values()
                ->map(function ($sourceFile) use ($inspection) {
                    $url = route('inspections.digital-twin.source-files.download', [$inspection, $sourceFile]);

                    return [
                        'id' => 'source-' . $sourceFile->id,
                        'title' => basename((string) ($sourceFile->relative_path ?: $sourceFile->original_filename)),
                        'viewerType' => 'image',
                        'fileUrl' => $url,
                        'downloadUrl' => $url,
                        'extension' => $sourceFile->extension,
                        'fileRole' => $sourceFile->file_role,
                        'fileSize' => $sourceFile->file_size,
                    ];
                })
                ->all();

            return [
                'id' => 'matterpak-textures-capture-' . ($captureId ?: 'none'),
                'title' => 'MatterPak texture maps',
                'spatialModelId' => null,
                'sourceFileId' => null,
                'captureSessionId' => (int) $captureId ?: null,
                'provider' => $firstTexture?->captureSession?->provider ?? 'matterport',
                'providerLabel' => $firstTexture?->captureSession?->provider_label ?? 'Matterport',
                'sourceTypeLabel' => 'MatterPak texture gallery',
                'viewerType' => 'media_gallery',
                'fileUrl' => null,
                'thumbnailUrl' => null,
                'externalUrl' => null,
                'downloadUrl' => null,
                'runtimeFormat' => null,
                'originalFormat' => 'jpg',
                'accuracyClass' => $firstTexture?->captureSession?->accuracy_class,
                'isPrimary' => false,
                'extension' => 'jpg',
                'processingStatus' => 'uploaded',
                'mediaItems' => $items,
                'mediaCount' => count($items),
                'matterPakMediaRole' => 'texture_gallery',
                'icon' => 'mdi-image-multiple-outline',
                'openSourceUrl' => null,
            ];
        })
        ->values();

    $viewerSources = $viewerSources
        ->concat($sourceViewerSources)
        ->concat($matterPakMediaSources)
        ->concat($matterPakTextureGallerySources)
        ->values();

    if ($viewerSources->isEmpty() && $legacyMatterportModel) {
        $viewerSources = collect([[
            'id' => 'legacy-matterport-' . $legacyMatterportModel->id,
            'title' => $legacyMatterportModel->model_name ?: 'Matterport legacy source',
            'provider' => 'matterport',
            'providerLabel' => 'Matterport',
            'sourceTypeLabel' => 'Hosted Tour',
            'viewerType' => 'hosted_tour',
            'fileUrl' => null,
            'thumbnailUrl' => null,
            'externalUrl' => $legacyMatterportModel->showcaseUrl(config('services.matterport.sdk_key')),
            'downloadUrl' => $legacyMatterportModel->showcaseUrl(config('services.matterport.sdk_key')),
            'runtimeFormat' => 'hosted',
            'originalFormat' => 'matterport_sid',
            'accuracyClass' => null,
            'isPrimary' => true,
            'extension' => null,
        ]]);
    }

    $modelsByCapture = $spatialModels->groupBy(fn ($model) => (int) $model->capture_session_id);
    $sourcesByCapture = $sourceFiles->groupBy(fn ($sourceFile) => (int) $sourceFile->capture_session_id);
    $jobsByCapture = $processingJobs->groupBy(fn ($processingJob) => (int) $processingJob->capture_session_id);
    $markersByCapture = $issueMarkers->groupBy(fn ($marker) => (int) $marker->capture_session_id);
    $ungroupedModelCount = $spatialModels->filter(fn ($model) => !$model->capture_session_id)->count();
    $matterPakConvertAction = function ($sourceFile, $jobs) use ($inspection, $canManageDigitalTwin) {
        if (!$canManageDigitalTwin || !$sourceFile || $sourceFile->file_role !== 'matterpak_archive') {
            return null;
        }

        $jobs = collect($jobs);
        $activeJob = $jobs
            ->where('source_file_id', $sourceFile->id)
            ->whereIn('status', ['queued', 'processing'])
            ->sortByDesc('id')
            ->first();
        $latestSourceJob = $jobs
            ->where('source_file_id', $sourceFile->id)
            ->sortByDesc('id')
            ->first();

        if ($activeJob) {
            $isProcessing = $activeJob->status === 'processing';

            return [
                'url' => route('inspections.digital-twin.source-files.convert', [$inspection, $sourceFile]),
                'label' => $isProcessing ? 'Converting' : 'Conversion queued',
                'title' => $isProcessing
                    ? 'Blender conversion is already running for this MatterPak archive.'
                    : 'MatterPak conversion is already queued for this archive.',
                'icon' => 'mdi-progress-clock',
                'disabled' => true,
                'status' => $activeJob->status,
            ];
        }

        $isRetry = $sourceFile->processing_status === 'failed' || $latestSourceJob?->status === 'failed';
        $isReady = $sourceFile->processing_status === 'ready'
            || $sourceFile->spatial_model_id
            || $latestSourceJob?->status === 'ready';

        return [
            'url' => route('inspections.digital-twin.source-files.convert', [$inspection, $sourceFile]),
            'label' => $isRetry ? 'Retry GLB' : ($isReady ? 'Reconvert GLB' : 'Convert to GLB'),
            'title' => $isRetry
                ? 'Retry Blender conversion for this MatterPak archive.'
                : ($isReady
                    ? 'Run Blender again and replace the browser-ready GLB for this same capture.'
                    : 'Start Blender conversion for this MatterPak archive.'),
            'icon' => $isRetry || $isReady ? 'mdi-refresh' : 'mdi-cube-send',
            'disabled' => false,
            'status' => $latestSourceJob?->status ?: $sourceFile->processing_status,
        ];
    };
    $viewerSources = $viewerSources->map(function ($source) use ($inspection, $sourceFiles, $sourcesByCapture, $jobsByCapture, $canCreateIssueMarkers, $matterPakConvertAction) {
        $captureId = (int) ($source['captureSessionId'] ?? 0);
        $spatialModelId = (int) ($source['spatialModelId'] ?? 0);
        $sourceFileId = (int) ($source['sourceFileId'] ?? 0);
        $sessionSources = $captureId ? $sourcesByCapture->get($captureId, collect()) : collect();
        $sessionJobs = $captureId ? $jobsByCapture->get($captureId, collect()) : collect();
        $latestJob = $sessionJobs->sortByDesc('id')->first();
        $primarySource = $sourceFileId ? $sourceFiles->firstWhere('id', $sourceFileId) : null;
        $primarySource = $primarySource ?: ($sessionSources->firstWhere('parent_source_file_id', null) ?: $sessionSources->first());
        $matterPakArchive = $sessionSources->firstWhere('file_role', 'matterpak_archive');
        $convertAction = $matterPakConvertAction($matterPakArchive, $sessionJobs);
        $openSourceUrl = array_key_exists('openSourceUrl', $source)
            ? $source['openSourceUrl']
            : ($primarySource?->storage_path
                ? route('inspections.digital-twin.source-files.download', [$inspection, $primarySource])
                : ($source['externalUrl'] ?? $source['downloadUrl'] ?? null));

        return array_merge($source, [
            'viewUrl' => $spatialModelId
                ? route('inspections.digital-twin', [$inspection, 'model' => $spatialModelId]) . '#digitalTwinViewer'
                : ($captureId
                    ? route('inspections.digital-twin', [$inspection, 'capture' => $captureId]) . '#digitalTwinViewer'
                    : route('inspections.digital-twin', $inspection) . '#digitalTwinViewer'),
            'openSourceUrl' => $openSourceUrl,
            'canAddFinding' => (bool) ($canCreateIssueMarkers && $captureId),
            'addFindingCaptureSessionId' => $captureId ?: null,
            'addFindingSpatialModelId' => $spatialModelId ?: null,
            'addFindingSourceProvider' => $source['provider'] ?? 'manual',
            'addFindingSourceReference' => $source['title'] ?? null,
            'convertUrl' => $convertAction['url'] ?? null,
            'convertLabel' => $convertAction['label'] ?? null,
            'convertTitle' => $convertAction['title'] ?? null,
            'convertIcon' => $convertAction['icon'] ?? null,
            'convertDisabled' => (bool) ($convertAction['disabled'] ?? false),
            'jobLabel' => $latestJob ? ucfirst(str_replace('_', ' ', (string) $latestJob->job_type)) : null,
            'jobStatus' => $latestJob?->status,
            'actionMeta' => trim(implode(' / ', array_filter([
                $source['providerLabel'] ?? null,
                $source['sourceTypeLabel'] ?? null,
                $latestJob?->status ? ucfirst(str_replace('_', ' ', (string) $latestJob->status)) : null,
            ]))),
        ]);
    })->values();
    $requestedCaptureId = request()->integer('capture') ?: null;
    $requestedModelId = request()->integer('model') ?: null;
    $initialViewerSource = $viewerSources->first(function ($source) use ($requestedCaptureId, $requestedModelId) {
        if ($requestedModelId && (int) ($source['spatialModelId'] ?? 0) === $requestedModelId) {
            return true;
        }

        return $requestedCaptureId && (int) ($source['captureSessionId'] ?? 0) === $requestedCaptureId;
    }) ?: ($viewerSources->firstWhere('isPrimary', true) ?: $viewerSources->first());
    $markerLayerCounts = [
        'critical' => $viewerMarkers->where('severity', 'critical')->count(),
        'high' => $viewerMarkers->where('severity', 'high')->count(),
        'medium' => $viewerMarkers->where('severity', 'medium')->count(),
        'low' => $viewerMarkers->where('severity', 'low')->count(),
        'phar' => $viewerMarkers->filter(fn ($marker) => !empty($marker['pharFindingId']))->count(),
        'unlinked' => $viewerMarkers->filter(fn ($marker) => empty($marker['pharFindingId']))->count(),
    ];
    $markerBelongsToViewerSource = function ($marker, $source) {
        $markerModelId = (int) ($marker['spatialModelId'] ?? 0);
        $sourceModelId = (int) ($source['spatialModelId'] ?? 0);

        if ($markerModelId && $sourceModelId) {
            return $markerModelId === $sourceModelId;
        }

        $markerCaptureId = (int) ($marker['captureSessionId'] ?? 0);
        $sourceCaptureId = (int) ($source['captureSessionId'] ?? 0);

        return $markerCaptureId && $sourceCaptureId && $markerCaptureId === $sourceCaptureId;
    };
    $sourceMarkerCount = fn ($source) => $viewerMarkers
        ->filter(fn ($marker) => $markerBelongsToViewerSource($marker, $source))
        ->count();
    $iniBytes = function ($value) {
        $value = trim((string) $value);
        $number = (float) $value;
        $unit = strtolower(substr($value, -1));

        return match ($unit) {
            'g' => (int) ($number * 1024 * 1024 * 1024),
            'm' => (int) ($number * 1024 * 1024),
            'k' => (int) ($number * 1024),
            default => (int) $number,
        };
    };
    $formatFileSize = function ($bytes) {
        $bytes = (float) ($bytes ?: 0);

        if ($bytes >= 1024 * 1024 * 1024) {
            return number_format($bytes / 1024 / 1024 / 1024, 2) . ' GB';
        }

        if ($bytes >= 1024 * 1024) {
            return number_format($bytes / 1024 / 1024, 1) . ' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1) . ' KB';
        }

        return number_format($bytes) . ' B';
    };
    $serverUploadBytes = min(
        $iniBytes(ini_get('upload_max_filesize')),
        $iniBytes(ini_get('post_max_size')),
        max(1, (int) config('digital_twin.upload_max_kilobytes', 102400)) * 1024
    );
    $serverUploadMb = max(1, (int) floor($serverUploadBytes / 1024 / 1024));
    $statusBadgeClass = fn ($status) => match ($status) {
        'ready' => 'bg-success',
        'queued', 'processing', 'awaiting_processing' => 'bg-warning text-dark',
        'failed' => 'bg-danger',
        'cancelled' => 'bg-secondary',
        default => 'bg-light text-dark',
    };
    $uploadErrorFields = collect([
        'provider',
        'capture_type',
        'source_type',
        'display_name',
        'source_file',
        'external_url',
        'thumbnail_file',
        'status',
        'accuracy_class',
        'captured_at',
        'notes',
    ]);
    $uploadPanelOpen = $uploadErrorFields->contains(fn ($field) => $errors->has($field));
    $highlightCaptureId = (int) (session('digital_twin_capture_id') ?: ($requestedCaptureId ?: 0));
@endphp

<style>
    .twin-page {
        color: #102033;
    }

    .twin-toolbar,
    .twin-panel,
    .twin-viewer,
    .twin-list-item,
    .twin-empty {
        background: #fff;
        border: 1px solid #dbe4f0;
        border-radius: 8px;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.06);
    }

    .twin-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 18px 20px;
        margin-bottom: 16px;
    }

    .twin-title {
        margin: 0;
        font-size: 22px;
        font-weight: 700;
        color: #06143a;
    }

    .twin-subtitle {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 8px;
        color: #52627a;
        font-size: 13px;
    }

    .twin-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 10px;
        border-radius: 999px;
        background: #eef5ff;
        color: #164c96;
        font-size: 12px;
        font-weight: 600;
    }

    .twin-layout {
        display: grid;
        grid-template-columns: minmax(0, 2fr) minmax(320px, 1fr);
        gap: 16px;
        align-items: start;
    }

    .twin-layout.twin-layout-full {
        grid-template-columns: 1fr;
    }

    .twin-panel {
        margin-bottom: 16px;
        overflow: hidden;
    }

    .twin-panel-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 14px 16px;
        border-bottom: 1px solid #e4ebf5;
    }

    .twin-panel-header h3 {
        margin: 0;
        font-size: 16px;
        font-weight: 700;
        color: #081c44;
    }

    .twin-panel-body {
        padding: 16px;
    }

    .twin-form-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 14px;
    }

    .twin-wide {
        grid-column: span 3;
    }

    .twin-label {
        display: block;
        margin-bottom: 6px;
        color: #23324a;
        font-size: 13px;
        font-weight: 600;
    }

    .twin-help {
        display: block;
        margin-top: 6px;
        color: #64748b;
        font-size: 12px;
        line-height: 1.4;
    }

    .twin-upload-warning {
        display: none;
        margin-top: 8px;
        padding: 10px 12px;
        border: 1px solid #fed7aa;
        border-radius: 8px;
        background: #fff7ed;
        color: #9a3412;
        font-size: 12px;
        font-weight: 600;
    }

    .twin-upload-warning.is-visible {
        display: block;
    }

    .twin-advanced-fields {
        grid-column: span 3;
        border: 1px solid #e4ebf5;
        border-radius: 8px;
        background: #f8fbff;
        overflow: hidden;
    }

    .twin-advanced-fields summary {
        padding: 12px 14px;
        color: #164c96;
        cursor: pointer;
        font-size: 13px;
        font-weight: 700;
        list-style: none;
    }

    .twin-advanced-fields summary::-webkit-details-marker {
        display: none;
    }

    .twin-advanced-inner {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 14px;
        padding: 0 14px 14px;
    }

    .twin-viewer {
        overflow: hidden;
        margin-bottom: 16px;
    }

    .twin-frame-wrap {
        position: relative;
        min-height: 500px;
        background: #07111f;
    }

    .twin-viewer-sourcebar {
        display: flex;
        gap: 8px;
        overflow-x: auto;
        padding: 12px 14px;
        border-bottom: 1px solid #e4ebf5;
        background: #f8fbff;
    }

    .twin-viewer-actionbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 12px 14px;
        border-bottom: 1px solid #e4ebf5;
        background: #fff;
    }

    .twin-viewer-current {
        min-width: 0;
    }

    .twin-viewer-current strong {
        display: block;
        overflow: hidden;
        color: #0f172a;
        font-size: 14px;
        line-height: 1.25;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .twin-viewer-current small {
        display: block;
        color: #64748b;
        font-size: 12px;
        line-height: 1.35;
    }

    .twin-viewer-actions {
        display: flex;
        flex: 0 0 auto;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 8px;
    }

    .twin-source-button {
        flex: 0 0 auto;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        max-width: 240px;
        padding: 8px 10px;
        border: 1px solid #cbd8ea;
        border-radius: 8px;
        background: #fff;
        color: #172033;
        font-size: 12px;
        text-align: left;
    }

    .twin-source-button.is-active {
        border-color: #1769e8;
        background: #eaf2ff;
        color: #0b4bb3;
    }

    .twin-source-button .twin-source-label {
        display: block;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .twin-source-marker-count {
        flex: 0 0 auto;
        min-width: 22px;
        height: 22px;
        display: inline-grid;
        place-items: center;
        border-radius: 999px;
        background: #172554;
        color: #fff;
        font-size: 11px;
        font-style: normal;
        font-weight: 700;
    }

    .twin-source-media-count {
        flex: 0 0 auto;
        min-width: 22px;
        height: 22px;
        display: inline-grid;
        place-items: center;
        border-radius: 999px;
        background: #e2e8f0;
        color: #334155;
        font-size: 11px;
        font-style: normal;
        font-weight: 700;
    }

    .twin-showcase-layout {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 340px;
        min-height: clamp(620px, 72vh, 860px);
    }

    .twin-viewer-stage {
        min-height: clamp(620px, 72vh, 860px);
        background: #07111f;
    }

    .twin-showcase-rail {
        max-height: clamp(620px, 72vh, 860px);
        border-left: 1px solid #e4ebf5;
        background: #f8fbff;
        overflow: auto;
    }

    .twin-showcase-rail-header {
        position: sticky;
        top: 0;
        z-index: 2;
        padding: 14px;
        border-bottom: 1px solid #e4ebf5;
        background: rgba(248, 251, 255, 0.96);
        backdrop-filter: blur(8px);
    }

    .twin-showcase-rail-header h4 {
        margin: 0;
        color: #081c44;
        font-size: 14px;
        font-weight: 700;
    }

    .twin-marker-filters {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: 12px;
    }

    .twin-filter-button {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 8px;
        border: 1px solid #cbd8ea;
        border-radius: 999px;
        background: #fff;
        color: #334155;
        font-size: 12px;
        font-weight: 700;
        line-height: 1.2;
    }

    .twin-filter-button.is-active {
        border-color: #1769e8;
        background: #eaf2ff;
        color: #0b4bb3;
    }

    .twin-showcase-marker-list {
        padding: 12px;
    }

    .twin-three-stage,
    .twin-panorama-stage,
    .twin-point-cloud-stage {
        position: relative;
        height: clamp(620px, 72vh, 860px);
        min-height: 620px;
        background: #07111f;
    }

    .twin-three-stage:focus,
    .twin-point-cloud-stage:focus {
        outline: 3px solid rgba(14, 165, 233, 0.55);
        outline-offset: -3px;
    }

    .twin-three-stage canvas,
    .twin-panorama-stage canvas,
    .twin-point-cloud-stage canvas {
        display: block;
        width: 100% !important;
        height: 100% !important;
    }

    .twin-three-stage:fullscreen,
    .twin-point-cloud-stage:fullscreen {
        width: 100vw;
        height: 100vh;
        min-height: 100vh;
    }

    .twin-point-cloud-status {
        position: absolute;
        left: 14px;
        bottom: 14px;
        z-index: 4;
        max-width: min(460px, calc(100% - 28px));
        padding: 10px 12px;
        border: 1px solid rgba(148, 163, 184, 0.48);
        border-radius: 8px;
        background: rgba(15, 23, 42, 0.78);
        color: #e5eefb;
        font-size: 12px;
        line-height: 1.45;
        backdrop-filter: blur(8px);
    }

    .twin-placement-hint {
        position: absolute;
        left: 14px;
        bottom: 14px;
        z-index: 3;
        max-width: min(420px, calc(100% - 28px));
        padding: 10px 12px;
        border: 1px solid rgba(148, 163, 184, 0.55);
        border-radius: 8px;
        background: rgba(15, 23, 42, 0.78);
        color: #e5eefb;
        font-size: 12px;
        line-height: 1.45;
        backdrop-filter: blur(8px);
    }

    .twin-placement-hint.is-selected {
        border-color: rgba(34, 197, 94, 0.8);
        background: rgba(6, 78, 59, 0.82);
    }

    .twin-placement-pin {
        position: absolute;
        z-index: 4;
        width: 18px;
        height: 18px;
        border: 3px solid #ffffff;
        border-radius: 999px;
        background: #ef4444;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.35);
        pointer-events: none;
        transform: translate(-50%, -50%);
    }

    .twin-view-controls {
        position: absolute;
        top: 14px;
        right: 14px;
        z-index: 6;
        display: grid;
        gap: 8px;
        padding: 8px;
        border: 1px solid rgba(148, 163, 184, 0.46);
        border-radius: 8px;
        background: rgba(15, 23, 42, 0.78);
        box-shadow: 0 12px 28px rgba(0, 0, 0, 0.24);
        backdrop-filter: blur(8px);
    }

    .twin-view-control-pad {
        display: grid;
        grid-template-columns: repeat(3, 34px);
        grid-template-rows: repeat(3, 34px);
        gap: 5px;
    }

    .twin-view-control-row {
        display: flex;
        gap: 5px;
    }

    .twin-view-control-button {
        width: 34px;
        height: 34px;
        display: inline-grid;
        place-items: center;
        border: 1px solid rgba(226, 232, 240, 0.22);
        border-radius: 8px;
        background: rgba(248, 250, 252, 0.1);
        color: #f8fafc;
        font-size: 18px;
        line-height: 1;
    }

    .twin-view-control-button:hover,
    .twin-view-control-button:focus,
    .twin-view-control-button.is-active {
        border-color: rgba(125, 211, 252, 0.75);
        background: rgba(14, 165, 233, 0.32);
        color: #fff;
        outline: 0;
    }

    .twin-view-control-button.is-pad-up {
        grid-column: 2;
        grid-row: 1;
    }

    .twin-view-control-button.is-pad-left {
        grid-column: 1;
        grid-row: 2;
    }

    .twin-view-control-button.is-pad-reset {
        grid-column: 2;
        grid-row: 2;
    }

    .twin-view-control-button.is-pad-right {
        grid-column: 3;
        grid-row: 2;
    }

    .twin-view-control-button.is-pad-down {
        grid-column: 2;
        grid-row: 3;
    }

    .twin-marker-tooltip {
        position: absolute;
        right: 14px;
        bottom: 14px;
        z-index: 5;
        max-width: min(320px, calc(100% - 28px));
        padding: 12px;
        border: 1px solid rgba(148, 163, 184, 0.55);
        border-radius: 8px;
        background: rgba(15, 23, 42, 0.88);
        color: #e5eefb;
        font-size: 12px;
        line-height: 1.45;
        backdrop-filter: blur(8px);
    }

    .twin-marker-tooltip strong {
        display: block;
        margin-bottom: 3px;
        color: #fff;
    }

    .twin-preview-image {
        width: 100%;
        height: clamp(620px, 72vh, 860px);
        display: block;
        object-fit: contain;
        background: #07111f;
    }

    .twin-media-gallery {
        height: clamp(620px, 72vh, 860px);
        min-height: 620px;
        overflow: auto;
        padding: 18px;
        background: #07111f;
    }

    .twin-media-gallery-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 14px;
        color: #e5eefb;
    }

    .twin-media-gallery-header h3 {
        margin: 0;
        color: #fff;
        font-size: 15px;
        font-weight: 700;
    }

    .twin-media-gallery-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
        gap: 12px;
    }

    .twin-media-gallery-item {
        min-width: 0;
        overflow: hidden;
        border: 1px solid rgba(148, 163, 184, 0.34);
        border-radius: 8px;
        background: rgba(15, 23, 42, 0.82);
        color: #e5eefb;
        text-decoration: none;
    }

    .twin-media-gallery-item:hover,
    .twin-media-gallery-item:focus {
        border-color: rgba(125, 211, 252, 0.8);
        color: #fff;
        outline: 0;
    }

    .twin-media-gallery-thumb {
        aspect-ratio: 4 / 3;
        display: grid;
        place-items: center;
        background: #020617;
    }

    .twin-media-gallery-thumb img {
        width: 100%;
        height: 100%;
        display: block;
        object-fit: cover;
    }

    .twin-media-gallery-thumb i {
        color: #fca5a5;
        font-size: 42px;
    }

    .twin-media-gallery-caption {
        padding: 9px 10px;
        font-size: 12px;
        font-weight: 700;
        line-height: 1.3;
        overflow-wrap: anywhere;
    }

    .twin-viewer-card {
        min-height: 420px;
        display: grid;
        place-items: center;
        padding: 28px;
        text-align: center;
        background: linear-gradient(135deg, #f8fbff, #eef6f3);
    }

    .twin-viewer-card h3 {
        color: #06143a;
        font-size: 18px;
        margin-bottom: 8px;
    }

    .twin-viewer-card p {
        color: #52627a;
        max-width: 620px;
        margin: 0 auto;
    }

    .twin-frame {
        width: 100%;
        height: clamp(620px, 72vh, 860px);
        min-height: 620px;
        border: 0;
        display: block;
        background: #07111f;
    }

    .twin-runtime-placeholder {
        min-height: 420px;
        display: grid;
        place-items: center;
        padding: 28px;
        text-align: center;
        background: linear-gradient(135deg, #f8fbff, #eef6f3);
    }

    .twin-runtime-placeholder h3 {
        color: #06143a;
        font-size: 18px;
        margin-bottom: 8px;
    }

    .twin-runtime-placeholder p {
        color: #52627a;
        max-width: 620px;
        margin: 0 auto;
    }

    .twin-list {
        display: grid;
        gap: 10px;
    }

    .twin-capture-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }

    .twin-capture-card {
        border: 1px solid #dbe4f0;
        border-radius: 8px;
        background: #fff;
        padding: 14px;
    }

    .twin-capture-card.is-highlighted {
        border-color: #1769e8;
        box-shadow: 0 0 0 3px rgba(23, 105, 232, 0.14);
    }

    .twin-capture-title {
        color: #0f172a;
        font-size: 15px;
        font-weight: 700;
        line-height: 1.25;
    }

    .twin-capture-files {
        margin-top: 10px;
        padding-top: 10px;
        border-top: 1px dashed #dbe4f0;
    }

    .twin-quality-strip {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
        gap: 8px;
        margin-top: 10px;
    }

    .twin-quality-metric {
        padding: 8px 10px;
        border: 1px solid #dbe4f0;
        border-radius: 8px;
        background: #f8fbff;
        color: #475569;
        font-size: 12px;
        line-height: 1.25;
    }

    .twin-quality-metric strong {
        display: block;
        margin-top: 3px;
        color: #0f172a;
        font-size: 13px;
    }

    .twin-source-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 5px 0;
        color: #475569;
        font-size: 12px;
    }

    .twin-source-row strong {
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .twin-list-item {
        padding: 12px;
        box-shadow: none;
    }

    .twin-marker-card {
        width: 100%;
        border: 1px solid #dbe4f0;
        color: inherit;
        text-align: left;
        cursor: pointer;
    }

    .twin-marker-card.is-active {
        border-color: #1769e8;
        background: #eaf2ff;
        box-shadow: 0 0 0 3px rgba(23, 105, 232, 0.12);
    }

    .twin-marker-card.is-hidden {
        display: none;
    }

    .twin-marker-card:disabled {
        cursor: default;
        opacity: 0.76;
    }

    .twin-list-item.is-child-source {
        margin-left: 18px;
        border-left: 4px solid #dbeafe;
    }

    .twin-item-title {
        color: #0f172a;
        font-weight: 700;
    }

    .twin-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 8px;
        color: #64748b;
        font-size: 12px;
    }

    .twin-marker-dot {
        width: 10px;
        height: 10px;
        border-radius: 999px;
        display: inline-block;
        margin-right: 6px;
        background: #f59e0b;
    }

    .twin-marker-dot.is-critical,
    .twin-marker-dot.is-high {
        background: #dc2626;
    }

    .twin-marker-dot.is-low {
        background: #16a34a;
    }

    .twin-empty {
        padding: 18px;
        color: #64748b;
        box-shadow: none;
    }

    @media (max-width: 1100px) {
        .twin-layout.twin-layout-full,
        .twin-layout,
        .twin-form-grid,
        .twin-capture-grid,
        .twin-advanced-inner,
        .twin-showcase-layout {
            grid-template-columns: 1fr;
        }

        .twin-wide,
        .twin-advanced-fields {
            grid-column: span 1;
        }
    }

    @media (max-width: 680px) {
        .twin-toolbar {
            align-items: flex-start;
            flex-direction: column;
        }

        .twin-viewer-actionbar {
            align-items: stretch;
            flex-direction: column;
        }

        .twin-viewer-actions {
            justify-content: flex-start;
        }

        .twin-showcase-rail {
            max-height: none;
            border-top: 1px solid #e4ebf5;
            border-left: 0;
        }

        .twin-frame,
        .twin-frame-wrap,
        .twin-viewer-stage,
        .twin-three-stage,
        .twin-panorama-stage {
            height: 420px;
            min-height: 420px;
        }
    }
</style>

<div class="twin-page">
    <div class="twin-toolbar">
        <div>
            <h2 class="twin-title">{{ $propertyName }}</h2>
            <div class="twin-subtitle">
                <span class="twin-pill" title="{{ $inspectionRecordReference }}">
                    <i class="mdi mdi-clipboard-text-outline"></i>
                    {{ $inspectionReference }}
                </span>
                <span class="twin-pill">
                    <i class="mdi mdi-home-map-marker"></i>
                    {{ $property?->property_address ?: 'Address not recorded' }}
                </span>
                <span class="twin-pill">
                    <i class="mdi mdi-progress-check"></i>
                    {{ $statusLabel }}
                </span>
            </div>
        </div>

        <a href="{{ $backUrl }}" class="btn btn-outline-secondary btn-sm">
            <i class="mdi mdi-arrow-left me-1"></i> Back
        </a>
    </div>

    @if($canManageDigitalTwin)
        <section class="twin-panel">
            <div class="twin-panel-header">
                <div class="d-flex align-items-center gap-2">
                    <h3>Add Capture Source</h3>
                    <span class="badge bg-primary">Vendor neutral</span>
                </div>
                <button
                    type="button"
                    class="btn btn-outline-primary btn-sm"
                    data-bs-toggle="collapse"
                    data-bs-target="#captureUploadPanel"
                    aria-expanded="{{ $uploadPanelOpen ? 'true' : 'false' }}"
                    aria-controls="captureUploadPanel">
                    <i class="mdi mdi-plus-circle-outline me-1"></i>Add Capture
                </button>
            </div>
            <div id="captureUploadPanel" class="collapse {{ $uploadPanelOpen ? 'show' : '' }}">
                <div class="twin-panel-body">
                <form method="POST" action="{{ route('inspections.digital-twin.models.store', $inspection) }}" enctype="multipart/form-data">
                    @csrf
                    <div class="twin-form-grid">
                        <div>
                            <label class="twin-label" for="provider">Capture provider</label>
                            <select id="provider" name="provider" class="form-select" required>
                                @foreach($providers as $value => $label)
                                    <option value="{{ $value }}" @selected(old('provider') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="twin-label" for="source_type">Twin layer</label>
                            <select id="source_type" name="source_type" class="form-select" required>
                                @foreach($sourceTypes as $value => $label)
                                    <option value="{{ $value }}" @selected(old('source_type') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="twin-label" for="display_name">Display name</label>
                            <input id="display_name" name="display_name" type="text" class="form-control" value="{{ old('display_name') }}" placeholder="Matterport tour, LiDAR cloud, drone photos">
                        </div>
                        <div>
                            <label class="twin-label" for="capture_type">Capture type</label>
                            <select id="capture_type" name="capture_type" class="form-select" required>
                                @foreach($captureTypes as $value => $label)
                                    <option value="{{ $value }}" @selected(old('capture_type') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="twin-label" for="source_file">Source file</label>
                            <input
                                id="source_file"
                                name="source_file"
                                type="file"
                                class="form-control"
                                accept="{{ $supportedUploadExtensions }}"
                                data-max-upload-bytes="{{ $serverUploadBytes }}"
                                data-max-upload-mb="{{ $serverUploadMb }}"
                            >
                            <small class="twin-help">GLB/glTF opens in the viewer. MatterPak ZIP is stored privately, extracted, and queued for Blender GLB conversion. E57/LAS/LAZ are preserved for later point-cloud processing.</small>
                            <div class="twin-upload-warning" id="sourceFileWarning"></div>
                        </div>
                        <div>
                            <label class="twin-label" for="external_url">Cloud URL</label>
                            <input id="external_url" name="external_url" type="url" class="form-control" value="{{ old('external_url') }}" placeholder="https://my.matterport.com/show/?m=...">
                            <small class="twin-help">Use this for Matterport, Azure Blob, AWS S3/CloudFront, or another hosted twin asset.</small>
                        </div>
                        <div class="d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_primary" name="is_primary" value="1" @checked(old('is_primary', true))>
                                <label class="form-check-label" for="is_primary">Use as primary twin layer</label>
                            </div>
                        </div>
                        <details class="twin-advanced-fields">
                            <summary><i class="mdi mdi-tune-variant me-1"></i> Advanced capture details</summary>
                            <div class="twin-advanced-inner">
                                <div>
                                    <label class="twin-label" for="device_name">Device</label>
                                    <input id="device_name" name="device_name" type="text" class="form-control" value="{{ old('device_name') }}" placeholder="Matterport Pro3, iPhone LiDAR, DJI drone">
                                </div>
                                <div>
                                    <label class="twin-label" for="device_serial">Device serial / scan ID</label>
                                    <input id="device_serial" name="device_serial" type="text" class="form-control" value="{{ old('device_serial') }}" placeholder="Scanner serial or capture package ID">
                                </div>
                                <div>
                                    <label class="twin-label" for="captured_at">Captured at</label>
                                    <input id="captured_at" name="captured_at" type="datetime-local" class="form-control" value="{{ old('captured_at') }}">
                                </div>
                                <div>
                                    <label class="twin-label" for="original_format">Original format</label>
                                    <input id="original_format" name="original_format" type="text" class="form-control" value="{{ old('original_format') }}" placeholder="E57, LAS, JPG, PDF, OBJ">
                                </div>
                                <div>
                                    <label class="twin-label" for="runtime_format">Runtime format</label>
                                    <input id="runtime_format" name="runtime_format" type="text" class="form-control" value="{{ old('runtime_format') }}" placeholder="GLB, hosted, Potree">
                                </div>
                                <div>
                                    <label class="twin-label" for="accuracy_class">Accuracy class</label>
                                    <input id="accuracy_class" name="accuracy_class" type="text" class="form-control" value="{{ old('accuracy_class') }}" placeholder="visual, measured, survey-grade">
                                </div>
                                <div>
                                    <label class="twin-label" for="provider_model_id">Provider model ID</label>
                                    <input id="provider_model_id" name="provider_model_id" type="text" class="form-control" value="{{ old('provider_model_id') }}" placeholder="Matterport SID or scanner ID">
                                </div>
                                <div>
                                    <label class="twin-label" for="thumbnail_file">Thumbnail</label>
                                    <input id="thumbnail_file" name="thumbnail_file" type="file" accept="image/*" class="form-control">
                                </div>
                                <div>
                                    <label class="twin-label" for="status">Status</label>
                                    <select id="status" name="status" class="form-select">
                                        @foreach(['active' => 'Active', 'draft' => 'Draft', 'archived' => 'Archived'] as $value => $label)
                                            <option value="{{ $value }}" @selected(old('status', 'active') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </details>
                        <div class="twin-wide">
                            <label class="twin-label" for="notes">Capture notes</label>
                            <textarea id="notes" name="notes" rows="2" class="form-control" placeholder="Scan scope, room, wall, image set, camera notes, or import assumptions">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="mdi mdi-plus-circle-outline me-1"></i>
                            Add Capture Source
                        </button>
                    </div>
                </form>
                </div>
            </div>
        </section>
    @endif

    <section class="twin-viewer" id="digitalTwinViewer">
                <div class="twin-panel-header">
                    <h3>Digital Twin Viewer</h3>
                    @if($primaryModel)
                        <span class="badge bg-success">{{ $primaryModel->provider_label }}</span>
                    @elseif($legacyMatterportModel)
                        <span class="badge bg-success">Matterport legacy source</span>
                    @else
                        <span class="badge bg-secondary">No capture source</span>
                    @endif
                </div>

                @if($viewerSources->isNotEmpty())
                    <div
                        data-digital-twin-viewer
                        data-initial-source="{{ $initialViewerSource['id'] ?? '' }}"
                        aria-label="Vendor-neutral digital twin viewer">
                        <script type="application/json" data-twin-sources>
                            @json($viewerSources)
                        </script>
                        <script type="application/json" data-twin-markers>
                            @json($viewerMarkers)
                        </script>

                        <div class="twin-viewer-actionbar" data-twin-viewer-actions>
                            <div class="twin-viewer-current">
                                <strong data-twin-action-title>{{ $initialViewerSource['title'] ?? 'Capture source' }}</strong>
                                <small data-twin-action-meta>{{ $initialViewerSource['actionMeta'] ?? 'Capture source' }}</small>
                            </div>
                            <div class="twin-viewer-actions">
                                <a
                                    href="{{ $initialViewerSource['viewUrl'] ?? '#digitalTwinViewer' }}"
                                    class="btn btn-outline-primary btn-sm"
                                    data-twin-action-view>
                                    <i class="mdi mdi-eye-outline me-1"></i>View Capture
                                </a>
                                @if($canCreateIssueMarkers)
                                    <a
                                        href="#issueMarkerPanel"
                                        class="btn btn-outline-success btn-sm {{ empty($initialViewerSource['canAddFinding']) ? 'd-none' : '' }}"
                                        data-twin-action-add-finding
                                        data-twin-add-marker
                                        data-capture-session-id="{{ $initialViewerSource['addFindingCaptureSessionId'] ?? '' }}"
                                        data-spatial-model-id="{{ $initialViewerSource['addFindingSpatialModelId'] ?? '' }}"
                                        data-source-provider="{{ $initialViewerSource['addFindingSourceProvider'] ?? 'manual' }}"
                                        data-source-reference="{{ $initialViewerSource['addFindingSourceReference'] ?? '' }}">
                                        <i class="mdi mdi-map-marker-plus-outline me-1"></i>Add Finding
                                    </a>
                                @endif
                                <a
                                    href="{{ $initialViewerSource['openSourceUrl'] ?? '#' }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="btn btn-light btn-sm {{ empty($initialViewerSource['openSourceUrl']) ? 'd-none' : '' }}"
                                    data-twin-action-open-source>
                                    <i class="mdi mdi-download-outline me-1"></i>Open Source
                                </a>
                                @if($canManageDigitalTwin)
                                    <form
                                        method="POST"
                                        action="{{ $initialViewerSource['convertUrl'] ?? '#' }}"
                                        class="d-inline-flex {{ empty($initialViewerSource['convertUrl']) ? 'd-none' : '' }}"
                                        data-twin-action-convert-form>
                                        @csrf
                                        <button
                                            type="submit"
                                            class="btn btn-outline-warning btn-sm"
                                            title="{{ $initialViewerSource['convertTitle'] ?? 'Start MatterPak GLB conversion' }}"
                                            data-twin-action-convert
                                            @disabled(!empty($initialViewerSource['convertDisabled']))>
                                            <i class="mdi {{ $initialViewerSource['convertIcon'] ?? 'mdi-cube-send' }} me-1" data-twin-action-convert-icon></i>
                                            <span data-twin-action-convert-label>{{ $initialViewerSource['convertLabel'] ?? 'Convert to GLB' }}</span>
                                        </button>
                                    </form>
                                @endif
                                <span class="badge bg-light text-dark align-self-center {{ empty($initialViewerSource['jobLabel']) ? 'd-none' : '' }}" data-twin-action-job>
                                    {{ $initialViewerSource['jobLabel'] ?? '' }}
                                </span>
                            </div>
                        </div>

                        <div class="twin-viewer-sourcebar" role="tablist" aria-label="Digital twin sources">
                            @foreach($viewerSources as $source)
                                @php
                                    $sourceMarkerTotal = $sourceMarkerCount($source);
                                @endphp
                                <button
                                    type="button"
                                    class="twin-source-button"
                                    data-twin-source-button="{{ $source['id'] }}"
                                    title="{{ $source['title'] }}">
                                    <i class="mdi {{ $source['icon'] ?? 'mdi-cube-outline' }}"></i>
                                    <span class="twin-source-label">
                                        <strong>{{ $source['title'] }}</strong>
                                        <small class="d-block text-muted">{{ $source['providerLabel'] }} / {{ $source['sourceTypeLabel'] }}</small>
                                    </span>
                                    @if($sourceMarkerTotal > 0)
                                        <em class="twin-source-marker-count" title="{{ $sourceMarkerTotal }} issue marker{{ $sourceMarkerTotal === 1 ? '' : 's' }}">{{ $sourceMarkerTotal }}</em>
                                    @elseif(!empty($source['mediaCount']))
                                        <em class="twin-source-media-count" title="{{ $source['mediaCount'] }} media file{{ $source['mediaCount'] === 1 ? '' : 's' }}">{{ $source['mediaCount'] }}</em>
                                    @endif
                                </button>
                            @endforeach
                        </div>

                        <div class="twin-showcase-layout">
                            <div class="twin-viewer-stage" data-twin-stage>
                                <div class="twin-viewer-card">
                                    <div>
                                        <h3>Loading digital twin source</h3>
                                        <p>The viewer is preparing this capture source.</p>
                                    </div>
                                </div>
                            </div>
                            <aside class="twin-showcase-rail" data-twin-marker-rail>
                                <div class="twin-showcase-rail-header">
                                    <div class="d-flex align-items-center justify-content-between gap-2">
                                        <h4>Issue Markers</h4>
                                        <span class="badge bg-light text-dark">{{ $issueMarkers->count() }}</span>
                                    </div>
                                    @if($viewerMarkers->isNotEmpty())
                                        <div class="twin-marker-filters" data-twin-marker-filters aria-label="Marker layers">
                                            <button type="button" class="twin-filter-button is-active" data-twin-marker-filter="all">All {{ $viewerMarkers->count() }}</button>
                                            @foreach(['critical' => 'Critical', 'high' => 'High', 'medium' => 'Medium', 'low' => 'Low'] as $severity => $label)
                                                @if(($markerLayerCounts[$severity] ?? 0) > 0)
                                                    <button type="button" class="twin-filter-button" data-twin-marker-filter="{{ $severity }}">{{ $label }} {{ $markerLayerCounts[$severity] }}</button>
                                                @endif
                                            @endforeach
                                            @if(($markerLayerCounts['phar'] ?? 0) > 0)
                                                <button type="button" class="twin-filter-button" data-twin-marker-filter="phar">PHAR {{ $markerLayerCounts['phar'] }}</button>
                                            @endif
                                            @if(($markerLayerCounts['unlinked'] ?? 0) > 0)
                                                <button type="button" class="twin-filter-button" data-twin-marker-filter="unlinked">Needs PHAR {{ $markerLayerCounts['unlinked'] }}</button>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                                <div class="twin-showcase-marker-list">
                                    @if($issueMarkers->isNotEmpty())
                                        <div class="twin-list" data-twin-marker-list>
                                            @foreach($issueMarkers as $marker)
                                                @php
                                                    $hasPosition = $marker->position_x !== null && $marker->position_y !== null && $marker->position_z !== null;
                                                    $markerSourceTitle = $marker->spatialModel
                                                        ? ($marker->spatialModel->display_name ?: $marker->spatialModel->source_type_label)
                                                        : ($marker->captureSession?->provider_label ?: null);
                                                    $markerHasPhar = (bool) $marker->phar_finding_id;
                                                @endphp
                                                <button
                                                    type="button"
                                                    class="twin-list-item twin-marker-card"
                                                    data-twin-marker-card
                                                    data-marker-id="{{ $marker->id }}"
                                                    data-spatial-model-id="{{ $marker->spatial_model_id }}"
                                                    data-capture-session-id="{{ $marker->capture_session_id }}"
                                                    data-severity="{{ $marker->severity }}"
                                                    data-status="{{ $marker->status }}"
                                                    data-has-phar="{{ $markerHasPhar ? '1' : '0' }}"
                                                    data-has-position="{{ $hasPosition ? '1' : '0' }}"
                                                    @disabled(!$hasPosition)>
                                                    <div class="twin-item-title">
                                                        <span class="twin-marker-dot is-{{ $marker->severity }}"></span>{{ $marker->title }}
                                                    </div>
                                                    <div class="small text-muted mt-1">{{ $marker->description ?: 'No description recorded.' }}</div>
                                                    <div class="twin-meta">
                                                        <span>{{ ucfirst($marker->severity) }}</span>
                                                        <span>{{ ucfirst(str_replace('_', ' ', $marker->status)) }}</span>
                                                        <span>{{ $marker->room_name ?: 'location not recorded' }}</span>
                                                        @if($marker->surface_label)
                                                            <span>{{ $marker->surface_label }}</span>
                                                        @endif
                                                        @if($markerSourceTitle)
                                                            <span>{{ $markerSourceTitle }}</span>
                                                        @endif
                                                        @if($markerHasPhar)
                                                            <span>PHAR #{{ $marker->phar_finding_id }}</span>
                                                        @else
                                                            <span>Needs PHAR</span>
                                                        @endif
                                                        @if($marker->source_reference)
                                                            <span>{{ $marker->source_reference }}</span>
                                                        @endif
                                                        @if($hasPosition)
                                                            <span>X {{ $marker->position_x }}, Y {{ $marker->position_y }}, Z {{ $marker->position_z }}</span>
                                                        @endif
                                                    </div>
                                                </button>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="twin-empty">No issue markers have been placed yet.</div>
                                    @endif
                                </div>
                            </aside>
                        </div>
                    </div>
                @else
                    <div class="twin-runtime-placeholder">
                        <div>
                            <h3>No digital twin source attached yet</h3>
                            <p>
                                Add a capture source from Matterport, RESOLV, phone camera, 360 camera, drone, LiDAR, thermal camera, BIM/CAD, or manual upload to begin building this property twin.
                            </p>
                        </div>
                    </div>
                @endif
    </section>

    <div class="twin-layout {{ $canCreateIssueMarkers ? '' : 'twin-layout-full' }}">
        <main>
            <section class="twin-panel">
                <div class="twin-panel-header">
                    <h3>Capture Sessions</h3>
                    <span class="badge bg-light text-dark">
                        {{ $captureSessions->count() }} capture{{ $captureSessions->count() === 1 ? '' : 's' }}
                    </span>
                </div>
                <div class="twin-panel-body">
                    @if($captureSessions->isNotEmpty())
                        <div class="twin-capture-grid">
                            @foreach($captureSessions as $session)
                                @php
                                    $sessionModels = $modelsByCapture->get((int) $session->id, collect());
                                    $sessionSources = $sourcesByCapture->get((int) $session->id, collect());
                                    $sessionJobs = $jobsByCapture->get((int) $session->id, collect());
                                    $sessionMarkers = $markersByCapture->get((int) $session->id, collect());
                                    $readyModel = $sessionModels->firstWhere('processing_status', 'ready') ?: $sessionModels->first();
                                    $primarySource = $sessionSources->firstWhere('parent_source_file_id', null) ?: $sessionSources->first();
                                    $matterPakArchive = $sessionSources->firstWhere('file_role', 'matterpak_archive');
                                    $convertAction = $matterPakConvertAction($matterPakArchive, $sessionJobs);
                                    $latestJob = $sessionJobs->sortByDesc('id')->first();
                                    $sessionStatus = $latestJob?->status ?: ($readyModel?->processing_status ?: $session->status);
                                    $sessionTitle = $readyModel?->display_name
                                        ?: ($primarySource?->original_filename
                                            ?: ($session->provider_label . ' ' . $session->capture_type_label));
                                @endphp
                                <article class="twin-capture-card {{ $highlightCaptureId === (int) $session->id ? 'is-highlighted' : '' }}">
                                    <div class="d-flex justify-content-between gap-2 align-items-start">
                                        <div>
                                            <div class="twin-capture-title">{{ $sessionTitle }}</div>
                                            <div class="twin-meta">
                                                <span><i class="mdi mdi-home-map-marker me-1"></i>{{ $propertyName }}</span>
                                                <span><i class="mdi mdi-clipboard-text-outline me-1"></i>{{ $inspectionReference }}</span>
                                                <span><i class="mdi mdi-camera-outline me-1"></i>{{ $session->provider_label }}</span>
                                                <span>{{ $session->capture_type_label }}</span>
                                            </div>
                                        </div>
                                        <span class="badge {{ $statusBadgeClass($sessionStatus) }}">
                                            {{ ucfirst(str_replace('_', ' ', (string) $sessionStatus)) }}
                                        </span>
                                    </div>
                                    <div class="twin-meta">
                                        <span>{{ $sessionModels->count() }} viewer layer{{ $sessionModels->count() === 1 ? '' : 's' }}</span>
                                        <span>{{ $sessionSources->count() }} source record{{ $sessionSources->count() === 1 ? '' : 's' }}</span>
                                        <span>{{ $sessionMarkers->count() }} marker{{ $sessionMarkers->count() === 1 ? '' : 's' }}</span>
                                        @if($session->captured_at)
                                            <span>Captured {{ $session->captured_at->format('M j, Y g:i A') }}</span>
                                        @endif
                                        @if($latestJob?->completed_at)
                                            <span>Processed {{ $latestJob->completed_at->format('M j, Y g:i A') }}</span>
                                        @endif
                                    </div>

                                    @if($readyModel?->isRawPointCloud())
                                        <div class="small text-muted mt-2">
                                            Raw point-cloud processing and tiling are handled outside ETOGO. Attach browser-ready Potree/Cesium output as another capture source when available.
                                        </div>
                                    @endif

                                    @if($latestJob?->processing_error)
                                        <div class="small text-danger mt-2">{{ $latestJob->processing_error }}</div>
                                    @elseif(in_array($sessionStatus, ['queued', 'processing', 'awaiting_processing'], true))
                                        <div class="small text-muted mt-2">
                                            This capture is stored under this property diagnosis and is waiting for processing before browser viewing.
                                        </div>
                                    @endif

                                    @php
                                        $modelMetadata = $readyModel ? ($readyModel->metadata ?: []) : [];
                                        $jobMetadata = $latestJob ? ($latestJob->metadata ?: []) : [];
                                        $conversionDiagnostics = $modelMetadata['conversion_diagnostics'] ?? $jobMetadata['conversion_diagnostics'] ?? [];
                                        $materialTextures = $conversionDiagnostics['material_textures'] ?? [];
                                        $textureSources = $conversionDiagnostics['texture_sources'] ?? [];
                                        $pointPreview = $modelMetadata['point_cloud_preview'] ?? $jobMetadata['point_cloud_preview'] ?? [];
                                        $glbBytes = $conversionDiagnostics['glb_output']['size_bytes'] ?? null;
                                        $textureReferenceCount = $materialTextures['texture_reference_count'] ?? null;
                                        $missingTextureCount = $materialTextures['missing_texture_count'] ?? null;
                                        $textureSourceCount = $textureSources['texture_file_count'] ?? null;
                                        $textureTotalBytes = $textureSources['texture_total_bytes'] ?? null;
                                        $sampledPointCount = $pointPreview['sampled_point_count'] ?? null;
                                        $sourcePointCount = $pointPreview['source_point_count'] ?? null;
                                    @endphp
                                    @if($glbBytes || $textureReferenceCount !== null || $textureSourceCount !== null || $sampledPointCount)
                                        <div class="twin-quality-strip" aria-label="MatterPak conversion quality">
                                            @if($glbBytes)
                                                <div class="twin-quality-metric">
                                                    Browser model
                                                    <strong>{{ $formatFileSize($glbBytes) }} GLB</strong>
                                                </div>
                                            @endif
                                            @if($textureReferenceCount !== null)
                                                <div class="twin-quality-metric">
                                                    Texture coverage
                                                    <strong>{{ number_format((int) $textureReferenceCount) }} mapped / {{ number_format((int) ($missingTextureCount ?? 0)) }} missing</strong>
                                                </div>
                                            @endif
                                            @if($textureSourceCount !== null)
                                                <div class="twin-quality-metric">
                                                    Source textures
                                                    <strong>{{ number_format((int) $textureSourceCount) }} file{{ (int) $textureSourceCount === 1 ? '' : 's' }}{{ $textureTotalBytes ? ' / ' . $formatFileSize($textureTotalBytes) : '' }}</strong>
                                                </div>
                                            @endif
                                            @if($sampledPointCount)
                                                <div class="twin-quality-metric">
                                                    Point preview
                                                    <strong>{{ number_format((int) $sampledPointCount) }} of {{ number_format((int) ($sourcePointCount ?: $sampledPointCount)) }}</strong>
                                                </div>
                                            @endif
                                        </div>
                                    @endif

                                    @if($sessionSources->isNotEmpty())
                                        <div class="twin-capture-files">
                                            @foreach($sessionSources->whereNull('parent_source_file_id')->take(3) as $sourceFile)
                                                <div class="twin-source-row">
                                                    <strong title="{{ $sourceFile->original_filename }}">{{ $sourceFile->original_filename }}</strong>
                                                    <span class="badge {{ $statusBadgeClass($sourceFile->processing_status) }}">
                                                        {{ $sourceFile->processing_status_label }}
                                                    </span>
                                                </div>
                                            @endforeach
                                            @if($sessionSources->whereNotNull('parent_source_file_id')->count() > 0)
                                                <div class="small text-muted mt-1">
                                                    {{ $sessionSources->whereNotNull('parent_source_file_id')->count() }} extracted MatterPak source record{{ $sessionSources->whereNotNull('parent_source_file_id')->count() === 1 ? '' : 's' }} preserved.
                                                </div>
                                            @endif
                                        </div>
                                    @endif

                                    @if($convertAction)
                                        <form method="POST" action="{{ $convertAction['url'] }}" class="d-inline-flex mt-3">
                                            @csrf
                                            <button
                                                type="submit"
                                                class="btn btn-outline-warning btn-sm"
                                                title="{{ $convertAction['title'] }}"
                                                @disabled($convertAction['disabled'])>
                                                <i class="mdi {{ $convertAction['icon'] }} me-1"></i>{{ $convertAction['label'] }}
                                            </button>
                                        </form>
                                    @endif
                                </article>
                            @endforeach
                        </div>
                        @if($ungroupedModelCount > 0)
                            <div class="twin-empty mt-3">
                                {{ $ungroupedModelCount }} legacy viewer layer{{ $ungroupedModelCount === 1 ? '' : 's' }} do not have a capture session yet. They still remain linked to {{ $inspectionReference }}.
                            </div>
                        @endif
                    @else
                        <div class="twin-empty">No captures have been submitted for this property diagnosis yet.</div>
                    @endif
                </div>
            </section>
        </main>

        @if($canCreateIssueMarkers)
            <aside>
                <section class="twin-panel" id="issueMarkerPanel">
                    <div class="twin-panel-header">
                        <h3>Add Issue Marker</h3>
                        <i class="mdi mdi-map-marker-plus-outline"></i>
                    </div>
                    <div class="twin-panel-body">
                        <div class="alert alert-info py-2 small">
                            <i class="mdi mdi-cursor-default-click-outline me-1"></i>
                            For GLB or glTF layers, click directly on the 3D surface to fill marker coordinates and link the marker to that spatial model.
                        </div>
                        <form method="POST" action="{{ route('inspections.digital-twin.markers.store', $inspection) }}" data-issue-marker-form>
                            @csrf
                            <div class="mb-3">
                                <label class="twin-label" for="title">Issue title</label>
                                <input id="title" name="title" type="text" class="form-control" value="{{ old('title') }}" placeholder="Possible concealed pipe leak" required>
                            </div>
                            <div class="mb-3">
                                <label class="twin-label" for="spatial_model_id">Spatial model</label>
                                <select id="spatial_model_id" name="spatial_model_id" class="form-select" data-marker-field="spatial_model_id">
                                    <option value="">General diagnosis marker</option>
                                    @foreach($spatialModels as $model)
                                        <option value="{{ $model->id }}" @selected(old('spatial_model_id') == $model->id)>{{ $model->display_name ?: $model->source_type_label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="twin-label" for="capture_session_id">Capture session</label>
                                <select id="capture_session_id" name="capture_session_id" class="form-select" data-marker-field="capture_session_id">
                                    <option value="">No source session link</option>
                                    @foreach($captureSessions as $session)
                                        <option value="{{ $session->id }}" @selected(old('capture_session_id') == $session->id)>
                                            {{ $session->provider_label }} - {{ $session->capture_type_label }} #{{ $session->id }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="twin-label" for="phar_finding_id">PHAR finding</label>
                                <select id="phar_finding_id" name="phar_finding_id" class="form-select">
                                    <option value="">No PHAR finding link yet</option>
                                    @foreach($pharFindings as $finding)
                                        @php
                                            $findingLabel = $finding->task_question
                                                ?: $finding->observed_condition
                                                ?: $finding->category
                                                ?: 'Finding #' . $finding->id;
                                        @endphp
                                        <option value="{{ $finding->id }}" @selected(old('phar_finding_id') == $finding->id)>
                                            #{{ $finding->id }} {{ \Illuminate\Support\Str::limit($findingLabel, 70) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @if(!$inspection->findings_report_shared_at && !in_array($inspection->status, ['findings_shared', 'client_committed', 'estimation_in_progress', 'estimation_completed', 'quotation_shared', 'quotation_approved', 'completed', 'approved'], true))
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="create_phar_finding" name="create_phar_finding" value="1" @checked(old('create_phar_finding'))>
                                    <label class="form-check-label" for="create_phar_finding">
                                        Create PHAR finding from this marker
                                    </label>
                                    <div class="small text-muted mt-1">
                                        Use this when the marker is a new diagnosis item. You can finish system classification and costing in PHAR.
                                    </div>
                                </div>
                                @if($buildingSystems->isNotEmpty())
                                    <div class="mb-3">
                                        <label class="twin-label" for="building_system_id">Building system</label>
                                        <select id="building_system_id" name="building_system_id" class="form-select">
                                            <option value="">Choose during PHAR review</option>
                                            @foreach($buildingSystems as $system)
                                                <option value="{{ $system->id }}" @selected(old('building_system_id') == $system->id)>{{ $system->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="twin-label" for="building_subsystem_id">Subsystem</label>
                                        <select id="building_subsystem_id" name="building_subsystem_id" class="form-select">
                                            <option value="">No subsystem selected</option>
                                            @foreach($buildingSystems as $system)
                                                @if($system->subsystems->isNotEmpty())
                                                    <optgroup label="{{ $system->name }}">
                                                        @foreach($system->subsystems as $subsystem)
                                                            <option value="{{ $subsystem->id }}" @selected(old('building_subsystem_id') == $subsystem->id)>{{ $subsystem->name }}</option>
                                                        @endforeach
                                                    </optgroup>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="twin-label" for="building_component_id">Component</label>
                                        <select id="building_component_id" name="building_component_id" class="form-select">
                                            <option value="">No component selected</option>
                                            @foreach($buildingSystems as $system)
                                                @foreach($system->subsystems as $subsystem)
                                                    @if($subsystem->components->isNotEmpty())
                                                        <optgroup label="{{ $system->name }} / {{ $subsystem->name }}">
                                                            @foreach($subsystem->components as $component)
                                                                <option value="{{ $component->id }}" @selected(old('building_component_id') == $component->id)>{{ $component->name }}</option>
                                                            @endforeach
                                                        </optgroup>
                                                    @endif
                                                @endforeach
                                            @endforeach
                                        </select>
                                    </div>
                                @endif
                            @endif
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="twin-label" for="source_provider">Source</label>
                                    <select id="source_provider" name="source_provider" class="form-select" required>
                                        <option value="manual" @selected(old('source_provider', 'manual') === 'manual')>Manual</option>
                                        @foreach($providers as $value => $label)
                                            <option value="{{ $value }}" @selected(old('source_provider') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="twin-label" for="marker_type">Type</label>
                                    <input id="marker_type" name="marker_type" type="text" class="form-control" value="{{ old('marker_type', 'issue') }}" required>
                                </div>
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="twin-label" for="severity">Severity</label>
                                    <select id="severity" name="severity" class="form-select">
                                        @foreach(['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical'] as $value => $label)
                                            <option value="{{ $value }}" @selected(old('severity', 'medium') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="twin-label" for="marker_status">Status</label>
                                    <select id="marker_status" name="status" class="form-select">
                                        @foreach(['open' => 'Open', 'monitoring' => 'Monitoring', 'quoted' => 'Quoted', 'in_progress' => 'In progress', 'resolved' => 'Resolved', 'closed' => 'Closed'] as $value => $label)
                                            <option value="{{ $value }}" @selected(old('status', 'open') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-4">
                                    <label class="twin-label" for="position_x">X</label>
                                    <input id="position_x" name="position_x" type="number" step="0.0001" class="form-control" value="{{ old('position_x') }}" data-marker-field="position_x">
                                </div>
                                <div class="col-4">
                                    <label class="twin-label" for="position_y">Y</label>
                                    <input id="position_y" name="position_y" type="number" step="0.0001" class="form-control" value="{{ old('position_y') }}" data-marker-field="position_y">
                                </div>
                                <div class="col-4">
                                    <label class="twin-label" for="position_z">Z</label>
                                    <input id="position_z" name="position_z" type="number" step="0.0001" class="form-control" value="{{ old('position_z') }}" data-marker-field="position_z">
                                </div>
                            </div>
                            <input type="hidden" id="normal_x" name="normal_x" value="{{ old('normal_x') }}" data-marker-field="normal_x">
                            <input type="hidden" id="normal_y" name="normal_y" value="{{ old('normal_y') }}" data-marker-field="normal_y">
                            <input type="hidden" id="normal_z" name="normal_z" value="{{ old('normal_z') }}" data-marker-field="normal_z">
                            <input type="hidden" id="camera_position" name="camera_position" value="{{ old('camera_position') }}" data-marker-field="camera_position">
                            <input type="hidden" id="camera_target" name="camera_target" value="{{ old('camera_target') }}" data-marker-field="camera_target">
                            <input type="hidden" id="object_uuid" name="object_uuid" value="{{ old('object_uuid') }}" data-marker-field="object_uuid">
                            <div class="mb-3">
                                <label class="twin-label" for="room_name">Room / area</label>
                                <input id="room_name" name="room_name" type="text" class="form-control" value="{{ old('room_name') }}" placeholder="Kitchen, roof, north wall">
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="twin-label" for="surface_label">Surface / element</label>
                                    <input id="surface_label" name="surface_label" type="text" class="form-control" value="{{ old('surface_label') }}" placeholder="North wall, roof plane, BIM wall ID">
                                </div>
                                <div class="col-6">
                                    <label class="twin-label" for="confidence">Confidence %</label>
                                    <input id="confidence" name="confidence" type="number" min="0" max="100" step="0.01" class="form-control" value="{{ old('confidence') }}">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="twin-label" for="source_reference">Source reference</label>
                                <input id="source_reference" name="source_reference" type="text" class="form-control" value="{{ old('source_reference') }}" placeholder="File name, scan station, panorama node, or report page">
                            </div>
                            <div class="mb-3">
                                <label class="twin-label" for="description">Description</label>
                                <textarea id="description" name="description" class="form-control" rows="3" placeholder="What was observed and why it matters">{{ old('description') }}</textarea>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="mdi mdi-map-marker-plus-outline me-1"></i>Add Marker
                            </button>
                            <a href="{{ route('inspections.phar-data', $inspection) }}" class="btn btn-outline-primary w-100 mt-2">
                                <i class="mdi mdi-clipboard-plus-outline me-1"></i>Create or edit PHAR finding
                            </a>
                        </form>
                    </div>
                </section>
            </aside>
        @endif
    </div>
</div>
@endsection

@push('scripts')
    @vite('resources/js/digital-twin-viewer.js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var sourceFile = document.getElementById('source_file');
            var warning = document.getElementById('sourceFileWarning');
            var originalFormat = document.getElementById('original_format');
            var runtimeFormat = document.getElementById('runtime_format');
            var captureType = document.getElementById('capture_type');
            var sourceType = document.getElementById('source_type');
            var submitButton = sourceFile ? sourceFile.closest('form').querySelector('button[type="submit"]') : null;

            if (!sourceFile || !warning) {
                return;
            }

            var pointCloudExtensions = ['e57', 'las', 'laz'];
            var imageExtensions = ['jpg', 'jpeg', 'png', 'webp'];

            function setSelectValue(select, value) {
                if (!select) {
                    return;
                }

                var option = Array.from(select.options).find(function (item) {
                    return item.value === value;
                });

                if (option) {
                    select.value = value;
                }
            }

            function formatBytes(bytes) {
                if (bytes >= 1024 * 1024 * 1024) {
                    return (bytes / 1024 / 1024 / 1024).toFixed(2) + ' GB';
                }

                return (bytes / 1024 / 1024).toFixed(1) + ' MB';
            }

            sourceFile.addEventListener('change', function () {
                var file = sourceFile.files && sourceFile.files[0];
                var maxBytes = parseInt(sourceFile.dataset.maxUploadBytes || '0', 10);
                var maxMb = sourceFile.dataset.maxUploadMb || '100';

                warning.classList.remove('is-visible');
                warning.textContent = '';
                if (submitButton) {
                    submitButton.disabled = false;
                }

                if (!file) {
                    return;
                }

                var parts = file.name.split('.');
                var extension = parts.length > 1 ? parts.pop().toLowerCase() : '';

                if (extension && originalFormat && !originalFormat.value) {
                    originalFormat.value = extension.toUpperCase();
                }

                if (pointCloudExtensions.includes(extension)) {
                    setSelectValue(captureType, 'point_cloud');
                    setSelectValue(sourceType, 'master_point_cloud');
                    if (runtimeFormat) {
                        runtimeFormat.value = '';
                    }
                } else if (extension === 'obj' || extension === 'zip') {
                    setSelectValue(captureType, 'obj_mesh');
                    setSelectValue(sourceType, 'runtime_3d_model');
                    if (runtimeFormat) {
                        runtimeFormat.value = '';
                    }
                } else if (extension === 'glb' || extension === 'gltf') {
                    setSelectValue(captureType, 'glb_model');
                    setSelectValue(sourceType, 'runtime_3d_model');
                    if (runtimeFormat && !runtimeFormat.value) {
                        runtimeFormat.value = extension.toUpperCase();
                    }
                } else if (imageExtensions.includes(extension)) {
                    setSelectValue(captureType, 'photo_set');
                    setSelectValue(sourceType, 'document_reference');
                } else if (extension === 'pdf') {
                    setSelectValue(captureType, 'document');
                    setSelectValue(sourceType, 'document_reference');
                }

                if (maxBytes > 0 && file.size > maxBytes) {
                    warning.textContent = 'This file is ' + formatBytes(file.size) + '. The current browser upload limit is ' + maxMb + ' MB, so this upload will fail until WAMP/PHP and the app limit are increased.';
                    warning.classList.add('is-visible');
                    if (submitButton) {
                        submitButton.disabled = true;
                    }
                }
            });
        });

        document.addEventListener('DOMContentLoaded', function () {
            var markerForm = document.querySelector('[data-issue-marker-form]');

            if (!markerForm) {
                return;
            }

            function setMarkerField(name, value, overwrite) {
                var field = markerForm.querySelector('[name="' + name + '"]');

                if (!field || (overwrite === false && field.value)) {
                    return;
                }

                if (field.tagName === 'SELECT') {
                    var hasOption = Array.from(field.options).some(function (option) {
                        return option.value === String(value || '');
                    });

                    if (!hasOption) {
                        return;
                    }
                }

                field.value = value || '';
                field.dispatchEvent(new Event('change', { bubbles: true }));
            }

            document.querySelectorAll('[data-twin-add-marker]').forEach(function (button) {
                button.addEventListener('click', function () {
                    setMarkerField('capture_session_id', button.dataset.captureSessionId || '');
                    setMarkerField('spatial_model_id', button.dataset.spatialModelId || '');
                    setMarkerField('source_provider', button.dataset.sourceProvider || 'manual');
                    setMarkerField('source_reference', button.dataset.sourceReference || '', false);

                    var createFinding = markerForm.querySelector('[name="create_phar_finding"]');
                    var pharFinding = markerForm.querySelector('[name="phar_finding_id"]');
                    if (createFinding && pharFinding && !pharFinding.value) {
                        createFinding.checked = true;
                    }

                    markerForm.scrollIntoView({ behavior: 'smooth', block: 'start' });

                    var title = markerForm.querySelector('[name="title"]');
                    if (title && !title.value) {
                        title.focus({ preventScroll: true });
                    }
                });
            });
        });
    </script>
@endpush
