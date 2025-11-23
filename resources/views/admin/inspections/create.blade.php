@extends('admin.layout')

@section('title', 'Start Inspection')

@section('content')
<div class="content-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h3 class="mb-1">Start Property Inspection</h3>
                    <p class="text-muted mb-0">
                        <code>{{ $property->property_code }}</code> • {{ $property->property_name }}
                    </p>
                </div>
                <div>
                    <a href="{{ route('inspections.index') }}" class="btn btn-secondary btn-sm">
                        <i class="mdi mdi-arrow-left me-2"></i>Back to Inspections
                    </a>
                </div>
            </div>

            @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            <!-- Property Information Card -->
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title mb-3">
                        <i class="mdi mdi-home-modern text-primary me-2"></i>Property Information
                    </h5>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Property Name:</strong> {{ $property->property_name }}</p>
                            <p><strong>Property Code:</strong> <code>{{ $property->property_code }}</code></p>
                            <p><strong>Address:</strong> {{ $property->property_address }}</p>
                            <p><strong>City:</strong> {{ $property->city }}, {{ $property->province }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Owner:</strong> {{ $property->owner_first_name }} {{ $property->owner_last_name }}</p>
                            <p><strong>Phone:</strong> {{ $property->owner_phone }}</p>
                            <p><strong>Email:</strong> {{ $property->owner_email }}</p>
                            @if($property->inspection_scheduled_at)
                            <p><strong>Scheduled:</strong> 
                                <span class="badge badge-success">
                                    {{ $property->inspection_scheduled_at->format('M d, Y h:i A') }}
                                </span>
                            </p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Inspection Form -->
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">
                        <i class="mdi mdi-clipboard-check text-success me-2"></i>Inspection Details
                    </h5>
                    
                    <form action="{{ route('inspections.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="property_id" value="{{ $property->id }}">

                        <!-- Basic Information -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="inspection_date">Inspection Date <span class="text-danger">*</span></label>
                                    <input type="datetime-local" 
                                           class="form-control @error('inspection_date') is-invalid @enderror" 
                                           id="inspection_date" 
                                           name="inspection_date" 
                                           value="{{ old('inspection_date', $property->inspection_scheduled_at?->format('Y-m-d\TH:i')) }}" 
                                           required>
                                    @error('inspection_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="inspection_type">Inspection Type <span class="text-danger">*</span></label>
                                    <select class="form-control @error('inspection_type') is-invalid @enderror" 
                                            id="inspection_type" 
                                            name="inspection_type" 
                                            required>
                                        <option value="">Select Type</option>
                                        <option value="initial" {{ old('inspection_type') == 'initial' ? 'selected' : '' }}>Initial Inspection</option>
                                        <option value="routine" {{ old('inspection_type') == 'routine' ? 'selected' : '' }}>Routine Inspection</option>
                                        <option value="follow-up" {{ old('inspection_type') == 'follow-up' ? 'selected' : '' }}>Follow-up Inspection</option>
                                        <option value="emergency" {{ old('inspection_type') == 'emergency' ? 'selected' : '' }}>Emergency Inspection</option>
                                    </select>
                                    @error('inspection_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Inspection Status -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="status">Inspection Status <span class="text-danger">*</span></label>
                                    <select class="form-control @error('status') is-invalid @enderror" 
                                            id="status" 
                                            name="status" 
                                            required>
                                        <option value="scheduled" {{ old('status') == 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                                        <option value="in_progress" {{ old('status', 'in_progress') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                        <option value="completed" {{ old('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                                    </select>
                                    @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="overall_condition">Overall Condition</label>
                                    <select class="form-control @error('overall_condition') is-invalid @enderror" 
                                            id="overall_condition" 
                                            name="overall_condition">
                                        <option value="">Select Condition</option>
                                        <option value="excellent" {{ old('overall_condition') == 'excellent' ? 'selected' : '' }}>Excellent</option>
                                        <option value="good" {{ old('overall_condition') == 'good' ? 'selected' : '' }}>Good</option>
                                        <option value="fair" {{ old('overall_condition') == 'fair' ? 'selected' : '' }}>Fair</option>
                                        <option value="poor" {{ old('overall_condition') == 'poor' ? 'selected' : '' }}>Poor</option>
                                    </select>
                                    @error('overall_condition')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Inspection Notes -->
                        <div class="form-group mb-4">
                            <label for="notes">Inspection Notes</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" 
                                      id="notes" 
                                      name="notes" 
                                      rows="5" 
                                      placeholder="Enter detailed inspection notes, observations, and findings...">{{ old('notes') }}</textarea>
                            @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Issues Found -->
                        <div class="form-group mb-4">
                            <label for="issues_found">Issues Found</label>
                            <textarea class="form-control @error('issues_found') is-invalid @enderror" 
                                      id="issues_found" 
                                      name="issues_found" 
                                      rows="4" 
                                      placeholder="List any issues, defects, or concerns found during inspection...">{{ old('issues_found') }}</textarea>
                            @error('issues_found')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Recommendations -->
                        <div class="form-group mb-4">
                            <label for="recommendations">Recommendations</label>
                            <textarea class="form-control @error('recommendations') is-invalid @enderror" 
                                      id="recommendations" 
                                      name="recommendations" 
                                      rows="4" 
                                      placeholder="Enter recommendations for repairs, maintenance, or follow-up actions...">{{ old('recommendations') }}</textarea>
                            @error('recommendations')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Photos Upload -->
                        <div class="form-group mb-4">
                            <label for="photos">Inspection Photos</label>
                            <input type="file" 
                                   class="form-control @error('photos.*') is-invalid @enderror" 
                                   id="photos" 
                                   name="photos[]" 
                                   multiple 
                                   accept="image/*">
                            <small class="form-text text-muted">
                                You can upload multiple photos (max 10MB each). Accepted formats: JPG, PNG, WEBP
                            </small>
                            @error('photos.*')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Report Upload -->
                        <div class="form-group mb-4">
                            <label for="report">Inspection Report (PDF)</label>
                            <input type="file" 
                                   class="form-control @error('report') is-invalid @enderror" 
                                   id="report" 
                                   name="report" 
                                   accept=".pdf">
                            <small class="form-text text-muted">
                                Upload a detailed inspection report in PDF format (max 20MB)
                            </small>
                            @error('report')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('inspections.index') }}" class="btn btn-secondary">
                                <i class="mdi mdi-close me-2"></i>Cancel
                            </a>
                            <div>
                                <button type="submit" name="action" value="save_draft" class="btn btn-outline-primary me-2">
                                    <i class="mdi mdi-content-save me-2"></i>Save as Draft
                                </button>
                                <button type="submit" name="action" value="submit" class="btn btn-success">
                                    <i class="mdi mdi-check-circle me-2"></i>Submit Inspection
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Ensure form content is visible */
body.light-theme .content-wrapper {
    background-color: #f4f5f7 !important;
}

body.light-theme .card {
    background-color: #ffffff !important;
    color: #212529 !important;
    border: 1px solid #e3e6f0;
}

body.light-theme .card-title {
    color: #212529 !important;
}

body.light-theme .form-label,
body.light-theme label {
    color: #212529 !important;
}

body.light-theme .form-control {
    background-color: #ffffff !important;
    color: #212529 !important;
    border: 1px solid #ced4da;
}

body.light-theme .form-control:focus {
    background-color: #ffffff !important;
    color: #212529 !important;
    border-color: #80bdff;
}

body.light-theme .form-select {
    background-color: #ffffff !important;
    color: #212529 !important;
}
</style>
@endsection
