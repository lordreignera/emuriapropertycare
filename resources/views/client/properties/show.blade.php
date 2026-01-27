@extends('client.layout')

@section('title', 'Property Details')

@section('header', $property->property_name)

@section('breadcrumbs')
<li class="breadcrumb-item"><a href="{{ route('client.properties.index') }}">Properties</a></li>
<li class="breadcrumb-item active" aria-current="page">{{ $property->property_code }}</li>
@endsection

@section('content')
<div class="row">
    {{-- Property Status Banner --}}
    <div class="col-12 mb-3">
        @if($property->status === 'pending_approval')
        <div class="alert alert-info d-flex align-items-center" role="alert">
            <i class="mdi mdi-calendar-check me-3" style="font-size: 2rem;"></i>
            <div class="flex-grow-1">
                <h5 class="alert-heading mb-1">Action Required: Schedule Your Inspection</h5>
                <p class="mb-2">Your property has been added successfully! To get started, you need to:</p>
                <ol class="mb-2 ps-3">
                    <li>Schedule your property for inspection/assessment</li>
                    <li>Complete the payment to confirm your assessment</li>
                </ol>
                <a href="{{ route('client.inspections.schedule', $property->id) }}" class="btn btn-primary btn-sm mt-2">
                    <i class="mdi mdi-calendar-plus me-1"></i> Schedule Inspection Now
                </a>
            </div>
        </div>
        @elseif($property->status === 'approved')
        <div class="alert alert-success d-flex align-items-center" role="alert">
            <i class="mdi mdi-check-circle me-3" style="font-size: 2rem;"></i>
            <div class="flex-grow-1">
                <h5 class="alert-heading mb-1">Approved</h5>
                <p class="mb-0">This property has been approved on {{ $property->approved_at->format('M d, Y') }}</p>
            </div>
        </div>
        @elseif($property->status === 'rejected')
        <div class="alert alert-danger d-flex align-items-center" role="alert">
            <i class="mdi mdi-close-circle me-3" style="font-size: 2rem;"></i>
            <div class="flex-grow-1">
                <h5 class="alert-heading mb-1">Rejected</h5>
                <p class="mb-0">This property submission was rejected. Please contact support for details.</p>
            </div>
        </div>
        @endif
    </div>

    {{-- Property Photos --}}
    @if($property->property_photos && count($property->property_photos) > 0)
    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">
                    <i class="mdi mdi-camera text-primary"></i> Property Photos
                </h4>
                <div class="row">
                    @foreach($property->property_photos as $photo)
                    <div class="col-md-4 mb-3">
                        <div class="property-photo-wrapper">
                            <img src="{{ Storage::url($photo) }}" 
                                 alt="Property Photo" 
                                 class="img-fluid property-photo"
                                 data-bs-toggle="modal" 
                                 data-bs-target="#photoModal{{ $loop->index }}">
                        </div>
                    </div>

                    {{-- Photo Modal --}}
                    <div class="modal fade" id="photoModal{{ $loop->index }}" tabindex="-1">
                        <div class="modal-dialog modal-lg modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Photo {{ $loop->iteration }}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body text-center">
                                    <img src="{{ Storage::url($photo) }}" alt="Property Photo" class="img-fluid">
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Property Information --}}
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-body">
                <h4 class="card-title mb-4">
                    <i class="mdi mdi-information text-primary"></i> Property Information
                </h4>
                <table class="table table-borderless">
                    <tr>
                        <th width="200">Property Code:</th>
                        <td><span class="badge badge-info">{{ $property->property_code }}</span></td>
                    </tr>
                    <tr>
                        <th>Property Name:</th>
                        <td><strong>{{ $property->property_name }}</strong></td>
                    </tr>
                    @if($property->property_brand)
                    <tr>
                        <th>Brand:</th>
                        <td>{{ $property->property_brand }}</td>
                    </tr>
                    @endif
                    <tr>
                        <th>Type:</th>
                        <td><span class="badge badge-primary">{{ ucfirst(str_replace('_', ' ', $property->type)) }}</span></td>
                    </tr>
                    @if($property->year_built)
                    <tr>
                        <th>Year Built:</th>
                        <td>{{ $property->year_built }} <small class="text-muted">({{ date('Y') - $property->year_built }} years old)</small></td>
                    </tr>
                    @endif
                    <tr>
                        <th>Full Address:</th>
                        <td>{{ $property->full_address }}</td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- Property Size --}}
        @if($property->total_square_footage > 0)
        <div class="card mb-4">
            <div class="card-body">
                <h4 class="card-title mb-4">
                    <i class="mdi mdi-floor-plan text-success"></i> Property Size
                </h4>
                <table class="table table-borderless">
                    @if($property->square_footage_interior)
                    <tr>
                        <th width="200">Interior:</th>
                        <td>{{ number_format($property->square_footage_interior, 2) }} sq ft</td>
                    </tr>
                    @endif
                    @if($property->square_footage_green)
                    <tr>
                        <th>Green Space:</th>
                        <td>{{ number_format($property->square_footage_green, 2) }} sq ft</td>
                    </tr>
                    @endif
                    @if($property->square_footage_paved)
                    <tr>
                        <th>Paved Area:</th>
                        <td>{{ number_format($property->square_footage_paved, 2) }} sq ft</td>
                    </tr>
                    @endif
                    @if($property->square_footage_extra)
                    <tr>
                        <th>Extra Space:</th>
                        <td>{{ number_format($property->square_footage_extra, 2) }} sq ft</td>
                    </tr>
                    @endif
                    <tr class="border-top">
                        <th><strong>Total:</strong></th>
                        <td><strong>{{ number_format($property->total_square_footage, 2) }} sq ft</strong></td>
                    </tr>
                </table>
            </div>
        </div>
        @endif

        {{-- Occupancy Information --}}
        <div class="card mb-4">
            <div class="card-body">
                <h4 class="card-title mb-4">
                    <i class="mdi mdi-account-group text-warning"></i> Occupancy Information
                </h4>
                <table class="table table-borderless">
                    @if($property->occupied_by)
                    <tr>
                        <th width="200">Occupied By:</th>
                        <td>{{ ucfirst($property->occupied_by) }}</td>
                    </tr>
                    @endif
                    <tr>
                        <th>Has Pets:</th>
                        <td>
                            @if($property->has_pets)
                                <span class="badge badge-success"><i class="mdi mdi-check"></i> Yes</span>
                            @else
                                <span class="badge badge-secondary"><i class="mdi mdi-close"></i> No</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Has Children:</th>
                        <td>
                            @if($property->has_kids)
                                <span class="badge badge-success"><i class="mdi mdi-check"></i> Yes</span>
                            @else
                                <span class="badge badge-secondary"><i class="mdi mdi-close"></i> No</span>
                            @endif
                        </td>
                    </tr>
                    @if($property->has_tenants)
                    <tr>
                        <th>Multi-Tenant Property:</th>
                        <td><span class="badge badge-info">{{ $property->number_of_units }} units</span></td>
                    </tr>
                    <tr>
                        <th>Tenant Password:</th>
                        <td><code>{{ $property->tenant_common_password }}</code></td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>
    </div>

    {{-- Owner & Admin Info --}}
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-body">
                <h4 class="card-title mb-4">
                    <i class="mdi mdi-account text-primary"></i> Owner Information
                </h4>
                <p class="mb-2"><strong>{{ $property->owner_first_name }}</strong></p>
                <p class="mb-1">
                    <i class="mdi mdi-phone text-success"></i> {{ $property->owner_phone }}
                </p>
                <p class="mb-0">
                    <i class="mdi mdi-email text-info"></i> {{ $property->owner_email }}
                </p>
            </div>
        </div>

        @if($property->admin_first_name)
        <div class="card mb-4">
            <div class="card-body">
                <h4 class="card-title mb-4">
                    <i class="mdi mdi-account-tie text-secondary"></i> Property Administrator
                </h4>
                <p class="mb-2"><strong>{{ $property->admin_first_name }} {{ $property->admin_last_name }}</strong></p>
                @if($property->admin_phone)
                <p class="mb-1">
                    <i class="mdi mdi-phone text-success"></i> {{ $property->admin_phone }}
                </p>
                @endif
                @if($property->admin_email)
                <p class="mb-0">
                    <i class="mdi mdi-email text-info"></i> {{ $property->admin_email }}
                </p>
                @endif
            </div>
        </div>
        @endif

        {{-- Blueprint --}}
        @if($property->blueprint_file)
        <div class="card mb-4">
            <div class="card-body">
                <h4 class="card-title mb-4">
                    <i class="mdi mdi-floor-plan text-danger"></i> Blueprint / Floor Plan
                </h4>
                @if(str_ends_with($property->blueprint_file, '.pdf'))
                    <a href="{{ Storage::url($property->blueprint_file) }}" target="_blank" class="btn btn-outline-primary btn-block">
                        <i class="mdi mdi-file-pdf"></i> View PDF Blueprint
                    </a>
                @else
                    <img src="{{ Storage::url($property->blueprint_file) }}" alt="Blueprint" class="img-fluid" style="cursor: pointer;" data-bs-toggle="modal" data-bs-target="#blueprintModal">
                    
                    {{-- Blueprint Modal --}}
                    <div class="modal fade" id="blueprintModal" tabindex="-1">
                        <div class="modal-dialog modal-xl modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Blueprint / Floor Plan</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body text-center">
                                    <img src="{{ Storage::url($property->blueprint_file) }}" alt="Blueprint" class="img-fluid">
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
        @endif

        {{-- Action Buttons --}}
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Actions</h4>
                <div class="d-grid gap-2">
                    <a href="{{ route('client.properties.index') }}" class="btn btn-light">
                        <i class="mdi mdi-arrow-left"></i> Back to List
                    </a>
                    @if($property->status !== 'approved')
                    <a href="{{ route('client.properties.edit', $property->id) }}" class="btn btn-warning">
                        <i class="mdi mdi-pencil"></i> Edit Property
                    </a>
                    <form action="{{ route('client.properties.destroy', $property->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this property?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger w-100">
                            <i class="mdi mdi-delete"></i> Delete Property
                        </button>
                    </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Property Details --}}
    @if($property->personality || $property->known_problems || $property->sensitivities)
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">
                    <i class="mdi mdi-text-box text-info"></i> Additional Details
                </h4>
                
                @if($property->personality)
                <div class="mb-3">
                    <h6><strong>Property Personality/Style:</strong></h6>
                    <p class="text-muted">{{ $property->personality }}</p>
                </div>
                @endif

                @if($property->known_problems)
                <div class="mb-3">
                    <h6><strong>Known Problems/Issues:</strong></h6>
                    <p class="text-muted">{{ $property->known_problems }}</p>
                </div>
                @endif

                @if($property->sensitivities)
                <div class="mb-3">
                    <h6><strong>Sensitivities or Special Considerations:</strong></h6>
                    <ul class="list-unstyled">
                        @foreach($property->sensitivities as $sensitivity)
                        <li><i class="mdi mdi-check text-success"></i> {{ $sensitivity }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif
</div>

<style>
.property-photo {
    width: 100%;
    height: 250px;
    object-fit: cover;
    border-radius: 8px;
    cursor: pointer;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.property-photo:hover {
    transform: scale(1.05);
    box-shadow: 0 8px 16px rgba(0,0,0,0.2);
}
.property-photo-wrapper {
    overflow: hidden;
    border-radius: 8px;
}
</style>
@endsection
