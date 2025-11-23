@extends('admin.layout')

@section('title', 'Edit Permission')

@section('header', 'Edit Permission: ' . $permission->name)

@section('breadcrumbs')
<li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.permissions.index') }}">Permission Management</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.permissions.show', $permission) }}">{{ $permission->name }}</a></li>
<li class="breadcrumb-item active" aria-current="page">Edit</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Edit Permission</h4>
                <p class="card-description">
                    Modify the permission name to better reflect its purpose
                </p>

                <form action="{{ route('admin.permissions.update', $permission) }}" method="POST" class="forms-sample">
                    @csrf
                    @method('PUT')
                    
                    <div class="form-group">
                        <label for="name">Permission Name</label>
                        <input 
                            type="text" 
                            class="form-control @error('name') is-invalid @enderror" 
                            id="name" 
                            name="name" 
                            value="{{ old('name', $permission->name) }}" 
                            placeholder="Enter permission name"
                            required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">
                            Use format: <code>action resource</code> (e.g., "view properties", "edit users")
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="guard_name">Guard Name</label>
                        <input 
                            type="text" 
                            class="form-control" 
                            id="guard_name" 
                            value="{{ $permission->guard_name }}" 
                            disabled>
                        <small class="form-text text-muted">
                            Guard name cannot be changed after creation
                        </small>
                    </div>

                    <div class="form-group">
                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-primary mr-2">
                                <i class="mdi mdi-content-save"></i> Update Permission
                            </button>
                            <a href="{{ route('admin.permissions.show', $permission) }}" class="btn btn-light">
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
                <h5 class="card-title">Permission Information</h5>
                <p><strong>Current Name:</strong> <code>{{ $permission->name }}</code></p>
                <p><strong>Guard:</strong> <span class="badge badge-info">{{ $permission->guard_name }}</span></p>
                <p><strong>Assigned to:</strong> {{ $permission->roles->count() }} role(s)</p>
                <p><strong>Created:</strong> {{ $permission->created_at->format('M d, Y') }}</p>
                <p><strong>Last Updated:</strong> {{ $permission->updated_at->format('M d, Y') }}</p>

                <hr>

                <h6 class="mt-4">Naming Guidelines</h6>
                <ul class="text-muted" style="font-size: 0.875rem;">
                    <li>Use lowercase letters</li>
                    <li>Use spaces, not underscores</li>
                    <li>Format: action + resource</li>
                    <li>Be specific and descriptive</li>
                    <li>Examples: <code>view properties</code>, <code>create inspections</code></li>
                </ul>

                @if($permission->roles->count() > 0)
                <div class="alert alert-warning mt-3">
                    <small>
                        <i class="mdi mdi-alert"></i>
                        <strong>Warning:</strong> This permission is assigned to {{ $permission->roles->count() }} role(s). Changes may affect system access.
                    </small>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
