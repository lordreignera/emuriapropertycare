@extends('admin.layout')

@section('title', 'Welcome')

@section('header')
Welcome back, {{ auth()->user()->name }}
@endsection

@section('breadcrumbs')
<li class="breadcrumb-item active" aria-current="page">{{ now()->format('l, M d, Y') }}</li>
@endsection

@section('content')

@if(auth()->user()->hasRole('Store Manager'))
{{-- ═══════════════════════════════════════════════════════════
     STORE MANAGER DASHBOARD
════════════════════════════════════════════════════════════════ --}}
<div class="row mb-3">
    <div class="col-12">
        <div class="alert alert-primary mb-0" role="alert" style="border-left:4px solid #0d6efd;">
            <strong>Tools overview.</strong> Manage tool stock, assignments and returns from here.
        </div>
    </div>
</div>

{{-- KPI Cards --}}
<div class="row">
    <div class="col-md-3 stretch-card grid-margin">
        <div class="card bg-gradient-danger text-white emuria-dashboard-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="font-weight-normal mb-2">Total Tools</h6>
                        <h2 class="mb-0">{{ $totalTools ?? 0 }}</h2>
                        <p class="text-white-50 mb-0 mt-2 small">Active tool records</p>
                    </div>
                    <i class="mdi mdi-toolbox mdi-48px opacity-50"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3 stretch-card grid-margin">
        <div class="card bg-gradient-info text-white emuria-dashboard-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="font-weight-normal mb-2">Tools In Use</h6>
                        <h2 class="mb-0">{{ $toolsInUse ?? 0 }}</h2>
                        <p class="text-white-50 mb-0 mt-2 small">Units currently deployed</p>
                    </div>
                    <i class="mdi mdi-wrench mdi-48px opacity-50"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3 stretch-card grid-margin">
        <div class="card bg-gradient-success text-white emuria-dashboard-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="font-weight-normal mb-2">Tools Owned</h6>
                        <h2 class="mb-0">{{ $toolsOwned ?? 0 }}</h2>
                        <p class="text-white-50 mb-0 mt-2 small">Company-owned tools</p>
                    </div>
                    <i class="mdi mdi-check-decagram mdi-48px opacity-50"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3 stretch-card grid-margin">
        <div class="card bg-gradient-warning text-white emuria-dashboard-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="font-weight-normal mb-2">Tools Hired</h6>
                        <h2 class="mb-0">{{ $toolsHired ?? 0 }}</h2>
                        <p class="text-white-50 mb-0 mt-2 small">Hired / rented tools</p>
                    </div>
                    <i class="mdi mdi-handshake mdi-48px opacity-50"></i>
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
                    <a href="{{ route('tool-assignments.index') }}" class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom text-decoration-none text-dark">
                        <div class="d-flex align-items-center">
                            <i class="mdi mdi-clock-alert text-warning mdi-24px me-2"></i>
                            <span>Pending Tool Assignment</span>
                        </div>
                        <span class="badge badge-warning">{{ $pendingToolAssignment ?? 0 }}</span>
                    </a>
                    <a href="{{ route('admin.tool-settings.index') }}" class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom text-decoration-none text-dark">
                        <div class="d-flex align-items-center">
                            <i class="mdi mdi-toolbox text-primary mdi-24px me-2"></i>
                            <span>Total Active Tools</span>
                        </div>
                        <span class="badge badge-primary">{{ $totalTools ?? 0 }}</span>
                    </a>
                    <a href="{{ route('admin.tool-settings.index') }}?availability_status=available" class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom text-decoration-none text-dark">
                        <div class="d-flex align-items-center">
                            <i class="mdi mdi-check-circle text-success mdi-24px me-2"></i>
                            <span>Available Tools</span>
                        </div>
                        <span class="badge badge-success">{{ $availableTools ?? 0 }}</span>
                    </a>
                    <a href="{{ route('tool-assignments.index') }}" class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom text-decoration-none text-dark">
                        <div class="d-flex align-items-center">
                            <i class="mdi mdi-swap-horizontal text-info mdi-24px me-2"></i>
                            <span>Unreturned Assignments</span>
                        </div>
                        <span class="badge badge-info">{{ $unreturnedRecords ?? 0 }}</span>
                    </a>
                    <a href="{{ route('admin.tool-settings.index') }}?ownership_status=hired" class="d-flex justify-content-between align-items-center text-decoration-none text-dark">
                        <div class="d-flex align-items-center">
                            <i class="mdi mdi-handshake text-danger mdi-24px me-2"></i>
                            <span>Hired Tools</span>
                        </div>
                        <span class="badge badge-danger">{{ $toolsHired ?? 0 }}</span>
                    </a>
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
                    <a href="{{ route('tool-assignments.index') }}" class="btn btn-outline-warning btn-icon-text">
                        <i class="mdi mdi-toolbox btn-icon-prepend"></i> Assign / Return Tools
                    </a>
                    <a href="{{ route('admin.tool-settings.index') }}" class="btn btn-outline-primary btn-icon-text">
                        <i class="mdi mdi-cog btn-icon-prepend"></i> Manage Tool Settings
                    </a>
                    <a href="{{ route('admin.tool-settings.create') }}" class="btn btn-outline-success btn-icon-text">
                        <i class="mdi mdi-plus btn-icon-prepend"></i> Add New Tool
                    </a>
                    <a href="{{ route('inspections.index') }}?view=pending-etogo" class="btn btn-outline-info btn-icon-text">
                        <i class="mdi mdi-format-list-checks btn-icon-prepend"></i> View Awaiting Assignment
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Recent Tool Assignments --}}
<div class="row">
    <div class="col-md-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-3">Recent Tool Assignments</h4>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Tool</th>
                                <th>Property</th>
                                <th>Qty</th>
                                <th>Type</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentAssignments ?? [] as $assignment)
                            <tr>
                                <td>{{ $assignment->created_at->format('M d, Y') }}</td>
                                <td>{{ $assignment->tool_name ?? $assignment->toolSetting?->tool_name ?? 'N/A' }}</td>
                                <td>{{ $assignment->inspection?->property?->property_name ?? $assignment->inspection?->property?->property_code ?? 'N/A' }}</td>
                                <td>{{ $assignment->quantity }}</td>
                                <td>
                                    <span class="badge badge-{{ $assignment->ownership_status === 'hired' ? 'warning' : 'primary' }}">
                                        {{ ucfirst($assignment->ownership_status ?? 'owned') }}
                                    </span>
                                </td>
                                <td>
                                    @if($assignment->returned_at)
                                        <span class="badge badge-success">Returned</span>
                                    @else
                                        <span class="badge badge-info">In Use</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    No tool assignments yet.
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

