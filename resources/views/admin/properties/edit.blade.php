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
                                <h5 class="mb-3">Property Status</h5>
                                <div class="form-group">
                                    <label for="status">Status <span class="text-danger">*</span></label>
                                    <select name="status" id="status" class="form-control @error('status') is-invalid @enderror" required>
                                        <option value="pending_approval" {{ $property->status == 'pending_approval' ? 'selected' : '' }}>
                                            Pending Approval
                                        </option>
                                        <option value="approved" {{ $property->status == 'approved' ? 'selected' : '' }}>
                                            Approved
                                        </option>
                                        <option value="rejected" {{ $property->status == 'rejected' ? 'selected' : '' }}>
                                            Rejected
                                        </option>
                                    </select>
                                    @error('status')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div id="rejection-reason-group" style="{{ $property->status == 'rejected' ? '' : 'display: none;' }}">
                                    <div class="form-group mt-3">
                                        <label for="rejection_reason">Rejection Reason</label>
                                        <textarea name="rejection_reason" id="rejection_reason" 
                                                  class="form-control @error('rejection_reason') is-invalid @enderror" 
                                                  rows="3">{{ old('rejection_reason', $property->rejection_reason) }}</textarea>
                                        @error('rejection_reason')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
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

@push('scripts')
<script>
$(document).ready(function() {
    $('#status').on('change', function() {
        if ($(this).val() === 'rejected') {
            $('#rejection-reason-group').slideDown();
            $('#rejection_reason').attr('required', true);
        } else {
            $('#rejection-reason-group').slideUp();
            $('#rejection_reason').attr('required', false);
        }
    });
});
</script>
@endpush
