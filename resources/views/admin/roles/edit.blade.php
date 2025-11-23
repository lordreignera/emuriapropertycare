@extends('admin.layout')

@section('title', 'Edit Role')

@section('header', 'Edit Role: ' . $role->name)

@section('breadcrumbs')
<li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.roles.index') }}">Role Management</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.roles.show', $role) }}">{{ $role->name }}</a></li>
<li class="breadcrumb-item active" aria-current="page">Edit</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Edit Role</h4>

                @if(in_array($role->name, ['Super Admin', 'Administrator', 'Client']))
                <div class="alert alert-warning">
                    <i class="mdi mdi-alert"></i>
                    <strong>Warning:</strong> This is a system role. Modifying it may affect critical system functionality. Proceed with caution.
                </div>
                @endif

                <form action="{{ route('admin.roles.update', $role) }}" method="POST" class="forms-sample">
                    @csrf
                    @method('PUT')
                    
                    <div class="form-group">
                        <label for="name">Role Name</label>
                        <input 
                            type="text" 
                            class="form-control @error('name') is-invalid @enderror" 
                            id="name" 
                            name="name" 
                            value="{{ old('name', $role->name) }}" 
                            placeholder="Enter role name"
                            required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">
                            The display name for this role (e.g., "Site Supervisor", "Quality Control")
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="guard_name">Guard Name</label>
                        <input 
                            type="text" 
                            class="form-control" 
                            id="guard_name" 
                            value="{{ $role->guard_name }}" 
                            disabled>
                        <small class="form-text text-muted">
                            Guard name cannot be changed after creation
                        </small>
                    </div>

                    <div class="form-group">
                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-primary mr-2">
                                <i class="mdi mdi-content-save"></i> Update Role
                            </button>
                            <a href="{{ route('admin.roles.show', $role) }}" class="btn btn-light">
                                Cancel
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Role Information</h5>
                <p><strong>Current Name:</strong> 
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
                <p><strong>Users:</strong> {{ $role->users->count() }}</p>
                <p><strong>Permissions:</strong> {{ $role->permissions->count() }}</p>
                <p><strong>Created:</strong> {{ $role->created_at->format('M d, Y') }}</p>
                <p><strong>Last Updated:</strong> {{ $role->updated_at->format('M d, Y') }}</p>

                <hr>

                <h6 class="mt-4">Naming Guidelines</h6>
                <ul class="text-muted" style="font-size: 0.875rem;">
                    <li>Use clear, descriptive names</li>
                    <li>Be consistent with existing roles</li>
                    <li>Avoid special characters</li>
                    <li>Use title case (e.g., "Project Manager")</li>
                </ul>

                @if(!in_array($role->name, ['Super Admin', 'Administrator', 'Client']))
                <div class="alert alert-info mt-3">
                    <small>
                        <i class="mdi mdi-information"></i>
                        This is a custom role. You can safely rename it without affecting system functionality.
                    </small>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
