@extends('admin.layout')

@section('title', 'Create Role')

@section('header', 'Create New Role')

@section('breadcrumbs')
<li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.roles.index') }}">Role Management</a></li>
<li class="breadcrumb-item active" aria-current="page">Create Role</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8 offset-md-2 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Role Details</h4>
                <p class="card-description">Create a new role for the system</p>

                <form action="{{ route('admin.roles.store') }}" method="POST" class="forms-sample">
                    @csrf

                    <div class="form-group">
                        <label for="name">Role Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                               id="name" name="name" placeholder="Enter role name (e.g., Content Manager)" 
                               value="{{ old('name') }}" required autofocus>
                        <small class="form-text text-muted">
                            Use descriptive names like "Content Manager", "Site Supervisor", etc.
                        </small>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="guard_name">Guard Name</label>
                        <input type="text" class="form-control @error('guard_name') is-invalid @enderror" 
                               id="guard_name" name="guard_name" placeholder="web" 
                               value="{{ old('guard_name', 'web') }}">
                        <small class="form-text text-muted">
                            Leave as "web" for standard web authentication
                        </small>
                        @error('guard_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="alert alert-info">
                        <h6><i class="mdi mdi-information"></i> Note:</h6>
                        <p class="mb-0">After creating the role, you can assign permissions to it from the role details page.</p>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('admin.roles.index') }}" class="btn btn-light">
                            <i class="mdi mdi-arrow-left"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="mdi mdi-check"></i> Create Role
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
