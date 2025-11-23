@extends('admin.layout')

@section('title', 'Project Manager Dashboard')

@section('header', 'Project Manager Dashboard')

@section('breadcrumbs')
<li class="breadcrumb-item active" aria-current="page">Dashboard</li>
@endsection

@section('content')
<div class="row">
    {{-- Stats Cards --}}
    <div class="col-md-3 stretch-card grid-margin">
        <div class="card bg-gradient-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="font-weight-normal mb-2">Assigned Properties</h6>
                        <h2 class="mb-0">{{ $assignedCount }}</h2>
                        <p class="text-white-50 mb-0 mt-2 small">Under Management</p>
                    </div>
                    <div>
                        <i class="mdi mdi-briefcase mdi-48px opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 stretch-card grid-margin">
        <div class="card bg-gradient-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="font-weight-normal mb-2">Scheduled</h6>
                        <h2 class="mb-0">{{ $scheduledCount }}</h2>
                        <p class="text-white-50 mb-0 mt-2 small">Date Set</p>
                    </div>
                    <div>
                        <i class="mdi mdi-calendar-check mdi-48px opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 stretch-card grid-margin">
        <div class="card bg-gradient-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="font-weight-normal mb-2">Unscheduled</h6>
                        <h2 class="mb-0">{{ $unscheduledCount }}</h2>
                        <p class="text-white-50 mb-0 mt-2 small">Needs Scheduling</p>
                    </div>
                    <div>
                        <i class="mdi mdi-calendar-alert mdi-48px opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 stretch-card grid-margin">
        <div class="card bg-gradient-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="font-weight-normal mb-2">Active Projects</h6>
                        <h2 class="mb-0">{{ $activeProjectsCount }}</h2>
                        <p class="text-white-50 mb-0 mt-2 small">In Progress</p>
                    </div>
                    <div>
                        <i class="mdi mdi-chart-line mdi-48px opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    {{-- Upcoming Inspections --}}
    <div class="col-md-8 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="card-title mb-0">Upcoming Inspections</h4>
                    <a href="{{ route('inspections.index', ['status' => 'scheduled']) }}" class="btn btn-sm btn-primary">
                        View All
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>Property</th>
                                <th>Location</th>
                                <th>Inspector</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($upcomingInspections as $property)
                            <tr>
                                <td>
                                    <strong>{{ $property->inspection_scheduled_at->format('M d, Y') }}</strong><br>
                                    <small class="text-muted">{{ $property->inspection_scheduled_at->format('h:i A') }}</small>
                                </td>
                                <td>
                                    <strong>{{ $property->property_name }}</strong><br>
                                    <small class="text-muted"><code>{{ $property->property_code }}</code></small>
                                </td>
                                <td>{{ $property->city }}, {{ $property->province }}</td>
                                <td>
                                    @if($property->inspector)
                                    <span class="badge badge-info">
                                        <i class="mdi mdi-account"></i> {{ $property->inspector->name }}
                                    </span>
                                    @else
                                    <span class="text-muted">Not assigned</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('properties.show', $property->id) }}" 
                                       class="btn btn-sm btn-info">
                                        <i class="mdi mdi-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <i class="mdi mdi-calendar-blank mdi-36px d-block mb-2"></i>
                                    No upcoming inspections scheduled
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="col-md-4 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-3">Quick Actions</h4>
                <div class="d-grid gap-2">
                    <a href="{{ route('inspections.index') }}" class="btn btn-outline-primary btn-icon-text">
                        <i class="mdi mdi-clipboard-list btn-icon-prepend"></i> View All Properties
                    </a>
                    <a href="{{ route('inspections.index', ['status' => 'unscheduled']) }}" class="btn btn-outline-warning btn-icon-text">
                        <i class="mdi mdi-calendar-alert btn-icon-prepend"></i> Need Scheduling
                    </a>
                    <a href="{{ route('inspections.index', ['status' => 'scheduled']) }}" class="btn btn-outline-success btn-icon-text">
                        <i class="mdi mdi-calendar-check btn-icon-prepend"></i> View Schedule
                    </a>
                </div>

                <hr class="my-4">

                <h5 class="mb-3">Property Overview</h5>
                <div class="property-stats">
                    <div class="d-flex justify-content-between mb-3 pb-3 border-bottom">
                        <span>Total Properties</span>
                        <strong>{{ $assignedCount }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-3 pb-3 border-bottom">
                        <span>Scheduled Inspections</span>
                        <strong class="text-success">{{ $scheduledCount }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-3 pb-3 border-bottom">
                        <span>Unscheduled</span>
                        <strong class="text-warning">{{ $unscheduledCount }}</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Active Projects</span>
                        <strong class="text-info">{{ $activeProjectsCount }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- All Assigned Properties --}}
<div class="row">
    <div class="col-md-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="card-title mb-0">My Managed Properties</h4>
                    <span class="badge badge-primary">{{ $assignedProperties->count() }} Properties</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover" id="managedPropertiesTable">
                        <thead>
                            <tr>
                                <th>Property Code</th>
                                <th>Property Name</th>
                                <th>Location</th>
                                <th>Owner</th>
                                <th>Inspector</th>
                                <th>Assigned Date</th>
                                <th>Inspection Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($assignedProperties as $property)
                            <tr>
                                <td><code>{{ $property->property_code }}</code></td>
                                <td>
                                    <strong>{{ $property->property_name }}</strong>
                                    @if($property->property_brand)
                                    <br><small class="text-muted">{{ $property->property_brand }}</small>
                                    @endif
                                </td>
                                <td>{{ $property->city }}, {{ $property->province }}</td>
                                <td>
                                    {{ $property->owner_first_name }}<br>
                                    <small class="text-muted">{{ $property->owner_phone }}</small>
                                </td>
                                <td>
                                    @if($property->inspector)
                                    <span class="badge badge-info">
                                        <i class="mdi mdi-account"></i> {{ $property->inspector->name }}
                                    </span>
                                    @else
                                    <span class="text-muted">Not assigned</span>
                                    @endif
                                </td>
                                <td>{{ $property->assigned_at->format('M d, Y') }}</td>
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
                                        <a href="{{ route('properties.edit', $property->id) }}" 
                                           class="btn btn-sm btn-warning" title="Edit Property">
                                            <i class="mdi mdi-pencil"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Ensure PM dashboard content is visible */
body.light-theme .content-wrapper {
    background-color: #f4f5f7 !important;
}

body.light-theme .card {
    background-color: #ffffff !important;
    color: #212529 !important;
    border: 1px solid #e3e6f0;
}

body.light-theme .card-title {
    color: #212529 !important;
}

body.light-theme .card-body {
    color: #212529 !important;
}

body.light-theme .table {
    color: #212529 !important;
}

body.light-theme .table thead th {
    color: #212529 !important;
    border-color: #dee2e6 !important;
}

body.light-theme .text-muted {
    color: #6c757d !important;
}

/* Gradient cards */
.bg-gradient-primary {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%) !important;
}

.bg-gradient-success {
    background: linear-gradient(135deg, #28a745 0%, #218838 100%) !important;
}

.bg-gradient-warning {
    background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%) !important;
}

.bg-gradient-info {
    background: linear-gradient(135deg, #17a2b8 0%, #138496 100%) !important;
}
</style>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    @if($assignedProperties->count() > 0)
    $('#managedPropertiesTable').DataTable({
        "pageLength": 10,
        "order": [[6, "asc"]],
        "columnDefs": [
            { "orderable": false, "targets": [7] }
        ]
    });
    @endif
});
</script>
@endpush
