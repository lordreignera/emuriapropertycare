@extends('admin.layout')

@section('title', 'PHAR FORM')

@section('content')
<div class="content-wrapper">
    <div class="row">
        <div class="col-12">
            <div class="card mb-3 border-0 shadow-sm" style="background: linear-gradient(135deg, #5b67ca 0%, #4854b8 100%);">
                <div class="card-body text-white p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-2 fw-bold">
                                <i class="mdi mdi-home-city-outline me-2"></i>Property Health Assessment Form
                            </h3>
                            <p class="mb-1 opacity-75">
                                <span class="badge bg-light text-dark me-2">{{ $property->property_code }}</span>
                                {{ $property->property_name }}
                            </p>
                            <p class="mb-0 opacity-75">
                                <i class="mdi mdi-map-marker me-1"></i>{{ $property->property_address }}, {{ $property->city }}
                            </p>
                        </div>
                        <div>
                            <span class="badge bg-warning text-dark fs-6 px-3 py-2">
                                {{ ucfirst(str_replace('_', ' ', $property->type)) }} Property
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <form action="{{ route('inspections.store') }}" method="POST" enctype="multipart/form-data" id="inspectionSystemsForm">
                @csrf
                <input type="hidden" name="property_id" value="{{ $property->id }}">
                <input type="hidden" name="service_request_id" value="{{ old('service_request_id', $serviceRequest->id ?? '') }}">
                <input type="hidden" name="status" value="in_progress">

                @if($errors->any())
                    <div class="alert alert-danger">
                        <strong>We could not save the inspection form.</strong>
                        <ul class="mb-0 mt-2">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="card mb-4">
                    <div class="card-header" style="background: #5b67ca; color: white;">
                        <h5 class="mb-0">
                            <i class="mdi mdi-information-outline me-2"></i>Inspection Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Inspection Date & Time <span class="text-danger">*</span></label>
                                    <input type="datetime-local" name="inspection_date" class="form-control" value="{{ old('inspection_date', optional($inspection->scheduled_date)->format('Y-m-d\\TH:i') ?? now()->format('Y-m-d\\TH:i')) }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Inspector</label>
                                    <input type="text" class="form-control" value="{{ Auth::user()->name }}" readonly>
                                    <input type="hidden" name="inspector_id" value="{{ Auth::id() }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Weather Condition</label>
                                    <select name="weather_conditions" class="form-control">
                                        <option value="" {{ old('weather_conditions', $inspection->weather_conditions ?? '') === '' ? 'selected' : '' }}>Select weather</option>
                                        <option value="clear" {{ old('weather_conditions', $inspection->weather_conditions ?? '') === 'clear' ? 'selected' : '' }}>Clear</option>
                                        <option value="cloudy" {{ old('weather_conditions', $inspection->weather_conditions ?? '') === 'cloudy' ? 'selected' : '' }}>Cloudy</option>
                                        <option value="rainy" {{ old('weather_conditions', $inspection->weather_conditions ?? '') === 'rainy' ? 'selected' : '' }}>Rainy</option>
                                        <option value="snowy" {{ old('weather_conditions', $inspection->weather_conditions ?? '') === 'snowy' ? 'selected' : '' }}>Snowy</option>
                                        <option value="windy" {{ old('weather_conditions', $inspection->weather_conditions ?? '') === 'windy' ? 'selected' : '' }}>Windy</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">
                        <h6 class="text-primary">Property Owner</h6>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Name</label>
                                    <input type="text" class="form-control" value="{{ $property->user->name ?? 'N/A' }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="text" class="form-control" value="{{ $property->user->email ?? 'N/A' }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Phone</label>
                                    <input type="text" class="form-control" value="{{ $property->owner_phone ?: (($property->user->phone ?? null) ?: ($property->admin_phone ?: 'N/A')) }}" readonly>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">
                        <h6 class="text-primary">Property Information</h6>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Property Type</label>
                                    <input type="text" class="form-control" value="{{ ucfirst(str_replace('_', ' ', $property->type)) }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Year Built</label>
                                    <input type="text" class="form-control" value="{{ $property->year_built ?? 'N/A' }}" readonly>
                                </div>
                            </div>
                            @if(in_array($property->type, ['residential', 'mixed_use']))
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Residential Units</label>
                                        <input type="text" class="form-control" value="{{ $property->number_of_units ?: ($property->residential_units ?? 0) }}" readonly>
                                    </div>
                                </div>
                            @endif
                            @if(in_array($property->type, ['commercial', 'mixed_use']))
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Commercial Insights (SqFt)</label>
                                        <input type="text" class="form-control" value="{{ $property->square_footage_interior ?? 0 }}" readonly>
                                    </div>
                                </div>
                            @endif
                            @if($property->type === 'mixed_use')
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Commercial Weight (%)</label>
                                        <input type="text" class="form-control" value="{{ $property->mixed_use_commercial_weight ?? 0 }}" readonly>
                                    </div>
                                </div>
                            @endif
                        </div>


                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="mdi mdi-view-list-outline me-2 text-primary"></i>Property Systems Inspection
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">Add findings per system — each finding is a card showing all fields at a glance.</p>

                        @if(!empty($serviceRequest))
                            <div class="alert alert-info">
                                <strong>Seeded from Service Request:</strong> {{ $serviceRequest->request_number }}
                                <div class="small mt-1">Client-reported items are preloaded below as initial findings. Reassign systems/subsystems as needed before saving.</div>
                            </div>
                        @endif

                        @if($systems->isEmpty())
                            <div class="alert alert-warning mb-0">
                                No systems found. Run database seeding for systems/subsystems first.
                            </div>
                        @else
                            @foreach($systems as $system)
                                <div class="card mb-3 border">
                                    <div class="card-header d-flex justify-content-between align-items-center" style="background:#f8f9fc;">
                                        <div>
                                            <strong>{{ $system->name }}</strong>
                                            @if($system->description)
                                                <span class="text-muted ms-2 small">{{ $system->description }}</span>
                                            @endif
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addSystemFindingRow({{ $system->id }})">
                                            <i class="mdi mdi-plus"></i> Add Finding
                                        </button>
                                    </div>
                                    <div class="card-body p-2" id="system-rows-{{ $system->id }}">
                                        <p class="text-muted small mb-0 px-1" id="system-empty-{{ $system->id }}">No findings added yet. Click <strong>Add Finding</strong> to record an issue.</p>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="mdi mdi-clipboard-text me-2 text-primary"></i>Overall Assessment
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Overall Condition</label>
                                    <select name="overall_condition" class="form-control">
                                        <option value="">Select condition</option>
                                        <option value="excellent" {{ old('overall_condition', $inspection->overall_condition ?? '') === 'excellent' ? 'selected' : '' }}>Excellent</option>
                                        <option value="good" {{ old('overall_condition', $inspection->overall_condition ?? '') === 'good' ? 'selected' : '' }}>Good</option>
                                        <option value="fair" {{ old('overall_condition', $inspection->overall_condition ?? '') === 'fair' ? 'selected' : '' }}>Fair</option>
                                        <option value="poor" {{ old('overall_condition', $inspection->overall_condition ?? '') === 'poor' ? 'selected' : '' }}>Poor</option>
                                        <option value="critical" {{ old('overall_condition', $inspection->overall_condition ?? '') === 'critical' ? 'selected' : '' }}>Critical</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Inspector Notes</label>
                            <textarea name="inspector_notes" class="form-control" rows="3" placeholder="Inspector observations">{{ old('inspector_notes', $inspection->inspector_notes ?? '') }}</textarea>
                        </div>
                        <div class="form-group">
                            <label>Recommendations</label>
                            <textarea name="recommendations" class="form-control" rows="3" placeholder="Final recommendations">{{ old('recommendations', $inspection->recommendations ?? '') }}</textarea>
                        </div>
                        <div class="form-group mb-0">
                            <label>Risk Summary</label>
                            <textarea name="risk_summary" class="form-control" rows="3" placeholder="Major risks identified">{{ old('risk_summary', $inspection->risk_summary ?? '') }}</textarea>
                        </div>
                    </div>
                </div>

                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('inspections.index') }}" class="btn btn-secondary">
                                <i class="mdi mdi-arrow-left me-1"></i>Cancel
                            </a>
                            <div>
                                <small id="formAutosaveStatus" class="text-muted me-3">Autosave: waiting...</small>
                                <button type="submit" class="btn btn-warning me-2">
                                    <i class="mdi mdi-content-save me-1"></i>Save as Draft
                                </button>
                                <button type="submit" name="next_stage" value="phar" class="btn btn-success">
                                    <i class="mdi mdi-arrow-right-bold-circle me-1"></i>Save Form & review costs
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@php
    $findingTemplatesRaw = \App\Models\FindingTemplateSetting::query()
        ->where('is_active', true)
        ->orderBy('sort_order')
        ->get(['task_question', 'system_id', 'subsystem_id']);

    $recommendationSettingsRaw = \App\Models\RecommendationSetting::query()
        ->where('is_active', true)
        ->orderBy('sort_order')
        ->orderBy('recommendation')
        ->get(['recommendation', 'system_id', 'subsystem_id']);

    $recommendationConfig = [
        'global' => [],
        'system' => [],
        'subsystem' => [],
    ];

    foreach ($recommendationSettingsRaw as $recommendationRow) {
        $recommendationText = trim((string) ($recommendationRow->recommendation ?? ''));
        if ($recommendationText === '') {
            continue;
        }

        if (!empty($recommendationRow->subsystem_id)) {
            $key = (string) $recommendationRow->subsystem_id;
            $recommendationConfig['subsystem'][$key] = $recommendationConfig['subsystem'][$key] ?? [];
            $recommendationConfig['subsystem'][$key][] = $recommendationText;
            continue;
        }

        if (!empty($recommendationRow->system_id)) {
            $key = (string) $recommendationRow->system_id;
            $recommendationConfig['system'][$key] = $recommendationConfig['system'][$key] ?? [];
            $recommendationConfig['system'][$key][] = $recommendationText;
            continue;
        }

        $recommendationConfig['global'][] = $recommendationText;
    }

    $recommendationConfig['global'] = array_values(array_unique($recommendationConfig['global']));
    $recommendationConfig['system'] = collect($recommendationConfig['system'])
        ->map(fn($rows) => array_values(array_unique($rows)))
        ->all();
    $recommendationConfig['subsystem'] = collect($recommendationConfig['subsystem'])
        ->map(fn($rows) => array_values(array_unique($rows)))
        ->all();

    // Full PHAR catalog keyed by "system|subsystem|finding" for JS auto-fill
    $pharFindingCatalog = \App\Support\PharCatalog::findingCatalog();

    $systemsConfig = $systems->map(function ($system) use ($findingTemplatesRaw) {
        $systemLevelFindings = $findingTemplatesRaw
            ->where('system_id', $system->id)
            ->whereNull('subsystem_id')
            ->pluck('task_question')
            ->values()
            ->all();

        return [
            'id' => $system->id,
            'name' => $system->name,
            'recommended_actions' => collect($system->recommended_actions ?? [])->values()->all(),
            'findings' => $systemLevelFindings,
            'subsystems' => $system->subsystems->map(function ($subsystem) use ($findingTemplatesRaw) {
                return [
                    'id' => $subsystem->id,
                    'name' => $subsystem->name,
                    'recommended_actions' => collect($subsystem->recommended_actions ?? [])->values()->all(),
                    'findings' => $findingTemplatesRaw
                        ->where('subsystem_id', $subsystem->id)
                        ->pluck('task_question')
                        ->values()
                        ->all(),
                ];
            })->values()->all(),
        ];
    })->values()->all();

    // Pre-generate per-finding photo URLs server-side so they work for both
    // local/public storage and private S3 buckets (which require signed temp URLs).
    $findingPhotoUrls = [];
    foreach (($inspection->findings ?? []) as $fi => $finding) {
        $paths = is_array($finding['finding_photos'] ?? null) ? $finding['finding_photos'] : [];
        if (!empty($paths)) {
            $findingPhotoUrls[$fi] = array_map(
                fn($p) => $inspection->getStorageUrl($p),
                $paths
            );
        }
    }
