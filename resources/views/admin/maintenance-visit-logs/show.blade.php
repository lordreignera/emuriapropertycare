@extends('admin.layout')

@section('content')
@php
    $property       = $inspection->property;
    $logsByFinding  = $inspection->maintenanceVisitLogs->groupBy('finding_id');
    $generalLogs    = $logsByFinding->get(null, collect())->sortByDesc('created_at');

    // JSON findings on inspection (may carry finding_photos)
    $inspFindingsJson = is_array($inspection->findings)
        ? $inspection->findings
        : (json_decode($inspection->getRawOriginal('findings') ?? '[]', true) ?? []);

    // Overall inspection site photos (uploaded via the general photos[] field during Step 1)
    $sitePhotos = $inspection->photos ?? [];
    if (is_string($sitePhotos)) {
        $sitePhotos = json_decode($sitePhotos, true) ?? [];
    }
    $sitePhotos = array_values(array_filter((array) $sitePhotos));

    $totalFindings    = $findings->count();
    $resolvedFindings = 0;
    foreach ($findings as $f) {
        if ($logsByFinding->get($f->id, collect())->where('status', 'completed')->isNotEmpty()) {
            $resolvedFindings++;
        }
    }
    $overallPct = $totalFindings > 0 ? round(($resolvedFindings / $totalFindings) * 100) : 0;
@endphp

