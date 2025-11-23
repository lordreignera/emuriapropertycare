@extends('client.layout')

@section('title', 'Client Dashboard')

@section('content')
<div class="row">
    <div class="col-md-12 grid-margin">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h3 class="font-weight-bold mb-0">Welcome back, {{ Auth::user()->name }}!</h3>
                <p class="text-muted mb-0">Here's what's happening with your properties today</p>
            </div>
            <div>
                <a href="{{ route('client.properties.create') }}" class="btn btn-primary btn-icon-text">
                    <i class="mdi mdi-home-plus btn-icon-prepend"></i> Add Property
                </a>
            </div>
        </div>
    </div>
</div>

{{-- Stats Cards --}}
<div class="row">
    <div class="col-xl-3 col-sm-6 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-9">
                        <div class="d-flex align-items-center align-self-start">
                            <h3 class="mb-0">{{ $propertiesCount }}</h3>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="icon icon-box-success">
                            <span class="mdi mdi-home-modern icon-item"></span>
                        </div>
                    </div>
                </div>
                <h6 class="text-muted font-weight-normal">My Properties</h6>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-9">
                        <div class="d-flex align-items-center align-self-start">
                            <h3 class="mb-0">{{ $pendingInspections }}</h3>
                            @if($pendingInspections > 0)
                            <p class="text-warning ms-2 mb-0 font-weight-medium">Pending</p>
                            @endif
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="icon icon-box-warning">
                            <span class="mdi mdi-clipboard-check icon-item"></span>
                        </div>
                    </div>
                </div>
                <h6 class="text-muted font-weight-normal">Inspections</h6>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-9">
                        <div class="d-flex align-items-center align-self-start">
                            <h3 class="mb-0">{{ $projectsCount }}</h3>
                            @if($projectsCount > 0)
                            <p class="text-success ms-2 mb-0 font-weight-medium">Active</p>
                            @endif
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="icon icon-box-info">
                            <span class="mdi mdi-briefcase icon-item"></span>
                        </div>
                    </div>
                </div>
                <h6 class="text-muted font-weight-normal">Projects</h6>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-9">
                        <div class="d-flex align-items-center align-self-start">
                            <h3 class="mb-0">{{ $unpaidInvoices }}</h3>
                            @if($unpaidInvoices > 0)
                            <p class="text-danger ms-2 mb-0 font-weight-medium">Unpaid</p>
                            @endif
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="icon icon-box-danger">
                            <span class="mdi mdi-file-document icon-item"></span>
                        </div>
                    </div>
                </div>
                <h6 class="text-muted font-weight-normal">Invoices</h6>
            </div>
        </div>
    </div>
</div>

<div class="row">
    {{-- Recent Properties --}}
    <div class="col-md-6 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="card-title mb-0">My Properties</h4>
                    <a href="{{ route('client.properties.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                @if($recentProperties->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Property Name</th>
                                <th>Type</th>
                                <th>Location</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentProperties as $property)
                            <tr>
                                <td>
                                    <i class="mdi mdi-home text-primary me-2"></i>
                                    {{ $property->property_name }}
                                </td>
                                <td>
                                    <span class="badge badge-info">{{ ucfirst($property->property_type) }}</span>
                                </td>
                                <td>{{ $property->city }}, {{ $property->country }}</td>
                                <td>
                                    <a href="{{ route('client.properties.show', $property->id) }}" class="btn btn-sm btn-primary">
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
                    <i class="mdi mdi-home-outline display-4 text-muted"></i>
                    <p class="text-muted mt-3">No properties yet</p>
                    <a href="{{ route('client.properties.create') }}" class="btn btn-primary">
                        <i class="mdi mdi-plus"></i> Add Your First Property
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Quick Actions & Subscription --}}
    <div class="col-md-6 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Quick Actions</h4>
                <div class="list-wrapper">
                    <ul class="d-flex flex-column list-style-none">
                        <li class="mb-3">
                            <a href="{{ route('client.properties.create') }}" class="d-flex align-items-center p-3 bg-light rounded">
                                <i class="mdi mdi-home-plus text-success icon-lg me-3"></i>
                                <div>
                                    <h6 class="mb-0">Add New Property</h6>
                                    <p class="text-muted mb-0 small">Register a new property</p>
                                </div>
                            </a>
                        </li>
                        <li class="mb-3">
                            <a href="{{ route('client.inspections.index') }}" class="d-flex align-items-center p-3 bg-light rounded">
                                <i class="mdi mdi-clipboard-check text-warning icon-lg me-3"></i>
                                <div>
                                    <h6 class="mb-0">Schedule Inspection</h6>
                                    <p class="text-muted mb-0 small">Book a property inspection</p>
                                </div>
                            </a>
                        </li>
                        <li class="mb-3">
                            <a href="{{ route('client.invoices.index') }}" class="d-flex align-items-center p-3 bg-light rounded">
                                <i class="mdi mdi-file-document text-primary icon-lg me-3"></i>
                                <div>
                                    <h6 class="mb-0">View Invoices</h6>
                                    <p class="text-muted mb-0 small">Check billing & payments</p>
                                </div>
                            </a>
                        </li>
                        <li class="mb-3">
                            <a href="{{ route('client.support') }}" class="d-flex align-items-center p-3 bg-light rounded">
                                <i class="mdi mdi-help-circle text-info icon-lg me-3"></i>
                                <div>
                                    <h6 class="mb-0">Get Support</h6>
                                    <p class="text-muted mb-0 small">Contact our support team</p>
                                </div>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Alerts & Notifications --}}
@if($unpaidInvoices > 0 || $pendingInspections > 0)
<div class="row">
    <div class="col-md-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-3">
                    <i class="mdi mdi-bell-ring text-warning"></i> Action Required
                </h4>
                <div class="row">
                    @if($unpaidInvoices > 0)
                    <div class="col-md-6 mb-3">
                        <div class="alert alert-warning d-flex align-items-center" role="alert">
                            <i class="mdi mdi-alert-circle me-3 icon-lg"></i>
                            <div class="flex-grow-1">
                                <strong>Unpaid Invoices</strong>
                                <p class="mb-0">You have {{ $unpaidInvoices }} unpaid invoice(s).</p>
                            </div>
                            <a href="{{ route('client.invoices.index') }}" class="btn btn-sm btn-warning">Pay Now</a>
                        </div>
                    </div>
                    @endif
                    
                    @if($pendingInspections > 0)
                    <div class="col-md-6 mb-3">
                        <div class="alert alert-info d-flex align-items-center" role="alert">
                            <i class="mdi mdi-calendar-clock me-3 icon-lg"></i>
                            <div class="flex-grow-1">
                                <strong>Scheduled Inspections</strong>
                                <p class="mb-0">{{ $pendingInspections }} inspection(s) scheduled.</p>
                            </div>
                            <a href="{{ route('client.inspections.index') }}" class="btn btn-sm btn-info">View</a>
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
.icon-lg {
    font-size: 2rem;
}
.list-style-none {
    list-style: none;
    padding: 0;
}
.list-wrapper a {
    text-decoration: none;
    color: inherit;
    transition: all 0.3s ease;
}
.list-wrapper a:hover {
    background-color: #f0f0f0 !important;
    transform: translateX(5px);
}
</style>
@endsection
