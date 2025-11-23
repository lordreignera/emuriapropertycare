@extends('admin.layout')

@section('title', 'Permission Management')

@section('header', 'Permission Management')

@section('breadcrumbs')
<li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
<li class="breadcrumb-item active" aria-current="page">Permission Management</li>
@endsection

@section('content')
<div class="row">
    <div class="col-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="card-title mb-0">All Permissions</h4>
                    <a href="{{ route('admin.permissions.create') }}" class="btn btn-primary">
                        <i class="mdi mdi-plus"></i> Create New Permission
                    </a>
                </div>

                @if($groupedPermissions->count() > 0)
                    @foreach($groupedPermissions as $group => $permissions)
                    <div class="mb-4">
                        <h5 class="text-primary mb-3">
                            <i class="mdi mdi-folder-outline"></i> {{ ucfirst($group) }} Permissions
                            <span class="badge badge-secondary">{{ $permissions->count() }}</span>
                        </h5>
                        <div class="table-responsive">
                            <table class="table table-hover permissions-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Permission Name</th>
                                        <th>Guard</th>
                                        <th>Assigned to Roles</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($permissions as $permission)
                                    <tr>
                                        <td>{{ $permission->id }}</td>
                                        <td>
                                            <strong>{{ $permission->name }}</strong>
                                        </td>
                                        <td>
                                            <span class="badge badge-info">{{ $permission->guard_name }}</span>
                                        </td>
                                        <td>
                                            @if($permission->roles->count() > 0)
                                                @foreach($permission->roles as $role)
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
                                                @endforeach
                                            @else
                                                <span class="text-muted">None</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.permissions.show', $permission) }}" class="btn btn-sm btn-info" title="View Details">
                                                <i class="mdi mdi-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.permissions.edit', $permission) }}" class="btn btn-sm btn-warning" title="Edit">
                                                <i class="mdi mdi-pencil"></i>
                                            </a>
                                            <form action="{{ route('admin.permissions.destroy', $permission) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this permission?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                    <i class="mdi mdi-delete"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endforeach
                @else
                    <div class="text-center py-5">
                        <i class="mdi mdi-shield-off" style="font-size: 48px; color: #ccc;"></i>
                        <p class="text-muted mt-3">No permissions found</p>
                        <a href="{{ route('admin.permissions.create') }}" class="btn btn-primary">
                            <i class="mdi mdi-plus"></i> Create Your First Permission
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Permission Guidelines</h5>
                <div class="row">
                    <div class="col-md-4">
                        <h6 class="text-primary">Naming Convention</h6>
                        <p class="text-muted small">
                            Use format: <code>action resource</code><br>
                            Examples: <code>view properties</code>, <code>create projects</code>, <code>delete users</code>
                        </p>
                    </div>
                    <div class="col-md-4">
                        <h6 class="text-primary">Standard Actions</h6>
                        <p class="text-muted small">
                            <code>view</code> - Read access<br>
                            <code>create</code> - Create new records<br>
                            <code>edit</code> - Modify existing records<br>
                            <code>delete</code> - Remove records<br>
                            <code>manage</code> - Full control
                        </p>
                    </div>
                    <div class="col-md-4">
                        <h6 class="text-primary">Best Practices</h6>
                        <p class="text-muted small">
                            • Keep permissions granular<br>
                            • Use consistent naming<br>
                            • Group by resource type<br>
                            • Document special permissions
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Initialize DataTables for each permission group table
        $('.permissions-table').each(function() {
            $(this).DataTable({
                "pageLength": 10,
                "order": [[0, "asc"]],
                "language": {
                    "search": "Search permissions:",
                    "lengthMenu": "Show _MENU_ permissions per page",
                    "info": "Showing _START_ to _END_ of _TOTAL_ permissions",
                    "infoEmpty": "No permissions available",
                    "infoFiltered": "(filtered from _MAX_ total permissions)",
                    "zeroRecords": "No matching permissions found"
                },
                "columnDefs": [
                    { "orderable": false, "targets": 4 } // Disable sorting on Actions column
                ]
            });
        });
    });
</script>
@endpush