@endphp

<script>
const systemsConfig = @json($systemsConfig);
const CPI_PROPERTY_ID = {{ $property->id }};
const CPI_AUTOSAVE_URL = @json(route('inspections.autosave-draft'));
const MATERIAL_UNITS = @json($materialUnits ?? []);
const FMC_MATERIAL_SETTINGS = @json($fmcMaterialSettings ?? []);
const PHAR_CATEGORIES = @json($pharCategories ?? []);
const PHAR_FINDING_CATALOG = @json($pharFindingCatalog ?? []);
const RECOMMENDATION_CONFIG = @json($recommendationConfig ?? ['global' => [], 'system' => [], 'subsystem' => []]);
// Photo URLs pre-resolved server-side (works for local disk and private S3 signed URLs)
const FINDING_PHOTO_URLS = @json($findingPhotoUrls ?? []);
// Stored paths (for hidden input preservation on re-submit — keyed by [findingIndex][photoIndex])
const FINDING_PHOTO_PATHS = @json(array_map(fn($f) => is_array($f['finding_photos'] ?? null) ? $f['finding_photos'] : [], ($inspection->findings ?? [])));
function getStoredPhotoPath(findingIdx, photoIdx) {
    return (FINDING_PHOTO_PATHS?.[findingIdx]?.[photoIdx]) ?? '';
}

const initialFindings = @json(old('system_findings', $seededSystemFindings ?? $inspection->findings ?? []));
let findingIndex = 0;
let findingMediaViewerState = {
    items: [],
    index: 0,
    zoom: 1,
    rotation: 0,
};

function escapeHtml(value) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };

    return String(value || '').replace(/[&<>"']/g, function (match) {
        return map[match];
    });
}

function isVideoMedia(pathOrName) {
    const clean = String(pathOrName || '').split('?')[0].toLowerCase();
    return /\.(mp4|webm|mov|avi|mkv|m4v)$/.test(clean);
}

function ensureFindingMediaViewer() {
    if (document.getElementById('finding-media-viewer')) {
        return;
    }

    const viewer = document.createElement('div');
    viewer.id = 'finding-media-viewer';
    viewer.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.82);z-index:12000;display:none;';
    viewer.innerHTML = `
        <div style="position:absolute;top:14px;left:50%;transform:translateX(-50%);display:flex;gap:8px;flex-wrap:wrap;justify-content:center;">
            <button type="button" class="btn btn-sm btn-light media-viewer-prev"><i class="mdi mdi-chevron-left"></i> Prev</button>
            <button type="button" class="btn btn-sm btn-light media-viewer-next">Next <i class="mdi mdi-chevron-right"></i></button>
            <button type="button" class="btn btn-sm btn-light media-viewer-zoom-out"><i class="mdi mdi-magnify-minus-outline"></i> Zoom Out</button>
            <button type="button" class="btn btn-sm btn-light media-viewer-zoom-in"><i class="mdi mdi-magnify-plus-outline"></i> Zoom In</button>
            <button type="button" class="btn btn-sm btn-light media-viewer-rotate-left"><i class="mdi mdi-rotate-left"></i> Rotate Left</button>
            <button type="button" class="btn btn-sm btn-light media-viewer-rotate-right"><i class="mdi mdi-rotate-right"></i> Rotate Right</button>
            <button type="button" class="btn btn-sm btn-danger media-viewer-close"><i class="mdi mdi-close"></i> Close</button>
        </div>
        <div style="position:absolute;inset:66px 30px 30px 30px;display:flex;align-items:center;justify-content:center;overflow:auto;">
            <img class="media-viewer-image" alt="Finding media" style="max-width:100%;max-height:100%;object-fit:contain;display:none;transform-origin:center center;">
            <video class="media-viewer-video" controls style="max-width:100%;max-height:100%;display:none;transform-origin:center center;background:#000;"></video>
        </div>
        <div class="media-viewer-caption" style="position:absolute;bottom:10px;left:0;right:0;text-align:center;color:#fff;font-size:12px;"></div>
    `;

    document.body.appendChild(viewer);

    const closeBtn = viewer.querySelector('.media-viewer-close');
    const prevBtn = viewer.querySelector('.media-viewer-prev');
    const nextBtn = viewer.querySelector('.media-viewer-next');
    const zoomInBtn = viewer.querySelector('.media-viewer-zoom-in');
    const zoomOutBtn = viewer.querySelector('.media-viewer-zoom-out');
    const rotateLeftBtn = viewer.querySelector('.media-viewer-rotate-left');
    const rotateRightBtn = viewer.querySelector('.media-viewer-rotate-right');

    closeBtn?.addEventListener('click', closeFindingMediaViewer);
    prevBtn?.addEventListener('click', () => stepFindingMedia(-1));
    nextBtn?.addEventListener('click', () => stepFindingMedia(1));
    zoomInBtn?.addEventListener('click', () => {
        findingMediaViewerState.zoom = Math.min(4, Number((findingMediaViewerState.zoom + 0.2).toFixed(2)));
        renderFindingMediaViewer();
    });
    zoomOutBtn?.addEventListener('click', () => {
        findingMediaViewerState.zoom = Math.max(0.4, Number((findingMediaViewerState.zoom - 0.2).toFixed(2)));
        renderFindingMediaViewer();
    });
    rotateLeftBtn?.addEventListener('click', () => {
        findingMediaViewerState.rotation -= 90;
        renderFindingMediaViewer();
    });
    rotateRightBtn?.addEventListener('click', () => {
        findingMediaViewerState.rotation += 90;
        renderFindingMediaViewer();
    });

    viewer.addEventListener('click', function (event) {
        if (event.target === viewer) {
            closeFindingMediaViewer();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (viewer.style.display !== 'block') {
            return;
        }
        if (event.key === 'Escape') {
            closeFindingMediaViewer();
        } else if (event.key === 'ArrowLeft') {
            stepFindingMedia(-1);
        } else if (event.key === 'ArrowRight') {
            stepFindingMedia(1);
        }
    });
}

