@extends('client.layout')

@section('title', 'Client Dashboard')

@section('content')
<!-- Welcome Header -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-lg" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            <div class="card-body text-white p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="fw-bold mb-2">Welcome back, {{ Auth::user()->name }}! 👋</h2>
                        <p class="mb-0 opacity-75 fs-5">Here's what's happening with your properties today</p>
                    </div>
                    <div>
                        <a href="{{ route('client.properties.create') }}" class="btn btn-light btn-lg shadow-sm">
                            <i class="mdi mdi-home-plus me-2"></i> Add Property
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Stats Cards --}}
<div class="row g-4 mb-4">
    <!-- Properties Card -->
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #28a745 !important;">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-2 fw-semibold text-uppercase small">My Properties</p>
                        <h2 class="fw-bold mb-0">{{ $propertiesCount }}</h2>
                    </div>
                    <div class="rounded-3 p-3" style="background-color: rgba(40, 167, 69, 0.1);">
                        <i class="mdi mdi-home-modern text-success" style="font-size: 2rem;"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="{{ route('client.properties.index') }}" class="text-success text-decoration-none fw-semibold small">
                        View all properties <i class="mdi mdi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Inspections Card -->
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #ffc107 !important;">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-2 fw-semibold text-uppercase small">Inspections</p>
                        <h2 class="fw-bold mb-0">{{ $pendingInspections }}</h2>
                        @if($pendingInspections > 0)
                        <span class="badge bg-warning text-dark mt-2">Pending</span>
                        @endif
                    </div>
                    <div class="rounded-3 p-3" style="background-color: rgba(255, 193, 7, 0.1);">
                        <i class="mdi mdi-clipboard-check text-warning" style="font-size: 2rem;"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="{{ route('client.inspections.index') }}" class="text-warning text-decoration-none fw-semibold small">
                        View inspections <i class="mdi mdi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Projects Card -->
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #17a2b8 !important;">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-2 fw-semibold text-uppercase small">Projects</p>
                        <h2 class="fw-bold mb-0">{{ $projectsCount }}</h2>
                        @if($projectsCount > 0)
                        <span class="badge bg-info text-dark mt-2">Active</span>
                        @endif
                    </div>
                    <div class="rounded-3 p-3" style="background-color: rgba(23, 162, 184, 0.1);">
                        <i class="mdi mdi-briefcase text-info" style="font-size: 2rem;"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <span class="text-muted small">Ongoing projects</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Invoices Card -->
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #dc3545 !important;">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-2 fw-semibold text-uppercase small">Invoices</p>
                        <h2 class="fw-bold mb-0">{{ $unpaidInvoices }}</h2>
                        @if($unpaidInvoices > 0)
                        <span class="badge bg-danger mt-2">Unpaid</span>
                        @endif
                    </div>
                    <div class="rounded-3 p-3" style="background-color: rgba(220, 53, 69, 0.1);">
                        <i class="mdi mdi-file-document text-danger" style="font-size: 2rem;"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="{{ route('client.invoices.index') }}" class="text-danger text-decoration-none fw-semibold small">
                        View invoices <i class="mdi mdi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    {{-- Recent Properties --}}
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="fw-bold mb-1">My Properties</h4>
                        <p class="text-muted mb-0 small">Recently added properties</p>
                    </div>
                    <a href="{{ route('client.properties.index') }}" class="btn btn-outline-primary btn-sm">
                        View All <i class="mdi mdi-arrow-right"></i>
                    </a>
                </div>
                
                @if($recentProperties->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th class="border-0 text-uppercase small fw-semibold">Property</th>
                                <th class="border-0 text-uppercase small fw-semibold">Type</th>
                                <th class="border-0 text-uppercase small fw-semibold">Location</th>
                                <th class="border-0 text-uppercase small fw-semibold text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentProperties as $property)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle bg-primary bg-opacity-10 p-2 me-3">
                                            <i class="mdi mdi-home text-primary"></i>
                                        </div>
                                        <div>
                                            <div class="fw-semibold">{{ $property->property_name }}</div>
                                            <small class="text-muted">{{ $property->property_code }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25">
                                        {{ ucfirst($property->property_type) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="text-muted small">
                                        <i class="mdi mdi-map-marker me-1"></i>{{ $property->city }}, {{ $property->country }}
                                    </div>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('client.properties.show', $property->id) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="mdi mdi-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-5">
                    <div class="rounded-circle bg-light d-inline-flex p-4 mb-3">
                        <i class="mdi mdi-home-outline text-muted" style="font-size: 4rem;"></i>
                    </div>
                    <h5 class="fw-semibold">No properties yet</h5>
                    <p class="text-muted">Start by adding your first property</p>
                    <a href="{{ route('client.properties.create') }}" class="btn btn-success mt-2">
                        <i class="mdi mdi-home-plus me-2"></i> Add Property
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Quick Actions & Subscription --}}
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h4 class="fw-bold mb-4">Quick Actions</h4>
                <div class="d-flex flex-column gap-3">
                    <a href="{{ route('client.properties.create') }}" class="text-decoration-none">
                        <div class="d-flex align-items-center p-3 rounded-3 border border-2 hover-shadow transition">
                            <div class="rounded-3 p-3 me-3" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                                <i class="mdi mdi-home-plus text-white" style="font-size: 1.75rem;"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-0 fw-semibold text-dark">Add New Property</h6>
                                <small class="text-muted">Register a new property</small>
                            </div>
                            <i class="mdi mdi-chevron-right text-muted"></i>
                        </div>
                    </a>
                    
                    <a href="{{ route('client.inspections.index') }}" class="text-decoration-none">
                        <div class="d-flex align-items-center p-3 rounded-3 border border-2 hover-shadow transition">
                            <div class="rounded-3 p-3 me-3" style="background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);">
                                <i class="mdi mdi-clipboard-check text-white" style="font-size: 1.75rem;"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-0 fw-semibold text-dark">Schedule Inspection</h6>
                                <small class="text-muted">Book a property inspection</small>
                            </div>
                            <i class="mdi mdi-chevron-right text-muted"></i>
                        </div>
                    </a>
                    
                    <a href="{{ route('client.invoices.index') }}" class="text-decoration-none">
                        <div class="d-flex align-items-center p-3 rounded-3 border border-2 hover-shadow transition">
                            <div class="rounded-3 p-3 me-3" style="background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);">
                                <i class="mdi mdi-file-document text-white" style="font-size: 1.75rem;"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-0 fw-semibold text-dark">View Invoices</h6>
                                <small class="text-muted">Check billing & payments</small>
                            </div>
                            <i class="mdi mdi-chevron-right text-muted"></i>
                        </div>
                    </a>
                    
                    <a href="{{ route('client.support') }}" class="text-decoration-none">
                        <div class="d-flex align-items-center p-3 rounded-3 border border-2 hover-shadow transition">
                            <div class="rounded-3 p-3 me-3" style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);">
                                <i class="mdi mdi-help-circle text-white" style="font-size: 1.75rem;"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-0 fw-semibold text-dark">Get Support</h6>
                                <small class="text-muted">Contact our support team</small>
                            </div>
                            <i class="mdi mdi-chevron-right text-muted"></i>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Alerts & Notifications --}}
