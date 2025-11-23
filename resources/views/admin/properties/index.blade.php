@extends('admin.layout')

@section('title', 'Property Approvals')

@section('content')
<div class="content-wrapper">
    <div class="row">
        <div class="col-lg-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h4 class="card-title mb-0">Property Management</h4>
                            <p class="text-muted small mb-0">Review and approve client property submissions</p>
                        </div>
                    </div>

                    @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    @endif

                    @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    @endif

                    <!-- Filter Tabs -->
                    <ul class="nav nav-pills mb-3" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link {{ request('status') == 'pending_approval' || !request('status') ? 'active' : '' }}" 
                               href="{{ route('properties.index', ['status' => 'pending_approval']) }}">
                                Pending Approval 
                                <span class="badge bg-warning ms-1">{{ \App\Models\Property::where('status', 'pending_approval')->count() }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request('status') == 'approved' ? 'active' : '' }}" 
                               href="{{ route('properties.index', ['status' => 'approved']) }}">
                                Approved
                                <span class="badge bg-success ms-1">{{ \App\Models\Property::where('status', 'approved')->count() }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request('status') == 'awaiting_inspection' ? 'active' : '' }}" 
                               href="{{ route('properties.index', ['status' => 'awaiting_inspection']) }}">
                                Awaiting Inspection
                                <span class="badge bg-info ms-1">{{ \App\Models\Property::where('status', 'awaiting_inspection')->count() }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request('status') == 'rejected' ? 'active' : '' }}" 
                               href="{{ route('properties.index', ['status' => 'rejected']) }}">
                                Rejected
                                <span class="badge bg-danger ms-1">{{ \App\Models\Property::where('status', 'rejected')->count() }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request('status') == '' && request()->has('status') ? 'active' : '' }}" 
                               href="{{ route('properties.index') }}">
                                All Properties
                            </a>
                        </li>
                    </ul>

                    <!-- Search Form -->
                    <form method="GET" action="{{ route('properties.index') }}" class="mb-3">
                        <input type="hidden" name="status" value="{{ request('status') }}">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Search by property name, code, city, or address..." 
                                   value="{{ request('search') }}">
                            <button type="submit" class="btn btn-primary">
                                <i class="mdi mdi-magnify"></i> Search
                            </button>
                            @if(request('search'))
                            <a href="{{ route('properties.index', ['status' => request('status')]) }}" class="btn btn-secondary">
                                <i class="mdi mdi-close"></i> Clear
                            </a>
                            @endif
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table id="propertiesTable" class="table table-hover table-striped">
                            <thead>
                                <tr>
                                    <th>Property Code</th>
                                    <th>Property Name</th>
                                    <th>Owner</th>
                                    <th>Location</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Submitted</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($properties as $property)
                                <tr>
                                    <td><code>{{ $property->property_code }}</code></td>
                                    <td>
                                        <strong>{{ $property->property_name }}</strong>
                                        @if($property->property_brand)
                                        <br><small class="text-muted">Brand: {{ $property->property_brand }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <div>{{ $property->owner_first_name }}</div>
                                        <small class="text-muted">{{ $property->owner_email }}</small>
                                    </td>
                                    <td>
                                        {{ $property->city }}, {{ $property->province }}<br>
                                        <small class="text-muted">{{ $property->country }}</small>
                                    </td>
                                    <td>
                                        <span class="badge badge-info">
                                            {{ ucfirst(str_replace('_', ' ', $property->type)) }}
                                        </span>
                                        @if($property->has_tenants)
                                        <br><span class="badge badge-primary mt-1">
                                            <i class="mdi mdi-account-group"></i> Multi-Tenant
                                        </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($property->status === 'pending_approval')
                                        <span class="badge badge-warning">Pending Approval</span>
                                        @elseif($property->status === 'approved')
                                        <span class="badge badge-success">Approved</span>
                                        <br><small class="text-muted">{{ $property->approved_at?->format('M d, Y') }}</small>
                                        @if(!$property->project_manager_id || !$property->inspector_id)
                                        <br><small class="text-danger"><i class="mdi mdi-alert"></i> Not Assigned</small>
                                        @endif
                                        @elseif($property->status === 'awaiting_inspection')
                                        <span class="badge badge-info">Awaiting Inspection</span>
                                        <br><small class="text-muted">Assigned {{ $property->assigned_at?->format('M d, Y') }}</small>
                                        @elseif($property->status === 'rejected')
                                        <span class="badge badge-danger">Rejected</span>
                                        @endif
                                    </td>
                                    <td>{{ $property->created_at->format('M d, Y') }}</td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('properties.show', $property->id) }}" 
                                               class="btn btn-sm btn-info" title="View Details">
                                                <i class="mdi mdi-eye"></i>
                                            </a>
                                            
                                            @if($property->status === 'pending_approval')
                                            <button type="button" class="btn btn-sm btn-success" 
                                                    onclick="approveProperty({{ $property->id }})" 
                                                    title="Approve">
                                                <i class="mdi mdi-check"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger" 
                                                    onclick="rejectProperty({{ $property->id }})" 
                                                    title="Reject">
                                                <i class="mdi mdi-close"></i>
                                            </button>
                                            @endif
                                            
                                            @if($property->status === 'approved' && (!$property->project_manager_id || !$property->inspector_id))
                                            <button type="button" class="btn btn-sm btn-primary" 
                                                    onclick="assignStaff({{ $property->id }})" 
                                                    title="Assign Staff">
                                                <i class="mdi mdi-account-multiple-plus"></i>
                                            </button>
                                            @endif
                                            
                                            <form action="{{ route('properties.destroy', $property->id) }}" 
                                                  method="POST" 
                                                  onsubmit="return confirm('Are you sure you want to delete this property?');"
                                                  class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-dark" title="Delete">
                                                    <i class="mdi mdi-delete"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <i class="mdi mdi-home-outline" style="font-size: 3rem; color: #ddd;"></i>
                                        <p class="text-muted mt-2">No properties found</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    @if($properties->hasPages())
                    <div class="mt-3">
                        {{ $properties->links() }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Approval Form (Hidden) -->
<form id="approveForm" method="POST" style="display: none;">
    @csrf
    @method('PUT')
    <input type="hidden" name="status" value="approved">
</form>

<!-- Rejection Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject Property</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="rejectForm" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="status" value="rejected">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="rejection_reason">Reason for Rejection <span class="text-danger">*</span></label>
                        <textarea name="rejection_reason" id="rejection_reason" 
                                  class="form-control" rows="4" required
                                  placeholder="Please provide a reason for rejecting this property..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject Property</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Assign Staff Modal -->
<div class="modal fade" id="assignModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="background-color: #ffffff !important;">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="mdi mdi-account-multiple-plus me-2"></i>Assign Project Manager & Inspector
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="assignForm" method="POST">
                @csrf
                <div class="modal-body" style="background-color: #ffffff !important; color: #000000 !important;">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="project_manager_id" style="color: #000000 !important;">Project Manager <span class="text-danger">*</span></label>
                                <select name="project_manager_id" id="project_manager_id" class="form-control" required style="background-color: #ffffff !important; color: #000000 !important;">
                                    <option value="">-- Select Project Manager --</option>
                                    @foreach(\App\Models\User::role('Project Manager')->get() as $pm)
                                    <option value="{{ $pm->id }}">{{ $pm->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="inspector_id" style="color: #000000 !important;">Inspector <span class="text-danger">*</span></label>
                                <select name="inspector_id" id="inspector_id" class="form-control" required style="background-color: #ffffff !important; color: #000000 !important;">
                                    <option value="">-- Select Inspector --</option>
                                    @foreach(\App\Models\User::role('Inspector')->get() as $inspector)
                                    <option value="{{ $inspector->id }}">{{ $inspector->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="inspection_scheduled_at" style="color: #000000 !important;">Schedule Inspection (Optional)</label>
                        <input type="datetime-local" name="inspection_scheduled_at" id="inspection_scheduled_at" 
                               class="form-control" min="{{ date('Y-m-d\TH:i') }}" style="background-color: #ffffff !important; color: #000000 !important;">
                        <small class="text-muted" style="color: #666666 !important;">Leave empty to schedule later</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="mdi mdi-check me-2"></i>Assign Staff
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function approveProperty(propertyId) {
    if (confirm('Are you sure you want to approve this property?')) {
        const form = document.getElementById('approveForm');
        form.action = '/properties/' + propertyId;
        form.submit();
    }
}

function rejectProperty(propertyId) {
    const form = document.getElementById('rejectForm');
    form.action = '/properties/' + propertyId;
    const modal = new bootstrap.Modal(document.getElementById('rejectModal'));
    modal.show();
}

function assignStaff(propertyId) {
    const form = document.getElementById('assignForm');
    form.action = '/properties/' + propertyId + '/assign';
    const modal = new bootstrap.Modal(document.getElementById('assignModal'));
    modal.show();
}

$(document).ready(function() {
    @if($properties->count() > 0)
    $('#propertiesTable').DataTable({
        "pageLength": 15,
        "lengthMenu": [[15, 25, 50, -1], [15, 25, 50, "All"]],
        "order": [[6, "desc"]],
        "language": {
            "search": "Search:",
            "lengthMenu": "Show _MENU_ properties",
            "info": "Showing _START_ to _END_ of _TOTAL_ properties"
        },
        "columnDefs": [
            { "orderable": false, "targets": [7] }
        ]
    });
    @endif
});
</script>
@endpush
