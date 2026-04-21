@extends('admin.layout')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold mb-0"><i class="mdi mdi-clipboard-list-outline me-2 text-success"></i>Property Maintenance Visit Logs</h4>
            <p class="text-muted small mb-0">Track before &amp; after photos and work done on each scheduled visit</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($inspections->isEmpty())
        <div class="card border-0 shadow-sm text-center py-5">
            <div class="card-body">
                <i class="mdi mdi-calendar-clock text-muted" style="font-size:3rem;"></i>
                <h5 class="mt-3 text-muted">No scheduled properties yet</h5>
                <p class="text-muted small">Visit logs will appear here once a property maintenance work schedule has been set.</p>
            </div>
        </div>
    @else
        <div class="row g-3">
            @foreach($inspections as $inspection)
                @php
                    $schedule     = collect($inspection->work_schedule ?? []);
                    $totalVisits  = $schedule->count();
                    $doneVisits   = $schedule->where('status', 'completed')->count();
                    $progress     = $totalVisits > 0 ? round(($doneVisits / $totalVisits) * 100) : 0;
                    $logCount     = $inspection->maintenanceVisitLogs->count();
                    $property     = $inspection->property;
                    $lastLog      = $inspection->maintenanceVisitLogs->sortByDesc('created_at')->first();
                @endphp
                <div class="col-md-6 col-xl-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body pb-2">
                            <div class="d-flex align-items-start justify-content-between mb-2">
                                <div>
                                    <h6 class="fw-semibold mb-0">{{ $property->property_name ?? 'Property #'.$inspection->property_id }}</h6>
                                    <div class="text-muted small">{{ $property->property_address ?? '' }}</div>
                                </div>
                                <span class="badge bg-success-subtle text-success ms-2">Active</span>
                            </div>

                            <div class="row text-center mt-3 g-0 border rounded overflow-hidden">
                                <div class="col border-end py-2">
                                    <div class="fw-bold text-primary">{{ $totalVisits }}</div>
                                    <div class="text-muted" style="font-size:.7rem;">SCHEDULED VISITS</div>
                                </div>
                                <div class="col border-end py-2">
                                    <div class="fw-bold text-success">{{ $doneVisits }}</div>
                                    <div class="text-muted" style="font-size:.7rem;">COMPLETED</div>
                                </div>
                                <div class="col py-2">
                                    <div class="fw-bold text-warning">{{ $logCount }}</div>
                                    <div class="text-muted" style="font-size:.7rem;">LOG ENTRIES</div>
                                </div>
                            </div>

                            @if($totalVisits > 0)
                            <div class="mt-3">
                                <div class="d-flex justify-content-between small text-muted mb-1">
                                    <span>Schedule Progress</span><span>{{ $progress }}%</span>
                                </div>
                                <div class="progress" style="height:6px;">
                                    <div class="progress-bar bg-success" style="width:{{ $progress }}%"></div>
                                </div>
                            </div>
                            @endif

                            @if($inspection->planned_start_date)
                                <div class="mt-2 small text-muted">
                                    <i class="mdi mdi-calendar-start me-1"></i>{{ \Carbon\Carbon::parse($inspection->planned_start_date)->format('d M Y') }}
                                    @if($inspection->target_completion_date)
                                        → {{ \Carbon\Carbon::parse($inspection->target_completion_date)->format('d M Y') }}
                                    @endif
                                </div>
                            @endif

                            @if($lastLog)
                                <div class="mt-2 small text-muted">
                                    <i class="mdi mdi-clock-outline me-1"></i>Last log: {{ $lastLog->created_at->diffForHumans() }}
                                </div>
                            @endif
                        </div>
                        <div class="card-footer bg-transparent border-top-0 pt-0 pb-3 px-3">
                            <a href="{{ route('maintenance-visit-logs.show', $inspection) }}"
                               class="btn btn-success btn-sm w-100">
                                <i class="mdi mdi-plus-circle me-1"></i>Log Work &amp; View Details
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