<div class="container-fluid py-4">

    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <a href="{{ route('maintenance-visit-logs.index') }}" class="text-muted small text-decoration-none">
                <i class="mdi mdi-arrow-left me-1"></i>All Active Projects
            </a>
            <h4 class="fw-bold mb-0 mt-1">
                <i class="mdi mdi-clipboard-list-outline me-2 text-success"></i>
                {{ $property->property_name ?? 'Property #'.$inspection->property_id }}
            </h4>
            <p class="text-muted small mb-0">{{ $property->property_address ?? '' }} &mdash; Work Log</p>
        </div>
        <div class="text-end">
            <div class="fw-bold text-success" style="font-size:1.4rem;">{{ $overallPct }}%</div>
            <div class="text-muted small">{{ $resolvedFindings }} / {{ $totalFindings }} issues resolved</div>
            <div class="progress mt-1" style="width:120px;height:8px;">
                <div class="progress-bar bg-success" style="width:{{ $overallPct }}%"></div>
            </div>
        </div>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- ====== SITE PHOTOS FROM INSPECTION ====== --}}
    @if(!empty($sitePhotos))
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <span class="fw-semibold">
                <i class="mdi mdi-image-multiple-outline me-1 text-primary"></i>Site Photos — Taken During Inspection
            </span>
            <span class="badge bg-light border text-dark">{{ count($sitePhotos) }} photo{{ count($sitePhotos) > 1 ? 's' : '' }}</span>
        </div>
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2">
                @foreach($sitePhotos as $spIdx => $sp)
                    @php $spUrl = $inspection->getStorageUrl($sp); @endphp
                    <img src="{{ $spUrl }}"
                         class="photo-thumb"
                         data-src="{{ $spUrl }}"
                         data-group="site-photos"
                         data-index="{{ $spIdx }}"
                         style="height:120px;width:120px;object-fit:cover;border-radius:8px;
                                border:2px solid #dee2e6;cursor:zoom-in;transition:transform .15s;"
                         alt="Site photo {{ $spIdx + 1 }}"
                         onmouseover="this.style.transform='scale(1.06)'"
                         onmouseout="this.style.transform='scale(1)'">
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- ====== TOOLS & EQUIPMENT ASSIGNED TO THIS PROJECT ====== --}}
    @if($toolAssignments->isNotEmpty())
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <span class="fw-semibold">
                <i class="mdi mdi-toolbox-outline me-1 text-info"></i>Tools &amp; Equipment — This Project
            </span>
            <div class="d-flex gap-2 align-items-center">
                <span class="badge bg-warning text-dark">
                    {{ $toolAssignments->where('returned_at', null)->where('quantity', '>', 0)->count() }} in use
                </span>
                <span class="badge bg-success">
                    {{ $toolAssignments->whereNotNull('returned_at')->count() }} returned
                </span>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Tool / Equipment</th>
                            <th class="text-center">Qty</th>
                            <th>Ownership</th>
                            <th>Status</th>
                            <th>Dispatch Notes</th>
                            <th>Returned</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($toolAssignments->where('quantity', '>', 0) as $ta)
                        <tr class="{{ $ta->returned_at ? 'table-success' : '' }}">
                            <td class="fw-semibold">{{ $ta->tool_name }}</td>
                            <td class="text-center">
                                <span class="badge bg-{{ $ta->returned_at ? 'success' : 'warning text-dark' }}">{{ $ta->quantity }}</span>
                            </td>
                            <td class="small text-capitalize text-muted">{{ $ta->ownership_status ?? '—' }}</td>
                            <td>
                                @if($ta->returned_at)
                                    <span class="badge bg-success"><i class="mdi mdi-check me-1"></i>Returned {{ $ta->returned_at->format('M d') }}</span>
                                @else
                                    <span class="badge bg-warning text-dark"><i class="mdi mdi-hammer-wrench me-1"></i>In Use</span>
                                @endif
                            </td>
                            <td class="small text-muted">{{ $ta->assign_notes ?? '—' }}</td>
                            <td class="small text-muted">
                                @if($ta->returned_at)
                                    {{ $ta->returnedBy->name ?? 'N/A' }}
                                    @if($ta->return_notes)
                                        <div class="text-muted" style="font-size:.78rem;">{{ $ta->return_notes }}</div>
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- One card per PHAR finding --}}
    @forelse($findings as $fi => $finding)
        @php
            // Photos live in the inspection's findings JSON blob (finding_photos key)
            // The JSON entries use 'issue' as the question key, not 'task_question'
            $beforePhotos = [];
            $matchedJson = collect($inspFindingsJson)->first(fn($f) =>
                ($f['issue'] ?? '') === $finding->task_question ||
                ($f['task_question'] ?? '') === $finding->task_question
            );
            if (!empty($matchedJson['finding_photos'])) {
                $beforePhotos = array_values(array_filter($matchedJson['finding_photos']));
            }
            // Fallback 1: photo_ids column
            if (empty($beforePhotos)) {
                $rawBefore    = $finding->getRawOriginal('photo_ids');
                $beforePhotos = is_string($rawBefore) ? (json_decode($rawBefore, true) ?? []) : ($finding->photo_ids ?? []);
            }
            // Fallback 2: use site photos when no finding-specific photos exist
            $usingSitePhotosAsFallback = empty($beforePhotos) && !empty($sitePhotos);

            $findingLogs = $logsByFinding->get($finding->id, collect())->sortByDesc('created_at');
            $isResolved  = $findingLogs->where('status', 'completed')->isNotEmpty();
            $logCount    = $findingLogs->count();

            $priorityColor = match(strtolower($finding->priority ?? '')) {
                'critical', 'high' => 'danger',
                'medium'           => 'warning',
                'low'              => 'info',
                default            => 'secondary',
            };
        @endphp

        <div class="card border-0 shadow-sm mb-4" id="finding-{{ $finding->id }}">

            {{-- Card header --}}
            <div class="card-header bg-white py-3"
                 style="border-left:4px solid var(--bs-{{ $priorityColor }},#6c757d);">
                <div class="d-flex align-items-start justify-content-between gap-2 flex-wrap">
                    <div class="d-flex align-items-start gap-2">
                        <span class="badge bg-{{ $priorityColor }} mt-1">{{ strtoupper($finding->priority ?? 'N/A') }}</span>
                        <div>
                            <div class="fw-semibold">{{ $finding->task_question }}</div>
                            @if($finding->category)
                                <div class="text-muted small">{{ $finding->category }}</div>
                            @endif
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-shrink-0 flex-wrap">
                        {{-- Estimated vs actual hours --}}
                        @php
                            $estHours    = (float)($finding->labour_hours ?? 0);
                            $actualHours = $findingLogs->sum('hours_worked');
                        @endphp
                        @if($estHours > 0)
                            <span class="badge bg-light border text-dark"
                                  title="Estimated hours from inspection">
                                <i class="mdi mdi-clock-outline me-1 text-muted"></i>Est: {{ $estHours }}h
                            </span>
                            <span class="badge {{ $actualHours > $estHours ? 'bg-danger' : ($actualHours > 0 ? 'bg-primary' : 'bg-light border text-dark') }}"
                                  title="Actual hours logged so far">
                                <i class="mdi mdi-timer-outline me-1"></i>Actual: {{ $actualHours > 0 ? $actualHours.'h' : '—' }}
                            </span>
                        @elseif($actualHours > 0)
                            <span class="badge bg-primary" title="Actual hours logged">
                                <i class="mdi mdi-timer-outline me-1"></i>{{ $actualHours }}h logged
                            </span>
                        @endif
                        @if($isResolved)
                            <span class="badge bg-success"><i class="mdi mdi-check-circle me-1"></i>Resolved</span>
                        @else
                            <span class="badge bg-warning text-dark"><i class="mdi mdi-clock-outline me-1"></i>Pending</span>
                        @endif
                        @if($logCount > 0)
                            <span class="badge bg-light text-dark border">{{ $logCount }} {{ Str::plural('log', $logCount) }}</span>
                        @endif
                        <button class="btn btn-sm btn-success" type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#log-finding-{{ $finding->id }}">
                            <i class="mdi mdi-plus me-1"></i>Log Work
                        </button>
                    </div>
                </div>
            </div>

            {{-- Before photos (per-finding, or site-wide if none specific) --}}
            @if(!empty($beforePhotos) || $usingSitePhotosAsFallback)
            @php $displayPhotos = !empty($beforePhotos) ? $beforePhotos : $sitePhotos; @endphp
            <div class="card-body border-bottom py-3" style="background:#fff8f8;">
                <div class="fw-semibold small text-danger mb-2">
                    <i class="mdi mdi-image-outline me-1"></i>BEFORE — Condition at Inspection
                    @if($usingSitePhotosAsFallback)
                        <span class="badge bg-secondary ms-1" style="font-size:.7rem;font-weight:500;">General Site Photos</span>
                    @endif
                    <span class="text-muted fw-normal ms-1">(click any photo to zoom &amp; rotate)</span>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    @foreach($displayPhotos as $bpIdx => $bp)
                        @php $bpUrl = $inspection->getStorageUrl($bp); @endphp
                        <img src="{{ $bpUrl }}"
                             class="photo-thumb"
                             data-src="{{ $bpUrl }}"
                             data-group="before-{{ $finding->id }}"
                             data-index="{{ $bpIdx }}"
                             style="height:110px;width:110px;object-fit:cover;border-radius:8px;
                                    border:3px solid #dc3545;cursor:zoom-in;transition:transform .15s;"
                             alt="Before photo {{ $bpIdx+1 }}"
                             onmouseover="this.style.transform='scale(1.06)'"
                             onmouseout="this.style.transform='scale(1)'">
                    @endforeach
                </div>
            </div>
            @else
            <div class="card-body border-bottom py-2" style="background:#fff8f8;">
                <p class="text-muted small mb-0">
                    <i class="mdi mdi-image-off-outline me-1"></i>No before-photos were uploaded for this finding during the inspection.
                </p>
            </div>
            @endif

            {{-- Log Work form (collapsed) --}}
            <div class="collapse {{ $errors->any() && old('finding_id') == $finding->id ? 'show' : '' }}"
                 id="log-finding-{{ $finding->id }}">
                <div class="card-body bg-light border-bottom">
                    <form action="{{ route('maintenance-visit-logs.store', $inspection) }}"
                          method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="finding_id" value="{{ $finding->id }}">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Visit Date <span class="text-danger">*</span></label>
                                @if($schedule->isNotEmpty())
                                <select name="visit_date" class="form-select" required>
                                    <option value="">— Select visit —</option>
                                    @foreach($schedule as $sv)
                                        @php
                                            $svDate   = $sv['date'] ?? '';
                                            $svStatus = $sv['status'] ?? 'scheduled';
                                            $svLabel  = \Carbon\Carbon::parse($svDate)->format('D, d M Y');
                                            $isCompleted = $svStatus === 'completed';
                                        @endphp
                                        <option value="{{ $svDate }}"
                                            {{ old('visit_date') === $svDate ? 'selected' : '' }}>
                                            {{ $svLabel }}{{ $isCompleted ? ' ✓ done' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">Dates are from the scheduled work plan.</div>
                                @else
                                <input type="date" name="visit_date" class="form-control" required
                                       value="{{ old('visit_date', date('Y-m-d')) }}">
                                <div class="form-text text-warning">No schedule set yet — any date allowed.</div>
                                @endif
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                                <select name="status" class="form-select" required>
                                    <option value="in_progress">In Progress</option>
                                    <option value="completed">Completed / Resolved</option>
                                    <option value="pending">Pending / Revisit needed</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-semibold">Actual Hours Worked</label>
                                <input type="number" name="hours_worked" class="form-control"
                                       step="0.5" min="0" max="24" placeholder="e.g. 3">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">
                                    How was this resolved? <span class="text-danger">*</span>
                                </label>
                                <textarea name="work_description" class="form-control" rows="3" required
                                          placeholder="Describe the work done to fix or address this issue…"></textarea>
                            </div>
                            <div class="col-md-7">
                                <label class="form-label fw-semibold">Notes</label>
                                <input type="text" name="notes" class="form-control"
                                       placeholder="Materials used, observations, follow-up required…">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fw-semibold">
                                    After Photos <span class="text-muted fw-normal">(up to 10)</span>
                                </label>
                                <input type="file" name="after_photos[]" class="form-control"
                                       accept="image/*" multiple>
                                <div class="form-text">JPEG / PNG / WebP, max 5 MB each</div>
                            </div>
                        </div>
                        <div class="mt-3 d-flex gap-2">
                            <button type="submit" class="btn btn-success px-4">
                                <i class="mdi mdi-content-save me-1"></i>Save Log Entry
                            </button>
                            <button type="button" class="btn btn-outline-secondary"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#log-finding-{{ $finding->id }}">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Existing logs for this finding --}}
            <div class="card-body py-3">
                @if($findingLogs->isEmpty())
                    <p class="text-muted small mb-0">
                        <i class="mdi mdi-clock-outline me-1"></i>
                        No work logged yet. Click <strong>Log Work</strong> above to record progress on this issue.
                    </p>
                @else
                    @foreach($findingLogs as $log)
                        @php $afterPhotos = $log->after_photos ?? []; @endphp
                        <div class="border rounded p-3 mb-3 {{ $log->status === 'completed' ? 'border-success' : '' }}"
                             style="{{ $log->status === 'completed' ? 'background:#f0fff4;' : '' }}">
                            <div class="d-flex justify-content-between align-items-start flex-wrap mb-2">
                                <div class="d-flex gap-2 flex-wrap">
                                    <span class="badge bg-{{ $log->status === 'completed' ? 'success' : ($log->status === 'in_progress' ? 'primary' : 'secondary') }}">
                                        {{ ucfirst(str_replace('_', ' ', $log->status)) }}
                                    </span>
                                    @if($log->hours_worked)
                                        <span class="badge bg-light text-dark border">{{ $log->hours_worked }}h</span>
                                    @endif
                                    <span class="badge bg-light text-muted border">
                                        <i class="mdi mdi-calendar me-1"></i>{{ \Carbon\Carbon::parse($log->visit_date)->format('d M Y') }}
                                    </span>
                                </div>
                                <div class="text-muted small">
                                    <i class="mdi mdi-account me-1"></i>{{ $log->loggedBy->name ?? 'Unknown' }}
                                    &mdash; {{ $log->created_at->format('d M Y, H:i') }}
                                </div>
                            </div>
                            <p class="mb-1"><strong>Work Done:</strong> {{ $log->work_description }}</p>
                            @if($log->notes)
                                <p class="text-muted small mb-2"><strong>Notes:</strong> {{ $log->notes }}</p>
                            @endif

                            {{-- After photos --}}
                            @if(!empty($afterPhotos))
                                <div class="mt-3">
                                    <div class="fw-semibold small text-success mb-2">
                                        <i class="mdi mdi-image-check-outline me-1"></i>AFTER
                                        <span class="text-muted fw-normal ms-1">(click to zoom &amp; rotate)</span>
                                    </div>
                                    <div class="d-flex flex-wrap gap-2">
                                        @foreach($afterPhotos as $apIdx => $ap)
                                            @php $apUrl = $inspection->getStorageUrl($ap); @endphp
                                            <img src="{{ $apUrl }}"
                                                 class="photo-thumb"
                                                 data-src="{{ $apUrl }}"
                                                 data-group="after-{{ $log->id }}"
                                                 data-index="{{ $apIdx }}"
                                                 style="height:110px;width:110px;object-fit:cover;border-radius:8px;
                                                        border:3px solid #198754;cursor:zoom-in;transition:transform .15s;"
                                                 alt="After photo {{ $apIdx+1 }}"
                                                 onmouseover="this.style.transform='scale(1.06)'"
                                                 onmouseout="this.style.transform='scale(1)'">
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                @endif
            </div>
        </div>

    @empty
        <div class="card border-0 shadow-sm text-center py-5">
            <div class="card-body">
                <i class="mdi mdi-check-all text-success" style="font-size:3rem;"></i>
                <h5 class="mt-3">No PHAR findings on record</h5>
                <p class="text-muted small">Findings are recorded during the PHAR inspection process.</p>
            </div>
        </div>
    @endforelse

    {{-- General / non-finding logs --}}
    @if($generalLogs->isNotEmpty())
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <span class="fw-semibold"><i class="mdi mdi-note-text-outline me-1 text-primary"></i>General Work Logs</span>
            <span class="badge bg-primary ms-2">{{ $generalLogs->count() }}</span>
            <span class="text-muted small ms-2">— not tied to a specific finding</span>
        </div>
        <div class="card-body py-3">
            @foreach($generalLogs as $log)
                @php $afterPhotos = $log->after_photos ?? []; @endphp
                <div class="border rounded p-3 mb-3">
                    <div class="d-flex justify-content-between align-items-start flex-wrap mb-2">
                        <div class="d-flex gap-2 flex-wrap">
                            <span class="badge bg-{{ $log->status === 'completed' ? 'success' : ($log->status === 'in_progress' ? 'primary' : 'secondary') }}">
                                {{ ucfirst(str_replace('_', ' ', $log->status)) }}
                            </span>
                            @if($log->hours_worked)
                                <span class="badge bg-light text-dark border">{{ $log->hours_worked }}h</span>
                            @endif
                            <span class="badge bg-light text-muted border">{{ \Carbon\Carbon::parse($log->visit_date)->format('d M Y') }}</span>
                        </div>
                        <div class="text-muted small">
                            {{ $log->loggedBy->name ?? 'Unknown' }} &mdash; {{ $log->created_at->format('d M Y, H:i') }}
                        </div>
                    </div>
                    <p class="mb-1"><strong>Work Done:</strong> {{ $log->work_description }}</p>
                    @if($log->notes)
                        <p class="text-muted small mb-2"><strong>Notes:</strong> {{ $log->notes }}</p>
                    @endif
                    @if(!empty($afterPhotos))
                        <div class="mt-2">
                            <div class="fw-semibold small text-success mb-2">
                                <i class="mdi mdi-image-check-outline me-1"></i>AFTER
                                <span class="text-muted fw-normal ms-1">(click to zoom &amp; rotate)</span>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($afterPhotos as $apIdx => $ap)
                                    @php $apUrl = $inspection->getStorageUrl($ap); @endphp
                                    <img src="{{ $apUrl }}"
                                         class="photo-thumb"
                                         data-src="{{ $apUrl }}"
                                         data-group="gen-{{ $log->id }}"
                                         data-index="{{ $apIdx }}"
                                         style="height:110px;width:110px;object-fit:cover;border-radius:8px;
                                                border:3px solid #198754;cursor:zoom-in;transition:transform .15s;"
                                         alt="After photo"
                                         onmouseover="this.style.transform='scale(1.06)'"
                                         onmouseout="this.style.transform='scale(1)'">
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
    @endif

</div>

{{-- ── Photo Lightbox ─────────────────────────────────────────── --}}
<div id="photoLightbox"
     style="position:fixed;inset:0;z-index:9999;display:none;
            background:rgba(0,0,0,.92);flex-direction:column;
            align-items:center;justify-content:center;">

    {{-- Toolbar --}}
    <div style="position:absolute;top:0;left:0;right:0;display:flex;align-items:center;
                justify-content:space-between;padding:10px 16px;background:rgba(0,0,0,.5);z-index:2;">
        <div id="lbCaption" style="color:#fff;font-size:.9rem;"></div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <button onclick="lbZoom(-0.25)" title="Zoom out"
                    style="background:rgba(255,255,255,.15);border:none;color:#fff;border-radius:6px;padding:6px 12px;cursor:pointer;font-size:1.1rem;">
                <i class="mdi mdi-magnify-minus-outline"></i>
            </button>
            <button onclick="lbZoom(0.25)" title="Zoom in"
                    style="background:rgba(255,255,255,.15);border:none;color:#fff;border-radius:6px;padding:6px 12px;cursor:pointer;font-size:1.1rem;">
                <i class="mdi mdi-magnify-plus-outline"></i>
            </button>
            <button onclick="lbRotate(-90)" title="Rotate left"
                    style="background:rgba(255,255,255,.15);border:none;color:#fff;border-radius:6px;padding:6px 12px;cursor:pointer;font-size:1.1rem;">
                <i class="mdi mdi-rotate-left"></i>
            </button>
            <button onclick="lbRotate(90)" title="Rotate right"
                    style="background:rgba(255,255,255,.15);border:none;color:#fff;border-radius:6px;padding:6px 12px;cursor:pointer;font-size:1.1rem;">
                <i class="mdi mdi-rotate-right"></i>
            </button>
            <button onclick="lbReset()" title="Reset view"
                    style="background:rgba(255,255,255,.15);border:none;color:#fff;border-radius:6px;padding:6px 14px;cursor:pointer;font-size:.85rem;">
                Reset
            </button>
            <button onclick="lbClose()" title="Close (Esc)"
                    style="background:rgba(220,53,69,.8);border:none;color:#fff;border-radius:6px;padding:6px 14px;cursor:pointer;font-size:1.1rem;">
                <i class="mdi mdi-close"></i>
            </button>
        </div>
    </div>

    {{-- Prev / Next --}}
    <button id="lbPrev" onclick="lbNav(-1)"
            style="position:absolute;left:10px;top:50%;transform:translateY(-50%);
                   background:rgba(255,255,255,.15);border:none;color:#fff;border-radius:50%;
                   width:48px;height:48px;font-size:1.5rem;cursor:pointer;z-index:2;display:flex;
                   align-items:center;justify-content:center;">
        <i class="mdi mdi-chevron-left"></i>
    </button>
    <button id="lbNext" onclick="lbNav(1)"
            style="position:absolute;right:10px;top:50%;transform:translateY(-50%);
                   background:rgba(255,255,255,.15);border:none;color:#fff;border-radius:50%;
                   width:48px;height:48px;font-size:1.5rem;cursor:pointer;z-index:2;display:flex;
                   align-items:center;justify-content:center;">
        <i class="mdi mdi-chevron-right"></i>
    </button>

    {{-- Image wrapper --}}
    <div style="overflow:hidden;display:flex;align-items:center;justify-content:center;
                max-width:90vw;max-height:80vh;">
        <img id="lbImg" src="" alt="Photo"
             style="max-width:88vw;max-height:78vh;object-fit:contain;
                    transition:transform .25s ease;transform-origin:center center;display:block;">
    </div>

    {{-- Counter --}}
    <div id="lbCounter" style="color:rgba(255,255,255,.6);font-size:.8rem;margin-top:10px;"></div>
</div>

<script>
(function () {
    var lbGroup  = [];
    var lbIndex  = 0;
    var lbScale  = 1;
    var lbDeg    = 0;

    var box     = document.getElementById('photoLightbox');
    var img     = document.getElementById('lbImg');
    var counter = document.getElementById('lbCounter');
    var prevBtn = document.getElementById('lbPrev');
    var nextBtn = document.getElementById('lbNext');

    // Build groups
    var groups = {};
    document.querySelectorAll('.photo-thumb').forEach(function(el) {
        var g = el.dataset.group;
        if (!groups[g]) groups[g] = [];
        groups[g].push(el.dataset.src);
    });

    // Attach click
    document.querySelectorAll('.photo-thumb').forEach(function(el) {
        el.addEventListener('click', function() {
            var g    = this.dataset.group;
            lbGroup  = groups[g] || [this.dataset.src];
            lbIndex  = parseInt(this.dataset.index) || 0;
            lbScale  = 1;
            lbDeg    = 0;
            box.style.display = 'flex';
            lbRender();
        });
    });

    function lbRender() {
        img.src = lbGroup[lbIndex];
        applyTransform();
        counter.textContent = (lbIndex + 1) + ' / ' + lbGroup.length;
        prevBtn.style.opacity = lbGroup.length > 1 ? '1' : '0';
        nextBtn.style.opacity = lbGroup.length > 1 ? '1' : '0';
    }

    function applyTransform() {
        img.style.transform = 'scale(' + lbScale + ') rotate(' + lbDeg + 'deg)';
    }

    window.lbClose  = function() { box.style.display = 'none'; img.src = ''; };
    window.lbZoom   = function(d) { lbScale = Math.max(0.2, Math.min(6, lbScale + d)); applyTransform(); };
    window.lbRotate = function(d) { lbDeg   = (lbDeg + d + 360) % 360; applyTransform(); };
    window.lbReset  = function()  { lbScale = 1; lbDeg = 0; applyTransform(); };
    window.lbNav    = function(d) {
        lbIndex = (lbIndex + d + lbGroup.length) % lbGroup.length;
        lbScale = 1; lbDeg = 0;
        lbRender();
    };

    // Scroll-wheel zoom
    img.addEventListener('wheel', function(e) {
        e.preventDefault();
        lbZoom(e.deltaY < 0 ? 0.15 : -0.15);
    }, { passive: false });

    // Click backdrop to close
    box.addEventListener('click', function(e) {
        if (e.target === box) lbClose();
    });

    // Keyboard
    document.addEventListener('keydown', function(e) {
        if (box.style.display === 'none') return;
        if (e.key === 'Escape')                   lbClose();
        if (e.key === 'ArrowLeft')                lbNav(-1);
        if (e.key === 'ArrowRight')               lbNav(1);
        if (e.key === '+' || e.key === '=')       lbZoom(0.25);
        if (e.key === '-')                        lbZoom(-0.25);
        if (e.key === 'r' || e.key === 'R')       lbRotate(90);
        if (e.key === 'l' || e.key === 'L')       lbRotate(-90);
    });
})();
</script>
@endsection