@if($unpaidInvoices > 0 || $pendingInspections > 0)
<div class="row mt-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm border-start border-warning border-4">
            <div class="card-body">
                <h5 class="fw-bold mb-3">
                    <i class="mdi mdi-bell-ring text-warning me-2"></i> Action Required
                </h5>
                <div class="row g-3">
                    @if($unpaidInvoices > 0)
                    <div class="col-md-6">
                        <div class="alert alert-warning border-0 shadow-sm mb-0">
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle bg-warning bg-opacity-25 p-3 me-3">
                                    <i class="mdi mdi-alert-circle text-warning" style="font-size: 1.5rem;"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 fw-semibold">Unpaid Invoices</h6>
                                    <p class="mb-0 small">You have {{ $unpaidInvoices }} unpaid invoice(s)</p>
                                </div>
                                <a href="{{ route('client.invoices.index') }}" class="btn btn-warning btn-sm">
                                    Pay Now
                                </a>
                            </div>
                        </div>
                    </div>
                    @endif
                    
                    @if($pendingInspections > 0)
                    <div class="col-md-6">
                        <div class="alert alert-info border-0 shadow-sm mb-0">
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle bg-info bg-opacity-25 p-3 me-3">
                                    <i class="mdi mdi-calendar-clock text-info" style="font-size: 1.5rem;"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 fw-semibold">Scheduled Inspections</h6>
                                    <p class="mb-0 small">{{ $pendingInspections }} inspection(s) scheduled</p>
                                </div>
                                <a href="{{ route('client.inspections.index') }}" class="btn btn-info btn-sm">
                                    View
                                </a>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<style>
.hover-shadow {
    transition: all 0.3s ease;
}
.hover-shadow:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    transform: translateY(-2px);
}
.transition {
    transition: all 0.3s ease;
}
</style>
@endsection
