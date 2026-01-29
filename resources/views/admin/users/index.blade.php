@extends('admin.layout')

@section('title', 'User Management')

@section('header', 'User Management')

@section('breadcrumbs')
<li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
<li class="breadcrumb-item active" aria-current="page">User Management</li>
@endsection

@section('content')
{{-- KPI Cards --}}
<div class="row mb-4">
    <div class="col-md-3 stretch-card">
        <div class="card stat-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1 font-weight-medium">Total Users</p>
                        <h2 class="mb-0 font-weight-bold">{{ $totalUsers }}</h2>
                    </div>
                    <div class="icon-wrapper bg-primary-light">
                        <i class="mdi mdi-account-multiple text-primary" style="font-size: 28px;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 stretch-card">
        <div class="card stat-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1 font-weight-medium">Clients</p>
                        <h2 class="mb-0 font-weight-bold">{{ $totalClients }}</h2>
                    </div>
                    <div class="icon-wrapper bg-success-light">
                        <i class="mdi mdi-account-circle text-success" style="font-size: 28px;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 stretch-card">
        <div class="card stat-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1 font-weight-medium">Inspectors</p>
                        <h2 class="mb-0 font-weight-bold">{{ $totalInspectors }}</h2>
                    </div>
                    <div class="icon-wrapper bg-info-light">
                        <i class="mdi mdi-magnify text-info" style="font-size: 28px;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 stretch-card">
        <div class="card stat-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1 font-weight-medium">Project Managers</p>
                        <h2 class="mb-0 font-weight-bold">{{ $totalProjectManagers }}</h2>
                    </div>
                    <div class="icon-wrapper bg-warning-light">
                        <i class="mdi mdi-briefcase text-warning" style="font-size: 28px;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="card-title mb-0">All Users</h4>
                    <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm">
                        <i class="mdi mdi-plus"></i> Add New User
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover" id="usersTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Roles</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($users as $user)
                            <tr>
                                <td>{{ $user->id }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="{{ $user->profile_photo_url }}" alt="{{ $user->name }}" class="rounded-circle me-2" width="32" height="32">
                                        <span>{{ $user->name }}</span>
                                    </div>
                                </td>
                                <td>{{ $user->email }}</td>
                                <td>
                                    @forelse($user->roles as $role)
                                        <span class="badge 
                                            @if($role->name === 'Super Admin') badge-danger
                                            @elseif($role->name === 'Administrator') badge-warning
                                            @elseif($role->name === 'Project Manager') badge-primary
                                            @elseif($role->name === 'Inspector') badge-info
                                            @elseif($role->name === 'Technician') badge-secondary
                                            @elseif($role->name === 'Finance Officer') badge-success
                                            @else badge-dark
                                            @endif">
                                            {{ $role->name }}
                                        </span>
                                    @empty
                                        <span class="badge badge-light">No Role</span>
                                    @endforelse
                                </td>
                                <td>{{ $user->created_at->format('M d, Y') }}</td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('admin.users.show', $user) }}" class="btn btn-sm btn-info" title="View">
                                            <i class="mdi mdi-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-warning" title="Edit">
                                            <i class="mdi mdi-pencil"></i>
                                        </a>
                                        @if($user->id !== auth()->id())
                                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                <i class="mdi mdi-delete"></i>
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    No users found. <a href="{{ route('admin.users.create') }}">Create one now</a>
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
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('#usersTable').DataTable({
            "pageLength": 10,
            "order": [[0, "desc"]],
            "language": {
                "search": "Search users:",
                "lengthMenu": "Show _MENU_ users per page",
                "info": "Showing _START_ to _END_ of _TOTAL_ users",
                "infoEmpty": "No users available",
                "infoFiltered": "(filtered from _MAX_ total users)",
                "zeroRecords": "No matching users found"
            },
            "columnDefs": [
                { "orderable": false, "targets": 5 } // Disable sorting on Actions column
            ]
        });
    });
</script>
@endpush

@push('styles')
<style>
    /* Fix table styling - white background with proper text colors */
    body.light-theme .table {
        background-color: #ffffff !important;
        color: #343a40 !important;
    }
    
    body.light-theme .table thead th {
        background-color: #f8f9fa !important;
        color: #343a40 !important;
        font-weight: 600 !important;
        border-color: #dee2e6 !important;
    }
    
    body.light-theme .table tbody td {
        background-color: #ffffff !important;
        color: #343a40 !important;
        border-color: #dee2e6 !important;
    }
    
    /* Fix hover effect - light gray background with dark text */
    body.light-theme .table-hover tbody tr:hover {
        background-color: #f8f9fa !important;
    }
    
    body.light-theme .table-hover tbody tr:hover td {
        background-color: #f8f9fa !important;
        color: #343a40 !important;
    }
    
    /* Ensure text in hovered rows stays visible */
    body.light-theme .table-hover tbody tr:hover td span,
    body.light-theme .table-hover tbody tr:hover td a,
    body.light-theme .table-hover tbody tr:hover td .badge {
        color: inherit !important;
    }
    
    /* Fix link colors in table */
    body.light-theme .table a {
        color: #2ecc71 !important;
    }
    
    body.light-theme .table a:hover {
        color: #27ae60 !important;
    }
    
    /* Fix badge visibility */
    body.light-theme .table .badge {
        font-weight: 600 !important;
    }
    
    /* Fix button visibility on hover */
    body.light-theme .table-hover tbody tr:hover .btn {
        opacity: 1 !important;
    }
</style>
@endpush
