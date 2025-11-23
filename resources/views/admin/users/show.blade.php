@extends('admin.layout')

@section('title', 'User Details')

@section('header', 'User Details')

@section('breadcrumbs')
<li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">User Management</a></li>
<li class="breadcrumb-item active" aria-current="page">User Details</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="card-title mb-0">User Information</h4>
                    <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-warning btn-sm">
                        <i class="mdi mdi-pencil"></i> Edit User
                    </a>
                </div>

                <div class="row mb-3">
                    <div class="col-md-3">
                        <img src="{{ $user->profile_photo_url }}" alt="{{ $user->name }}" class="rounded-circle" width="120" height="120">
                    </div>
                    <div class="col-md-9">
                        <h3>{{ $user->name }}</h3>
                        <p class="text-muted">{{ $user->email }}</p>
                        
                        <div class="mt-3">
                            <strong>Roles:</strong><br>
                            @forelse($user->roles as $role)
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
                            @empty
                                <span class="badge badge-light">No Role</span>
                            @endforelse
                        </div>
                    </div>
                </div>

                <hr>

                <div class="row">
                    <div class="col-md-6">
                        <p><strong>User ID:</strong> {{ $user->id }}</p>
                        <p><strong>Email Verified:</strong> 
                            @if($user->email_verified_at)
                                <span class="badge badge-success">Yes</span>
                                <small class="text-muted">({{ $user->email_verified_at->format('M d, Y') }})</small>
                            @else
                                <span class="badge badge-warning">No</span>
                            @endif
                        </p>
                        <p><strong>Account Created:</strong> {{ $user->created_at->format('M d, Y H:i') }}</p>
                        <p><strong>Last Updated:</strong> {{ $user->updated_at->format('M d, Y H:i') }}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Total Permissions:</strong> {{ $user->getAllPermissions()->count() }}</p>
                        <p><strong>Direct Permissions:</strong> {{ $user->permissions->count() }}</p>
                        <p><strong>Via Roles:</strong> {{ $user->getAllPermissions()->count() - $user->permissions->count() }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-body">
                <h4 class="card-title">Activity Log</h4>
                <p class="text-muted">Recent activity will be displayed here</p>
                {{-- Add activity log integration later --}}
            </div>
        </div>
    </div>

    <div class="col-md-4 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Permissions</h4>
                
                <div class="permissions-list" style="max-height: 400px; overflow-y: auto;">
                    @forelse($user->getAllPermissions()->groupBy(function($permission) {
                        return explode(' ', $permission->name)[1] ?? 'Other';
                    }) as $group => $permissions)
                        <div class="mb-3">
                            <h6 class="text-primary">{{ ucfirst($group) }}</h6>
                            @foreach($permissions as $permission)
                                <div class="form-check">
                                    <label class="form-check-label">
                                        <input type="checkbox" class="form-check-input" checked disabled>
                                        {{ $permission->name }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    @empty
                        <p class="text-muted">No permissions assigned</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-body">
                <h4 class="card-title">Quick Actions</h4>
                
                <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-warning btn-block mb-2">
                    <i class="mdi mdi-pencil"></i> Edit User
                </a>

                @if($user->id !== auth()->id())
                <form action="{{ route('admin.users.destroy', $user) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-block">
                        <i class="mdi mdi-delete"></i> Delete User
                    </button>
                </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
