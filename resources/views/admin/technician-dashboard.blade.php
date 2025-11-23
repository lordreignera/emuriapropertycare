@extends('admin.layout')

@section('title', 'Technician Dashboard')

@section('header', 'Technician Dashboard')

@section('breadcrumbs')
<li class="breadcrumb-item active" aria-current="page">Dashboard</li>
@endsection

@section('content')
<div class="row">
    {{-- Stats Cards --}}
    <div class="col-md-3 stretch-card grid-margin">
        <div class="card bg-gradient-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="font-weight-normal mb-2">Active Projects</h6>
                        <h2 class="mb-0">{{ $activeProjectsCount }}</h2>
                        <p class="text-white-50 mb-0 mt-2 small">Currently Working</p>
                    </div>
                    <div>
                        <i class="mdi mdi-wrench mdi-48px opacity-50"></i>
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
                        <h6 class="font-weight-normal mb-2">Pending Projects</h6>
                        <h2 class="mb-0">{{ $pendingProjectsCount }}</h2>
                        <p class="text-white-50 mb-0 mt-2 small">Awaiting Start</p>
                    </div>
                    <div>
                        <i class="mdi mdi-clock-alert mdi-48px opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 stretch-card grid-margin">
        <div class="card bg-gradient-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="font-weight-normal mb-2">Completed</h6>
                        <h2 class="mb-0">{{ $completedProjectsCount }}</h2>
                        <p class="text-white-50 mb-0 mt-2 small">Total Finished</p>
                    </div>
                    <div>
                        <i class="mdi mdi-check-circle mdi-48px opacity-50"></i>
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
                        <h6 class="font-weight-normal mb-2">Today's Work Logs</h6>
                        <h2 class="mb-0">{{ $todayWorkLogs }}</h2>
                        <p class="text-white-50 mb-0 mt-2 small">Logged Today</p>
                    </div>
                    <div>
                        <i class="mdi mdi-clipboard-text mdi-48px opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    {{-- Upcoming Projects --}}
    <div class="col-md-8 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="card-title mb-0">Upcoming Projects</h4>
                    <a href="{{ route('projects.index') }}" class="btn btn-sm btn-primary">
                        View All Projects
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Start Date</th>
                                <th>Project Name</th>
                                <th>Property</th>
                                <th>Client</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($upcomingProjects as $project)
                            <tr>
                                <td>
                                    <strong>{{ $project->start_date ? \Carbon\Carbon::parse($project->start_date)->format('M d, Y') : 'Not Set' }}</strong>
                                </td>
                                <td>
                                    <strong>{{ $project->name }}</strong><br>
                                    <small class="text-muted">{{ Str::limit($project->description, 40) }}</small>
                                </td>
                                <td>
                                    @if($project->property)
                                    {{ $project->property->property_name }}<br>
                                    <small class="text-muted"><code>{{ $project->property->property_code }}</code></small>
                                    @else
                                    <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    @if($project->property && $project->property->user)
                                    {{ $project->property->user->name }}<br>
                                    <small class="text-muted">{{ $project->property->owner_phone }}</small>
                                    @else
                                    <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-warning">{{ ucfirst($project->status) }}</span>
                                </td>
                                <td>
                                    <a href="{{ route('projects.show', $project->id) }}" 
                                       class="btn btn-sm btn-info">
                                        <i class="mdi mdi-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <i class="mdi mdi-calendar-blank mdi-36px d-block mb-2"></i>
                                    No upcoming projects
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Actions & Stats --}}
    <div class="col-md-4 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-3">Quick Actions</h4>
                <div class="d-grid gap-2">
                    <a href="{{ route('projects.index') }}" class="btn btn-outline-success btn-icon-text">
                        <i class="mdi mdi-briefcase btn-icon-prepend"></i> View My Projects
                    </a>
                    <a href="{{ route('work-logs.create') }}" class="btn btn-outline-info btn-icon-text">
                        <i class="mdi mdi-clipboard-text btn-icon-prepend"></i> Log Work
                    </a>
                    <a href="{{ route('work-logs.index') }}" class="btn btn-outline-primary btn-icon-text">
                        <i class="mdi mdi-history btn-icon-prepend"></i> View Work History
                    </a>
                </div>

                <hr class="my-4">

                <h5 class="mb-3">Project Overview</h5>
                <div class="project-stats">
                    <div class="d-flex justify-content-between mb-3 pb-3 border-bottom">
                        <span>Active Projects</span>
                        <strong class="text-success">{{ $activeProjectsCount }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-3 pb-3 border-bottom">
                        <span>Pending Start</span>
                        <strong class="text-warning">{{ $pendingProjectsCount }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-3 pb-3 border-bottom">
                        <span>On Hold</span>
                        <strong class="text-danger">{{ $onHoldProjectsCount }}</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Completed</span>
                        <strong class="text-primary">{{ $completedProjectsCount }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- All Assigned Projects --}}
<div class="row">
    <div class="col-md-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="card-title mb-0">My Assigned Projects</h4>
                    <span class="badge badge-success">{{ $assignedProjects->count() }} Projects</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover" id="assignedProjectsTable">
                        <thead>
                            <tr>
                                <th>Project Name</th>
                                <th>Property</th>
                                <th>Client</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Status</th>
                                <th>Progress</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($assignedProjects as $project)
                            <tr>
                                <td>
                                    <strong>{{ $project->name }}</strong><br>
                                    <small class="text-muted">{{ Str::limit($project->description, 40) }}</small>
                                </td>
                                <td>
                                    @if($project->property)
                                    {{ $project->property->property_name }}<br>
                                    <small class="text-muted"><code>{{ $project->property->property_code }}</code></small>
                                    @else
                                    <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    @if($project->property && $project->property->user)
                                    {{ $project->property->user->name }}<br>
                                    <small class="text-muted">{{ $project->property->owner_phone }}</small>
                                    @else
                                    <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>{{ $project->start_date ? \Carbon\Carbon::parse($project->start_date)->format('M d, Y') : 'Not Set' }}</td>
                                <td>{{ $project->end_date ? \Carbon\Carbon::parse($project->end_date)->format('M d, Y') : 'Not Set' }}</td>
                                <td>
                                    @if($project->status == 'active')
                                    <span class="badge badge-success">Active</span>
                                    @elseif($project->status == 'completed')
                                    <span class="badge badge-primary">Completed</span>
                                    @elseif($project->status == 'pending')
                                    <span class="badge badge-warning">Pending</span>
                                    @elseif($project->status == 'on_hold')
                                    <span class="badge badge-danger">On Hold</span>
                                    @else
                                    <span class="badge badge-secondary">{{ ucfirst($project->status) }}</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar bg-success" role="progressbar" 
                                             style="width: {{ $project->progress ?? 0 }}%"
                                             aria-valuenow="{{ $project->progress ?? 0 }}" 
                                             aria-valuemin="0" aria-valuemax="100">
                                            {{ $project->progress ?? 0 }}%
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('projects.show', $project->id) }}" 
                                           class="btn btn-sm btn-info" title="View Project">
                                            <i class="mdi mdi-eye"></i>
                                        </a>
                                        <a href="{{ route('work-logs.create', ['project_id' => $project->id]) }}" 
                                           class="btn btn-sm btn-success" title="Log Work">
                                            <i class="mdi mdi-clipboard-plus"></i>
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
/* Ensure technician dashboard content is visible */
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
.bg-gradient-success {
    background: linear-gradient(135deg, #28a745 0%, #218838 100%) !important;
}

.bg-gradient-warning {
    background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%) !important;
}

.bg-gradient-primary {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%) !important;
}

.bg-gradient-info {
    background: linear-gradient(135deg, #17a2b8 0%, #138496 100%) !important;
}
</style>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    @if($assignedProjects->count() > 0)
    $('#assignedProjectsTable').DataTable({
        "pageLength": 10,
        "order": [[3, "asc"]],
        "columnDefs": [
            { "orderable": false, "targets": [7] }
        ]
    });
    @endif
});
</script>
@endpush