function closeFindingMediaViewer() {
    const viewer = document.getElementById('finding-media-viewer');
    if (!viewer) {
        return;
    }

    const video = viewer.querySelector('.media-viewer-video');
    if (video) {
        video.pause();
    }
    viewer.style.display = 'none';
}

function stepFindingMedia(direction) {
    const items = findingMediaViewerState.items || [];
    if (!items.length) {
        return;
    }

    const next = (findingMediaViewerState.index + direction + items.length) % items.length;
    findingMediaViewerState.index = next;
    findingMediaViewerState.zoom = 1;
    findingMediaViewerState.rotation = 0;
    renderFindingMediaViewer();
}

function renderFindingMediaViewer() {
    const viewer = document.getElementById('finding-media-viewer');
    if (!viewer) {
        return;
    }

    const image = viewer.querySelector('.media-viewer-image');
    const video = viewer.querySelector('.media-viewer-video');
    const caption = viewer.querySelector('.media-viewer-caption');
    const items = findingMediaViewerState.items || [];
    const current = items[findingMediaViewerState.index];
    if (!current) {
        return;
    }

    const transform = `scale(${findingMediaViewerState.zoom}) rotate(${findingMediaViewerState.rotation}deg)`;

    if (current.type === 'video') {
        if (image) {
            image.style.display = 'none';
            image.src = '';
        }
        if (video) {
            video.style.display = '';
            video.src = current.src;
            video.style.transform = transform;
        }
    } else {
        if (video) {
            video.pause();
            video.style.display = 'none';
            video.src = '';
        }
        if (image) {
            image.style.display = '';
            image.src = current.src;
            image.style.transform = transform;
        }
    }

    if (caption) {
        caption.textContent = `${findingMediaViewerState.index + 1} / ${items.length} - ${current.label}`;
    }
}

function openFindingMediaViewer(items, startIndex) {
    ensureFindingMediaViewer();
    findingMediaViewerState.items = items;
    findingMediaViewerState.index = startIndex;
    findingMediaViewerState.zoom = 1;
    findingMediaViewerState.rotation = 0;
    renderFindingMediaViewer();

    const viewer = document.getElementById('finding-media-viewer');
    if (viewer) {
        viewer.style.display = 'block';
    }
}

function initializeFindingMediaState(card, findingIndexValue) {
    if (!Array.isArray(card._findingMediaSelectedFiles)) {
        card._findingMediaSelectedFiles = [];
    }

    if (!Array.isArray(card._findingMediaSavedItems)) {
        const savedUrls = Array.isArray(FINDING_PHOTO_URLS?.[findingIndexValue]) ? FINDING_PHOTO_URLS[findingIndexValue] : [];
        card._findingMediaSavedItems = savedUrls.map((url, index) => ({
            url,
            path: getStoredPhotoPath(findingIndexValue, index),
        }));
    }
}

function syncFindingMediaInputFiles(card) {
    const input = card.querySelector('.finding-media-input');
    if (!input) {
        return;
    }

    const transfer = new DataTransfer();
    (card._findingMediaSelectedFiles || []).forEach((file) => transfer.items.add(file));
    input.files = transfer.files;
}

function appendFindingMediaFiles(card, fileList) {
    const files = Array.from(fileList || []);
    if (!files.length) {
        return;
    }

    if (!Array.isArray(card._findingMediaSelectedFiles)) {
        card._findingMediaSelectedFiles = [];
    }

    const selected = card._findingMediaSelectedFiles || [];
    const seen = new Set(selected.map((file) => `${file.name}|${file.size}|${file.lastModified}`));

    files.forEach((file) => {
        const key = `${file.name}|${file.size}|${file.lastModified}`;
        if (!seen.has(key)) {
            selected.push(file);
            seen.add(key);
        }
    });

    card._findingMediaSelectedFiles = selected;
}

function renderFindingMediaGallery(card, findingIndexValue) {
    const gallery = card.querySelector('.finding-media-gallery');
    const input = card.querySelector('.finding-media-input');
    if (!gallery || !input) {
        return;
    }

    initializeFindingMediaState(card, findingIndexValue);

    if (Array.isArray(card._findingMediaBlobUrls)) {
        card._findingMediaBlobUrls.forEach((url) => URL.revokeObjectURL(url));
    }
    card._findingMediaBlobUrls = [];

    const savedItems = Array.isArray(card._findingMediaSavedItems) ? card._findingMediaSavedItems : [];
    const selectedFiles = Array.isArray(card._findingMediaSelectedFiles) ? card._findingMediaSelectedFiles : [];
    const existingHiddenWrap = card.querySelector('.finding-existing-photos');

    if (existingHiddenWrap) {
        existingHiddenWrap.innerHTML = savedItems
            .map((saved) => `<input type="hidden" name="existing_finding_photos[${findingIndexValue}][]" value="${escapeHtml(saved.path || '')}">`)
            .join('');
    }

    const items = [];
    savedItems.forEach((saved) => {
        const mediaType = isVideoMedia(saved.url) ? 'video' : 'image';
        items.push({
            source: 'saved',
            type: mediaType,
            src: saved.url,
            path: saved.path,
            label: 'Saved ' + mediaType,
        });
    });

    selectedFiles.forEach((file) => {
        const blobUrl = URL.createObjectURL(file);
        card._findingMediaBlobUrls.push(blobUrl);
        items.push({
            source: 'new',
            type: file.type.startsWith('video/') ? 'video' : 'image',
            src: blobUrl,
            fileKey: `${file.name}|${file.size}|${file.lastModified}`,
            label: 'New ' + (file.type.startsWith('video/') ? 'video' : 'photo'),
        });
    });

    gallery.innerHTML = '';

    if (!items.length) {
        gallery.innerHTML = '<small class="text-muted">No media added yet for this finding.</small>';
        return;
    }

    items.forEach((item, idx) => {
        const wrapper = document.createElement('button');
        wrapper.type = 'button';
        wrapper.className = 'btn p-0 border rounded overflow-hidden';
        wrapper.style.cssText = 'width:88px;height:88px;position:relative;background:#fff;';

        if (item.type === 'video') {
            wrapper.innerHTML = `
                <video src="${item.src}" style="width:100%;height:100%;object-fit:cover;" muted playsinline preload="metadata"></video>
                <span style="position:absolute;right:4px;bottom:4px;background:rgba(0,0,0,.65);color:#fff;font-size:10px;padding:1px 5px;border-radius:999px;">VIDEO</span>
            `;
        } else {
            wrapper.innerHTML = `
                <img src="${item.src}" alt="Finding photo" style="width:100%;height:100%;object-fit:cover;">
                <span style="position:absolute;right:4px;bottom:4px;background:rgba(25,135,84,.85);color:#fff;font-size:10px;padding:1px 5px;border-radius:999px;">PHOTO</span>
            `;
        }

        wrapper.addEventListener('click', function () {
            openFindingMediaViewer(items, idx);
        });

        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'btn btn-sm btn-danger';
        removeBtn.innerHTML = '&times;';
        removeBtn.style.cssText = 'position:absolute;top:3px;right:3px;line-height:1;padding:1px 6px;font-size:14px;border-radius:999px;';
        removeBtn.title = 'Remove this item';
        removeBtn.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();

            if (item.source === 'saved') {
                const savedList = Array.isArray(card._findingMediaSavedItems) ? card._findingMediaSavedItems : [];
                card._findingMediaSavedItems = savedList.filter(saved => !(saved.path === item.path && saved.url === item.src));
            } else {
                const selectedList = Array.isArray(card._findingMediaSelectedFiles) ? card._findingMediaSelectedFiles : [];
                card._findingMediaSelectedFiles = selectedList.filter(file => (`${file.name}|${file.size}|${file.lastModified}`) !== item.fileKey);
                syncFindingMediaInputFiles(card);
            }

            renderFindingMediaGallery(card, findingIndexValue);
        });

        wrapper.appendChild(removeBtn);

        gallery.appendChild(wrapper);
    });
}

function getSystemConfig(systemId) {
    return systemsConfig.find(system => String(system.id) === String(systemId));
}

function buildSubsystemOptions(systemId, selectedSubsystemId = '') {
    const system = getSystemConfig(systemId);
    let options = '<option value="">General</option>';

    if (!system || !Array.isArray(system.subsystems)) {
        return options;
    }

    system.subsystems.forEach(subsystem => {
        const selected = String(subsystem.id) === String(selectedSubsystemId) ? 'selected' : '';
        options += `<option value="${subsystem.id}" ${selected}>${escapeHtml(subsystem.name)}</option>`;
    });

    return options;
}

function getSubsystemConfig(systemId, subsystemId) {
    const system = getSystemConfig(systemId);
    if (!system || !Array.isArray(system.subsystems)) {
        return null;
    }

    return system.subsystems.find(subsystem => String(subsystem.id) === String(subsystemId)) || null;
}

