@extends('admin.layout')

@section('title', 'Create User')

@section('header', 'Create New User')

@section('breadcrumbs')
<li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">User Management</a></li>
<li class="breadcrumb-item active" aria-current="page">Create User</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">User Details</h4>
                <p class="card-description">Create a new staff user account</p>

                <form action="{{ route('admin.users.store') }}" method="POST" class="forms-sample">
                    @csrf

                    <div class="form-group">
                        <label for="name">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                               id="name" name="name" placeholder="Enter full name" 
                               value="{{ old('name') }}" required autofocus>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address <span class="text-danger">*</span></label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" 
                               id="email" name="email" placeholder="Enter email address" 
                               value="{{ old('email') }}" required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="text" class="form-control @error('phone') is-invalid @enderror" 
                               id="phone" name="phone" placeholder="Enter phone number" 
                               value="{{ old('phone') }}">
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="role">Assign Role <span class="text-danger">*</span></label>
                        <select class="form-control @error('role') is-invalid @enderror" 
                                id="role" name="role" required>
                            <option value="">-- Select Role --</option>
                            @foreach($roles as $role)
                                @if($role->name !== 'Client')
                                <option value="{{ $role->name }}" {{ old('role') == $role->name ? 'selected' : '' }}>
                                    {{ $role->name }}
                                </option>
                                @endif
                            @endforeach
                        </select>
                        <small class="form-text text-muted">
                            Clients are created through self-registration. Only staff roles can be created here.
                        </small>
                        @error('role')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="password">Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" 
                               id="password" name="password" placeholder="Enter password" required>
                        <small class="form-text text-muted">Minimum 8 characters</small>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="password_confirmation">Confirm Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" 
                               id="password_confirmation" name="password_confirmation" 
                               placeholder="Confirm password" required>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('admin.users.index') }}" class="btn btn-light">
                            <i class="mdi mdi-arrow-left"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="mdi mdi-check"></i> Create User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Role Information</h4>
                
                <div class="role-info">
                    <h6 class="text-danger"><i class="mdi mdi-shield-star"></i> Super Admin</h6>
                    <p class="text-muted small">Full system access. Can manage everything including other admins.</p>
                </div>

                <div class="role-info mt-3">
                    <h6 class="text-warning"><i class="mdi mdi-shield-account"></i> Administrator</h6>
                    <p class="text-muted small">Can create users and manage system except Super Admin functions.</p>
                </div>

                <div class="role-info mt-3">
                    <h6 class="text-primary"><i class="mdi mdi-briefcase"></i> Project Manager</h6>
                    <p class="text-muted small">Reviews properties, creates projects, assigns inspectors, manages scope of work.</p>
                </div>

                <div class="role-info mt-3">
                    <h6 class="text-info"><i class="mdi mdi-clipboard-check"></i> Inspector</h6>
                    <p class="text-muted small">Conducts inspections, creates reports. Paid hourly.</p>
                </div>

                <div class="role-info mt-3">
                    <h6 class="text-secondary"><i class="mdi mdi-wrench"></i> Technician</h6>
                    <p class="text-muted small">Executes assigned tasks, logs work. Paid hourly.</p>
                </div>

                <div class="role-info mt-3">
                    <h6 class="text-success"><i class="mdi mdi-cash-multiple"></i> Finance Officer</h6>
                    <p class="text-muted small">Manages subscriptions, invoices, and staff payments.</p>
                </div>
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
