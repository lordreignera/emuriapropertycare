@extends('admin.layout')

@section('title', 'Role Management')

@section('header', 'Role Management')

@section('breadcrumbs')
<li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
<li class="breadcrumb-item active" aria-current="page">Role Management</li>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="card-title mb-0">All Roles</h4>
                    <a href="{{ route('admin.roles.create') }}" class="btn btn-primary btn-sm">
                        <i class="mdi mdi-plus"></i> Create New Role
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover" id="rolesTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Role Name</th>
                                <th>Users</th>
                                <th>Permissions</th>
                                <th>Guard</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($roles as $role)
                            <tr>
                                <td>{{ $role->id }}</td>
                                <td>
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
                                </td>
                                <td>
                                    <span class="badge badge-light">{{ $role->users_count }} users</span>
                                </td>
                                <td>
                                    <span class="badge badge-info">{{ $role->permissions_count }} permissions</span>
                                </td>
                                <td>
                                    <small class="text-muted">{{ $role->guard_name }}</small>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('admin.roles.show', $role) }}" class="btn btn-sm btn-info" title="View">
                                            <i class="mdi mdi-eye"></i>
                                        </a>
                                        @if(!in_array($role->name, ['Super Admin', 'Administrator', 'Client']))
                                        <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-sm btn-warning" title="Edit">
                                            <i class="mdi mdi-pencil"></i>
                                        </a>
                                        <form action="{{ route('admin.roles.destroy', $role) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this role?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                <i class="mdi mdi-delete"></i>
                                            </button>
                                        </form>
                                        @else
                                        <button class="btn btn-sm btn-secondary" disabled title="System Role">
                                            <i class="mdi mdi-lock"></i>
                                        </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    No roles found.
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

<div class="row mt-3">
    <div class="col-12">
        <div class="alert alert-info">
            <h6><i class="mdi mdi-information"></i> Role Information:</h6>
            <ul class="mb-0">
                <li><strong>Super Admin, Administrator, Client:</strong> System roles that cannot be modified or deleted</li>
                <li><strong>Custom Roles:</strong> Can be created, edited, and deleted (if no users assigned)</li>
                <li><strong>Permissions:</strong> Click "View" to manage permissions for each role</li>
            </ul>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('#rolesTable').DataTable({
            "pageLength": 10,
            "order": [[0, "asc"]],
            "language": {
                "search": "Search roles:",
                "lengthMenu": "Show _MENU_ roles per page",
                "info": "Showing _START_ to _END_ of _TOTAL_ roles",
                "infoEmpty": "No roles available",
                "infoFiltered": "(filtered from _MAX_ total roles)",
                "zeroRecords": "No matching roles found"
            },
            "columnDefs": [
                { "orderable": false, "targets": 5 } // Disable sorting on Actions column
            ]
        });
    });
</script>
@endpush

