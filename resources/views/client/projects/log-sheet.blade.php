@extends('client.layout')

@section('title', 'Project Work Log')
@section('header', 'Project Work Log')

@section('breadcrumbs')
<li class="breadcrumb-item"><a href="{{ route('client.projects.index') }}">Projects</a></li>
<li class="breadcrumb-item active" aria-current="page">Work Log</li>
@endsection

@section('content')
@php
    // Compute overall project progress
    $totalFindings = $findings->count();
    $resolvedCount = 0;
    $overallSum    = 0;
    foreach ($findings as $f) {
        if ($completedFindingIds->contains((int) $f->id)) {
            $overallSum += 100;
            $resolvedCount++;
        } elseif ($totalScheduledDates > 0) {
            $loggedDates = ($allLogsByFinding->get((int) $f->id) ?? collect())
                ->pluck('visit_date')
                ->map(fn($d) => is_string($d) ? $d : $d->toDateString())
                ->unique()->count();
            $overallSum += min(99, (int) round(($loggedDates / $totalScheduledDates) * 100));
        }
    }
    $overallPct    = $totalFindings > 0 ? (int) round($overallSum / $totalFindings) : 0;
    $projectDone   = $overallPct >= 100;
@endphp

{{-- Project header card --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <h5 class="fw-bold mb-1">{{ $project->property?->property_name ?? 'Project' }}</h5>
                <p class="text-muted mb-2 small">{{ $project->property?->property_address ?? '' }}</p>
                <a href="{{ route('client.projects.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="mdi mdi-arrow-left me-1"></i>Back to Projects
                </a>
            </div>
            {{-- Overall progress --}}
            <div class="text-end">
                <div class="fw-bold mb-1 {{ $projectDone ? 'text-success' : 'text-primary' }}"
                     style="font-size:2rem;line-height:1;">{{ $overallPct }}%</div>
                <div class="text-muted small mb-2">
                    {{ $resolvedCount }} / {{ $totalFindings }} issues fully resolved
                </div>
                <div class="progress mb-1" style="width:160px;height:10px;border-radius:5px;">
                    <div class="progress-bar {{ $projectDone ? 'bg-success' : 'bg-primary' }}"
                         style="width:{{ $overallPct }}%;border-radius:5px;"></div>
                </div>
                @if($projectDone)
                <span class="badge bg-success mt-1 px-3 py-2" style="font-size:.8rem;">
                    <i class="mdi mdi-check-decagram me-1"></i>Project Complete
                </span>
                @else
                <span class="badge bg-warning text-dark mt-1 px-2 py-1" style="font-size:.75rem;">
                    <i class="mdi mdi-hammer-wrench me-1"></i>Work In Progress
                </span>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Summary stats --}}
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="text-muted small">Total Issues</div>
            <div class="fw-bold fs-4">{{ $totalFindings }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="text-muted small">Fully Resolved</div>
            <div class="fw-bold fs-4 text-success">{{ $resolvedCount }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="text-muted small">Log Entries</div>
            <div class="fw-bold fs-4 text-primary">{{ $inspection->maintenanceVisitLogs->count() }}</div>
        </div>
    </div>
</div>

@if($findings->isEmpty())
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-4">
            <i class="mdi mdi-clipboard-remove-outline text-muted" style="font-size:2rem;"></i>
            <p class="text-muted mt-2 mb-0">No approved findings were found for this inspection.</p>
        </div>
    </div>
@else
    @foreach($findings as $finding)
        @php
            $fid        = (int) $finding->id;
            $fResolved  = $completedFindingIds->contains($fid);
            $fLogs      = ($allLogsByFinding->get($fid) ?? collect())->sortByDesc(fn($l) => $l->created_at);
            $fLogCount  = $fLogs->count();
            $fLoggedDates = $fLogs->pluck('visit_date')
                ->map(fn($d) => is_string($d) ? $d : $d->toDateString())
                ->unique()->count();
            $fPct = $fResolved
                ? 100
                : ($totalScheduledDates > 0
                    ? min(99, (int) round(($fLoggedDates / $totalScheduledDates) * 100))
                    : ($fLogCount > 0 ? 50 : 0));
            $fPctColor  = $fPct >= 100 ? 'success' : ($fPct >= 50 ? 'primary' : ($fPct > 0 ? 'warning' : 'secondary'));
            $borderColor = $fResolved ? '#198754' : ($fPct > 0 ? '#1e40af' : '#dee2e6');
        @endphp
        <div class="card border-0 shadow-sm mb-3" style="border-left:4px solid {{ $borderColor }} !important;">
            <div class="card-header bg-white border-0 py-3">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                    <div style="flex:1;min-width:0;">
                        <h6 class="mb-0 fw-semibold">{{ $finding->task_question }}</h6>
                        <small class="text-muted">{{ $finding->category ?? 'General' }}</small>
                    </div>
                    {{-- Per-finding progress --}}
                    <div class="text-end flex-shrink-0" style="min-width:130px;">
                        <span class="fw-semibold {{ $fResolved ? 'text-success' : 'text-primary' }}"
                              style="font-size:.9rem;">
                            {{ $fPct }}%
                            @if($fResolved)<i class="mdi mdi-check-circle ms-1 text-success"></i>@endif
                        </span>
                        <div class="progress my-1" style="height:6px;width:120px;margin-left:auto;">
                            <div class="progress-bar bg-{{ $fPctColor }}" style="width:{{ $fPct }}%;"></div>
                        </div>
                        <span class="text-muted" style="font-size:.7rem;">
                            {{ $fLoggedDates }} / {{ $totalScheduledDates }} day{{ $totalScheduledDates !== 1 ? 's' : '' }}
                            &nbsp;·&nbsp; {{ $fLogCount }} {{ \Str::plural('log', $fLogCount) }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="card-body pt-0">
                @if($fLogs->isEmpty())
                    <p class="text-muted small mb-0">
                        <i class="mdi mdi-clock-outline me-1"></i>No work logged yet for this issue.
                    </p>
                @else
                    @foreach($fLogs as $log)
                        @php
                            $afterPhotos = $log->after_photos ?? [];
                            $acTasks     = $log->accomplished_tasks ?? [];
                        @endphp
                        <div class="border rounded p-3 mb-3 {{ $log->status === 'completed' ? 'border-success' : '' }}"
                             style="{{ $log->status === 'completed' ? 'background:#f0fff4;' : '' }}">
                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
                                <div>
                                    <span class="badge bg-{{ $log->status === 'completed' ? 'success' : ($log->status === 'in_progress' ? 'primary' : 'secondary') }}">
                                        {{ ucfirst(str_replace('_', ' ', $log->status)) }}
                                    </span>
                                </div>
                                <small class="text-muted">
                                    <i class="mdi mdi-calendar me-1"></i>{{ optional($log->visit_date)->format('d M Y') }}
                                    @if($log->loggedBy)
                                        &nbsp;&mdash;&nbsp;{{ $log->loggedBy->name }}
                                    @endif
                                </small>
                            </div>

                            <p class="mb-1 text-muted small"><i class="mdi mdi-calendar me-1"></i>{{ optional($log->visit_date)->format('d M Y') }}</p>
                            @if($log->notes)
                                <p class="mb-2 text-muted small"><strong>Notes:</strong> {{ $log->notes }}</p>
                            @endif

                            {{-- Task completion log --}}
                            @if(!empty($acTasks))
                            <div class="mb-2" style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:10px 14px;">
                                <div style="color:#166534;font-weight:600;font-size:.75rem;text-transform:uppercase;letter-spacing:.04em;margin-bottom:8px;">
                                    <i class="mdi mdi-clipboard-check-outline me-1"></i>Work Completed
                                </div>
                                @foreach($acTasks as $tIdx => $acTask)
                                    @php
                                        $isNew     = is_array($acTask);
                                        $taskLabel = $isNew ? ($acTask['task'] ?? '') : $acTask;
                                        $taskDesc  = $isNew ? ($acTask['description'] ?? '') : '';
                                    @endphp
                                    <div class="d-flex gap-2 align-items-start mb-2 {{ !$loop->last ? 'pb-2 border-bottom' : '' }}"
                                         style="{{ !$loop->last ? 'border-color:#bbf7d0!important;' : '' }}">
                                        <span class="badge flex-shrink-0 mt-1" style="background:#166534;font-size:.7rem;">{{ $tIdx + 1 }}</span>
                                        <div style="min-width:0;">
                                            <div class="fw-semibold small" style="color:#14532d;">{{ $taskLabel }}</div>
                                            @if($taskDesc)
                                                <div class="text-muted small mt-1" style="font-size:.8rem;">{{ $taskDesc }}</div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            @endif

                            {{-- After photos --}}
                            @if(!empty($afterPhotos))
                                <div class="d-flex flex-wrap gap-2 mt-2 justify-content-end">
                                    <div class="fw-semibold small text-success mb-1 w-100 text-end">
                                        AFTER <i class="mdi mdi-image-check-outline ms-1"></i>
                                    </div>
                                    @foreach($afterPhotos as $ap)
                                        @php $apUrl = $inspection->getStorageUrl($ap); @endphp
                                        <a href="{{ $apUrl }}" target="_blank" rel="noopener">
                                            <img src="{{ $apUrl }}"
                                                 alt="After photo"
                                                 style="height:90px;width:90px;object-fit:cover;border-radius:6px;border:2px solid #198754;">
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    @endforeach
@endif
@endsection