function collectRecommendationOptions(systemId, subsystemId = '') {
    const system = getSystemConfig(systemId);
    const subsystem = subsystemId ? getSubsystemConfig(systemId, subsystemId) : null;
    const globalRecommendations = Array.isArray(RECOMMENDATION_CONFIG?.global) ? RECOMMENDATION_CONFIG.global : [];
    const scopedSystemRecommendations = Array.isArray(RECOMMENDATION_CONFIG?.system?.[String(systemId)])
        ? RECOMMENDATION_CONFIG.system[String(systemId)]
        : [];
    const scopedSubsystemRecommendations = Array.isArray(RECOMMENDATION_CONFIG?.subsystem?.[String(subsystemId)])
        ? RECOMMENDATION_CONFIG.subsystem[String(subsystemId)]
        : [];
    const systemRecommendations = Array.isArray(system?.recommended_actions) ? system.recommended_actions : [];
    const subsystemRecommendations = Array.isArray(subsystem?.recommended_actions) ? subsystem.recommended_actions : [];

    const unique = new Set();
    [
        ...scopedSubsystemRecommendations,
        ...scopedSystemRecommendations,
        ...globalRecommendations,
        ...subsystemRecommendations,
        ...systemRecommendations,
    ].forEach(item => {
        const value = String(item || '').trim();
        if (value) {
            unique.add(value);
        }
    });

    return Array.from(unique);
}

function isKnownFinding(systemId, subsystemId, issue) {
    if (!issue) return true;
    const system = getSystemConfig(systemId);
    if (subsystemId) {
        const sub = getSubsystemConfig(systemId, subsystemId);
        if (Array.isArray(sub?.findings) && sub.findings.includes(issue)) return true;
    }
    return Array.isArray(system?.findings) && system.findings.includes(issue);
}

function buildFindingOptions(systemId, subsystemId, selectedValue) {
    selectedValue = selectedValue || '';
    const system = getSystemConfig(systemId);
    let findings = [];
    if (subsystemId) {
        const sub = getSubsystemConfig(systemId, subsystemId);
        findings = Array.isArray(sub?.findings) ? sub.findings : [];
    }
    if (findings.length === 0 && system) {
        findings = Array.isArray(system.findings) ? system.findings : [];
    }

    let options = '<option value="">-- Select Issue / Finding --</option>';
    findings.forEach(function (finding) {
        const esc = escapeHtml(finding);
        const sel = finding === selectedValue ? 'selected' : '';
        options += `<option value="${esc}" ${sel}>${esc}</option>`;
    });
    const isCustomSelected = selectedValue !== '' && !findings.includes(selectedValue);
    options += `<option value="__custom__" ${isCustomSelected ? 'selected' : ''}>Custom / Other...</option>`;
    return options;
}

/**
 * Look up a PHAR catalog entry for a given system name, subsystem name, and finding text.
 * Returns the matching catalog object or null.
 */
function lookupPharCatalog(systemName, subsystemName, findingText) {
    if (!findingText || !systemName) return null;
    const key = (systemName || '').toLowerCase().trim()
              + '|' + (subsystemName || '').toLowerCase().trim()
              + '|' + findingText.toLowerCase().trim();
    return PHAR_FINDING_CATALOG[key] || null;
}

/**
 * Auto-fill PHAR fields (labour hours, category, notes, material) on a card
 * when a catalog-known finding is selected via the issue dropdown.
 */
function applyPharCatalogToCard(card, systemName, subsystemName, findingText, currentIndex) {
    const entry = lookupPharCatalog(systemName, subsystemName, findingText);
    if (!entry) return;

    // Labour hours
    const labourInput = card.querySelector(`[name="system_findings[${currentIndex}][phar_labour_hours]"]`);
    if (labourInput && (!labourInput.value || parseFloat(labourInput.value) === 0)) {
        labourInput.value = entry.phar_labour_hours;
    }

    // Category
    const catSelect = card.querySelector(`[name="system_findings[${currentIndex}][phar_category]"]`);
    if (catSelect && !catSelect.value) {
        catSelect.value = entry.category;
    }

    // Additional notes (phar_notes)
    const notesEl = card.querySelector(`[name="system_findings[${currentIndex}][phar_notes]"]`);
    if (notesEl && !notesEl.value.trim()) {
        notesEl.value = entry.phar_notes;
    }

    // Auto-add material row if none exists and a material is defined
    const matContainer = card.querySelector('.cpi-materials-container');
    if (matContainer && matContainer.children.length === 0 && entry.material_name) {
        // Trigger the "Add Selected" button (or "Add Custom" if no preset matches) to create a row
        const addSelectedBtn = card.querySelector('.cpi-material-add-selected');
        const addCustomBtn   = card.querySelector('.cpi-material-add-custom');
        const matCustomInput = card.querySelector('.cpi-material-custom-input');
        const matTemplateSelect = card.querySelector('.cpi-material-template');

        // Try to match a preset; otherwise fall back to custom
        const presetMatch = Array.from(matTemplateSelect?.options || [])
            .find(o => o.value && o.value.toLowerCase() === String(entry.material_name || '').toLowerCase());

        if (presetMatch && matTemplateSelect) {
            matTemplateSelect.value = presetMatch.value;
            addSelectedBtn?.click();
        } else {
            if (matCustomInput) matCustomInput.value = entry.material_name;
            addCustomBtn?.click();
        }

        // Then fill that first row
        const firstRow = matContainer.querySelector('.cpi-material-row');
        if (firstRow) {
            const nameEl = firstRow.querySelector(`[name*="[material_name]"]`);
            const qtyEl  = firstRow.querySelector('.cpi-mat-qty');
            const unitEl = firstRow.querySelector(`select[name*="[unit]"]`);
            const costEl = firstRow.querySelector('.cpi-mat-cost');
            if (nameEl) nameEl.value = entry.material_name;
            if (qtyEl)  qtyEl.value  = entry.material_quantity;
            if (unitEl && entry.unit) {
                unitEl.value = entry.unit;
            } else if (unitEl && presetMatch?.dataset?.unit) {
                unitEl.value = presetMatch.dataset.unit;
            }

            // For preset materials, keep the precomputed taxed unit cost from FMC option data.
            // Only fall back to entry.unit_cost when no preset match exists (custom mode).
            if (costEl) {
                if (presetMatch?.dataset?.cost) {
                    costEl.value = presetMatch.dataset.cost;
                } else if (entry.unit_cost != null) {
                    costEl.value = entry.unit_cost;
                }
            }
            // Trigger line total recalc
            costEl?.dispatchEvent(new Event('input'));
        }
    }

    // Add recommendation if none has been added yet
    const recList = card.querySelector('.recommendation-tags-container, .recommendation-list, .recommendation-items');
    const recInput = card.querySelector('.recommendation-select');
    if (recInput && entry.recommendation) {
        // Only auto-add if the builder is empty
        const existingTags = card.querySelectorAll('.recommendation-tag, .recommendation-item');
        if (existingTags.length === 0) {
            // Try to find matching option in recommendation select and trigger "add"
            const opts = recInput.querySelectorAll('option');
            let matched = false;
            opts.forEach(opt => {
                if (opt.value === entry.recommendation || opt.textContent.trim() === entry.recommendation) {
                    recInput.value = opt.value;
                    matched = true;
                }
            });
            if (matched) {
                const addBtn = card.querySelector('.recommendation-add-selected');
                if (addBtn) addBtn.click();
            }
        }
    }
}

function normalizeRecommendationItems(value) {
    if (Array.isArray(value)) {
        return value.map(item => String(item || '').trim()).filter(item => item.length > 0);
    }

    return String(value || '')
        .split(/\r\n|\r|\n|\|/)
        .map(item => item.trim())
        .filter(item => item.length > 0);
}

function buildMaterialUnitsOptions() {
    return MATERIAL_UNITS.map((unit, idx) =>
        `<option value="${unit}" ${idx === 0 ? 'selected' : ''}>${unit.replace(/\b\w/g, c => c.toUpperCase())}</option>`
    ).join('');
}

function buildCpiMaterialPresetOptions(searchTerm = '', subsystemId = null) {
    const normalizedSearch = String(searchTerm || '').trim().toLowerCase();

    const scopedSettings = [...FMC_MATERIAL_SETTINGS].sort((left, right) => {
        const leftScore = subsystemId && String(left.subsystem_id || '') === String(subsystemId) ? 1 : 0;
        const rightScore = subsystemId && String(right.subsystem_id || '') === String(subsystemId) ? 1 : 0;

        if (leftScore !== rightScore) {
            return rightScore - leftScore;
        }

        return String(left.material_name || '').localeCompare(String(right.material_name || ''));
    });

    const seen = new Set();
    let html = '<option value="">Custom / Manual</option>';
    scopedSettings.forEach((setting) => {
        const safeName  = String(setting.material_name ?? '');
        if (!safeName) {
            return;
        }

        const dedupeKey = safeName.toLowerCase();
        if (seen.has(dedupeKey)) {
            return;
        }

        if (normalizedSearch && !dedupeKey.includes(normalizedSearch)) {
            return;
        }

        seen.add(dedupeKey);

        const safeUnit  = String(setting.default_unit ?? 'ea');
        const rawCost   = Number(setting.default_unit_cost ?? 0);
        const hst       = Number(setting.hst_rate ?? 5);
        const pst       = Number(setting.pst_rate ?? 7);
        const taxedFromPayload = Number(setting.taxed_unit_cost ?? NaN);
        const taxedCost = Number.isFinite(taxedFromPayload)
            ? taxedFromPayload.toFixed(2)
            : (rawCost * (1 + hst / 100) * (1 + pst / 100)).toFixed(2);
        html += `<option value="${safeName}" data-unit="${safeUnit}" data-cost="${taxedCost}" data-hst="${hst.toFixed(2)}" data-pst="${pst.toFixed(2)}" data-raw="${rawCost.toFixed(2)}">${safeName}</option>`;
    });
    return html;
}

