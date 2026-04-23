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
                            @php
                                $latestInspection = $property->latestInspection;
                                $completedInspection = $property->latestCompletedInspection;
                                $hasScheduledInspection = $latestInspection && $latestInspection->inspection_fee_status === 'paid';
                            @endphp
                            <tr>
                                <td>
                                    <span class="text-dark fw-semibold">
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
                                    <span class="text-dark">
                                        {{ ucfirst(str_replace('_', ' ', $property->type)) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="text-muted">
                                        <i class="mdi mdi-map-marker me-1"></i>{{ $property->city }}, {{ $property->country }}
                                    </div>
                                </td>
                                <td>
                                    @if($completedInspection)
                                        <span class="badge bg-info d-inline-flex align-items-center">
                                            <i class="mdi mdi-file-check me-1"></i> Inspected • Report Ready
                                        </span>
                                        <div class="text-muted small mt-1">
                                            {{ optional($completedInspection->completed_date)->format('M d, Y') }}
                                        </div>
                                        @if(($completedInspection->work_payment_status ?? 'pending') === 'paid')
                                            <div class="small mt-1 text-success">
                                                <i class="mdi mdi-check-circle-outline me-1"></i>
                                                Work Payment: Paid
                                                @if(($completedInspection->payment_plan ?? '') === 'installment')
                                                    (50% Deposit Plan)
                                                @elseif(($completedInspection->work_payment_cadence ?? '') === 'per_visit')
                                                    (Per Visit)
                                                @elseif(($completedInspection->work_payment_cadence ?? '') === 'full')
                                                    (In Full)
                                                @else
                                                    ({{ ucfirst($completedInspection->work_payment_cadence ?? '') }})
                                                @endif
                                            </div>
                                        @else
                                            <div class="small mt-1 text-warning">
                                                <i class="mdi mdi-credit-card-clock-outline me-1"></i>
                                                Work Payment: Pending
                                            </div>
                                        @endif
                                    @elseif($hasScheduledInspection)
                                        <span class="badge bg-success d-inline-flex align-items-center">
                                            <i class="mdi mdi-check-circle me-1"></i> Scheduled & Paid
                                        </span>
                                        <div class="text-muted small mt-1">
                                            {{ optional($latestInspection->inspection_fee_paid_at)->format('M d, Y') }}
                                        </div>
                                    @else
                                        <span class="badge bg-warning d-inline-flex align-items-center">
                                            <i class="mdi mdi-alert-circle-outline me-1"></i> Not Scheduled
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if($property->property_photos && count($property->property_photos) > 0)
                                        <span class="text-dark">
                                            <i class="mdi mdi-image me-1 text-primary"></i>{{ count($property->property_photos) }} photos
                                        </span>
                                    @else
                                        <span class="text-muted small">No photos</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex gap-1 justify-content-end flex-nowrap">
                                        {{-- View --}}
                                        <a href="{{ route('client.properties.show', $property->id) }}"
                                           class="btn btn-sm btn-outline-info action-btn"
                                           title="View Details"
                                           data-bs-toggle="tooltip">
                                            <i class="mdi mdi-eye"></i>
                                        </a>

                                        @if(!$hasScheduledInspection)
                                            {{-- Schedule inspection --}}
                                            <a href="{{ route('client.inspections.schedule', $property->id) }}"
                                               class="btn btn-sm btn-success action-btn"
                                               title="Schedule Inspection & Pay"
                                               data-bs-toggle="tooltip">
                                                <i class="mdi mdi-calendar-check me-1"></i> Schedule
                                            </a>
                                        @elseif($completedInspection)
                                            {{-- Report --}}
                                            <a href="{{ route('client.inspections.report', $completedInspection->id) }}"
                                               class="btn btn-sm btn-primary action-btn"
                                               title="View Inspection Report"
                                               data-bs-toggle="tooltip">
                                                <i class="mdi mdi-file-document-outline me-1"></i> Report
                                            </a>

                                            @if(($completedInspection->work_payment_status ?? 'pending') !== 'paid')
                                                {{-- Payment dropdown --}}
                                                <div class="btn-group" role="group">
                                                    <button type="button"
                                                            class="btn btn-sm btn-success action-btn dropdown-toggle"
                                                            data-bs-toggle="dropdown"
                                                            aria-expanded="false"
                                                            title="Choose payment plan">
                                                        <i class="mdi mdi-credit-card me-1"></i> Pay
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                                        <li>
                                                            <a class="dropdown-item"
                                                               href="{{ route('client.inspections.work-payment', ['inspection' => $completedInspection->id, 'plan' => 'per_visit']) }}">
                                                                <i class="mdi mdi-calendar-sync me-2 text-success"></i>
                                                                <strong>Pay Per Visit</strong>
                                                                <div class="text-muted small">Split across {{ $completedInspection->bdc_visits_per_year ?? '—' }} visits</div>
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item"
                                                               href="{{ route('client.inspections.work-payment', ['inspection' => $completedInspection->id, 'plan' => 'installment']) }}">
                                                                <i class="mdi mdi-percent me-2 text-warning"></i>
                                                                <strong>Pay 50% Deposit</strong>
                                                                <div class="text-muted small">Pay half now and half later (works for single-visit jobs)</div>
                                                            </a>
                                                        </li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <a class="dropdown-item"
                                                               href="{{ route('client.inspections.work-payment', ['inspection' => $completedInspection->id, 'plan' => 'full']) }}">
                                                                <i class="mdi mdi-credit-card-check me-2 text-primary"></i>
                                                                <strong>Pay in Full</strong>
                                                                <div class="text-muted small">Single annual payment</div>
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            @else
                                                <button class="btn btn-sm btn-outline-success action-btn" disabled>
                                                    <i class="mdi mdi-check-circle me-1"></i> Paid
                                                </button>
                                            @endif
                                        @else
                                            {{-- Awaiting inspection completion --}}
                                            <button class="btn btn-sm btn-outline-success action-btn"
                                                    title="Inspection Scheduled"
                                                    data-bs-toggle="tooltip"
                                                    disabled>
                                                <i class="mdi mdi-check-circle me-1"></i> Scheduled
                                            </button>
                                        @endif

                                        {{-- Edit --}}
                                        <a href="{{ route('client.properties.edit', $property->id) }}"
                                           class="btn btn-sm btn-outline-warning action-btn"
                                           title="Edit Property"
                                           data-bs-toggle="tooltip">
                                            <i class="mdi mdi-pencil"></i>
                                        </a>

                                        {{-- Delete --}}
                                        <form action="{{ route('client.properties.destroy', $property->id) }}"
                                              method="POST"
                                              onsubmit="return confirm('Are you sure you want to delete this property?');"
                                              class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="btn btn-sm btn-outline-danger action-btn"
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

@push('styles')
<style>
    .action-btn {
        min-width: 2.1rem;
        height: 2rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding-left: .5rem;
        padding-right: .5rem;
        font-size: .8rem;
        white-space: nowrap;
    }
    .action-btn.dropdown-toggle {
        padding-right: .65rem;
    }
    /* Keep the dropdown menu above DataTables overflow */
    #propertiesTable .btn-group .dropdown-menu {
        z-index: 1050;
    }
</style>
@endpush

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
        ],
        "drawCallback": function() {
            // Re-init tooltips after DataTable redraws
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function(el) { return new bootstrap.Tooltip(el); });
        }
    });
    @endif
});
</script>
@endpush
