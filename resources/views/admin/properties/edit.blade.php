@extends('admin.layout')

@section('title', 'Edit Property')

@section('content')
<div class="content-wrapper">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="card-title mb-0">
                            <i class="mdi mdi-pencil text-primary me-2"></i>Edit Property
                        </h4>
                        <a href="{{ route('properties.show', $property->id) }}" class="btn btn-secondary btn-sm">
                            <i class="mdi mdi-arrow-left me-2"></i>Back
                        </a>
                    </div>

                    <form action="{{ route('properties.update', $property->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <!-- Status Selection -->
                        <div class="card mb-3">
                            <div class="card-body">
                                <h5 class="mb-3">Property Lifecycle</h5>
                                <div class="form-group">
                                    <label for="status">Status <span class="text-danger">*</span></label>
                                    <select name="status" id="status" class="form-control @error('status') is-invalid @enderror" required>
                                        <option value="registered" {{ $property->status == 'registered' ? 'selected' : '' }}>
                                            Registered
                                        </option>
                                        <option value="awaiting_inspection" {{ $property->status == 'awaiting_inspection' ? 'selected' : '' }}>
                                            Awaiting Inspection
                                        </option>
                                        <option value="in_assessment" {{ $property->status == 'in_assessment' ? 'selected' : '' }}>
                                            In Assessment
                                        </option>
                                        <option value="assessed" {{ $property->status == 'assessed' ? 'selected' : '' }}>
                                            Assessed
                                        </option>
                                        <option value="archived" {{ $property->status == 'archived' ? 'selected' : '' }}>
                                            Archived
                                        </option>
                                    </select>
                                    <small class="text-muted d-block mt-1">Property registration no longer requires admin approval. Use this only to clean up lifecycle state.</small>
                                    @error('status')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('properties.show', $property->id) }}" class="btn btn-secondary">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="mdi mdi-content-save me-2"></i>Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