function createCpiMaterialRow(fi, mi, subsystemId = null) {
    return `<div class="cpi-material-row border rounded p-2 mb-1 bg-white" data-subsystem-id="${subsystemId ? String(subsystemId) : ''}">
        <div class="d-flex justify-content-between align-items-center mb-1">
            <span class="badge bg-light text-dark border" style="font-size:.72rem;">Material Item</span>
            <button type="button" class="btn btn-sm btn-outline-danger remove-cpi-material py-0 px-1">
                <i class="mdi mdi-delete-outline"></i> Remove
            </button>
        </div>
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label" style="font-size:.72rem;font-weight:600;color:#6c757d;">Description</label>
                <input type="text" name="system_findings[${fi}][materials][${mi}][material_name]"
                    class="form-control form-control-sm" placeholder="Material description">
            </div>
            <div class="col-md-2">
                <label class="form-label" style="font-size:.72rem;font-weight:600;color:#6c757d;">Qty</label>
                <input type="number" name="system_findings[${fi}][materials][${mi}][quantity]"
                    class="form-control form-control-sm cpi-mat-qty" min="0" step="0.01" value="1">
            </div>
            <div class="col-md-2">
                <label class="form-label" style="font-size:.72rem;font-weight:600;color:#6c757d;">Unit</label>
                <select name="system_findings[${fi}][materials][${mi}][unit]" class="form-select form-select-sm">
                    ${buildMaterialUnitsOptions()}
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" style="font-size:.72rem;font-weight:600;color:#6c757d;">Taxed Unit Cost ($)</label>
                <input type="number" name="system_findings[${fi}][materials][${mi}][unit_cost]"
                    class="form-control form-control-sm cpi-mat-cost" min="0" step="0.01" value="0">
                <small class="text-muted cpi-material-custom-hint" style="font-size:.70rem;display:none;">Custom mode: type your own taxed unit cost.</small>
            </div>
            <div class="col-md-2">
                <label class="form-label" style="font-size:.72rem;font-weight:600;color:#6c757d;">Line Total</label>
                <input type="text" class="form-control form-control-sm cpi-mat-total text-end fw-bold text-dark" style="background:#fff7db;border-color:#f1c96b;font-size:1.05rem;" readonly value="$0.00">
                <input type="hidden" name="system_findings[${fi}][materials][${mi}][line_total]"
                    class="cpi-mat-total-hidden" value="0">
            </div>
        </div>
        <div class="row g-1 mt-1">
            <div class="col-12">
                <small class="text-muted cpi-mat-tax-breakdown" style="font-size:.72rem;"></small>
            </div>
        </div>
        <input type="hidden" name="system_findings[${fi}][materials][${mi}][property_id]" value="${CPI_PROPERTY_ID}">
    </div>`;
}

