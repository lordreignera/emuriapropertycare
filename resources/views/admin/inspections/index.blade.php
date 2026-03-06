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
                            <h4 class="card-title mb-0">Inspections Management</h4>
                            <p class="text-muted small mb-0">Scheduled and paid inspections</p>
                        </div>
                    </div>

                    @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    @endif

                    <!-- Filter Tabs -->
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
                                                <span class="badge badge-info mt-1">
                                                    <i class="mdi mdi-credit-card-check-outline"></i>
                                                    Work: Paid {{ ucfirst($inspection->work_payment_cadence ?? 'monthly') }}
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
                                                @if(!$inspection->inspector_id || !$inspection->property?->project_manager_id)
                                            <button type="button" class="btn btn-sm btn-primary" 
                                                    onclick="assignInspector({{ $inspection->id }}, {{ $inspection->property_id }}, '{{ addslashes($inspection->property?->property_name ?? 'Property') }}', {{ $inspection->property?->project_manager_id ?? 'null' }}, {{ $inspection->inspector_id ?? 'null' }})" 
                                                    title="Assign Inspector">
                                                <i class="mdi mdi-account-plus"></i>
                                            </button>
                                            @endif
                                            @if($inspection->status !== 'completed')
                                            <a href="{{ route('inspections.create', ['property_id' => $inspection->property_id]) }}" 
                                               class="btn btn-success fw-bold px-3" 
                                               title="Start Inspection">
                                                <i class="mdi mdi-clipboard-check me-1"></i> Start Inspection
                                            </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center py-4">
                                        <i class="mdi mdi-clipboard-check-outline" style="font-size: 3rem; color: #ddd;"></i>
                                        <p class="text-muted mt-2">
                                            @if(request('status') == 'scheduled')
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
                    <i class="mdi mdi-account-multiple me-2"></i>Assign Inspector & Project Manager
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="assignTeamForm" method="POST" action="">
                @csrf
                <div class="modal-body bg-white text-dark">
                    <p class="mb-3 text-muted small">Inspection ID: <span id="assignInspectionId">-</span> • Property: <span id="assignPropertyName">-</span></p>

                    <div class="mb-3">
                        <label for="project_manager_id" class="form-label">Project Manager <span class="text-danger">*</span></label>
                        <select name="project_manager_id" id="project_manager_id" class="form-select" required>
                            <option value="">-- Select Project Manager --</option>
                            @foreach($projectManagers ?? [] as $projectManager)
                                <option value="{{ $projectManager->id }}">{{ $projectManager->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="inspector_id" class="form-label">Inspector <span class="text-danger">*</span></label>
                        <select name="inspector_id" id="inspector_id" class="form-select" required>
                            <option value="">-- Select Inspector --</option>
                            @foreach($inspectors ?? [] as $inspector)
                                <option value="{{ $inspector->id }}">{{ $inspector->name }}</option>
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
@endsection

@push('scripts')
<script>
function assignInspector(inspectionId, propertyId, propertyName, projectManagerId, inspectorId) {
    const form = document.getElementById('assignTeamForm');
    const inspectionIdNode = document.getElementById('assignInspectionId');
    const propertyNameNode = document.getElementById('assignPropertyName');
    const pmSelect = document.getElementById('project_manager_id');
    const inspectorSelect = document.getElementById('inspector_id');

    form.action = "{{ route('properties.assign', ['property' => '__PROPERTY_ID__']) }}".replace('__PROPERTY_ID__', propertyId);
    inspectionIdNode.textContent = inspectionId;
    propertyNameNode.textContent = propertyName || 'Property';
    pmSelect.value = (projectManagerId && projectManagerId !== 'null') ? String(projectManagerId) : '';
    inspectorSelect.value = (inspectorId && inspectorId !== 'null') ? String(inspectorId) : '';

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
            { "orderable": false, "targets": [8] }
        ]
    });
    @endif
});
</script>
@endpush
