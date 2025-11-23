@extends('client.layout')

@section('title', 'My Tenants')

@section('header', 'My Tenants')

@section('breadcrumbs')
<li class="breadcrumb-item active" aria-current="page">Tenants</li>
@endsection

@section('content')
<!-- Property Selection Card -->
@if($properties->count() > 0)
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-primary">
            <div class="card-body">
                <h5 class="card-title mb-3">
                    <i class="mdi mdi-home-search text-primary me-2"></i>Select Property to Manage Tenants
                </h5>
                <div class="row align-items-end">
                    <div class="col-md-6">
                        <label for="property_selector" class="form-label">Choose a Property:</label>
                        <select id="property_selector" class="form-control form-control-lg">
                            <option value="">-- Select a Property --</option>
                            @foreach($properties as $property)
                            <option value="{{ $property->id }}" 
                                    data-code="{{ $property->property_code }}"
                                    data-password="{{ $property->tenant_common_password }}"
                                    data-name="{{ $property->property_name }}"
                                    {{ request('property_id') == $property->id ? 'selected' : '' }}>
                                {{ $property->property_name }} ({{ $property->property_code }})
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <div id="property-credentials" class="alert alert-info mb-0" style="display: none;">
                            <h6 class="mb-2"><i class="mdi mdi-key me-2"></i>Tenant Login Credentials</h6>
                            <div class="row">
                                <div class="col-6">
                                    <strong>Property Code:</strong>
                                    <div class="bg-white p-2 rounded mt-1">
                                        <code id="display-code" class="text-primary fs-5">-</code>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <strong>Shared Password:</strong>
                                    <div class="bg-white p-2 rounded mt-1">
                                        <code id="display-password" class="text-danger fs-5">-</code>
                                        <button type="button" class="btn btn-sm btn-outline-secondary ms-2" onclick="copyPassword()" title="Copy Password">
                                            <i class="mdi mdi-content-copy"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <small class="text-muted d-block mt-2">
                                <i class="mdi mdi-information"></i> All tenants in <span id="property-name-display">this property</span> will use these credentials with their tenant number (e.g., <span id="example-login">CODE-1</span>, <span id="example-login2">CODE-2</span>)
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="card-title mb-0">Tenant List</h4>
                        <p class="text-muted mb-0" id="tenant-subtitle">
                            @if(request('property_id'))
                                Showing tenants for selected property
                            @else
                                Select a property above to manage tenants
                            @endif
                        </p>
                    </div>
                    <div>
                        @if($properties->count() > 0)
                        <a href="{{ route('client.tenants.export', request()->all()) }}" class="btn btn-success btn-sm me-2">
                            <i class="mdi mdi-file-excel me-2"></i>Export to Excel
                        </a>
                        <a href="#" id="add-tenant-btn" class="btn btn-primary btn-sm" style="display: none;">
                            <i class="mdi mdi-plus me-2"></i>Add Tenant
                        </a>
                        @else
                        <button class="btn btn-primary btn-sm" disabled title="You need an approved property with tenants enabled">
                            <i class="mdi mdi-plus me-2"></i>Add Tenant
                        </button>
                        @endif
                    </div>
                </div>

                <!-- Status Filter -->
                @if(request('property_id'))
                <form method="GET" action="{{ route('client.tenants.index') }}" class="mb-4">
                    <input type="hidden" name="property_id" value="{{ request('property_id') }}">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label for="status" class="form-label">Filter by Status</label>
                            <select name="status" id="status" class="form-control">
                                <option value="">All Statuses</option>
                                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                <option value="moved_out" {{ request('status') == 'moved_out' ? 'selected' : '' }}>Moved Out</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="mdi mdi-filter me-2"></i>Apply
                            </button>
                        </div>
                    </div>
                </form>
                @endif

                @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="mdi mdi-check-circle me-2"></i>
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                @endif

                @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="mdi mdi-alert-circle me-2"></i>
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                @endif

                <div class="table-responsive">
                    <table id="tenantsTable" class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th>Tenant Login</th>
                                <th>Name</th>
                                <th>Property</th>
                                <th>Unit</th>
                                <th>Contact</th>
                                <th>Move-In Date</th>
                                <th>Status</th>
                                <th>Emergency Reports</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tenants as $tenant)
                            <tr>
                                <td>
                                    <code class="bg-light px-2 py-1">{{ $tenant->tenant_login }}</code>
                                </td>
                                <td>
                                    <strong>{{ $tenant->full_name }}</strong>
                                    @if($tenant->can_report_emergency)
                                    <br><small class="text-success">
                                        <i class="mdi mdi-shield-check"></i> Can Report Emergency
                                    </small>
                                    @endif
                                </td>
                                <td>
                                    <div>{{ $tenant->property->property_name }}</div>
                                    <small class="text-muted">{{ $tenant->property->property_code }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-primary">Unit {{ $tenant->unit_number }}</span>
                                </td>
                                <td>
                                    @if($tenant->email)
                                    <div><i class="mdi mdi-email"></i> {{ $tenant->email }}</div>
                                    @endif
                                    @if($tenant->phone)
                                    <div><i class="mdi mdi-phone"></i> {{ $tenant->phone }}</div>
                                    @endif
                                    @if(!$tenant->email && !$tenant->phone)
                                    <span class="text-muted">No contact</span>
                                    @endif
                                </td>
                                <td>{{ $tenant->move_in_date->format('M d, Y') }}</td>
                                <td>
                                    @if($tenant->status === 'active')
                                    <span class="badge bg-success">Active</span>
                                    @elseif($tenant->status === 'inactive')
                                    <span class="badge bg-warning">Inactive</span>
                                    @else
                                    <span class="badge bg-secondary">Moved Out</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-info">{{ $tenant->emergencyReports->count() }}</span>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('client.tenants.show', $tenant->id) }}" 
                                           class="btn btn-sm btn-info" title="View Details">
                                            <i class="mdi mdi-eye"></i>
                                        </a>
                                        <a href="{{ route('client.tenants.edit', $tenant->id) }}" 
                                           class="btn btn-sm btn-warning" title="Edit">
                                            <i class="mdi mdi-pencil"></i>
                                        </a>
                                        <form action="{{ route('client.tenants.destroy', $tenant->id) }}" 
                                              method="POST" 
                                              onsubmit="return confirm('Are you sure you want to remove this tenant?');"
                                              class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                <i class="mdi mdi-delete"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center py-5">
                                    <div class="mb-3">
                                        <i class="mdi mdi-account-group-outline" style="font-size: 4rem; color: #6c757d;"></i>
                                    </div>
                                    <h5 class="text-muted">No Tenants Yet</h5>
                                    <p class="text-muted mb-3">You haven't added any tenants to your properties.</p>
                                    @if($properties->isEmpty())
                                    <p class="text-muted small">
                                        <strong>How it works:</strong><br>
                                        1. Add a property with "Has Tenants" enabled<br>
                                        2. Once approved, you can add tenants to that property<br>
                                        3. Each tenant gets a unique login (e.g., SUN-1, SUN-2)<br>
                                        4. All tenants share one password per property<br>
                                        5. Tenants can report emergencies and view property info
                                    </p>
                                    @endif
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                @if($tenants->hasPages())
                <div class="mt-3">
                    {{ $tenants->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Info Cards -->
<div class="row mt-4">
    <div class="col-md-4">
        <div class="card bg-light">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="mdi mdi-information text-primary me-2"></i>Tenant Login System
                </h5>
                <p class="mb-2">Each property has a unique code and password for all tenants:</p>
                <ul class="mb-0">
                    <li><strong>Property Code:</strong> From your property brand (e.g., SUN)</li>
                    <li><strong>Common Password:</strong> Auto-generated per property</li>
                    <li><strong>Tenant 1:</strong> SUN-1</li>
                    <li><strong>Tenant 2:</strong> SUN-2</li>
                    <li>And so on...</li>
                </ul>
                <small class="text-muted mt-2 d-block">
                    <i class="mdi mdi-lock"></i> All tenants in one property share the same password
                </small>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card bg-light">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="mdi mdi-shield-check text-success me-2"></i>Tenant Features
                </h5>
                <p class="mb-2">Tenants can:</p>
                <ul class="mb-0">
                    <li>View property information</li>
                    <li>Report emergencies with photos</li>
                    <li>Pin issues on floor plans</li>
                    <li>Track emergency report status</li>
                    <li>View maintenance schedules</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card bg-light">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="mdi mdi-file-excel text-success me-2"></i>Export & Share
                </h5>
                <p class="mb-2">Export tenant information:</p>
                <ul class="mb-0">
                    <li>Export all tenants to Excel/CSV</li>
                    <li>Filter by property or status</li>
                    <li>Share credentials with tenants</li>
                    <li>Track login activity</li>
                    <li>Manage multiple properties</li>
                </ul>
                <small class="text-muted mt-2 d-block">
                    <i class="mdi mdi-information"></i> Each tenant is linked to a specific property
                </small>
            </div>
        </div>
    </div>
</div>

<style>
/* Fix for dark input fields */
.form-control,
.form-control:focus {
    background-color: #ffffff !important;
    color: #000000 !important;
    border: 1px solid #ced4da !important;
}

.table {
    color: #212529 !important;
}

.card-body {
    color: #212529 !important;
}

.card-title {
    color: #212529 !important;
}

.bg-light {
    background-color: #f8f9fa !important;
}
</style>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Property selector change event
    $('#property_selector').on('change', function() {
        const selectedOption = $(this).find('option:selected');
        const propertyId = $(this).val();
        
        if (propertyId) {
            const propertyCode = selectedOption.data('code');
            const propertyPassword = selectedOption.data('password');
            const propertyName = selectedOption.data('name');
            
            // Show credentials
            $('#display-code').text(propertyCode);
            $('#display-password').text(propertyPassword);
            $('#property-name-display').text(propertyName);
            $('#example-login').text(propertyCode + '-1');
            $('#example-login2').text(propertyCode + '-2');
            $('#property-credentials').slideDown();
            
            // Show add tenant button and update link
            $('#add-tenant-btn').show().attr('href', '{{ route("client.tenants.create") }}?property_id=' + propertyId);
            
            // Update subtitle
            $('#tenant-subtitle').text('Showing tenants for ' + propertyName);
            
            // Redirect to filter by property
            window.location.href = '{{ route("client.tenants.index") }}?property_id=' + propertyId;
        } else {
            $('#property-credentials').slideUp();
            $('#add-tenant-btn').hide();
            $('#tenant-subtitle').text('Select a property above to manage tenants');
        }
    });
    
    // Trigger on page load if property is already selected
    @if(request('property_id'))
        $('#property_selector').trigger('change');
    @endif
    
    // Initialize DataTables
    @if($tenants->count() > 0)
    $('#tenantsTable').DataTable({
        "pageLength": 10,
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
        "order": [[0, "asc"]],
        "language": {
            "search": "Search tenants:",
            "lengthMenu": "Show _MENU_ tenants per page",
            "info": "Showing _START_ to _END_ of _TOTAL_ tenants",
            "infoEmpty": "No tenants available",
            "infoFiltered": "(filtered from _MAX_ total tenants)",
            "zeroRecords": "No matching tenants found",
            "paginate": {
                "first": "First",
                "last": "Last",
                "next": "Next",
                "previous": "Previous"
            }
        },
        "columnDefs": [
            { "orderable": false, "targets": [7, 8] } // Disable sorting for Emergency Reports and Actions columns
        ]
    });
    @endif
});

// Copy password function
function copyPassword() {
    const password = document.getElementById('display-password').textContent;
    
    // Create temporary textarea
    const textarea = document.createElement('textarea');
    textarea.value = password;
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    
    // Select and copy
    textarea.select();
    document.execCommand('copy');
    
    // Remove textarea
    document.body.removeChild(textarea);
    
    // Show feedback
    const button = event.target.closest('button');
    const originalHTML = button.innerHTML;
    button.innerHTML = '<i class="mdi mdi-check"></i>';
    button.classList.add('btn-success');
    button.classList.remove('btn-outline-secondary');
    
    setTimeout(() => {
        button.innerHTML = originalHTML;
        button.classList.remove('btn-success');
        button.classList.add('btn-outline-secondary');
    }, 2000);
}
</script>
@endpush