function addSystemFindingRow(systemId, prefill = {}) {
    const body = document.getElementById(`system-rows-${systemId}`);
    if (!body) {
        return;
    }

    // Hide the empty-state placeholder
    const emptyMsg = document.getElementById(`system-empty-${systemId}`);
    if (emptyMsg) emptyMsg.style.display = 'none';

    const currentIndex = findingIndex++;
    const findingNumber = body.querySelectorAll('.finding-card').length + 1;
    const subsystemOptions = buildSubsystemOptions(systemId, prefill.subsystem_id || '');
    const severityAliasMap = {
        urgent:                    'critical',
        health_safety_threatening: 'high',
        value_depreciation:        'medium',
        non_urgent:                'low'
    };
    const severity = severityAliasMap[prefill.severity] || prefill.severity || 'low';
    const recommendationItems = normalizeRecommendationItems(prefill.recommendations);

    const severityColors = {
        critical:        '#dc3545',
        high:            '#fd7e14',
        noi_protection:  '#7c3aed',
        medium:          '#ffc107',
        low:             '#198754'
    };
    const severityLabels = {
        critical:        'Safety & Health',
        high:            'Urgent',
        noi_protection:  'NOI Protection',
        medium:          'Value Depreciation',
        low:             'Non-Urgent'
    };

    const card = document.createElement('div');
    card.className = 'finding-card border rounded mb-2 bg-white';
    card.style.cssText = 'border-left: 4px solid ' + (severityColors[severity] || '#6c757d') + ' !important;';
    card.innerHTML = `
        <input type="hidden" name="system_findings[${currentIndex}][system_id]" value="${systemId}">
        <!-- Card header -->
        <div class="d-flex justify-content-between align-items-center px-3 py-2" style="background:#f8f9fc; border-bottom:1px solid #e9ecef; border-radius:0.25rem 0.25rem 0 0;">
            <span class="fw-semibold small text-secondary">Finding #${findingNumber}</span>
            <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="removeSystemFindingRow(this)" title="Remove finding">
                <i class="mdi mdi-delete-outline"></i> Remove
            </button>
        </div>
        <!-- Row 1: Subsystem | Issue | Severity -->
        <div class="row g-2 px-3 pt-2">
            <div class="col-md-4">
                <label class="form-label small fw-semibold text-muted mb-1">Subsystem</label>
                <select name="system_findings[${currentIndex}][subsystem_id]" class="form-select form-select-sm">
                    ${subsystemOptions}
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold text-muted mb-1">Issue / Finding</label>
                <select class="form-select form-select-sm issue-preset-select">
                    ${buildFindingOptions(systemId, prefill.subsystem_id || '', prefill.issue || '')}
                </select>
                <input type="text" class="form-control form-control-sm mt-1 issue-custom-text" placeholder="Describe the issue" style="display:none;">
                <input type="hidden" name="system_findings[${currentIndex}][issue]" class="issue-hidden-value" value="${escapeHtml(prefill.issue || '')}">
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold text-muted mb-1">Severity</label>
                <select name="system_findings[${currentIndex}][severity]" class="form-select form-select-sm severity-select">
                    <option value="critical"       ${severity === 'critical'       ? 'selected' : ''}>&#x1F534; Safety &amp; Health (100)</option>
                    <option value="high"           ${severity === 'high'           ? 'selected' : ''}>&#x1F7E0; Urgent (80)</option>
                    <option value="noi_protection" ${severity === 'noi_protection' ? 'selected' : ''}>&#x1F7E3; NOI Protection (60)</option>
                    <option value="medium"         ${severity === 'medium'         ? 'selected' : ''}>&#x1F7E1; Value Depreciation (40)</option>
                    <option value="low"            ${severity === 'low'            ? 'selected' : ''}>&#x1F7E2; Non-Urgent (0)</option>
                </select>
            </div>
        </div>
        <!-- Row 1b: Issue Description -->
        <div class="row g-2 px-3 pt-2">
            <div class="col-12">
                <label class="form-label small fw-semibold text-muted mb-1">Issue Description</label>
                <textarea name="system_findings[${currentIndex}][issue_description]" class="form-control form-control-sm" rows="3" placeholder="Describe the issue in detail (what was observed, how extensive it is, and any context)...">${escapeHtml(prefill.issue_description || '')}</textarea>
            </div>
        </div>
        <!-- Row 1c: Risk / Impact -->
        <div class="row g-2 px-3 pt-2">
            <div class="col-12">
                <label class="form-label small fw-semibold text-muted mb-1">Risk / Impact</label>
                <textarea name="system_findings[${currentIndex}][risk_impact]" class="form-control form-control-sm" rows="2" placeholder="Describe the risk or impact of this finding...">${escapeHtml(prefill.risk_impact || '')}</textarea>
            </div>
        </div>
        <!-- Row 2: Location | Spot -->
        <div class="row g-2 px-3 pt-2">
            <div class="col-md-6">
                <label class="form-label small fw-semibold text-muted mb-1">Location</label>
                <input type="text" name="system_findings[${currentIndex}][location]" class="form-control form-control-sm" value="${escapeHtml(prefill.location || '')}" placeholder="e.g. North wall, Basement">
            </div>
            <div class="col-md-6">
                <label class="form-label small fw-semibold text-muted mb-1">Spot</label>
                <input type="text" name="system_findings[${currentIndex}][spot]" class="form-control form-control-sm" value="${escapeHtml(prefill.spot || '')}" placeholder="e.g. Top-left corner">
            </div>
        </div>
        <!-- Row 3: Recommendations | Notes -->
        <div class="row g-2 px-3 pt-2 pb-3">
            <div class="col-md-6">
                <label class="form-label small fw-semibold text-muted mb-1">Recommendations</label>
                <div class="recommendation-builder" data-index="${currentIndex}">
                    <div class="input-group input-group-sm mb-1">
                        <select class="form-select form-select-sm recommendation-select">
                            <option value="">Select suggested recommendation</option>
                        </select>
                        <button type="button" class="btn btn-outline-primary btn-sm recommendation-add-selected">Add</button>
                    </div>
                    <div class="input-group input-group-sm mb-1">
                        <input type="text" class="form-control recommendation-input" placeholder="Or type a custom recommendation">
                        <button type="button" class="btn btn-primary btn-sm recommendation-add fw-semibold" title="Add custom recommendation">Add Custom</button>
                    </div>
                    <div class="recommendation-list small mt-1"></div>
                    <div class="recommendation-hidden-inputs"></div>
                    <div class="mt-2">
                        <label class="form-label small fw-semibold text-muted mb-1">Recommendation Details</label>
                        <textarea name="system_findings[${currentIndex}][recommendation_details]" class="form-control form-control-sm" rows="3" placeholder="Write detailed recommendation steps, scope, and inspector guidance...">${escapeHtml(prefill.recommendation_details || '')}</textarea>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label small fw-semibold text-muted mb-1">Notes</label>
                <textarea name="system_findings[${currentIndex}][notes]" class="form-control form-control-sm" rows="3" placeholder="Detailed observations...">${escapeHtml(prefill.notes || '')}</textarea>
            </div>
        </div>
        <!-- Row 4: Finding Photos -->
        <div class="row g-2 px-3 pt-2 pb-3" style="background:#fafbff;border-top:1px solid #e9ecef;">
            <div class="col-12">
                <label class="form-label small fw-semibold text-muted mb-1">
                    <i class="mdi mdi-camera-outline me-1"></i>Finding Photos
                    <span class="fw-normal text-muted">(optional)</span>
                </label>
                <div class="finding-media-gallery d-flex flex-wrap gap-2 mb-2"></div>
                <div class="finding-existing-photos">
                    ${(Array.isArray(FINDING_PHOTO_URLS?.[currentIndex]) ? FINDING_PHOTO_URLS[currentIndex] : []).map((_url, _pi) => `<input type="hidden" name="existing_finding_photos[${currentIndex}][]" value="${escapeHtml(getStoredPhotoPath(currentIndex, _pi))}">`).join('')}
                </div>
                <input type="file"
                    name="finding_photos[${currentIndex}][]"
                    class="form-control form-control-sm finding-media-input"
                    multiple
                    accept="image/*,video/*"
                    capture="environment">
                <div class="form-text">Attach photos or videos for this finding, or capture instantly with your camera on supported devices. You can select multiple times to add more files and remove individual items below before submitting (max 50 MB each).</div>
            </div>
        </div>
        <!-- Row 5: Labour Hours + Materials -->
        <div class="px-3 pt-2 pb-3" style="background:#eef3ff;border-top:1px solid #c9d8ff;">
            <div class="row g-2 mb-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1" style="color:#3d5a99;">
                        <i class="mdi mdi-clock-outline me-1"></i>Est. Labour Hours
                    </label>
                    <div class="input-group input-group-sm">
                        <input type="number"
                            name="system_findings[${currentIndex}][phar_labour_hours]"
                            class="form-control form-control-sm"
                            min="0" step="0.1" value="${escapeHtml(String(prefill.phar_labour_hours ?? '0'))}"
                            placeholder="0.0">
                        <span class="input-group-text">hrs</span>
                    </div>
                </div>
                <div class="col-md-9">
                    <label class="form-label small fw-semibold mb-1" style="color:#3d5a99;">
                        <i class="mdi mdi-package-variant me-1"></i>Add Materials
                    </label>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <input type="text" class="form-control form-control-sm cpi-material-search" placeholder="Search materials...">
                        </div>
                        <div class="col-md-6">
                            <div class="input-group input-group-sm">
                                <select class="form-select form-select-sm cpi-material-template">${buildCpiMaterialPresetOptions('', prefill.subsystem_id || null)}</select>
                                <button type="button" class="btn btn-outline-primary btn-sm cpi-material-add-selected">Add Selected</button>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <input type="text" class="form-control form-control-sm cpi-material-custom-input" placeholder="Or type a custom material (e.g., Asphalt shingles)">
                        </div>
                        <div class="col-md-4 d-grid">
                            <button type="button" class="btn btn-primary btn-sm cpi-material-add-custom" title="Add custom material">Add Custom</button>
                        </div>
                    </div>
                </div>
            </div>
            <label class="form-label small fw-semibold mb-1" style="color:#3d5a99;">Materials <span class="fw-normal text-muted">(optional)</span></label>
            <div class="cpi-materials-container"></div>

            <!-- Row 5b: Category / Included in Tier / Additional Notes -->
            <div class="row g-2 mt-2">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold mb-1" style="color:#3d5a99;">
                        <i class="mdi mdi-tag-outline me-1"></i>Category
                    </label>
                    <select name="system_findings[${currentIndex}][phar_category]"
                            class="form-select form-select-sm">
                        <option value="">— Select Category —</option>
                        ${PHAR_CATEGORIES.map(cat => `<option value="${escapeHtml(cat)}" ${escapeHtml(String(prefill.phar_category ?? '')) === escapeHtml(cat) ? 'selected' : ''}>${escapeHtml(cat)}</option>`).join('')}
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1" style="color:#3d5a99;">
                        <i class="mdi mdi-check-circle-outline me-1"></i>Included in Tier?
                    </label>
                    <select name="system_findings[${currentIndex}][phar_included_yn]"
                            class="form-select form-select-sm">
                        <option value="1" ${(prefill.phar_included_yn === false || prefill.phar_included_yn == 0) ? '' : 'selected'}>Yes</option>
                        <option value="0" ${(prefill.phar_included_yn === false || prefill.phar_included_yn == 0) ? 'selected' : ''}>No</option>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label small fw-semibold mb-1" style="color:#3d5a99;">
                        <i class="mdi mdi-note-text-outline me-1"></i>Additional Notes
                    </label>
                    <textarea name="system_findings[${currentIndex}][phar_notes]"
                              class="form-control form-control-sm"
                              rows="2"
                              placeholder="PHAR additional notes…">${escapeHtml(String(prefill.phar_notes ?? ''))}</textarea>
                </div>
            </div>
        </div>
    `;

    body.appendChild(card);

    const findingMediaInput = card.querySelector('.finding-media-input');
    if (findingMediaInput) {
        findingMediaInput.addEventListener('change', function () {
            appendFindingMediaFiles(card, this.files);
            syncFindingMediaInputFiles(card);
            this.value = '';
            renderFindingMediaGallery(card, currentIndex);
        });
        renderFindingMediaGallery(card, currentIndex);
    }

    // Wire issue preset select → hidden value
    const issuePresetSelect = card.querySelector('.issue-preset-select');
    const issueCustomText = card.querySelector('.issue-custom-text');
    const issueHiddenValue = card.querySelector('.issue-hidden-value');

    if (issuePresetSelect) {
        // If prefill issue is a custom (not in preset list), show the text input
        if (prefill.issue && issuePresetSelect.value === '__custom__') {
            issueCustomText.style.display = '';
            issueCustomText.value = prefill.issue;
        }

        issuePresetSelect.addEventListener('change', function () {
            if (this.value === '__custom__') {
                issueCustomText.style.display = '';
                issueCustomText.value = '';
                issueHiddenValue.value = '';
                issueCustomText.focus();
            } else {
                issueCustomText.style.display = 'none';
                issueCustomText.value = '';
                issueHiddenValue.value = this.value;

                // Auto-fill PHAR fields from catalog when a known finding is selected
                if (this.value) {
                    const sysName = getSystemConfig(systemId)?.name || '';
                    const subSel  = card.querySelector(`select[name="system_findings[${currentIndex}][subsystem_id]"]`);
                    const subId   = subSel ? subSel.value : '';
                    const subName = subId ? (getSubsystemConfig(systemId, subId)?.name || '') : '';
                    applyPharCatalogToCard(card, sysName, subName, this.value, currentIndex);
                }
            }
        });

        issueCustomText.addEventListener('input', function () {
            issueHiddenValue.value = this.value;
        });

        // Refresh issue options when subsystem changes
        const subsystemSelForIssue = card.querySelector(`select[name="system_findings[${currentIndex}][subsystem_id]"]`);
        if (subsystemSelForIssue) {
            subsystemSelForIssue.addEventListener('change', function () {
                const currentIssue = issueHiddenValue.value;
                issuePresetSelect.innerHTML = buildFindingOptions(systemId, this.value, currentIssue);
                if (issuePresetSelect.value === '__custom__') {
                    issueCustomText.style.display = '';
                    issueCustomText.value = currentIssue;
                } else {
                    issueCustomText.style.display = 'none';
                    issueHiddenValue.value = issuePresetSelect.value;
                }
            });
        }
    }

    // Update border colour when severity changes
    const severitySelect = card.querySelector('.severity-select');
    severitySelect.addEventListener('change', function () {
        card.style.cssText = 'border-left: 4px solid ' + (severityColors[this.value] || '#6c757d') + ' !important;';
    });

    initRecommendationBuilder(card, currentIndex, recommendationItems, systemId, prefill.subsystem_id || '');

    // ── Labour & Materials wiring ──────────────────────────────────────────────
    let cpiMatIdx = 0;
    const matContainer       = card.querySelector('.cpi-materials-container');
    const addSelectedBtn     = card.querySelector('.cpi-material-add-selected');
    const addCustomBtn       = card.querySelector('.cpi-material-add-custom');
    const matSearchInput     = card.querySelector('.cpi-material-search');
    const matTemplateSelect  = card.querySelector('.cpi-material-template');
    const matCustomInput     = card.querySelector('.cpi-material-custom-input');
    const subsystemSelForMat = card.querySelector(`select[name="system_findings[${currentIndex}][subsystem_id]"]`);

    function updateCpiLineTotal(row) {
        const qty   = parseFloat(row.querySelector('.cpi-mat-qty')?.value  || 0);
        const cost  = parseFloat(row.querySelector('.cpi-mat-cost')?.value || 0);
        const total = qty * cost;
        const display   = row.querySelector('.cpi-mat-total');
        const hidden    = row.querySelector('.cpi-mat-total-hidden');
        const breakdown = row.querySelector('.cpi-mat-tax-breakdown');
        if (display) display.value = '$' + total.toFixed(2);
        if (hidden)  hidden.value  = total.toFixed(2);
        if (breakdown && cost > 0) {
            const hst = row.dataset.hst ? parseFloat(row.dataset.hst) : null;
            const pst = row.dataset.pst ? parseFloat(row.dataset.pst) : null;
            const raw = row.dataset.raw ? parseFloat(row.dataset.raw) : null;
            if (hst !== null && pst !== null && raw !== null) {
                breakdown.textContent = `Base: $${raw.toFixed(2)}  ×(1+${hst}%)×(1+${pst}%) = $${cost.toFixed(2)} taxed unit cost`;
            } else {
                breakdown.textContent = '';
            }
        } else if (breakdown) {
            breakdown.textContent = '';
        }
    }

    // Wire up events on a material row (qty / cost recalc + remove)
    function wireCpiMaterialRow(row) {
        const costEl = row.querySelector('.cpi-mat-cost');
        const hintEl = row.querySelector('.cpi-material-custom-hint');

        // Show/hide hint and lock/unlock cost field based on preset vs custom mode
        function applyMode(isCustom) {
            if (!costEl) return;
            costEl.readOnly = !isCustom;
            costEl.style.background = isCustom ? '' : '#f8f9fa';
            costEl.style.cursor     = isCustom ? '' : 'default';
            if (hintEl) hintEl.style.display = isCustom ? '' : 'none';
        }

        // Initial mode is set by the caller via row.dataset; default to custom (editable)
        const hasPreset = !!(row.dataset.hst || row.dataset.pst || row.dataset.raw);
        applyMode(!hasPreset);

        row.querySelector('.cpi-mat-qty')?.addEventListener('input',  () => updateCpiLineTotal(row));
        row.querySelector('.cpi-mat-cost')?.addEventListener('input', () => updateCpiLineTotal(row));
        row.querySelector('.remove-cpi-material')?.addEventListener('click', () => row.remove());
    }

    // Create + append a material row, optionally pre-filled with preset or custom data
    function addMaterialRow(opts = {}) {
        const subId   = subsystemSelForMat ? (parseInt(subsystemSelForMat.value) || null) : null;
        const wrapper = document.createElement('div');
        wrapper.innerHTML = createCpiMaterialRow(currentIndex, cpiMatIdx++, subId);
        const row    = wrapper.firstElementChild;
        const nameEl = row.querySelector(`[name*="[material_name]"]`);
        const qtyEl  = row.querySelector('.cpi-mat-qty');
        const unitEl = row.querySelector(`select[name*="[unit]"]`);
        const costEl = row.querySelector('.cpi-mat-cost');

        if (opts.name  && nameEl) nameEl.value = opts.name;
        if (opts.qty   && qtyEl)  qtyEl.value  = opts.qty;
        if (opts.unit  && unitEl) unitEl.value  = opts.unit;
        if (opts.cost  && costEl) costEl.value  = opts.cost;

        if (opts.isPreset) {
            row.dataset.hst = opts.hst || '';
            row.dataset.pst = opts.pst || '';
            row.dataset.raw = opts.raw || '';
        }

        updateCpiLineTotal(row);
        wireCpiMaterialRow(row);
        matContainer.appendChild(row);
        return row;
    }

    // Wire search input to filter the preset dropdown
    if (matSearchInput && matTemplateSelect) {
        matSearchInput.addEventListener('input', function () {
            const subId   = subsystemSelForMat ? (subsystemSelForMat.value || null) : null;
            const curVal  = matTemplateSelect.value;
            matTemplateSelect.innerHTML = buildCpiMaterialPresetOptions(this.value, subId);
            if (curVal && Array.from(matTemplateSelect.options).some(o => o.value === curVal)) {
                matTemplateSelect.value = curVal;
            }
        });
    }

    // "Add Selected" — adds a row pre-filled from the chosen preset
    if (addSelectedBtn && matContainer) {
        addSelectedBtn.addEventListener('click', () => {
            if (!matTemplateSelect || !matTemplateSelect.value) {
                // Nothing selected — add a blank custom row
                addMaterialRow({});
                return;
            }
            const opt = matTemplateSelect.options[matTemplateSelect.selectedIndex];
            addMaterialRow({
                name:     matTemplateSelect.value,
                unit:     opt.dataset.unit || '',
                cost:     opt.dataset.cost || '0',
                hst:      opt.dataset.hst  || '',
                pst:      opt.dataset.pst  || '',
                raw:      opt.dataset.raw  || '',
                isPreset: true,
            });
        });
    }

    // "Add Custom" — adds a row in fully-editable mode with the typed name
    if (addCustomBtn && matContainer) {
        addCustomBtn.addEventListener('click', () => {
            const customName = matCustomInput ? String(matCustomInput.value || '').trim() : '';
            addMaterialRow({ name: customName });
            if (matCustomInput) matCustomInput.value = '';
        });
    }

    // When subsystem changes, refresh the card-level preset dropdown
    if (subsystemSelForMat && matTemplateSelect) {
        subsystemSelForMat.addEventListener('change', function () {
            const curVal    = matTemplateSelect.value;
            const searchVal = matSearchInput ? matSearchInput.value : '';
            matTemplateSelect.innerHTML = buildCpiMaterialPresetOptions(searchVal, this.value || null);
            if (curVal && Array.from(matTemplateSelect.options).some(o => o.value === curVal)) {
                matTemplateSelect.value = curVal;
            }
        });
    }

    // Pre-fill materials from edit / reload
    if (matContainer && Array.isArray(prefill.phar_materials) && prefill.phar_materials.length) {
        prefill.phar_materials.forEach((mat) => {
            const fmcMatch = FMC_MATERIAL_SETTINGS.find(s =>
                String(s.material_name || '').toLowerCase() === String(mat.material_name || '').toLowerCase()
            );
            if (fmcMatch) {
                const raw   = Number(fmcMatch.default_unit_cost ?? 0);
                const hst   = Number(fmcMatch.hst_rate ?? 5);
                const pst   = Number(fmcMatch.pst_rate ?? 7);
                const taxedFromPayload = Number(fmcMatch.taxed_unit_cost ?? NaN);
                const taxed = Number.isFinite(taxedFromPayload)
                    ? taxedFromPayload.toFixed(2)
                    : (raw * (1 + hst / 100) * (1 + pst / 100)).toFixed(2);
                addMaterialRow({
                    name:     fmcMatch.material_name,
                    qty:      mat.quantity ?? 1,
                    unit:     mat.unit || fmcMatch.default_unit || '',
                    cost:     taxed,
                    hst:      hst.toFixed(2),
                    pst:      pst.toFixed(2),
                    raw:      raw.toFixed(2),
                    isPreset: true,
                });
            } else {
                addMaterialRow({
                    name: mat.material_name ?? '',
                    qty:  mat.quantity ?? 1,
                    unit: mat.unit || '',
                    cost: mat.unit_cost != null ? String(mat.unit_cost) : '0',
                });
            }
        });
    }
}