@else
{{-- ═══════════════════════════════════════════════════════════
     GENERIC STAFF DASHBOARD (non-Store Manager)
════════════════════════════════════════════════════════════════ --}}

<div class="row mb-3">
    <div class="col-12">
        <div class="alert alert-primary mb-0" role="alert" style="border-left:4px solid #0d6efd;">
            <strong>You are doing great.</strong> Keep the momentum today and drive every project one step closer to completion.
        </div>
    </div>
</div>

<div class="row">
    {{-- Stats Cards --}}
    <div class="col-md-3 stretch-card grid-margin">
        <div class="card bg-gradient-danger emuria-dashboard-card">
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
        <div class="card bg-gradient-info emuria-dashboard-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="font-weight-normal mb-2">Inspections</h6>
                        <h2 class="mb-0">{{ $inspectionsCount ?? 0 }}</h2>
                        <p class="text-white-50 mb-0 mt-2 small">Total Inspections</p>
                        <p class="text-white-50 mb-0 small">Actually inspected: {{ $completedInspectionsCount ?? 0 }}</p>
                        <p class="text-white-50 mb-0 small">Paid for inspection: {{ $paidInspectionsCount ?? 0 }}</p>
                    </div>
                    <div>
                        <i class="mdi mdi-clipboard-check mdi-48px opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 stretch-card grid-margin">
        <div class="card bg-gradient-success emuria-dashboard-card">
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
        <div class="card bg-gradient-warning emuria-dashboard-card">
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
                    @role('Super Admin|Administrator')
                    <a href="{{ route('properties.index', ['status' => 'pending_approval']) }}" class="emuria-queue-link">
                        <div class="d-flex align-items-center">
                            <i class="mdi mdi-clock-alert text-warning mdi-24px me-2"></i>
                            <span>Pending Approvals</span>
                        </div>
                        <span class="badge badge-warning">{{ \App\Models\Property::where('status', 'pending_approval')->count() }}</span>
                    </a>
                    <a href="{{ route('admin.users.index') }}" class="emuria-queue-link">
                        <div class="d-flex align-items-center">
                            <i class="mdi mdi-account-multiple text-info mdi-24px me-2"></i>
                            <span>Total Users</span>
                        </div>
                        <span class="badge badge-info">{{ \App\Models\User::count() }}</span>
                    </a>
                    @endrole

                    @if(auth()->user()->hasRole(['Super Admin', 'Administrator', 'Project Manager', 'Inspector']) || auth()->user()->can('view-inspections'))
                    <a href="{{ route('properties.index', ['status' => 'not_inspected']) }}" class="emuria-queue-link">
                        <div class="d-flex align-items-center">
                            <i class="mdi mdi-clipboard-check text-primary mdi-24px me-2"></i>
                            <span>Not Inspected</span>
                        </div>
                        <span class="badge badge-primary">{{ \App\Models\Property::whereDoesntHave('inspections', function ($query) { $query->where('status', 'completed'); })->count() }}</span>
                    </a>
                    @endif

                    @can('view-invoices')
                    <a href="{{ route('invoices.index', ['status' => 'pending']) }}" class="emuria-queue-link">
                        <div class="d-flex align-items-center">
                            <i class="mdi mdi-file-document-alert text-danger mdi-24px me-2"></i>
                            <span>Unpaid Invoices</span>
                        </div>
                        <span class="badge badge-danger">{{ \App\Models\Invoice::pending()->count() }}</span>
                    </a>
                    @endcan

                    @role('Super Admin|Administrator')
                    <a href="{{ route('admin.trade-applications.index') }}" class="emuria-queue-link">
                        <div class="d-flex align-items-center">
                            <i class="mdi mdi-account-hard-hat text-success mdi-24px me-2"></i>
                            <span>Trade Applications</span>
                        </div>
                        <span class="badge badge-success">{{ $openTradeApplicationsCount ?? 0 }}</span>
                    </a>
                    @endrole

                    @unless(
                        auth()->user()->hasRole(['Super Admin', 'Administrator', 'Project Manager', 'Inspector'])
                        || auth()->user()->can('view-inspections')
                        || auth()->user()->can('view-invoices')
                    )
                        <div class="text-muted small">No system queues are available for your current permissions.</div>
                    @endunless
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
                    
                    @can('view-invoices')
                    <a href="{{ route('invoices.index') }}" class="btn btn-outline-success btn-icon-text">
                        <i class="mdi mdi-file-document btn-icon-prepend"></i> View Invoices
                    </a>
                    @endcan

                    @role('Super Admin|Administrator')
                    <a href="{{ route('admin.trade-applications.index') }}" class="btn btn-outline-primary btn-icon-text">
                        <i class="mdi mdi-account-hard-hat btn-icon-prepend"></i>
                        Review Trade Applications
                        @if(($openTradeApplicationsCount ?? 0) > 0)
                            <span class="badge badge-primary ms-2">{{ $openTradeApplicationsCount }}</span>
                        @endif
                    </a>
                    <a href="{{ route('trade-applications.create') }}" target="_blank" class="btn btn-outline-warning btn-icon-text">
                        <i class="mdi mdi-open-in-new btn-icon-prepend"></i> Open Public Trade Form
                    </a>
                    @endrole
                </div>
            </div>
        </div>
    </div>
