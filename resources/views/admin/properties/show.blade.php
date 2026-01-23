@extends('admin.layout')

@section('title', 'Property Details')

@section('content')
<div class="content-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h3 class="mb-1">{{ $property->property_name }}</h3>
                    <p class="text-muted mb-0">
                        <code>{{ $property->property_code }}</code> • 
                        Submitted {{ $property->created_at->format('M d, Y') }}
                    </p>
                </div>
                <div>
                    <a href="{{ $backUrl ?? route('properties.index') }}" class="btn btn-secondary btn-sm">
                        <i class="mdi mdi-arrow-left me-2"></i>Back to List
                    </a>
                </div>
            </div>

            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            <!-- Status Card -->
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h5 class="mb-2">Inspection Status</h5>
                            @php
                                $paidInspection = $property->inspections()
                                    ->where('inspection_fee_status', 'paid')
                                    ->first();
                            @endphp
                            
                            @if($paidInspection)
                                <span class="badge badge-success fs-6">
                                    <i class="mdi mdi-check-circle"></i> Scheduled & Paid
                                </span>
                                <p class="text-muted mt-2 mb-0">
                                    Paid on {{ $paidInspection->inspection_fee_paid_at->format('M d, Y') }}<br>
                                    Scheduled for {{ $paidInspection->scheduled_date->format('M d, Y \a\t g:i A') }}
                                </p>
                                @if($paidInspection->inspector_id)
                                    @php
                                        $inspector = \App\Models\User::find($paidInspection->inspector_id);
                                    @endphp
                                    <p class="text-success mt-2 mb-0">
                                        <i class="mdi mdi-account-check"></i> Inspector: <strong>{{ $inspector->name }}</strong>
                                    </p>
                                    @if($property->project_manager_id)
                                        @php
                                            $pm = \App\Models\User::find($property->project_manager_id);
                                        @endphp
                                        <p class="text-success mt-1 mb-0">
                                            <i class="mdi mdi-account-supervisor"></i> Project Manager: <strong>{{ $pm->name }}</strong>
                                        </p>
                                    @endif
                                @else
                                    <p class="text-warning mt-2 mb-0">
                                        <i class="mdi mdi-alert"></i> Staff not assigned yet
                                    </p>
                                @endif
                            @else
                                <span class="badge badge-warning fs-6">
                                    <i class="mdi mdi-alert-circle-outline"></i> Not Scheduled
                                </span>
                                <p class="text-muted mt-2 mb-0">
                                    Awaiting client to schedule and pay for inspection
                                </p>
                            @endif
                        </div>
                        <div class="col-md-6 text-end">
                            @if($paidInspection && !$paidInspection->inspector_id && Auth::user()->hasRole(['Super Admin', 'Administrator']))
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#assignInspectorModal">
                                <i class="mdi mdi-account-multiple-plus me-2"></i>Assign Staff
                            </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Property Information -->
                <div class="col-md-8">
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title mb-3">
                                <i class="mdi mdi-home text-primary me-2"></i>Property Information
                            </h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted">Property Name</label>
                                    <p class="mb-0"><strong>{{ $property->property_name }}</strong></p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted">Property Code</label>
                                    <p class="mb-0"><code>{{ $property->property_code }}</code></p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted">Property Brand</label>
                                    <p class="mb-0">{{ $property->property_brand ?? 'N/A' }}</p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted">Property Type</label>
                                    <p class="mb-0">
                                        <span class="badge badge-info">{{ ucfirst(str_replace('_', ' ', $property->type)) }}</span>
                                    </p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted">Year Built</label>
                                    <p class="mb-0">{{ $property->year_built ?? 'N/A' }}</p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted">Occupied By</label>
                                    <p class="mb-0">{{ ucfirst($property->occupied_by ?? 'N/A') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Location -->
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title mb-3">
                                <i class="mdi mdi-map-marker text-danger me-2"></i>Location
                            </h5>
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label class="text-muted">Address</label>
                                    <p class="mb-0">{{ $property->property_address }}</p>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="text-muted">City</label>
                                    <p class="mb-0">{{ $property->city }}</p>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="text-muted">Province/State</label>
                                    <p class="mb-0">{{ $property->province }}</p>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="text-muted">Postal Code</label>
                                    <p class="mb-0">{{ $property->postal_code }}</p>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label class="text-muted">Country</label>
                                    <p class="mb-0">{{ $property->country }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Square Footage -->
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title mb-3">
                                <i class="mdi mdi-floor-plan text-info me-2"></i>Square Footage
                            </h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted">Interior</label>
                                    <p class="mb-0">{{ number_format($property->square_footage_interior, 2) }} sq ft</p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted">Green Space</label>
                                    <p class="mb-0">{{ number_format($property->square_footage_green, 2) }} sq ft</p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted">Paved</label>
                                    <p class="mb-0">{{ number_format($property->square_footage_paved, 2) }} sq ft</p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted">Extra</label>
                                    <p class="mb-0">{{ number_format($property->square_footage_extra, 2) }} sq ft</p>
                                </div>
                                <div class="col-md-12">
                                    <label class="text-muted">Total Square Footage</label>
                                    <p class="mb-0"><strong>{{ number_format($property->total_square_footage, 2) }} sq ft</strong></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Property Photos -->
                    @if($property->property_photos && count($property->property_photos) > 0)
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title mb-3">
                                <i class="mdi mdi-image-multiple text-success me-2"></i>Property Photos ({{ count($property->property_photos) }})
                            </h5>
                            <div class="row">
                                @foreach($property->property_photos as $photo)
                                <div class="col-md-4 mb-3">
                                    <a href="{{ asset('storage/' . $photo) }}" target="_blank">
                                        <img src="{{ asset('storage/' . $photo) }}" class="img-fluid rounded" alt="Property Photo">
                                    </a>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Blueprint -->
                    @if($property->blueprint_file)
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title mb-3">
                                <i class="mdi mdi-file-document text-warning me-2"></i>Blueprint
                            </h5>
                            <a href="{{ asset('storage/' . $property->blueprint_file) }}" target="_blank" class="btn btn-primary">
                                <i class="mdi mdi-download me-2"></i>Download Blueprint
                            </a>
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Owner Information -->
                <div class="col-md-4">
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title mb-3">
                                <i class="mdi mdi-account text-primary me-2"></i>Owner Information
                            </h5>
                            <div class="mb-3">
                                <label class="text-muted">Name</label>
                                <p class="mb-0"><strong>{{ $property->owner_first_name }}</strong></p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted">Email</label>
                                <p class="mb-0">{{ $property->owner_email }}</p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted">Phone</label>
                                <p class="mb-0">{{ $property->owner_phone }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Occupancy Details -->
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title mb-3">
                                <i class="mdi mdi-home-account text-info me-2"></i>Occupancy Details
                            </h5>
                            <div class="mb-3">
                                <label class="text-muted">Has Pets</label>
                                <p class="mb-0">
                                    @if($property->has_pets)
                                    <span class="badge badge-success">Yes</span>
                                    @else
                                    <span class="badge badge-secondary">No</span>
                                    @endif
                                </p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted">Has Children</label>
                                <p class="mb-0">
                                    @if($property->has_kids)
                                    <span class="badge badge-success">Yes</span>
                                    @else
                                    <span class="badge badge-secondary">No</span>
                                    @endif
                                </p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted">Has Tenants</label>
                                <p class="mb-0">
                                    @if($property->has_tenants)
                                    <span class="badge badge-success">Yes</span>
                                    @else
                                    <span class="badge badge-secondary">No</span>
                                    @endif
                                </p>
                            </div>
                            @if($property->has_tenants)
                            <div class="mb-3">
                                <label class="text-muted">Number of Units</label>
                                <p class="mb-0"><strong>{{ $property->number_of_units }}</strong></p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted">Tenant Password</label>
                                <p class="mb-0"><code>{{ $property->tenant_common_password }}</code></p>
                            </div>
                            @endif
                            @if($property->personality)
                            <div class="mb-3">
                                <label class="text-muted">Property Personality</label>
                                <p class="mb-0">{{ ucfirst(str_replace('-', ' ', $property->personality)) }}</p>
                            </div>
                            @endif
                        </div>
                    </div>

                    @if($property->admin_first_name)
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title mb-3">
                                <i class="mdi mdi-account-tie text-warning me-2"></i>Property Administrator
                            </h5>
                            <div class="mb-3">
                                <label class="text-muted">Name</label>
                                <p class="mb-0">{{ $property->admin_first_name }} {{ $property->admin_last_name }}</p>
                            </div>
                            @if($property->admin_email)
                            <div class="mb-3">
                                <label class="text-muted">Email</label>
                                <p class="mb-0">{{ $property->admin_email }}</p>
                            </div>
                            @endif
                            @if($property->admin_phone)
                            <div class="mb-3">
                                <label class="text-muted">Phone</label>
                                <p class="mb-0">{{ $property->admin_phone }}</p>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Rejection Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject Property</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('properties.update', $property->id) }}" method="POST">
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

<!-- Assign Project Manager & Inspector Modal -->
<div class="modal fade" id="assignInspectorModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Assign Project Manager & Inspector</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('properties.assign', $property->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="mdi mdi-information"></i> 
                        The client has paid for this inspection. Please assign a Project Manager and Inspector.
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="project_manager_id">Project Manager <span class="text-danger">*</span></label>
                                <select name="project_manager_id" id="project_manager_id" class="form-control" required>
                                    <option value="">-- Select Project Manager --</option>
                                    @php
                                        $projectManagers = \App\Models\User::role('Project Manager')->get();
                                    @endphp
                                    @foreach($projectManagers as $pm)
                                        <option value="{{ $pm->id }}">{{ $pm->name }} ({{ $pm->email }})</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">PM supervises the inspection process</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="inspector_id">Inspector <span class="text-danger">*</span></label>
                                <select name="inspector_id" id="inspector_id" class="form-control" required>
                                    <option value="">-- Choose Inspector --</option>
                                    @php
                                        $inspectors = \App\Models\User::role('Inspector')->get();
                                    @endphp
                                    @foreach($inspectors as $inspector)
                                        <option value="{{ $inspector->id }}">{{ $inspector->name }} ({{ $inspector->email }})</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Conducts the assessment</small>
                            </div>
                        </div>
                    </div>

                    @php
                        $paidInspection = $property->inspections()
                            ->where('inspection_fee_status', 'paid')
                            ->first();
                    @endphp
                    @if($paidInspection)
                    <div class="bg-light p-3 rounded">
                        <p class="mb-2"><strong>Inspection Details:</strong></p>
                        <p class="mb-1"><i class="mdi mdi-calendar"></i> Scheduled: {{ $paidInspection->scheduled_date->format('M d, Y \a\t g:i A') }}</p>
                        <p class="mb-1"><i class="mdi mdi-cash"></i> Fee Paid: ${{ number_format($paidInspection->inspection_fee_amount, 2) }}</p>
                        @if($paidInspection->notes)
                        <p class="mb-0"><i class="mdi mdi-note-text"></i> Notes: {{ $paidInspection->notes }}</p>
                        @endif
                    </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Assign Staff</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
