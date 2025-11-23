@extends('admin.layout')

@section('title', 'Dashboard')

@section('header', 'Dashboard')

@section('breadcrumbs')
<li class="breadcrumb-item active" aria-current="page">Dashboard</li>
@endsection

@section('content')
<div class="row">
    {{-- Stats Cards --}}
    <div class="col-md-3 stretch-card grid-margin">
        <div class="card bg-gradient-danger text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="font-weight-normal mb-2">Properties</h6>
                        <h2 class="mb-0">{{ $propertiesCount ?? 0 }}</h2>
                        <p class="text-white-50 mb-0 mt-2 small">Registered Properties</p>
                    </div>
                    <div>
                        <i class="mdi mdi-home-modern mdi-48px opacity-50"></i>
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
                        <h6 class="font-weight-normal mb-2">Inspections</h6>
                        <h2 class="mb-0">{{ $inspectionsCount ?? 0 }}</h2>
                        <p class="text-white-50 mb-0 mt-2 small">Total Inspections</p>
                    </div>
                    <div>
                        <i class="mdi mdi-clipboard-check mdi-48px opacity-50"></i>
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
                        <h6 class="font-weight-normal mb-2">Projects</h6>
                        <h2 class="mb-0">{{ $projectsCount ?? 0 }}</h2>
                        <p class="text-white-50 mb-0 mt-2 small">Active Projects</p>
                    </div>
                    <div>
                        <i class="mdi mdi-briefcase mdi-48px opacity-50"></i>
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
                        <h6 class="font-weight-normal mb-2">Invoices</h6>
                        <h2 class="mb-0">{{ $invoicesCount ?? 0 }}</h2>
                        <p class="text-white-50 mb-0 mt-2 small">Total Invoices</p>
                    </div>
                    <div>
                        <i class="mdi mdi-file-document mdi-48px opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    {{-- System Overview --}}
    <div class="col-md-6 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-3">System Overview</h4>
                <div class="system-stats">
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                        <div class="d-flex align-items-center">
                            <i class="mdi mdi-clock-alert text-warning mdi-24px me-2"></i>
                            <span>Pending Approvals</span>
                        </div>
                        <span class="badge badge-warning">{{ \App\Models\Property::where('status', 'pending_approval')->count() }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                        <div class="d-flex align-items-center">
                            <i class="mdi mdi-account-multiple text-info mdi-24px me-2"></i>
                            <span>Total Users</span>
                        </div>
                        <span class="badge badge-info">{{ \App\Models\User::count() }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                        <div class="d-flex align-items-center">
                            <i class="mdi mdi-clipboard-check text-primary mdi-24px me-2"></i>
                            <span>Active Inspections</span>
                        </div>
                        <span class="badge badge-primary">{{ \App\Models\Inspection::where('status', 'in_progress')->count() }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <i class="mdi mdi-file-document-alert text-danger mdi-24px me-2"></i>
                            <span>Unpaid Invoices</span>
                        </div>
                        <span class="badge badge-danger">{{ \App\Models\Invoice::where('status', 'pending')->count() }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="col-md-6 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-3">Quick Actions</h4>
                <div class="d-grid gap-2">
                    @can('create properties')
                    <a href="{{ route('properties.create') }}" class="btn btn-outline-primary btn-icon-text">
                        <i class="mdi mdi-home-plus btn-icon-prepend"></i> Add New Property
                    </a>
                    @endcan
                    
                    @can('create inspections')
                    <a href="{{ route('inspections.create') }}" class="btn btn-outline-info btn-icon-text">
                        <i class="mdi mdi-clipboard-check btn-icon-prepend"></i> Schedule Inspection
                    </a>
                    @endcan
                    
                    @can('create projects')
                    <a href="{{ route('projects.create') }}" class="btn btn-outline-warning btn-icon-text">
                        <i class="mdi mdi-briefcase btn-icon-prepend"></i> Create Project
                    </a>
                    @endcan
                    
                    <a href="{{ route('invoices.index') }}" class="btn btn-outline-success btn-icon-text">
                        <i class="mdi mdi-file-document btn-icon-prepend"></i> View Invoices
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Recent Activity --}}
<div class="row">
    <div class="col-md-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-3">Recent Activity</h4>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Activity</th>
                                <th>Property</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentActivities ?? [] as $activity)
                            <tr>
                                <td>{{ $activity->created_at->format('M d, Y') }}</td>
                                <td>{{ $activity->description }}</td>
                                <td>{{ $activity->property->name ?? 'N/A' }}</td>
                                <td>
                                    <span class="badge badge-{{ $activity->status_color }}">
                                        {{ $activity->status }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    No recent activity. Start by adding a property!
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Ensure admin dashboard content is visible */
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

/* Gradient cards should maintain their colors */
.bg-gradient-danger {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%) !important;
}

.bg-gradient-info {
    background: linear-gradient(135deg, #17a2b8 0%, #138496 100%) !important;
}

.bg-gradient-success {
    background: linear-gradient(135deg, #28a745 0%, #218838 100%) !important;
}

.bg-gradient-warning {
    background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%) !important;
}

/* Button styling */
body.light-theme .btn {
    color: #ffffff !important;
}

body.light-theme .btn-outline-primary,
body.light-theme .btn-outline-info,
body.light-theme .btn-outline-success,
body.light-theme .btn-outline-warning {
    color: inherit !important;
}

body.light-theme .btn-outline-primary:hover {
    color: #ffffff !important;
}

/* Badge styling */
body.light-theme .badge {
    color: #ffffff !important;
}
</style>
@endsection
