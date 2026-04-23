@extends('client.layout')

@section('title', 'Completed Log Sheet')
@section('header', 'Completed Log Sheet')

@section('breadcrumbs')
<li class="breadcrumb-item"><a href="{{ route('client.projects.index') }}">Projects</a></li>
<li class="breadcrumb-item active" aria-current="page">Completed Log Sheet</li>
@endsection

@section('content')
<div class="row mb-3">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div>
                    <h5 class="fw-bold mb-1">{{ $project->property?->property_name ?? 'Project' }}</h5>
                    <p class="text-muted mb-0 small">Inspection #{{ $inspection->id }} completed work logs (read-only)</p>
                </div>
                <a href="{{ route('client.projects.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="mdi mdi-arrow-left me-1"></i>Back to Projects
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="text-muted small">Approved Findings</div>
                <div class="fw-bold fs-5">{{ $findings->count() }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="text-muted small">Completed Log Entries</div>
                <div class="fw-bold fs-5 text-success">{{ $completedLogs->count() }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                @php
                    $completedFindingCount = $findings->filter(fn($f) => $completedByFinding->has((int) $f->id))->count();
                @endphp
                <div class="text-muted small">Findings With Completed Logs</div>
                <div class="fw-bold fs-5 text-primary">{{ $completedFindingCount }} / {{ $findings->count() }}</div>
            </div>
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
            $rows = $completedByFinding->get((int) $finding->id, collect())->values();
        @endphp
        <div class="card border-0 shadow-sm mb-3" style="border-left:4px solid #198754 !important;">
            <div class="card-header bg-white border-0">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h6 class="mb-0 fw-semibold">{{ $finding->task_question }}</h6>
                        <small class="text-muted">{{ $finding->category ?? 'General' }}</small>
                    </div>
                    <span class="badge {{ $rows->isNotEmpty() ? 'bg-success' : 'bg-secondary' }}">
                        {{ $rows->count() }} completed {{ \Illuminate\Support\Str::plural('log', $rows->count()) }}
                    </span>
                </div>
            </div>
            <div class="card-body">
                @if($rows->isEmpty())
                    <p class="text-muted small mb-0">No completed log entries yet for this finding.</p>
                @else
                    @foreach($rows as $log)
                        <div class="border rounded p-3 mb-3 bg-light">
                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
                                <div>
                                    <span class="badge bg-success">Completed</span>
                                    @if($log->hours_worked)
                                        <span class="badge bg-white text-dark border ms-1">{{ $log->hours_worked }}h</span>
                                    @endif
                                </div>
                                <small class="text-muted">
                                    {{ optional($log->visit_date)->format('d M Y') }}
                                    @if($log->loggedBy)
                                        | {{ $log->loggedBy->name }}
                                    @endif
                                </small>
                            </div>
                            <p class="mb-1"><strong>Work done:</strong> {{ $log->work_description }}</p>
                            @if($log->notes)
                                <p class="mb-2 text-muted small"><strong>Notes:</strong> {{ $log->notes }}</p>
                            @endif

                            @php $afterPhotos = $log->after_photos ?? []; @endphp
                            @if(!empty($afterPhotos))
                                <div class="d-flex flex-wrap gap-2 mt-2">
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
