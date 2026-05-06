@extends('admin.layout')

@section('content')
@php
    $property       = $inspection->property;
    $logsByFinding  = $inspection->maintenanceVisitLogs->groupBy('finding_id');
    $generalLogs    = $logsByFinding->get(null, collect())->sortByDesc('created_at');
    $dailyHourCap   = 11.0;
    $hoursByVisitDate = $inspection->maintenanceVisitLogs
        ->groupBy(fn($log) => $log->visit_date->toDateString())
        ->map(fn($logs) => (float) $logs->sum(fn($log) => (float) ($log->hours_worked ?? 0)));

    // Flatten all scheduled deliverable days for the dropdown
    $allScheduleDays = [];
    foreach (collect($inspection->work_schedule ?? []) as $vIdx => $visit) {
        $deliverables = $visit['deliverables'] ?? [];
        if (!empty($deliverables)) {
            foreach ($deliverables as $dIdx => $dl) {
                $dlDate = $dl['date'] ?? ($visit['date'] ?? '');
                if ($dlDate === '') continue;
                $dlTasks = $dl['tasks'] ?? [];
                if (empty($dlTasks) && !empty($dl['planned_work'])) $dlTasks = [$dl['planned_work']];
                $allScheduleDays[] = [
                    'date'      => $dlDate,
                    'visit_num' => $vIdx + 1,
                    'day_num'   => $dl['day'] ?? ($dIdx + 1),
                    'tasks'     => array_values(array_filter(array_map('strval', $dlTasks))),
                    'status'    => $visit['status'] ?? 'scheduled',
                ];
            }
        } else {
            if (!empty($visit['date'])) {
                $allScheduleDays[] = [
                    'date'      => $visit['date'],
                    'visit_num' => $vIdx + 1,
                    'day_num'   => 1,
                    'tasks'     => [],
                    'status'    => $visit['status'] ?? 'scheduled',
                ];
            }
        }
    }
    usort($allScheduleDays, fn($a, $b) => strcmp($a['date'], $b['date']));

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
        if ($completedFindingIds->contains((int) $f->id)) {
            $resolvedFindings++;
        }
    }
    // Overall % = average of all per-finding progress percentages
    $overallPct = 0;
    if ($totalFindings > 0) {
        $sumPct = 0;
        foreach ($findings as $f) {
            if ($completedFindingIds->contains((int) $f->id)) {
                $sumPct += 100;
            } elseif ($totalScheduledDates > 0) {
                $loggedDateCount = $logsByFinding->get($f->id, collect())
                    ->pluck('visit_date')
                    ->map(fn($d) => is_string($d) ? $d : $d->toDateString())
                    ->unique()->count();
                $sumPct += min(99, (int) round(($loggedDateCount / $totalScheduledDates) * 100));
            }
        }
        $overallPct = (int) round($sumPct / $totalFindings);
    }
    $allResolved = $totalFindings > 0 && $resolvedFindings === $totalFindings;
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
            <div class="fw-bold {{ $overallPct >= 100 ? 'text-success' : 'text-primary' }}" style="font-size:1.4rem;">{{ $overallPct }}%</div>
            <div class="text-muted small">{{ $resolvedFindings }} / {{ $totalFindings }} issues resolved</div>
            <div class="progress mt-1" style="width:120px;height:8px;">
                <div class="progress-bar {{ $overallPct >= 100 ? 'bg-success' : 'bg-primary' }}" style="width:{{ $overallPct }}%"></div>
            </div>
            @if(!$allResolved)
            <form method="POST" action="{{ route('maintenance-visit-logs.complete-project', $inspection) }}"
                  class="mt-2"
                  onsubmit="return confirm('Mark ALL issues as resolved and close this project? This cannot be undone.');">
                @csrf
                <button type="submit" class="btn btn-sm btn-success fw-semibold px-3">
                    <i class="mdi mdi-check-all me-1"></i>Mark Project Complete
                </button>
            </form>
            @else
            <div class="mt-2 badge bg-success px-3 py-2" style="font-size:.8rem;">
                <i class="mdi mdi-check-decagram me-1"></i>Project Complete
            </div>
            @endif
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

    {{-- ====== VISIT WORK PLAN (day-by-day deliverables) ====== --}}
    @php
        $allVisitsForPlan = collect($schedule);
    @endphp
    @if($allVisitsForPlan->isNotEmpty())
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <span class="fw-semibold">
                <i class="mdi mdi-clipboard-text-clock-outline me-1 text-primary"></i>
                Visit Work Plan — Day-by-Day Deliverables
            </span>
            <span class="badge bg-light border text-dark">{{ $allVisitsForPlan->count() }} visit(s)</span>
        </div>
        <div class="accordion accordion-flush" id="visitPlanAccordion">
            @foreach($allVisitsForPlan as $vi => $visit)
                @php
                    $deliverables = $visit['deliverables'] ?? [];
                    $visitStatus  = $visit['status'] ?? 'scheduled';
                    $statusColor  = match($visitStatus) {
                        'completed'   => 'success',
                        'in_progress' => 'primary',
                        default       => 'secondary',
                    };
                @endphp
                <div class="accordion-item border-0 border-bottom">
                    <h2 class="accordion-header">
                        <button class="accordion-button {{ $vi > 0 ? 'collapsed' : '' }} py-2"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#visitPlan-{{ $vi }}"
                                aria-expanded="{{ $vi === 0 ? 'true' : 'false' }}">
                            <span class="badge bg-primary me-2">Visit {{ $vi + 1 }}</span>
                            <strong class="me-2">{{ \Carbon\Carbon::parse($visit['date'])->format('D, d M Y') }}</strong>
                            <span class="badge bg-{{ $statusColor }} me-2">{{ ucfirst(str_replace('_',' ',$visitStatus)) }}</span>
                            @if(!empty($deliverables))
                                <span class="text-muted small">{{ count($deliverables) }} day{{ count($deliverables) !== 1 ? 's' : '' }} planned</span>
                            @else
                                <span class="text-muted small fst-italic">No day-by-day plan added</span>
                            @endif
                        </button>
                    </h2>
                    <div id="visitPlan-{{ $vi }}"
                         class="accordion-collapse collapse {{ $vi === 0 ? 'show' : '' }}"
                         data-bs-parent="#visitPlanAccordion">
                        <div class="accordion-body py-3">
                            @if(!empty($deliverables))
                                <p class="text-muted small mb-2">
                                    <i class="mdi mdi-information-outline me-1"></i>
                                    Use this plan as a reference when logging work below. Each log entry should describe how you completed the planned activity for that day.
                                </p>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width:70px;">Day</th>
                                                <th style="width:170px;">Date</th>
                                                <th>Planned Activities</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($deliverables as $dl)
                                            <tr>
                                                <td class="text-center fw-semibold text-primary">{{ $dl['day'] }}</td>
                                                <td class="text-nowrap small">
                                                    {{ \Carbon\Carbon::parse($dl['date'])->format('D, d M Y') }}
                                                </td>
                                                <td>
                                                    @if(!empty($dl['tasks']))
                                                        <ul class="mb-0 ps-3 small">
                                                            @foreach($dl['tasks'] as $task)
                                                                <li>{{ $task }}</li>
                                                            @endforeach
                                                        </ul>
                                                    @else
                                                        {{ $dl['planned_work'] ?? '—' }}
                                                    @endif
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <p class="text-muted small mb-0 fst-italic">
                                    No day-by-day work plan was added when this visit was scheduled. You can edit the schedule to add one.
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
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
            $isResolved  = $completedFindingIds->contains((int) $finding->id);
            $logCount    = $findingLogs->count();

            // Per-finding progress: logged unique dates / total scheduled dates (capped at 99 unless explicitly completed)
            $loggedDateCount  = $findingLogs->pluck('visit_date')
                ->map(fn($d) => is_string($d) ? $d : $d->toDateString())
                ->unique()->count();
            $findingPct = $isResolved
                ? 100
                : ($totalScheduledDates > 0
                    ? min(99, (int) round(($loggedDateCount / $totalScheduledDates) * 100))
                    : ($logCount > 0 ? 50 : 0));
            $pctColor = $findingPct >= 100 ? 'success' : ($findingPct >= 50 ? 'primary' : ($findingPct > 0 ? 'warning' : 'secondary'));

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
                        {{-- Estimated vs actual hours (admin-only) --}}
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

                        {{-- Per-finding progress pill + bar --}}
                        <div class="d-flex align-items-center gap-2">
                            <div class="d-flex flex-column align-items-end" style="min-width:110px;">
                                <span class="fw-semibold" style="font-size:.82rem;color:{{ $findingPct >= 100 ? '#166534' : '#1e40af' }};">
                                    {{ $findingPct }}%
                                    @if($isResolved)
                                        <i class="mdi mdi-check-circle ms-1 text-success"></i>
                                    @endif
                                </span>
                                <div class="progress" style="width:100px;height:6px;margin-top:3px;">
                                    <div class="progress-bar bg-{{ $pctColor }}" style="width:{{ $findingPct }}%;"></div>
                                </div>
                                <span class="text-muted" style="font-size:.7rem;margin-top:2px;">
                                    {{ $loggedDateCount }} / {{ $totalScheduledDates }} day{{ $totalScheduledDates !== 1 ? 's' : '' }} logged
                                </span>
                            </div>
                        </div>

                        @if($logCount > 0)
                            <span class="badge bg-light text-dark border">{{ $logCount }} {{ Str::plural('log', $logCount) }}</span>
                        @endif

                        {{-- Mark Complete button (only if not yet resolved) --}}
                        @if(!$isResolved)
                        <form method="POST"
                              action="{{ route('maintenance-visit-logs.complete-finding', $inspection) }}"
                              onsubmit="return confirm('Mark this issue as fully resolved (100%)? You should only do this after all scheduled work has been completed.');">
                            @csrf
                            <input type="hidden" name="finding_id" value="{{ $finding->id }}">
                            <button type="submit" class="btn btn-sm btn-outline-success fw-semibold"
                                    title="Mark this issue as 100% resolved">
                                <i class="mdi mdi-check-circle-outline me-1"></i>Mark Resolved
                            </button>
                        </form>
                        @else
                        <span class="badge bg-success px-3"><i class="mdi mdi-check-circle me-1"></i>Resolved</span>
                        @endif

                        <button class="btn btn-sm btn-success" type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#log-finding-{{ $finding->id }}">
                            <i class="mdi mdi-plus me-1"></i>Log Work
                        </button>
                    </div>
                </div>
            </div>

            {{-- Log Work form (collapsed) --}}
            <div class="collapse {{ $errors->any() && old('finding_id') == $finding->id ? 'show' : '' }}"
                 id="log-finding-{{ $finding->id }}">
                <div class="card-body bg-light border-bottom">
                    <form action="{{ route('maintenance-visit-logs.store', $inspection) }}"
                          method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="finding_id" value="{{ $finding->id }}">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Visit Date <span class="text-danger">*</span></label>
                                @if(!empty($allScheduleDays))
                                <select name="visit_date" class="form-select" required
                                        onchange="showScheduleDayTasks(this, {{ $finding->id }})">
                                    <option value="">— Select day —</option>
                                    @foreach($allScheduleDays as $sd)
                                        @php
                                            $sdDate     = $sd['date'];
                                            $usedHours  = (float)($hoursByVisitDate->get($sdDate, 0));
                                            $remaining  = max(0, $dailyHourCap - $usedHours);
                                            $isFull     = $usedHours >= $dailyHourCap;
                                            $isDone     = ($sd['status'] ?? '') === 'completed';
                                            $isSelected = old('visit_date') === $sdDate;
                                            $remLabel   = $isFull ? '— 11h full' : ('— ' . rtrim(rtrim(number_format($remaining, 2, '.', ''), '0'), '.') . 'h left');
                                        @endphp
                                        <option value="{{ $sdDate }}"
                                                data-tasks="{{ json_encode($sd['tasks']) }}"
                                                {{ ($isFull && !$isSelected) ? 'disabled' : '' }}
                                                {{ $isSelected ? 'selected' : '' }}>
                                            Visit {{ $sd['visit_num'] }} · Day {{ $sd['day_num'] }} — {{ \Carbon\Carbon::parse($sdDate)->format('D, d M Y') }}{{ $isDone ? ' ✓' : '' }} {{ $remLabel }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">All scheduled work days. Days at 11h total are locked.</div>
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
                                <input type="number" name="hours_worked" class="form-control @error('hours_worked') is-invalid @enderror"
                                       step="0.5" min="0" max="11" placeholder="e.g. 3"
                                       value="{{ old('hours_worked') }}">
                                @error('hours_worked')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Per visit date total cannot exceed 11 hours.</div>
                            </div>
                            {{-- ── Task Completion Log ── --}}
                            <div class="col-12">
                                <label class="form-label fw-semibold">
                                    Task Completion Log
                                    <span class="text-muted fw-normal small ms-1">— describe how each task was accomplished today</span>
                                </label>

                                {{-- Task rows container --}}
                                <div id="task-logs-{{ $finding->id }}" class="mb-2">
                                    <p class="text-muted small fst-italic mb-1"
                                       id="task-empty-{{ $finding->id }}"
                                       style="{{ old('finding_id') == $finding->id ? 'display:none;' : '' }}">
                                        <i class="mdi mdi-information-outline me-1"></i>Select a visit date above to load planned tasks, or add a custom task below.
                                    </p>
                                    {{-- Restore previous submission on validation fail --}}
                                    @if(old('finding_id') == $finding->id && old('task_logs'))
                                        @foreach(old('task_logs', []) as $tIdx => $tEntry)
                                            @php
                                                $tTask = $tEntry['task'] ?? '';
                                                $tDesc = $tEntry['description'] ?? '';
                                                $isScheduled = !empty($tTask) && collect($allScheduleDays)->flatMap(fn($d) => $d['tasks'] ?? [])->contains($tTask);
                                            @endphp
                                            @if($isScheduled)
                                            <div class="task-log-entry border rounded p-3 mb-2" style="background:#f0f9ff;border-color:#bae6fd!important;" data-scheduled="true">
                                                <div class="d-flex align-items-center gap-2 mb-2">
                                                    <span class="badge" style="background:#0369a1;font-size:.72rem;">Task {{ $tIdx + 1 }}</span>
                                                    <span class="fw-semibold small" style="color:#1e3a5f;">{{ $tTask }}</span>
                                                    <input type="hidden" name="task_logs[{{ $tIdx }}][task]" value="{{ $tTask }}">
                                                </div>
                                                <textarea name="task_logs[{{ $tIdx }}][description]"
                                                          class="form-control form-control-sm" rows="2"
                                                          placeholder="How was this task accomplished today?..."
                                                          style="font-size:.82rem;">{{ $tDesc }}</textarea>
                                            </div>
                                            @else
                                            <div class="task-log-entry border rounded p-3 mb-2 bg-white" data-scheduled="false">
                                                <div class="d-flex align-items-start gap-2 mb-2">
                                                    <span class="badge bg-secondary" style="font-size:.72rem;white-space:nowrap;margin-top:4px;">Custom</span>
                                                    <input type="text" name="task_logs[{{ $tIdx }}][task]"
                                                           class="form-control form-control-sm"
                                                           value="{{ $tTask }}"
                                                           placeholder="What task did you work on?" required>
                                                    <button type="button"
                                                            class="btn btn-sm btn-outline-danger flex-shrink-0"
                                                            onclick="this.closest('.task-log-entry').remove();renumberTasks({{ $finding->id }});"
                                                            title="Remove this task">
                                                        <i class="mdi mdi-close"></i>
                                                    </button>
                                                </div>
                                                <textarea name="task_logs[{{ $tIdx }}][description]"
                                                          class="form-control form-control-sm" rows="2"
                                                          placeholder="How was this task accomplished today?..."
                                                          style="font-size:.82rem;">{{ $tDesc }}</textarea>
                                            </div>
                                            @endif
                                        @endforeach
                                    @endif
                                </div>

                                <button type="button"
                                        class="btn btn-sm btn-outline-primary"
                                        onclick="addCustomTask({{ $finding->id }})">
                                    <i class="mdi mdi-plus me-1"></i>Add Custom Task
                                </button>
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
                    {{-- No logs yet: show before photos + placeholder --}}
                    @if(!empty($beforePhotos) || $usingSitePhotosAsFallback)
                    @php $displayPhotos = !empty($beforePhotos) ? $beforePhotos : $sitePhotos; @endphp
                    <div class="d-flex align-items-start gap-3 flex-wrap">
                        <div style="min-width:0;">
                            <div class="fw-semibold small text-danger mb-2">
                                <i class="mdi mdi-image-outline me-1"></i>BEFORE — Condition at Inspection
                                @if($usingSitePhotosAsFallback)
                                    <span class="badge bg-secondary ms-1" style="font-size:.7rem;">General Site Photos</span>
                                @endif
                                <span class="text-muted fw-normal ms-1">(click to zoom)</span>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($displayPhotos as $bpIdx => $bp)
                                    @php $bpUrl = $inspection->getStorageUrl($bp); @endphp
                                    <img src="{{ $bpUrl }}" class="photo-thumb"
                                         data-src="{{ $bpUrl }}" data-group="before-{{ $finding->id }}" data-index="{{ $bpIdx }}"
                                         style="height:110px;width:110px;object-fit:cover;border-radius:8px;border:3px solid #dc3545;cursor:zoom-in;transition:transform .15s;"
                                         alt="Before photo {{ $bpIdx+1 }}"
                                         onmouseover="this.style.transform='scale(1.06)'" onmouseout="this.style.transform='scale(1)'">
                                @endforeach
                            </div>
                        </div>
                        <div class="ms-auto text-end" style="min-width:160px;">
                            <div class="text-muted small"><i class="mdi mdi-image-off-outline me-1"></i>No after photos yet</div>
                        </div>
                    </div>
                    @else
                    <p class="text-muted small mb-0">
                        <i class="mdi mdi-clock-outline me-1"></i>
                        No work logged yet. Click <strong>Log Work</strong> above to record progress on this issue.
                    </p>
                    @endif
                @else
                    @foreach($findingLogs as $log)
                        @php $afterPhotos = $log->after_photos ?? []; @endphp
                        <div class="border rounded p-3 mb-3 {{ $log->status === 'completed' ? 'border-success' : '' }}"
                             style="{{ $log->status === 'completed' ? 'background:#f0fff4;' : '' }}">

                            {{-- Log metadata row --}}
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

                            {{-- Task completion log --}}
                            @php $acTasks = $log->accomplished_tasks ?? []; @endphp
                            @if(!empty($acTasks))
                            <div class="mb-3" style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:10px 14px;">
                                <div style="color:#166534;font-weight:600;font-size:.76rem;text-transform:uppercase;letter-spacing:.04em;margin-bottom:8px;">
                                    <i class="mdi mdi-clipboard-check-outline me-1"></i>Task Completion Log
                                </div>
                                @foreach($acTasks as $tIdx => $acTask)
                                    @php
                                        $isNew = is_array($acTask);
                                        $taskLabel = $isNew ? ($acTask['task'] ?? '') : $acTask;
                                        $taskDesc  = $isNew ? ($acTask['description'] ?? '') : '';
                                    @endphp
                                    <div class="d-flex gap-2 align-items-start mb-2 {{ !$loop->last ? 'pb-2 border-bottom' : '' }}"
                                         style="{{ !$loop->last ? 'border-color:#bbf7d0!important;' : '' }}">
                                        <span class="badge flex-shrink-0 mt-1"
                                              style="background:#166534;font-size:.7rem;">{{ $tIdx + 1 }}</span>
                                        <div style="min-width:0;">
                                            <div class="fw-semibold small" style="color:#14532d;">{{ $taskLabel }}</div>
                                            @if($taskDesc)
                                                <div class="text-muted small mt-1" style="font-size:.8rem;">{{ $taskDesc }}</div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            @elseif($log->work_description && $log->work_description !== 'Work logged on this visit date.')
                            <p class="mb-1"><strong>Work Done:</strong> {{ $log->work_description }}</p>
                            @endif
                            @if($log->notes)
                                <p class="text-muted small mb-2"><strong>Notes:</strong> {{ $log->notes }}</p>
                            @endif

                            {{-- Before / After side by side --}}
                            @php
                                $displayPhotos = !empty($beforePhotos) ? $beforePhotos : ($usingSitePhotosAsFallback ? $sitePhotos : []);
                                $hasBeforePhotos = !empty($displayPhotos);
                                $hasAfterPhotos  = !empty($afterPhotos);
                            @endphp
                            @if($hasBeforePhotos || $hasAfterPhotos)
                            <div class="d-flex gap-3 mt-3 pt-2 border-top align-items-start">

                                {{-- BEFORE — left --}}
                                <div style="flex:1;min-width:0;">
                                    <div class="fw-semibold small text-danger mb-2">
                                        <i class="mdi mdi-image-outline me-1"></i>BEFORE
                                        @if($usingSitePhotosAsFallback && $hasBeforePhotos)
                                            <span class="badge bg-secondary ms-1" style="font-size:.65rem;">Site Photos</span>
                                        @endif
                                        <span class="text-muted fw-normal ms-1" style="font-size:.75rem;">(click to zoom)</span>
                                    </div>
                                    @if($hasBeforePhotos)
                                    <div class="d-flex flex-wrap gap-2">
                                        @foreach($displayPhotos as $bpIdx => $bp)
                                            @php $bpUrl = $inspection->getStorageUrl($bp); @endphp
                                            <img src="{{ $bpUrl }}" class="photo-thumb"
                                                 data-src="{{ $bpUrl }}" data-group="before-{{ $finding->id }}" data-index="{{ $bpIdx }}"
                                                 style="height:110px;width:110px;object-fit:cover;border-radius:8px;border:3px solid #dc3545;cursor:zoom-in;transition:transform .15s;"
                                                 alt="Before photo {{ $bpIdx+1 }}"
                                                 onmouseover="this.style.transform='scale(1.06)'" onmouseout="this.style.transform='scale(1)'">
                                        @endforeach
                                    </div>
                                    @else
                                    <p class="text-muted small mb-0"><i class="mdi mdi-image-off-outline me-1"></i>No before photos</p>
                                    @endif
                                </div>

                                {{-- Divider --}}
                                <div style="width:1px;background:#dee2e6;align-self:stretch;"></div>

                                {{-- AFTER — right --}}
                                <div style="flex:1;min-width:0;">
                                    <div class="fw-semibold small text-success mb-2 text-end">
                                        <span class="text-muted fw-normal me-1" style="font-size:.75rem;">(click to zoom)</span>
                                        AFTER <i class="mdi mdi-image-check-outline ms-1"></i>
                                    </div>
                                    @if($hasAfterPhotos)
                                    <div class="d-flex flex-wrap gap-2 justify-content-end">
                                        @foreach($afterPhotos as $apIdx => $ap)
                                            @php $apUrl = $inspection->getStorageUrl($ap); @endphp
                                            <img src="{{ $apUrl }}" class="photo-thumb"
                                                 data-src="{{ $apUrl }}" data-group="after-{{ $log->id }}" data-index="{{ $apIdx }}"
                                                 style="height:110px;width:110px;object-fit:cover;border-radius:8px;border:3px solid #198754;cursor:zoom-in;transition:transform .15s;"
                                                 alt="After photo {{ $apIdx+1 }}"
                                                 onmouseover="this.style.transform='scale(1.06)'" onmouseout="this.style.transform='scale(1)'">
                                        @endforeach
                                    </div>
                                    @else
                                    <p class="text-muted small mb-0 text-end"><i class="mdi mdi-image-off-outline me-1"></i>No after photos yet</p>
                                    @endif
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
                            <div class="fw-semibold small text-success mb-2 text-end">
                                <span class="text-muted fw-normal me-1">(click to zoom &amp; rotate)</span>
                                AFTER <i class="mdi mdi-image-check-outline ms-1"></i>
                            </div>
                            <div class="d-flex flex-wrap gap-2 justify-content-end">
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

// ── Task completion log — per-task descriptions ──────────────────────────
var taskCounters = {};

function _esc(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
function _quot(s) {
    return String(s).replace(/&/g,'&amp;').replace(/"/g,'&quot;');
}

function showScheduleDayTasks(selectEl, findingId) {
    var panel    = document.getElementById('task-logs-' + findingId);
    var emptyMsg = document.getElementById('task-empty-' + findingId);
    if (!panel) return;

    // Remove only scheduled-task rows (keep any custom rows the user already added)
    panel.querySelectorAll('[data-scheduled="true"]').forEach(function(el) { el.remove(); });

    var option = selectEl.options[selectEl.selectedIndex];
    var tasks = [];
    try { tasks = JSON.parse(option.getAttribute('data-tasks') || '[]'); } catch(e) {}
    tasks = tasks.filter(function(t) { return t && String(t).trim() !== ''; });

    if (tasks.length === 0) {
        if (emptyMsg) emptyMsg.style.display = '';
        _renumberPanel(findingId);
        return;
    }
    if (emptyMsg) emptyMsg.style.display = 'none';

    // Insert scheduled rows at the TOP of the panel
    var rows = tasks.map(function(t, idx) {
        return '<div class="task-log-entry border rounded p-3 mb-2" ' +
               'style="background:#f0f9ff;border-color:#bae6fd!important;" data-scheduled="true">' +
                   '<div class="d-flex align-items-center gap-2 mb-2">' +
                       '<span class="badge task-num-badge" style="background:#0369a1;font-size:.72rem;">' + (idx+1) + '</span>' +
                       '<span class="fw-semibold small" style="color:#1e3a5f;">' + _esc(t) + '</span>' +
                       '<input type="hidden" name="task_logs[' + idx + '][task]" value="' + _quot(t) + '">' +
                   '</div>' +
                   '<textarea name="task_logs[' + idx + '][description]" ' +
                             'class="form-control form-control-sm" rows="2" ' +
                             'placeholder="How was this task accomplished today?..." ' +
                             'style="font-size:.82rem;"></textarea>' +
               '</div>';
    }).join('');

    panel.insertAdjacentHTML('afterbegin', rows);
    taskCounters[findingId] = tasks.length;
    _renumberPanel(findingId);
}

function addCustomTask(findingId) {
    var panel    = document.getElementById('task-logs-' + findingId);
    var emptyMsg = document.getElementById('task-empty-' + findingId);
    if (!panel) return;
    if (emptyMsg) emptyMsg.style.display = 'none';

    if (!taskCounters[findingId]) taskCounters[findingId] = 0;
    var idx = taskCounters[findingId]++;

    var row =
        '<div class="task-log-entry border rounded p-3 mb-2 bg-white" data-scheduled="false">' +
            '<div class="d-flex align-items-start gap-2 mb-2">' +
                '<span class="badge bg-secondary task-num-badge" style="font-size:.72rem;white-space:nowrap;margin-top:4px;">Custom</span>' +
                '<input type="text" name="task_logs[' + idx + '][task]" ' +
                       'class="form-control form-control-sm" ' +
                       'placeholder="What task did you work on?" required>' +
                '<button type="button" class="btn btn-sm btn-outline-danger flex-shrink-0" ' +
                        'onclick="this.closest(\'.task-log-entry\').remove();renumberTasks(' + findingId + ');" ' +
                        'title="Remove this task">' +
                    '<i class="mdi mdi-close"></i>' +
                '</button>' +
            '</div>' +
            '<textarea name="task_logs[' + idx + '][description]" ' +
                      'class="form-control form-control-sm" rows="2" ' +
                      'placeholder="How was this task accomplished today?..." ' +
                      'style="font-size:.82rem;"></textarea>' +
        '</div>';

    panel.insertAdjacentHTML('beforeend', row);
}

function renumberTasks(findingId) {
    _renumberPanel(findingId);
}

function _renumberPanel(findingId) {
    var panel = document.getElementById('task-logs-' + findingId);
    if (!panel) return;
    var entries = panel.querySelectorAll('.task-log-entry');
    var schedNum = 1;
    entries.forEach(function(entry, globalIdx) {
        // Renumber hidden indices on all [name] inputs/textareas
        entry.querySelectorAll('[name]').forEach(function(el) {
            el.name = el.name.replace(/task_logs\[\d+\]/, 'task_logs[' + globalIdx + ']');
        });
        // Update visible badge for scheduled tasks
        if (entry.getAttribute('data-scheduled') === 'true') {
            var badge = entry.querySelector('.task-num-badge');
            if (badge) badge.textContent = schedNum++;
        }
    });
    taskCounters[findingId] = entries.length;
}
</script>
@endsection
