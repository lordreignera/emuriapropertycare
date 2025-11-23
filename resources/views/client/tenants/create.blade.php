@extends('client.layout')

@section('title', 'Add New Tenant')

@section('header', 'Add New Tenant')

@section('breadcrumbs')
<li class="breadcrumb-item"><a href="{{ route('client.tenants.index') }}">Tenants</a></li>
<li class="breadcrumb-item active" aria-current="page">Add Tenant</li>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">
                    <i class="mdi mdi-account-plus text-primary me-2"></i>Add New Tenant
                </h4>

                <form action="{{ route('client.tenants.store') }}" method="POST">
                    @csrf

                    <!-- Property Selection -->
                    <div class="form-group mb-4">
                        <label for="property_id">Select Property <span class="text-danger">*</span></label>
                        <select name="property_id" id="property_id" class="form-control @error('property_id') is-invalid @enderror" required>
                            <option value="">-- Choose Property --</option>
                            @foreach($properties as $property)
                            <option value="{{ $property->id }}" 
                                    data-code="{{ $property->property_code }}"
                                    data-password="{{ $property->tenant_common_password }}"
                                    {{ old('property_id', $selectedPropertyId) == $property->id ? 'selected' : '' }}>
                                {{ $property->property_name }} ({{ $property->property_code }})
                            </option>
                            @endforeach
                        </select>
                        @error('property_id')
                        <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Property Credentials Display -->
                    <div id="credentials-display" class="alert alert-info mb-4" style="display: none;">
                        <h6 class="mb-2"><i class="mdi mdi-key me-2"></i>Tenant Login Information</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Property Code:</strong>
                                <div class="bg-white p-2 rounded mt-1">
                                    <code id="prop-code" class="text-primary fs-6">-</code>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <strong>Shared Password:</strong>
                                <div class="bg-white p-2 rounded mt-1">
                                    <code id="prop-password" class="text-danger fs-6">-</code>
                                </div>
                            </div>
                        </div>
                        <small class="text-muted d-block mt-2">
                            <i class="mdi mdi-information"></i> This tenant will be assigned the next available tenant number (e.g., <span id="tenant-example">CODE-1</span>)
                        </small>
                    </div>

                    <div class="row">
                        <!-- First Name -->
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="first_name">First Name <span class="text-danger">*</span></label>
                                <input type="text" name="first_name" id="first_name" 
                                       class="form-control @error('first_name') is-invalid @enderror" 
                                       value="{{ old('first_name') }}" required>
                                @error('first_name')
                                <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <!-- Last Name -->
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="last_name">Last Name <span class="text-danger">*</span></label>
                                <input type="text" name="last_name" id="last_name" 
                                       class="form-control @error('last_name') is-invalid @enderror" 
                                       value="{{ old('last_name') }}" required>
                                @error('last_name')
                                <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Email -->
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="email">Email (Optional)</label>
                                <input type="email" name="email" id="email" 
                                       class="form-control @error('email') is-invalid @enderror" 
                                       value="{{ old('email') }}">
                                @error('email')
                                <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <!-- Phone -->
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="phone">Phone (Optional)</label>
                                <input type="text" name="phone" id="phone" 
                                       class="form-control @error('phone') is-invalid @enderror" 
                                       value="{{ old('phone') }}">
                                @error('phone')
                                <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Unit Number -->
                    <div class="form-group mb-3">
                        <label for="unit_number">Unit Number <span class="text-danger">*</span></label>
                        <input type="text" name="unit_number" id="unit_number" 
                               class="form-control @error('unit_number') is-invalid @enderror" 
                               value="{{ old('unit_number') }}" 
                               placeholder="e.g., 101, A-5, Unit 12"
                               required>
                        @error('unit_number')
                        <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="row">
                        <!-- Move-In Date -->
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="move_in_date">Move-In Date <span class="text-danger">*</span></label>
                                <input type="date" name="move_in_date" id="move_in_date" 
                                       class="form-control @error('move_in_date') is-invalid @enderror" 
                                       value="{{ old('move_in_date') }}" required>
                                @error('move_in_date')
                                <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <!-- Move-Out Date -->
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="move_out_date">Move-Out Date (Optional)</label>
                                <input type="date" name="move_out_date" id="move_out_date" 
                                       class="form-control @error('move_out_date') is-invalid @enderror" 
                                       value="{{ old('move_out_date') }}">
                                @error('move_out_date')
                                <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                                <small class="text-muted">Leave empty if tenant is still living here</small>
                            </div>
                        </div>
                    </div>

                    <!-- Emergency Reporting Permission -->
                    <div class="form-group mb-4">
                        <div class="form-check">
                            <input type="checkbox" name="can_report_emergency" id="can_report_emergency" 
                                   class="form-check-input" value="1" 
                                   {{ old('can_report_emergency', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="can_report_emergency">
                                <strong>Allow this tenant to report emergencies</strong>
                                <br><small class="text-muted">Tenant can submit emergency reports with photos and track their status</small>
                            </label>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('client.tenants.index') }}" class="btn btn-secondary">
                            <i class="mdi mdi-arrow-left me-2"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="mdi mdi-check me-2"></i>Add Tenant
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Show credentials when property is selected
    $('#property_id').on('change', function() {
        const selectedOption = $(this).find('option:selected');
        const propertyCode = selectedOption.data('code');
        const propertyPassword = selectedOption.data('password');
        
        if (propertyCode && propertyPassword) {
            $('#prop-code').text(propertyCode);
            $('#prop-password').text(propertyPassword);
            $('#tenant-example').text(propertyCode + '-X');
            $('#credentials-display').slideDown();
        } else {
            $('#credentials-display').slideUp();
        }
    });
    
    // Trigger on page load if property is pre-selected
    if ($('#property_id').val()) {
        $('#property_id').trigger('change');
    }
});
</script>
@endpush
