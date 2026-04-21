@extends('admin.layout')

@section('title', 'Inspections')

@section('content')
<div class="content-wrapper">
    <div class="row">
        <div class="col-lg-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            @if(request('view') === 'pending-etogo')
                                <h4 class="card-title mb-0"><i class="mdi mdi-pen me-2 text-warning"></i>Pending Etogo Signature</h4>
                                <p class="text-muted small mb-0">Client has signed &amp; paid — awaiting your countersignature to start work</p>
                            @elseif(request('view') === 'needs-schedule')
                                <h4 class="card-title mb-0"><i class="mdi mdi-calendar-clock me-2 text-primary"></i>Project Scheduling</h4>
                                <p class="text-muted small mb-0">Agreement countersigned — visit dates not yet set</p>
                            @else
                                <h4 class="card-title mb-0">Inspections Management</h4>
                                <p class="text-muted small mb-0">Scheduled and paid inspections</p>
                            @endif
                        </div>
                    </div>

                    @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    @endif

                    <!-- Filter Tabs — only shown in normal (non-special-view) mode -->
                    @if(!request('view'))
                    <ul class="nav nav-pills mb-3" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link {{ request('status') == 'scheduled' ? 'active' : '' }}" 
                               href="{{ route('inspections.index', ['status' => 'scheduled']) }}">
                                Scheduled & Paid
                                <span class="badge bg-success ms-1">{{ $scheduledCount }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request('status') == 'in_progress' ? 'active' : '' }}" 
                               href="{{ route('inspections.index', ['status' => 'in_progress']) }}">
                                In Progress
                                <span class="badge bg-primary ms-1">{{ $inProgressCount }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request('status') == 'completed' ? 'active' : '' }}" 
                               href="{{ route('inspections.index', ['status' => 'completed']) }}">
                                Completed
                                <span class="badge bg-info ms-1">{{ $completedCount }}</span>
                            </a>
                        </li>
                    </ul>
                    @endif

                    <!-- Search Form -->
                    <form method="GET" action="{{ route('inspections.index') }}" class="mb-3">
                        <input type="hidden" name="status" value="{{ request('status') }}">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Search by property name, code, or city..." 
                                   value="{{ request('search') }}">
                            <button type="submit" class="btn btn-primary">
                                <i class="mdi mdi-magnify"></i> Search
                            </button>
                            @if(request('search'))
                            <a href="{{ route('inspections.index', ['status' => request('status')]) }}" class="btn btn-secondary">
                                <i class="mdi mdi-close"></i> Clear
                            </a>
                            @endif
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table id="inspectionsTable" class="table table-hover table-striped">
                            <thead>
                                <tr>
                                    <th>Property Code</th>
                                    <th>Property Name</th>
                                    <th>Location</th>
                                    <th>Owner</th>
                                    <th>Inspector</th>
                                    <th>Technician</th>
                                    <th>Project Manager</th>
                                    <th>Scheduled Date</th>
                                    <th>Payment Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($inspections as $inspection)
                                <tr>
                                    <td><code>{{ $inspection->property->property_code }}</code></td>
                                    <td>
                                        <strong>{{ $inspection->property->property_name }}</strong>
                                        @if($inspection->property->property_brand)
                                        <br><small class="text-muted">{{ $inspection->property->property_brand }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $inspection->property->city }}, {{ $inspection->property->province }}<br>
                                        <small class="text-muted">{{ $inspection->property->country }}</small>
                                    </td>
                                    <td>
                                        {{ $inspection->property->user->name }}<br>
                                        <small class="text-muted">{{ $inspection->property->user->email }}</small>
                                    </td>
                                    <td>
                                        @if($inspection->inspector)
                                        <span class="badge badge-info">
                                            <i class="mdi mdi-account-check"></i> {{ $inspection->inspector->name }}
                                        </span>
                                        @else
                                        <span class="badge badge-warning">Not assigned</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($inspection->technician)
                                        <span class="badge badge-secondary">
                                            <i class="mdi mdi-tools"></i> {{ $inspection->technician->name }}
                                        </span>
                                        @else
                                        <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $projectManager = null;
                                            if ($inspection->project && $inspection->project->manager) {
                                                $projectManager = $inspection->project->manager;
                                            } elseif ($inspection->property && $inspection->property->projectManager) {
                                                $projectManager = $inspection->property->projectManager;
                                            }
                                        @endphp
                                        @if($projectManager)
                                        <span class="badge badge-primary">
                                            <i class="mdi mdi-account-hard-hat"></i> {{ $projectManager->name }}
                                        </span>
                                        @else
                                        <span class="badge badge-warning">Not assigned</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($inspection->scheduled_date)
                                        <span class="badge badge-success">
                                            {{ \Carbon\Carbon::parse($inspection->scheduled_date)->format('M d, Y') }}<br>
                                            {{ \Carbon\Carbon::parse($inspection->scheduled_date)->format('h:i A') }}
                                        </span>
                                        @else
                                        <span class="badge badge-warning">Not Scheduled</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($inspection->inspection_fee_status === 'paid')
                                        <span class="badge badge-success">
                                            <i class="mdi mdi-check-circle"></i> Paid
                                        </span>
                                        <br><small class="text-muted">${{ number_format($inspection->inspection_fee_amount, 2) }}</small>
                                        <br><small class="text-muted">{{ $inspection->inspection_fee_paid_at->format('M d, Y') }}</small>
                                        @else
                                        <span class="badge badge-danger">{{ ucfirst($inspection->inspection_fee_status) }}</span>
                                        @endif

                                        @if($inspection->status === 'completed')
                                            <br>
                                            @if(($inspection->work_payment_status ?? 'pending') === 'paid')
                                                @php
                                                    $cadenceLabel = match($inspection->work_payment_cadence ?? '') {
                                                        'full'      => 'In Full',
                                                        'per_visit' => 'Per Visit',
                                                        'annual'    => 'Annual',
                                                        'monthly'   => 'Monthly',
                                                        default     => ucfirst($inspection->work_payment_cadence ?? '')
                                                    };
                                                @endphp
                                                <span class="badge badge-info mt-1">
                                                    <i class="mdi mdi-credit-card-check-outline"></i>
                                                    Work: Paid {{ $cadenceLabel }}
                                                </span>
                                            @else
                                                <span class="badge badge-warning mt-1 text-dark">
                                                    <i class="mdi mdi-credit-card-clock-outline"></i> Work: Pending
                                                </span>
                                            @endif
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('properties.show', $inspection->property_id) }}" 
                                               class="btn btn-sm btn-info" title="View Property">
                                                <i class="mdi mdi-eye"></i>
                                            </a>
                                            @if($inspection->status === 'completed')
                                            <a href="{{ route('inspections.show', $inspection->id) }}" 
                                               class="btn btn-sm btn-success" 
                                               title="View Full Inspection Report">
                                                <i class="mdi mdi-file-document-outline"></i> Report
                                            </a>
                                            @if(($inspection->work_payment_status ?? 'pending') !== 'paid')
                                            <a href="{{ route('inspections.work-payment', $inspection->id) }}"
                                               class="btn btn-sm btn-warning"
                                               title="Pay to Start Work">
                                                <i class="mdi mdi-credit-card"></i> Pay
                                            </a>
                                            @else
                                            <span class="btn btn-sm btn-outline-success disabled" title="Work Payment Completed">
                                                <i class="mdi mdi-check-circle"></i> Paid
                                            </span>
                                            @endif
                                            @endif
                                                @php
                                                    $resolvedPm = $inspection->project?->managed_by
                                                        ?? $inspection->property?->project_manager_id;
                                                    $teamFullyAssigned = $inspection->inspector_id && $resolvedPm;
                                                @endphp
                                            <button type="button"
                                                    class="btn btn-sm {{ $teamFullyAssigned ? 'btn-outline-primary' : 'btn-primary' }}"
                                                    onclick="assignInspector({{ $inspection->id }}, {{ $inspection->property_id }}, '{{ addslashes($inspection->property?->property_name ?? 'Property') }}', {{ $resolvedPm ?? 'null' }}, {{ $inspection->inspector_id ?? 'null' }}, {{ $inspection->technician_id ?? 'null' }})"
                                                    title="{{ $teamFullyAssigned ? 'Edit Team / Add Technician' : 'Assign Inspector, Technician & Project Manager' }}">
                                                <i class="mdi mdi-account-{{ $teamFullyAssigned ? 'edit' : 'plus' }} me-1"></i>
                                                {{ $teamFullyAssigned ? 'Edit Team' : 'Assign Team' }}
                                            </button>
                                            @if($inspection->status !== 'completed')
                                            @php
                                                $isInProgress = $inspection->status === 'in_progress';
                                                // Determine where they left off:
                                                // - bdc_annual calculated → step 2 (phar-data)
                                                // - otherwise → step 1 (create/form-cpi)
                                                if ($isInProgress && ($inspection->bdc_annual ?? 0) > 0) {
                                                    $continueUrl = route('inspections.phar-data', $inspection->id);
                                                } else {
                                                    $continueUrl = route('inspections.create', ['property_id' => $inspection->property_id]);
                                                }
                                            @endphp
                                            <a href="{{ $continueUrl }}"
                                               class="btn {{ $isInProgress ? 'btn-warning' : 'btn-success' }} fw-bold px-3"
                                               title="{{ $isInProgress ? 'Continue Inspection' : 'Start Inspection' }}">
                                                <i class="mdi {{ $isInProgress ? 'mdi-play-circle-outline' : 'mdi-clipboard-check' }} me-1"></i>
                                                {{ $isInProgress ? 'Continue Inspection' : 'Start Inspection' }}
                                            </a>
                                            @endif
                                            @if(request('view') === 'pending-etogo' && !$inspection->etogo_signed_at)
                                            <form method="POST" action="{{ route('inspections.agreement.countersign', $inspection) }}" class="d-inline"
                                                  onsubmit="return confirm('Countersign agreement for {{ addslashes($inspection->property->property_name ?? 'this property') }}? This authorises work to begin.')">
                                                @csrf
                                                <button type="submit" class="btn btn-warning fw-bold px-3" title="Countersign Agreement">
                                                    <i class="mdi mdi-draw me-1"></i> Countersign
                                                </button>
                                            </form>
                                            @endif
                                            @if(request('view') === 'needs-schedule' && $inspection->etogo_signed_at)
                                            <button type="button"
                                                    class="btn btn-success fw-bold px-3"
                                                    onclick="openWorkScheduleModal(
                                                        {{ $inspection->id }},
                                                        '{{ addslashes($inspection->property?->property_name ?? '') }}',
                                                        {{ (int)($inspection->bdc_visits_per_year ?? 1) }},
                                                        {{ json_encode($inspection->work_schedule ?? []) }}
                                                    )"
                                                    title="Set visit schedule">
                                                <i class="mdi mdi-calendar-check me-1"></i> Schedule Visits
                                            </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="10" class="text-center py-4">
                                        <i class="mdi mdi-clipboard-check-outline" style="font-size: 3rem; color: #ddd;"></i>
                                        <p class="text-muted mt-2">
                                            @if(request('view') === 'pending-etogo')
                                                No properties awaiting Etogo countersignature
                                            @elseif(request('view') === 'needs-schedule')
                                                No properties awaiting project scheduling
                                            @elseif(request('status') == 'scheduled')
                                                No scheduled inspections found
                                            @elseif(request('status') == 'in_progress')
                                                No in progress inspections found
                                            @elseif(request('status') == 'completed')
                                                No completed inspections found
                                            @else
                                                No inspections found
                                            @endif
                                        </p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    @if($inspections->hasPages())
                    <div class="mt-3">
                        {{ $inspections->links() }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Schedule Inspection Modal -->
<div class="modal fade" id="scheduleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="background-color: #ffffff !important;">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="mdi mdi-calendar-clock me-2"></i>Schedule Inspection
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="scheduleForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body" style="background-color: #ffffff !important; color: #000000 !important;">
                    <div class="form-group">
                        <label for="inspection_scheduled_at" style="color: #000000 !important;">Inspection Date & Time <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="inspection_scheduled_at" id="inspection_scheduled_at" 
                               class="form-control" required min="{{ date('Y-m-d\TH:i') }}"
                               style="background-color: #ffffff !important; color: #000000 !important;">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="mdi mdi-check me-2"></i>Schedule
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Assign Team Modal -->
<div class="modal fade" id="assignTeamModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content bg-white text-dark">
            <div class="modal-header bg-white text-dark border-bottom">
                <h5 class="modal-title">
                    <i class="mdi mdi-account-multiple me-2"></i>Assign Project Team
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="assignTeamForm" method="POST" action="">
                @csrf
                <div class="modal-body bg-white text-dark">
                    <p class="mb-3 text-muted small">Inspection ID: <span id="assignInspectionId">-</span> &bull; Property: <span id="assignPropertyName">-</span></p>

                    <div class="mb-3">
                        <label for="project_manager_id" class="form-label">Project Manager <span class="text-muted small fw-normal">(leave blank to keep current)</span></label>
                        <select name="project_manager_id" id="project_manager_id" class="form-select">
                            <option value="">-- No change --</option>
                            @foreach($projectManagers ?? [] as $pm)
                                <option value="{{ $pm->id }}">{{ $pm->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="inspector_id" class="form-label">Inspector <span class="text-muted small fw-normal">(leave blank to keep current)</span></label>
                        <select name="inspector_id" id="inspector_id" class="form-select">
                            <option value="">-- No change --</option>
                            @foreach($inspectors ?? [] as $insp)
                                <option value="{{ $insp->id }}">{{ $insp->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="technician_id" class="form-label">Technician <span class="text-muted small fw-normal">(optional)</span></label>
                        <select name="technician_id" id="technician_id" class="form-select">
                            <option value="">-- None / Remove --</option>
                            @foreach($technicians ?? [] as $tech)
                                <option value="{{ $tech->id }}">{{ $tech->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-white border-top">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="mdi mdi-check me-1"></i> Save Assignment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Work Schedule Modal -->
<div class="modal fade" id="workScheduleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-white text-dark">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="mdi mdi-calendar-check me-2"></i>Set Visit Schedule
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="workScheduleForm" method="POST" action="">
                @csrf
                <div class="modal-body bg-white text-dark">
                    <div class="alert alert-info py-2 mb-3" id="scheduleInfo">
                        <strong id="schedulePropName"></strong> &mdash;
                        <span id="scheduleVisitCount"></span> visit(s) required per year
                    </div>

                    <div class="row align-items-end mb-3">
                        <div class="col-md-7">
                            <label class="form-label fw-semibold">First Visit Date <span class="text-danger">*</span></label>
                            <input type="date" id="firstVisitDate" class="form-control"
                                   min="{{ now()->toDateString() }}">
                            <div class="form-text">Remaining dates will auto-fill, spaced evenly across the year. Weekends are skipped.</div>
                        </div>
                        <div class="col-md-5">
                            <button type="button" class="btn btn-outline-primary w-100" onclick="generateVisitDates()">
                                <i class="mdi mdi-refresh me-1"></i> Auto-fill Remaining Dates
                            </button>
                        </div>
                    </div>

                    <div id="visitDatesList" class="row g-2"></div>
                </div>
                <div class="modal-footer bg-white border-top">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="mdi mdi-calendar-check me-1"></i> Save Schedule
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let _scheduleVisits = 1;

function nextWeekday(date) {
    let d = new Date(date);
    // Mon–Sat are working days; only skip Sunday (getDay() === 0)
    while (d.getDay() === 0) {
        d.setDate(d.getDate() + 1);
    }
    return d;
}

function toDateInputVal(d) {
    return d.toISOString().split('T')[0];
}

function generateVisitDates() {
    const firstVal = document.getElementById('firstVisitDate').value;
    if (!firstVal) {
        alert('Please pick a first visit date first.');
        return;
    }
    const total = _scheduleVisits;
    let dates = [];
    let current = nextWeekday(new Date(firstVal + 'T12:00:00'));
    dates.push(toDateInputVal(current));
    for (let i = 1; i < total; i++) {
        let next = new Date(current);
        // Advance 1 calendar day then skip any Sunday to get next working day
        next.setDate(next.getDate() + 1);
        next = nextWeekday(next);
        dates.push(toDateInputVal(next));
        current = next;
    }
    renderDateFields(dates);
}

function renderDateFields(dates) {
    const container = document.getElementById('visitDatesList');
    container.innerHTML = '';
    dates.forEach(function(d, i) {
        const col = document.createElement('div');
        col.className = 'col-md-4 col-sm-6';
        col.innerHTML = '<label class="form-label small fw-semibold text-muted">Visit ' + (i + 1) + '</label>' +
            '<input type="date" name="visit_dates[]" class="form-control" value="' + d + '" required min="{{ now()->toDateString() }}">';
        container.appendChild(col);
    });
}

function openWorkScheduleModal(inspectionId, propertyName, totalVisits, existingSchedule) {
    _scheduleVisits = totalVisits || 1;
    const form = document.getElementById('workScheduleForm');
    form.action = '/inspections/' + inspectionId + '/work-schedule';
    document.getElementById('schedulePropName').textContent = propertyName;
    document.getElementById('scheduleVisitCount').textContent = totalVisits;
    document.getElementById('firstVisitDate').value = '';

    // Pre-fill from existing schedule if any
    if (existingSchedule && existingSchedule.length > 0) {
        const existing = existingSchedule.map(function(e) { return e.date || e; });
        document.getElementById('firstVisitDate').value = existing[0];
        renderDateFields(existing);
    } else {
        document.getElementById('visitDatesList').innerHTML = '<p class="text-muted small col-12">Pick a first visit date above and click <strong>Auto-fill</strong>.</p>';
    }

    // When first date changes, auto-regenerate
    const firstInput = document.getElementById('firstVisitDate');
    firstInput.oninput = function() {
        if (this.value) generateVisitDates();
    };

    new bootstrap.Modal(document.getElementById('workScheduleModal')).show();
}

function assignInspector(inspectionId, propertyId, propertyName, projectManagerId, inspectorId, technicianId) {
    const form = document.getElementById('assignTeamForm');
    const inspectionIdNode = document.getElementById('assignInspectionId');
    const propertyNameNode = document.getElementById('assignPropertyName');
    const pmSelect = document.getElementById('project_manager_id');
    const inspectorSelect = document.getElementById('inspector_id');
    const techSelect = document.getElementById('technician_id');

    form.action = "{{ route('properties.assign', ['property' => '__PROPERTY_ID__']) }}".replace('__PROPERTY_ID__', propertyId);
    inspectionIdNode.textContent = inspectionId;
    propertyNameNode.textContent = propertyName || 'Property';
    pmSelect.value = (projectManagerId && projectManagerId !== 'null') ? String(projectManagerId) : '';
    inspectorSelect.value = (inspectorId && inspectorId !== 'null') ? String(inspectorId) : '';
    techSelect.value = (technicianId && technicianId !== 'null') ? String(technicianId) : '';

    const modal = new bootstrap.Modal(document.getElementById('assignTeamModal'));
    modal.show();
}

$(document).ready(function() {
    @if($inspections->count() > 0)
    $('#inspectionsTable').DataTable({
        "pageLength": 15,
        "lengthMenu": [[15, 25, 50, -1], [15, 25, 50, "All"]],
        "order": [[6, "asc"]],
        "language": {
            "search": "Search:",
            "lengthMenu": "Show _MENU_ inspections",
            "info": "Showing _START_ to _END_ of _TOTAL_ inspections"
        },
        "columnDefs": [
            { "orderable": false, "targets": [9] }  // Actions column is now index 9
        ]
    });
    @endif
});
</script>
@endpush