</div>

@role('Super Admin|Administrator')
<div class="row">
    <div class="col-md-12 grid-margin stretch-card">
        <div class="card" style="border-left:4px solid #28a745;">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h4 class="card-title mb-1">Trade Partner Onboarding</h4>
                    <p class="text-muted mb-0">
                        {{ $openTradeApplicationsCount ?? 0 }} open application(s), {{ $approvedTradeApplicationsCount ?? 0 }} approved trade partner(s).
                    </p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.trade-applications.index') }}" class="btn btn-success btn-icon-text">
                        <i class="mdi mdi-clipboard-account btn-icon-prepend"></i> Review Registered Trades
                    </a>
                    <a href="{{ route('trade-applications.create') }}" target="_blank" class="btn btn-outline-secondary btn-icon-text">
                        <i class="mdi mdi-link-variant btn-icon-prepend"></i> Public Registration Link
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endrole

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
                                <td>{{ $activity->property->property_name ?? $activity->property->name ?? 'N/A' }}</td>
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
body.light-theme .content-wrapper {
    background-color: #f4f7fb !important;
}

body.light-theme .card,
.card,
.emuria-dashboard-card,
.emuria-dashboard-card.bg-gradient-danger,
.emuria-dashboard-card.bg-gradient-info,
.emuria-dashboard-card.bg-gradient-success,
.emuria-dashboard-card.bg-gradient-warning {
    background: #ffffff !important;
    border: 1px solid #dfe6ef !important;
    border-left: 1px solid #dfe6ef !important;
    border-radius: 8px !important;
    box-shadow: 0 4px 12px rgba(16, 24, 40, .045) !important;
    color: #172033 !important;
}

