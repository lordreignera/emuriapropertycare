@extends('admin.layout')

@section('title', 'Permission Details')

@section('header', $permission->name)

@section('breadcrumbs')
<li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.permissions.index') }}">Permission Management</a></li>
<li class="breadcrumb-item active" aria-current="page">{{ $permission->name }}</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="card-title mb-0">Permission Information</h4>
                    <a href="{{ route('admin.permissions.edit', $permission) }}" class="btn btn-warning btn-sm">
                        <i class="mdi mdi-pencil"></i> Edit Permission
                    </a>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <p><strong>Permission Name:</strong> <code>{{ $permission->name }}</code></p>
                        <p><strong>Guard:</strong> <span class="badge badge-info">{{ $permission->guard_name }}</span></p>
                        <p><strong>ID:</strong> {{ $permission->id }}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Created:</strong> {{ $permission->created_at->format('M d, Y H:i') }}</p>
                        <p><strong>Updated:</strong> {{ $permission->updated_at->format('M d, Y H:i') }}</p>
                        <p><strong>Assigned to:</strong> {{ $permission->roles->count() }} role(s)</p>
                    </div>
                </div>

                <hr>

                <h5 class="mb-3">Roles with this Permission</h5>
                
                @if($permission->roles->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Role Name</th>
                                    <th>Users</th>
                                    <th>Permissions</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($permission->roles as $role)
                                <tr>
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
                                    <td>{{ $role->users->count() }}</td>
                                    <td>{{ $role->permissions->count() }}</td>
                                    <td>
                                        <a href="{{ route('admin.roles.show', $role) }}" class="btn btn-sm btn-info">
                                            <i class="mdi mdi-eye"></i> View
                                        </a>
                                        <form action="{{ route('admin.permissions.remove-role', [$permission, $role]) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this permission from {{ $role->name }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="mdi mdi-close"></i> Remove
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="alert alert-warning">
                        <i class="mdi mdi-alert"></i> This permission is not assigned to any roles yet.
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-4 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Assign to Role</h5>
                
                <form action="{{ route('admin.permissions.assign-role', $permission) }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label for="role">Select Role</label>
                        <select class="form-control" name="role" id="role" required>
                            <option value="">-- Select Role --</option>
                            @foreach($allRoles as $role)
                                @if(!$permission->roles->contains($role->id))
                                <option value="{{ $role->name }}">{{ $role->name }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success btn-block">
                        <i class="mdi mdi-plus"></i> Assign to Role
                    </button>
                </form>

                <div class="mt-4">
                    <h6>Quick Actions</h6>
                    <a href="{{ route('admin.permissions.edit', $permission) }}" class="btn btn-warning btn-block mb-2">
                        <i class="mdi mdi-pencil"></i> Edit Permission
                    </a>
                    <form action="{{ route('admin.permissions.destroy', $permission) }}" method="POST" onsubmit="return confirm('Are you sure? This action cannot be undone.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-block">
                            <i class="mdi mdi-delete"></i> Delete Permission
                        </button>
                    </form>
                </div>

                @if($permission->roles->count() > 0)
                <div class="alert alert-info mt-3">
                    <small>
                        <i class="mdi mdi-information"></i>
                        This permission is currently assigned to {{ $permission->roles->count() }} role(s). Remove from all roles before deleting.
                    </small>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
