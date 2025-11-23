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
                            <h4 class="card-title mb-0">Property Inspections</h4>
                            <p class="text-muted small mb-0">Properties awaiting inspection</p>
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
                            <a class="nav-link {{ !request('status') ? 'active' : '' }}" 
                               href="{{ route('inspections.index') }}">
                                All Pending 
                                <span class="badge bg-primary ms-1">{{ \App\Models\Property::where('status', 'awaiting_inspection')->count() }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request('status') == 'scheduled' ? 'active' : '' }}" 
                               href="{{ route('inspections.index', ['status' => 'scheduled']) }}">
                                Scheduled
                                <span class="badge bg-success ms-1">{{ \App\Models\Property::where('status', 'awaiting_inspection')->whereNotNull('inspection_scheduled_at')->count() }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request('status') == 'unscheduled' ? 'active' : '' }}" 
                               href="{{ route('inspections.index', ['status' => 'unscheduled']) }}">
                                Unscheduled
                                <span class="badge bg-warning ms-1">{{ \App\Models\Property::where('status', 'awaiting_inspection')->whereNull('inspection_scheduled_at')->count() }}</span>
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
                                    <th>Assigned Date</th>
                                    <th>Inspection Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($properties as $property)
                                <tr>
                                    <td><code>{{ $property->property_code }}</code></td>
                                    <td>
                                        <strong>{{ $property->property_name }}</strong>
                                        @if($property->property_brand)
                                        <br><small class="text-muted">{{ $property->property_brand }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $property->city }}, {{ $property->province }}<br>
                                        <small class="text-muted">{{ $property->country }}</small>
                                    </td>
                                    <td>
                                        {{ $property->owner_first_name }}<br>
                                        <small class="text-muted">{{ $property->owner_phone }}</small>
                                    </td>
                                    <td>
                                        @if($property->inspector)
                                        <span class="badge badge-info">
                                            <i class="mdi mdi-account-check"></i> {{ $property->inspector->name }}
                                        </span>
                                        @else
                                        <span class="text-muted">Not assigned</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($property->projectManager)
                                        <span class="badge badge-primary">
                                            <i class="mdi mdi-account-hard-hat"></i> {{ $property->projectManager->name }}
                                        </span>
                                        @else
                                        <span class="text-muted">Not assigned</span>
                                        @endif
                                    </td>
                                    <td>{{ $property->assigned_at?->format('M d, Y') ?? 'N/A' }}</td>
                                    <td>
                                        @if($property->inspection_scheduled_at)
                                        <span class="badge badge-success">
                                            {{ $property->inspection_scheduled_at->format('M d, Y') }}<br>
                                            {{ $property->inspection_scheduled_at->format('h:i A') }}
                                        </span>
                                        @else
                                        <span class="badge badge-warning">Not Scheduled</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('properties.show', $property->id) }}" 
                                               class="btn btn-sm btn-info" title="View Property">
                                                <i class="mdi mdi-eye"></i>
                                            </a>
                                            @if(!$property->inspection_scheduled_at)
                                            <button type="button" class="btn btn-sm btn-primary" 
                                                    onclick="scheduleInspection({{ $property->id }})" 
                                                    title="Schedule Inspection">
                                                <i class="mdi mdi-calendar-clock"></i>
                                            </button>
                                            @endif
                                            <a href="{{ route('inspections.create', ['property_id' => $property->id]) }}" 
                                               class="btn btn-sm btn-success" 
                                               title="Start Inspection">
                                                <i class="mdi mdi-clipboard-check"></i>
                                            </a>
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
                                            @elseif(request('status') == 'unscheduled')
                                                No unscheduled inspections found
                                            @else
                                                No properties awaiting inspection
                                            @endif
                                        </p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    @if($properties->hasPages())
                    <div class="mt-3">
                        {{ $properties->links() }}
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
@endsection

@push('scripts')
<script>
function scheduleInspection(propertyId) {
    const form = document.getElementById('scheduleForm');
    form.action = '/properties/' + propertyId;
    const modal = new bootstrap.Modal(document.getElementById('scheduleModal'));
    modal.show();
}

$(document).ready(function() {
    @if($properties->count() > 0)
    $('#inspectionsTable').DataTable({
        "pageLength": 15,
        "lengthMenu": [[15, 25, 50, -1], [15, 25, 50, "All"]],
        "order": [[7, "asc"]],
        "language": {
            "search": "Search:",
            "lengthMenu": "Show _MENU_ properties",
            "info": "Showing _START_ to _END_ of _TOTAL_ properties"
        },
        "columnDefs": [
            { "orderable": false, "targets": [8] }
        ]
    });
    @endif
});
</script>
@endpush