.card-body {
    color: #172033 !important;
}

.card-title {
    color: #172033 !important;
    font-weight: 800 !important;
}

.emuria-dashboard-card {
    min-height: 142px !important;
}

.emuria-dashboard-card .card-body {
    padding: 20px 22px !important;
}

.emuria-dashboard-card h6,
.emuria-dashboard-card p,
.emuria-dashboard-card .text-white-50 {
    color: #667085 !important;
}

.emuria-dashboard-card h6 {
    font-size: .76rem !important;
    font-weight: 800 !important;
    letter-spacing: .04em !important;
    text-transform: uppercase !important;
}

.emuria-dashboard-card h2 {
    color: #071426 !important;
    font-size: 2rem !important;
    font-weight: 900 !important;
}

.emuria-dashboard-card i.mdi-48px {
    width: 48px !important;
    height: 48px !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    background: #f3f6fa !important;
    color: #344054 !important;
    border-radius: 8px !important;
    font-size: 1.6rem !important;
    opacity: 1 !important;
}

.table {
    color: #172033 !important;
}

.table thead th {
    background: #f3f6fa !important;
    color: #344054 !important;
    border-bottom: 1px solid #dfe6ef !important;
}

.table tbody td {
    background: #ffffff !important;
    color: #172033 !important;
    border-top: 1px solid #eef2f6 !important;
}

.table-hover tbody tr:hover td {
    background: #f8fafc !important;
    box-shadow: none !important;
    transform: none !important;
}

body.light-theme .btn-outline-primary,
body.light-theme .btn-outline-info,
body.light-theme .btn-outline-success,
body.light-theme .btn-outline-warning {
    color: #2458d6 !important;
}
</style>

@endif {{-- end @if(Store Manager) / @else --}}

@endsection