function initRecommendationBuilder(row, rowIndex, initialItems = [], systemId = '', initialSubsystemId = '') {
    const builder = row.querySelector('.recommendation-builder');
    if (!builder) {
        return;
    }

    const subsystemSelect = row.querySelector(`select[name="system_findings[${rowIndex}][subsystem_id]"]`);
    const recommendationSelect = builder.querySelector('.recommendation-select');
    const addSelectedButton = builder.querySelector('.recommendation-add-selected');
    const input = builder.querySelector('.recommendation-input');
    const addButton = builder.querySelector('.recommendation-add');
    const list = builder.querySelector('.recommendation-list');
    const hiddenInputs = builder.querySelector('.recommendation-hidden-inputs');

    let recommendations = normalizeRecommendationItems(initialItems);

    function addRecommendationItem(value) {
        const normalizedValue = String(value || '').trim();
        if (!normalizedValue) {
            return;
        }

        const exists = recommendations.some(item => item.toLowerCase() === normalizedValue.toLowerCase());
        if (!exists) {
            recommendations.push(normalizedValue);
            renderRecommendations();
        }
    }

    function refreshRecommendationDropdown(selectedSubsystemId = '') {
        const options = collectRecommendationOptions(systemId, selectedSubsystemId || '');
        recommendationSelect.innerHTML = '<option value="">Select recommendation</option>';

        options.forEach(optionValue => {
            const option = document.createElement('option');
            option.value = optionValue;
            option.textContent = optionValue;
            recommendationSelect.appendChild(option);
        });
    }

    function renderRecommendations() {
        list.innerHTML = '';
        hiddenInputs.innerHTML = '';

        recommendations.forEach((item, itemIndex) => {
            const badge = document.createElement('span');
            badge.className = 'badge me-1 mb-1';
            badge.style.cssText = 'color:#212529 !important; background-color:#f8f9fa !important; border:1px solid #ced4da !important;';
            badge.innerHTML = `${escapeHtml(item)} <button type="button" class="btn btn-sm p-0 ms-1 recommendation-remove" data-item-index="${itemIndex}" style="line-height:1; border:none; background:transparent; color:#dc3545 !important;">&times;</button>`;
            list.appendChild(badge);

            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = `system_findings[${rowIndex}][recommendations][]`;
            hiddenInput.value = item;
            hiddenInputs.appendChild(hiddenInput);
        });

        const removeButtons = list.querySelectorAll('.recommendation-remove');
        removeButtons.forEach(button => {
            button.addEventListener('click', function () {
                const itemIndex = parseInt(this.dataset.itemIndex, 10);
                if (!isNaN(itemIndex)) {
                    recommendations.splice(itemIndex, 1);
                    renderRecommendations();
                }
            });
        });
    }

    function addRecommendation() {
        const value = String(input.value || '').trim();
        if (!value) {
            return;
        }

        addRecommendationItem(value);

        input.value = '';
    }

    function addSelectedRecommendations() {
        const selectedValue = String(recommendationSelect.value || '').trim();
        if (!selectedValue) {
            return;
        }

        addRecommendationItem(selectedValue);
        recommendationSelect.value = '';
    }

    addSelectedButton.addEventListener('click', addSelectedRecommendations);
    addButton.addEventListener('click', addRecommendation);
    input.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            addRecommendation();
        }
    });

    if (subsystemSelect) {
        subsystemSelect.addEventListener('change', function () {
            refreshRecommendationDropdown(this.value || '');
        });
    }

    refreshRecommendationDropdown(initialSubsystemId || (subsystemSelect ? subsystemSelect.value : ''));

    renderRecommendations();
}

