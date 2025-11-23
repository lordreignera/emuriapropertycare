@extends('admin.layout')

@section('title', 'Edit User')

@section('header', 'Edit User')

@section('breadcrumbs')
<li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">User Management</a></li>
<li class="breadcrumb-item active" aria-current="page">Edit User</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Edit User Details</h4>
                <p class="card-description">Update user information</p>

                <form action="{{ route('admin.users.update', $user) }}" method="POST" class="forms-sample">
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label for="name">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                               id="name" name="name" placeholder="Enter full name" 
                               value="{{ old('name', $user->name) }}" required autofocus>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address <span class="text-danger">*</span></label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" 
                               id="email" name="email" placeholder="Enter email address" 
                               value="{{ old('email', $user->email) }}" required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="password">New Password</label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" 
                               id="password" name="password" placeholder="Leave blank to keep current password">
                        <small class="form-text text-muted">Only fill if you want to change the password</small>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="password_confirmation">Confirm New Password</label>
                        <input type="password" class="form-control" 
                               id="password_confirmation" name="password_confirmation" 
                               placeholder="Confirm new password">
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('admin.users.index') }}" class="btn btn-light">
                            <i class="mdi mdi-arrow-left"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="mdi mdi-check"></i> Update User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Current Roles</h4>
                
                <div class="mb-3">
                    @forelse($user->roles as $role)
                        <span class="badge 
                            @if($role->name === 'Super Admin') badge-danger
                            @elseif($role->name === 'Administrator') badge-warning
                            @elseif($role->name === 'Project Manager') badge-primary
                            @elseif($role->name === 'Inspector') badge-info
                            @elseif($role->name === 'Technician') badge-secondary
                            @elseif($role->name === 'Finance Officer') badge-success
                            @else badge-dark
                            @endif mb-2">
                            {{ $role->name }}
                            @if(!$user->hasRole('Super Admin') || auth()->user()->hasRole('Super Admin'))
                            <form action="{{ route('admin.users.remove-role', [$user, $role]) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this role?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-link btn-sm p-0 text-white" style="text-decoration: none;">
                                    <i class="mdi mdi-close"></i>
                                </button>
                            </form>
                            @endif
                        </span>
                    @empty
                        <p class="text-muted">No roles assigned</p>
                    @endforelse
                </div>

                <hr>

                <h5 class="mt-3">Assign New Role</h5>
                <form action="{{ route('admin.users.assign-role', $user) }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <select class="form-control" name="role" required>
                            <option value="">-- Select Role --</option>
                            @foreach($roles as $role)
                                @if(!in_array($role->name, $userRoles) && $role->name !== 'Client')
                                <option value="{{ $role->name }}">{{ $role->name }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success btn-sm btn-block">
                        <i class="mdi mdi-plus"></i> Assign Role
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    /* Fix input field styling - white background with black text */
    body.light-theme .form-control {
        background-color: #ffffff !important;
        color: #343a40 !important;
        border: 1px solid #ced4da !important;
    }
    
    body.light-theme .form-control:focus {
        background-color: #ffffff !important;
        color: #343a40 !important;
        border-color: #2ecc71 !important;
        box-shadow: 0 0 0 0.2rem rgba(46, 204, 113, 0.25) !important;
    }
    
    body.light-theme .form-control::placeholder {
        color: #6c757d !important;
        opacity: 0.7 !important;
    }
    
    body.light-theme .form-control:disabled,
    body.light-theme .form-control[readonly] {
        background-color: #e9ecef !important;
        color: #6c757d !important;
    }
    
    body.light-theme select.form-control {
        background-color: #ffffff !important;
        color: #343a40 !important;
    }
    
    body.light-theme select.form-control option {
        background-color: #ffffff !important;
        color: #343a40 !important;
    }
    
    /* Fix labels */
    body.light-theme .form-group label {
        color: #343a40 !important;
        font-weight: 500 !important;
    }
    
    /* Fix helper text */
    body.light-theme .form-text {
        color: #6c757d !important;
    }
    
    /* Fix card text */
    body.light-theme .card-description {
        color: #6c757d !important;
    }
    
    body.light-theme .text-muted {
        color: #6c757d !important;
    }
</style>
@endpush
