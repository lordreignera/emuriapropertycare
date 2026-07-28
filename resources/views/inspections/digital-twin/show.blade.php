@extends($layout)

@section('title', 'Property Digital Twin')
@section('header', 'Property Digital Twin')

@section('content')
@php
    $propertyName = $property?->property_name ?: 'Property';
    $inspectionReference = 'Diagnosis #' . $inspection->id;
    $primaryModel = $spatialModels->firstWhere('is_primary', true) ?? $spatialModels->first();
    $legacyMatterportModel = $legacyMatterportModel ?? null;
    $statusLabel = str_replace('_', ' ', ucfirst((string) ($inspection->status ?? 'scheduled')));
    $viewerSources = $spatialModels->map(function ($model) {
        $externalUrl = $model->external_url;

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
            'fileUrl' => $model->file_url,
            'thumbnailUrl' => $model->thumbnail_url,
            'externalUrl' => $externalUrl,
            'downloadUrl' => $model->file_url ?: $externalUrl,
            'runtimeFormat' => $model->runtime_format,
            'originalFormat' => $model->original_format,
            'accuracyClass' => $model->accuracy_class,
            'processingStatus' => $model->processing_status,
            'isPrimary' => (bool) $model->is_primary,
            'extension' => $model->detected_extension,
            'conversionError' => data_get($model->metadata, 'conversion_error'),
        ];
    })->values();

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
            'processingStatus' => 'ready',
            'isPrimary' => true,
            'extension' => null,
        ]]);
    }

    $initialViewerSource = $viewerSources->firstWhere('isPrimary', true) ?: $viewerSources->first();
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
    $serverUploadBytes = min(
        $iniBytes(ini_get('upload_max_filesize')),
        $iniBytes(ini_get('post_max_size')),
        max(1, (int) config('digital_twin.upload_max_kilobytes', 102400)) * 1024
    );
    $serverUploadMb = max(1, (int) floor($serverUploadBytes / 1024 / 1024));
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

    .twin-source-button span {
        display: block;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .twin-viewer-stage {
        min-height: 560px;
        background: #07111f;
    }

    .twin-three-stage,
    .twin-panorama-stage {
        position: relative;
        height: 560px;
        min-height: 500px;
        background: #07111f;
    }

    .twin-three-stage canvas,
    .twin-panorama-stage canvas {
        display: block;
        width: 100% !important;
        height: 100% !important;
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

    .twin-preview-image {
        width: 100%;
        max-height: 640px;
        display: block;
        object-fit: contain;
        background: #07111f;
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
        height: 560px;
        min-height: 500px;
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

    .twin-list-item {
        padding: 12px;
        box-shadow: none;
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
        .twin-layout,
        .twin-form-grid,
        .twin-advanced-inner {
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
                <span class="twin-pill">
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
                <h3>Add Capture Source</h3>
                <span class="badge bg-primary">Vendor neutral</span>
            </div>
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
                            <input id="display_name" name="display_name" type="text" class="form-control" value="{{ old('display_name') }}" placeholder="Matterport E57 point cloud">
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
                                accept=".glb,.gltf,.obj,.fbx,.dae,.ply,.e57,.las,.laz,.pts,.ptx,.xyz,.zip,.jpg,.jpeg,.png,.webp,.pdf,.heic,.heif"
                                data-max-upload-bytes="{{ $serverUploadBytes }}"
                                data-max-upload-mb="{{ $serverUploadMb }}"
                            >
                            <small class="twin-help">Current browser upload limit: {{ $serverUploadMb }} MB. Very large E57 uploads also need matching WAMP/PHP limits and may take time.</small>
                            <div class="twin-upload-warning" id="sourceFileWarning"></div>
                        </div>
                        <div>
                            <label class="twin-label" for="external_url">External URL</label>
                            <input id="external_url" name="external_url" type="url" class="form-control" value="{{ old('external_url') }}" placeholder="https://my.matterport.com/show/?m=...">
                            <small class="twin-help">Use this for hosted Matterport tours or cloud-hosted source files.</small>
                        </div>
                        <div>
                            <label class="twin-label" for="processing_status">Processing</label>
                            <select id="processing_status" name="processing_status" class="form-select">
                                @foreach(['ready' => 'Ready', 'queued' => 'Queued', 'processing' => 'Processing', 'failed' => 'Failed'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('processing_status', 'ready') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
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
        </section>
    @endif

    <div class="twin-layout">
        <main>
            <section class="twin-viewer">
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

                        <div class="twin-viewer-sourcebar" role="tablist" aria-label="Digital twin sources">
                            @foreach($viewerSources as $source)
                                <button
                                    type="button"
                                    class="twin-source-button"
                                    data-twin-source-button="{{ $source['id'] }}"
                                    title="{{ $source['title'] }}">
                                    <i class="mdi mdi-cube-outline"></i>
                                    <span>
                                        <strong>{{ $source['title'] }}</strong>
                                        <small class="d-block text-muted">{{ $source['providerLabel'] }} / {{ $source['sourceTypeLabel'] }}</small>
                                    </span>
                                </button>
                            @endforeach
                        </div>

                        <div class="twin-viewer-stage" data-twin-stage>
                            <div class="twin-viewer-card">
                                <div>
                                    <h3>Loading digital twin source</h3>
                                    <p>The viewer is preparing this capture source.</p>
                                </div>
                            </div>
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

            <section class="twin-panel">
                <div class="twin-panel-header">
                    <h3>Spatial Models & Evidence Layers</h3>
                    <span class="badge bg-light text-dark">{{ $spatialModels->count() }} source{{ $spatialModels->count() === 1 ? '' : 's' }}</span>
                </div>
                <div class="twin-panel-body">
                    @if($spatialModels->isNotEmpty())
                        <div class="twin-list">
                            @foreach($spatialModels as $model)
                                <div class="twin-list-item">
                                    <div class="d-flex justify-content-between gap-2">
                                        <div class="twin-item-title">{{ $model->display_name ?: $model->source_type_label }}</div>
                                        <div class="d-flex flex-wrap gap-1 justify-content-end">
                                            <span class="badge {{ $model->is_primary ? 'bg-primary' : 'bg-light text-dark' }}">{{ $model->is_primary ? 'Primary' : ucfirst($model->status) }}</span>
                                            <span class="badge {{ $model->processing_status === 'ready' ? 'bg-success' : ($model->processing_status === 'failed' ? 'bg-danger' : 'bg-warning text-dark') }}">
                                                {{ ucfirst(str_replace('_', ' ', $model->processing_status)) }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="twin-meta">
                                        <span><i class="mdi mdi-camera-outline me-1"></i>{{ $model->provider_label }}</span>
                                        <span><i class="mdi mdi-cube-outline me-1"></i>{{ $model->source_type_label }}</span>
                                        <span><i class="mdi mdi-file-outline me-1"></i>{{ $model->original_format ?: 'format not recorded' }}</span>
                                        <span><i class="mdi mdi-ruler-square me-1"></i>{{ $model->accuracy_class ?: 'accuracy not stated' }}</span>
                                    </div>
                                    @if($model->isRawPointCloud())
                                        <div class="mt-3 d-flex flex-wrap align-items-center gap-2">
                                            @if($canManageDigitalTwin && in_array($model->processing_status, ['queued', 'failed'], true))
                                                <form method="POST" action="{{ route('inspections.digital-twin.models.convert', [$inspection, $model]) }}" class="m-0">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-primary">
                                                        <i class="mdi mdi-cog-transfer-outline me-1"></i>
                                                        {{ $model->processing_status === 'failed' ? 'Retry conversion' : 'Run conversion' }}
                                                    </button>
                                                </form>
                                            @endif
                                            @if($model->processing_status === 'processing')
                                                <span class="small text-muted">Conversion is running in the digital-twin queue.</span>
                                            @elseif($model->processing_status === 'queued')
                                                <span class="small text-muted">Waiting for worker: <code>php artisan queue:work --queue=digital-twin,default</code></span>
                                            @endif
                                        </div>
                                        @if(data_get($model->metadata, 'conversion_error'))
                                            <div class="alert alert-warning mt-3 mb-0 py-2">
                                                {{ data_get($model->metadata, 'conversion_error') }}
                                            </div>
                                        @endif
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="twin-empty">No spatial models or evidence layers have been attached to this diagnosis yet.</div>
                    @endif
                </div>
            </section>
        </main>

        <aside>
            @if($canCreateIssueMarkers)
                <section class="twin-panel">
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
                        </form>
                    </div>
                </section>
            @endif

            <section class="twin-panel">
                <div class="twin-panel-header">
                    <h3>Issue Markers</h3>
                    <span class="badge bg-light text-dark">{{ $issueMarkers->count() }}</span>
                </div>
                <div class="twin-panel-body">
                    @if($issueMarkers->isNotEmpty())
                        <div class="twin-list">
                            @foreach($issueMarkers as $marker)
                                <div class="twin-list-item">
                                    <div class="twin-item-title">
                                        <span class="twin-marker-dot is-{{ $marker->severity }}"></span>{{ $marker->title }}
                                    </div>
                                    <div class="small text-muted mt-1">{{ $marker->description ?: 'No description recorded.' }}</div>
                                    <div class="twin-meta">
                                        <span>{{ ucfirst($marker->severity) }}</span>
                                        <span>{{ ucfirst(str_replace('_', ' ', $marker->source_provider)) }}</span>
                                        <span>{{ $marker->room_name ?: 'location not recorded' }}</span>
                                        @if($marker->surface_label)
                                            <span>{{ $marker->surface_label }}</span>
                                        @endif
                                        @if($marker->confidence !== null)
                                            <span>{{ $marker->confidence }}% confidence</span>
                                        @endif
                                        @if($marker->source_reference)
                                            <span>{{ $marker->source_reference }}</span>
                                        @endif
                                        @if($marker->position_x !== null)
                                            <span>X {{ $marker->position_x }}, Y {{ $marker->position_y }}, Z {{ $marker->position_z }}</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="twin-empty">No issue markers have been placed yet.</div>
                    @endif
                </div>
            </section>
        </aside>
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
            var processingStatus = document.getElementById('processing_status');
            var submitButton = sourceFile ? sourceFile.closest('form').querySelector('button[type="submit"]') : null;

            if (!sourceFile || !warning) {
                return;
            }

            var conversionExtensions = ['obj', 'fbx', 'dae', 'ply', 'e57', 'las', 'laz', 'pts', 'ptx', 'xyz', 'zip'];
            var pointCloudExtensions = ['e57', 'las', 'laz', 'pts', 'ptx', 'xyz'];
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
                    if (runtimeFormat && !runtimeFormat.value) {
                        runtimeFormat.value = 'conversion_needed';
                    }
                    setSelectValue(processingStatus, 'queued');
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
                } else if (conversionExtensions.includes(extension)) {
                    if (runtimeFormat && !runtimeFormat.value) {
                        runtimeFormat.value = 'conversion_needed';
                    }
                    setSelectValue(processingStatus, 'queued');
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
    </script>
@endpush
