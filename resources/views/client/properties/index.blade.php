@extends('client.layout')

@section('title', 'My Properties')

@section('header', 'My Properties')

@section('breadcrumbs')
<li class="breadcrumb-item active" aria-current="page">Properties</li>
@endsection

@section('content')
<!-- Page Header -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            <div class="card-body text-white p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="fw-bold mb-1">My Properties</h3>
                        <p class="mb-0 opacity-75">Manage and track all your properties</p>
                    </div>
                    <a href="{{ route('client.properties.create') }}" class="btn btn-light btn-lg shadow-sm">
                        <i class="mdi mdi-plus me-2"></i> Add New Property
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                @if(request('success'))
                <div class="alert alert-success border-0 shadow-sm d-flex align-items-center mb-4" role="alert">
                    <div class="rounded-circle bg-success bg-opacity-25 p-2 me-3">
                        <i class="mdi mdi-check-circle text-success"></i>
                    </div>
                    <div>
                        <strong>Success!</strong> Inspection scheduled successfully! Your inspection fee has been processed. An inspector will be assigned to your property shortly.
                    </div>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                @endif
                
                @if($properties->count() > 0)
                <div class="table-responsive">
                    <table id="propertiesTable" class="table table-hover align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th class="border-0 text-uppercase small fw-semibold py-3">Property Code</th>
                                <th class="border-0 text-uppercase small fw-semibold py-3">Name</th>
                                <th class="border-0 text-uppercase small fw-semibold py-3">Type</th>
                                <th class="border-0 text-uppercase small fw-semibold py-3">Location</th>
                                <th class="border-0 text-uppercase small fw-semibold py-3">Status</th>
                                <th class="border-0 text-uppercase small fw-semibold py-3">Photos</th>
                                <th class="border-0 text-uppercase small fw-semibold py-3 text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($properties as $property)
                            <tr>
                                <td>
                                    <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 fw-semibold">
                                        {{ $property->property_code }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle bg-primary bg-opacity-10 p-2 me-3">
                                            <i class="mdi mdi-home text-primary"></i>
                                        </div>
                                        <strong>{{ $property->property_name }}</strong>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary">
                                        {{ ucfirst(str_replace('_', ' ', $property->type)) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="text-muted">
                                        <i class="mdi mdi-map-marker me-1"></i>{{ $property->city }}, {{ $property->country }}
                                    </div>
                                </td>
                                <td>
                                    @php
                                        $paidInspection = $property->inspections()
                                            ->where('inspection_fee_status', 'paid')
                                            ->first();
                                    @endphp
                                    
                                    @if($paidInspection)
                                        <span class="badge bg-success d-inline-flex align-items-center">
                                            <i class="mdi mdi-check-circle me-1"></i> Scheduled & Paid
                                        </span>
                                        <div class="text-muted small mt-1">
                                            {{ $paidInspection->inspection_fee_paid_at->format('M d, Y') }}
                                        </div>
                                    @else
                                        <span class="badge bg-warning d-inline-flex align-items-center">
                                            <i class="mdi mdi-alert-circle-outline me-1"></i> Not Scheduled
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if($property->property_photos && count($property->property_photos) > 0)
                                        <span class="badge bg-primary bg-opacity-10 text-primary">
                                            <i class="mdi mdi-image me-1"></i>{{ count($property->property_photos) }} photos
                                        </span>
                                    @else
                                        <span class="text-muted small">No photos</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex gap-2 justify-content-end">
                                        <a href="{{ route('client.properties.show', $property->id) }}" 
                                           class="btn btn-sm btn-outline-info" 
                                           title="View Details"
                                           data-bs-toggle="tooltip">
                                            <i class="mdi mdi-eye"></i>
                                        </a>
                                        
                                        @php
                                            // Check if inspection has been paid for this property
                                            $hasScheduledInspection = $property->inspections()
                                                ->where('inspection_fee_status', 'paid')
                                                ->exists();
                                        @endphp
                                        
                                        @if(!$hasScheduledInspection)
                                            {{-- Show Schedule button if inspection not yet paid --}}
                                            <a href="{{ route('client.inspections.schedule', $property->id) }}" 
                                               class="btn btn-sm btn-success" 
                                               title="Schedule Inspection & Pay"
                                               data-bs-toggle="tooltip">
                                                <i class="mdi mdi-calendar-check me-1"></i> Schedule
                                            </a>
                                        @else
                                            {{-- Inspection already scheduled and paid --}}
                                            <button class="btn btn-sm btn-outline-success" 
                                                    title="Inspection Scheduled"
                                                    data-bs-toggle="tooltip"
                                                    disabled>
                                                <i class="mdi mdi-check-circle me-1"></i> Scheduled
                                            </button>
                                        @endif
                                        
                                        <a href="{{ route('client.properties.edit', $property->id) }}" 
                                           class="btn btn-sm btn-outline-warning" 
                                           title="Edit Property"
                                           data-bs-toggle="tooltip">
                                            <i class="mdi mdi-pencil"></i>
                                        </a>
                                        
                                        <form action="{{ route('client.properties.destroy', $property->id) }}" 
                                              method="POST" 
                                              onsubmit="return confirm('Are you sure you want to delete this property?');"
                                              class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" 
                                                    class="btn btn-sm btn-outline-danger" 
                                                    title="Delete Property"
                                                    data-bs-toggle="tooltip">
                                                <i class="mdi mdi-delete"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                @else
                <div class="text-center py-5">
                    <div class="rounded-circle bg-light d-inline-flex p-5 mb-4">
                        <i class="mdi mdi-home-outline text-muted" style="font-size: 5rem;"></i>
                    </div>
                    <h4 class="fw-semibold mb-2">No properties found</h4>
                    <p class="text-muted mb-4">Start by adding your first property to get started</p>
                    <a href="{{ route('client.properties.create') }}" class="btn btn-success btn-lg shadow">
                        <i class="mdi mdi-home-plus me-2"></i> Add Your First Property
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    @if($properties->count() > 0)
    $('#propertiesTable').DataTable({
        "pageLength": 10,
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
        "order": [[0, "desc"]],
        "language": {
            "search": "Search properties:",
            "lengthMenu": "Show _MENU_ properties per page",
            "info": "Showing _START_ to _END_ of _TOTAL_ properties",
            "infoEmpty": "No properties available",
            "infoFiltered": "(filtered from _MAX_ total properties)",
            "zeroRecords": "No matching properties found",
            "paginate": {
                "first": "First",
                "last": "Last",
                "next": "Next",
                "previous": "Previous"
            }
        },
        "columnDefs": [
            { "orderable": false, "targets": [5, 6] } // Disable sorting for Photos and Actions columns
        ]
    });
    
    // Initialize Bootstrap tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    @endif
});
</script>
@endpush
