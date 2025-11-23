@extends('admin.layout')

@section('title', 'Create Permission')

@section('header', 'Create New Permission')

@section('breadcrumbs')
<li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.permissions.index') }}">Permission Management</a></li>
<li class="breadcrumb-item active" aria-current="page">Create</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Permission Information</h4>
                <p class="card-description">
                    Create a new permission to control access to system features
                </p>
                
                <form action="{{ route('admin.permissions.store') }}" method="POST" class="forms-sample">
                    @csrf
                    
                    <div class="form-group">
                        <label for="name">Permission Name <span class="text-danger">*</span></label>
                        <input 
                            type="text" 
                            class="form-control @error('name') is-invalid @enderror" 
                            id="name" 
                            name="name" 
                            value="{{ old('name') }}" 
                            placeholder="e.g., view properties, create inspections"
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
                        <select class="form-control" id="guard_name" name="guard_name">
                            <option value="web" selected>web</option>
                            <option value="api">api</option>
                        </select>
                        <small class="form-text text-muted">
                            Usually "web" for standard Laravel authentication
                        </small>
                    </div>

                    <div class="form-group">
                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-primary mr-2">
                                <i class="mdi mdi-content-save"></i> Create Permission
                            </button>
                            <a href="{{ route('admin.permissions.index') }}" class="btn btn-light">
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
                <h5 class="card-title">Common Permissions</h5>
                <p class="text-muted small mb-3">Here are some examples of well-structured permissions:</p>
                
                <div class="mb-3">
                    <h6 class="text-primary">Properties</h6>
                    <ul class="list-unstyled">
                        <li><code>view properties</code></li>
                        <li><code>create properties</code></li>
                        <li><code>edit properties</code></li>
                        <li><code>delete properties</code></li>
                    </ul>
                </div>

                <div class="mb-3">
                    <h6 class="text-primary">Inspections</h6>
                    <ul class="list-unstyled">
                        <li><code>view inspections</code></li>
                        <li><code>create inspections</code></li>
                        <li><code>edit inspections</code></li>
                        <li><code>approve inspections</code></li>
                    </ul>
                </div>

                <div class="mb-3">
                    <h6 class="text-primary">Projects</h6>
                    <ul class="list-unstyled">
                        <li><code>view projects</code></li>
                        <li><code>create projects</code></li>
                        <li><code>edit projects</code></li>
                        <li><code>assign projects</code></li>
                    </ul>
                </div>

                <div class="mb-3">
                    <h6 class="text-primary">Users & Roles</h6>
                    <ul class="list-unstyled">
                        <li><code>view users</code></li>
                        <li><code>manage users</code></li>
                        <li><code>manage roles</code></li>
                        <li><code>manage permissions</code></li>
                    </ul>
                </div>

                <div class="alert alert-info">
                    <small>
                        <i class="mdi mdi-information"></i>
                        <strong>Tip:</strong> After creating a permission, you'll be able to assign it to roles on the next page.
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