function removeSystemFindingRow(button) {
    const card = button.closest('.finding-card');
    if (!card) return;
    const container = card.parentElement;
    card.remove();
    // If no findings left, restore the empty-state message
    if (container && container.querySelectorAll('.finding-card').length === 0) {
        const emptyMsg = container.querySelector('[id^="system-empty-"]');
        if (emptyMsg) emptyMsg.style.display = '';
    }
}

// Severity order: critical (urgent) first, then high (H&S), then medium (value dep.), then low (non-urgent)
const severityOrder = { critical: 0, high: 1, medium: 2, low: 3 };

const AUTOSAVE_INTERVAL_MS = 5000;
const AUTOSAVE_KEY = `inspection_form_autosave_${CPI_PROPERTY_ID}`;

function setAutosaveStatus(message, isError = false) {
    const statusEl = document.getElementById('formAutosaveStatus');
    if (!statusEl) {
        return;
    }

    statusEl.textContent = message;
    statusEl.classList.remove('text-danger', 'text-success', 'text-muted');
    statusEl.classList.add(isError ? 'text-danger' : 'text-muted');
}

function collectAutosavePayload(form) {
    const payload = {};
    const fields = form.querySelectorAll('input[name], select[name], textarea[name]');

    fields.forEach((field) => {
        if (field.disabled || !field.name || field.type === 'file') {
            return;
        }

        let value;
        if (field.type === 'checkbox') {
            value = field.checked;
        } else if (field.type === 'radio') {
            if (!field.checked) {
                return;
            }
            value = field.value;
        } else {
            value = field.value;
        }

        if (Object.prototype.hasOwnProperty.call(payload, field.name)) {
            if (!Array.isArray(payload[field.name])) {
                payload[field.name] = [payload[field.name]];
            }
            payload[field.name].push(value);
        } else {
            payload[field.name] = value;
        }
    });

    return payload;
}

function applyAutosavePayload(form, payload) {
    if (!payload || typeof payload !== 'object') {
        return;
    }

    const fields = form.querySelectorAll('input[name], select[name], textarea[name]');
    const arrayIndexByName = {};

    fields.forEach((field) => {
        if (!field.name || !Object.prototype.hasOwnProperty.call(payload, field.name)) {
            return;
        }

        const rawValue = payload[field.name];
        let nextValue = rawValue;
        if (Array.isArray(rawValue)) {
            const idx = arrayIndexByName[field.name] || 0;
            nextValue = rawValue[idx];
            arrayIndexByName[field.name] = idx + 1;
        }

        if (field.type === 'checkbox') {
            field.checked = !!nextValue;
        } else if (field.type === 'radio') {
            field.checked = String(field.value) === String(nextValue);
        } else if (field.type !== 'file') {
            field.value = nextValue ?? '';
        }

        field.dispatchEvent(new Event('input', { bubbles: true }));
        field.dispatchEvent(new Event('change', { bubbles: true }));
    });
}

function initializeFormAutosave() {
    const form = document.getElementById('inspectionSystemsForm');
    if (!form) {
        return;
    }

    let lastSavedSnapshot = '';
    let lastServerSnapshot = '';
    let serverSaveInFlight = false;

    const csrfToken = form.querySelector('input[name="_token"]')?.value || '';

    try {
        const savedRaw = localStorage.getItem(AUTOSAVE_KEY);
        if (savedRaw) {
            const savedData = JSON.parse(savedRaw);
            applyAutosavePayload(form, savedData);
            setAutosaveStatus('Autosave: restored local draft');
        }
    } catch (_err) {
        setAutosaveStatus('Autosave: could not restore local draft', true);
    }

    const persist = () => {
        try {
            const payload = collectAutosavePayload(form);
            payload.status = 'in_progress';
            const snapshot = JSON.stringify(payload);

            if (snapshot !== lastSavedSnapshot) {
                localStorage.setItem(AUTOSAVE_KEY, snapshot);
                lastSavedSnapshot = snapshot;
            }

            const now = new Date();
            const hh = String(now.getHours()).padStart(2, '0');
            const mm = String(now.getMinutes()).padStart(2, '0');
            const ss = String(now.getSeconds()).padStart(2, '0');

            if (snapshot === lastServerSnapshot || serverSaveInFlight) {
                setAutosaveStatus(`Autosave: local saved at ${hh}:${mm}:${ss}`);
                return;
            }

            serverSaveInFlight = true;
            setAutosaveStatus('Autosave: syncing to server...');

            fetch(CPI_AUTOSAVE_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(payload),
            })
                .then(async (response) => {
                    if (!response.ok) {
                        const text = await response.text();
                        throw new Error(text || `HTTP ${response.status}`);
                    }
                    return response.json();
                })
                .then(() => {
                    lastServerSnapshot = snapshot;
                    const syncedAt = new Date();
                    const sh = String(syncedAt.getHours()).padStart(2, '0');
                    const sm = String(syncedAt.getMinutes()).padStart(2, '0');
                    const ss2 = String(syncedAt.getSeconds()).padStart(2, '0');
                    setAutosaveStatus(`Autosave: synced at ${sh}:${sm}:${ss2}`);
                })
                .catch(() => {
                    setAutosaveStatus('Autosave: local saved, server sync failed', true);
                })
                .finally(() => {
                    serverSaveInFlight = false;
                });
        } catch (_err) {
            setAutosaveStatus('Autosave: storage failed', true);
        }
    };

    form.addEventListener('submit', function () {
        // Keep one final snapshot in local storage in case server validation fails.
        persist();
    });

    window.setInterval(persist, AUTOSAVE_INTERVAL_MS);
    window.setTimeout(persist, 1200);
}

document.addEventListener('DOMContentLoaded', function () {
    if (Array.isArray(initialFindings) && initialFindings.length > 0) {
        // Sort by severity priority before rendering so most critical findings appear first
        const sorted = [...initialFindings].sort((a, b) => {
            const aOrder = severityOrder[a.severity] ?? 99;
            const bOrder = severityOrder[b.severity] ?? 99;
            return aOrder - bOrder;
        });

        sorted.forEach(finding => {
            if (!finding || !finding.system_id) {
                return;
            }

            addSystemFindingRow(finding.system_id, finding);
        });
    }

    initializeFormAutosave();
});

</script>
@endsection
