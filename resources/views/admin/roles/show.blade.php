@extends('admin.layout')

@section('title', 'Role Details')

@section('header', $role->name . ' Role')

@section('breadcrumbs')
<li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.roles.index') }}">Role Management</a></li>
<li class="breadcrumb-item active" aria-current="page">{{ $role->name }}</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="card-title mb-0">Role Information</h4>
                    @if(!in_array($role->name, ['Super Admin', 'Administrator', 'Client']))
                    <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-warning btn-sm">
                        <i class="mdi mdi-pencil"></i> Edit Role
                    </a>
                    @endif
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <p><strong>Role Name:</strong> 
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
                        </p>
                        <p><strong>Guard:</strong> {{ $role->guard_name }}</p>
                        <p><strong>Total Users:</strong> {{ $roleUsers->count() }}</p>
                        <p><strong>Total Permissions:</strong> {{ $rolePermissions->count() }}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Created:</strong> {{ $role->created_at->format('M d, Y H:i') }}</p>
                        <p><strong>Updated:</strong> {{ $role->updated_at->format('M d, Y H:i') }}</p>
                    </div>
                </div>

                <hr>

                <h5 class="mb-3">Assigned Permissions</h5>
                
                @if($rolePermissions->count() > 0)
                    @foreach($rolePermissions->groupBy(function($permission) {
                        $parts = explode('-', $permission->name);
                        return $parts[1] ?? 'Other';
                    }) as $group => $permissions)
                        <div class="mb-3">
                            <h6 class="text-primary">{{ ucfirst($group) }}</h6>
                            <div class="row">
                                @foreach($permissions as $permission)
                                <div class="col-md-6">
                                    <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded">
                                        <span>
                                            <i class="mdi mdi-check-circle text-success"></i>
                                            {{ $permission->name }}
                                        </span>
                                        <form action="{{ route('admin.roles.remove-permission', [$role, $permission]) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this permission?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-link text-danger p-0">
                                                <i class="mdi mdi-close"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                @else
                    <p class="text-muted">No permissions assigned to this role</p>
                @endif
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-body">
                <h5 class="card-title">Users with this Role</h5>
                
                @if($roleUsers->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($roleUsers as $user)
                                <tr>
                                    <td>{{ $user->name }}</td>
                                    <td>{{ $user->email }}</td>
                                    <td>
                                        <a href="{{ route('admin.users.show', $user) }}" class="btn btn-sm btn-info">
                                            <i class="mdi mdi-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted">No users assigned to this role</p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-4 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Assign Permissions</h5>
                
                <form action="{{ route('admin.roles.assign-permission', $role) }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label for="permission">Select Permission</label>
                        <select class="form-control" name="permission" id="permission" required>
                            <option value="">-- Select Permission --</option>
                            @foreach($allPermissions as $group => $permissions)
                                <optgroup label="{{ ucfirst($group) }}">
                                    @foreach($permissions as $permission)
                                        @if(!$role->hasPermissionTo($permission))
                                        <option value="{{ $permission->name }}">{{ $permission->name }}</option>
                                        @endif
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success btn-block">
                        <i class="mdi mdi-plus"></i> Assign Permission
                    </button>
                </form>

                <div class="mt-4">
                    <h6>Quick Actions</h6>
                    @if(!in_array($role->name, ['Super Admin', 'Administrator', 'Client']))
                    <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-warning btn-block mb-2">
                        <i class="mdi mdi-pencil"></i> Edit Role
                    </a>
                    <form action="{{ route('admin.roles.destroy', $role) }}" method="POST" onsubmit="return confirm('Are you sure? This action cannot be undone.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-block">
                            <i class="mdi mdi-delete"></i> Delete Role
                        </button>
                    </form>
                    @else
                    <div class="alert alert-warning">
                        <small><i class="mdi mdi-lock"></i> This is a system role and cannot be modified or deleted.</small>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
