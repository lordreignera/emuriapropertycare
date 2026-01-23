@extends('admin.layout')

@section('content')
<div class="row">
    <div class="col-lg-8 mx-auto grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Create Mixed-Use Setting</h4>
                
                <form action="{{ route('admin.mixed-use-settings.store') }}" method="POST" class="forms-sample">
                    @csrf

                    <div class="form-group">
                        <label for="setting_name">Setting Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('setting_name') is-invalid @enderror" 
                               id="setting_name" name="setting_name" value="{{ old('setting_name') }}" required>
                        @error('setting_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="setting_key">Setting Key <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('setting_key') is-invalid @enderror" 
                               id="setting_key" name="setting_key" value="{{ old('setting_key') }}" required>
                        @error('setting_key')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">e.g., weight_threshold_percentage</small>
                    </div>

                    <div class="form-group">
                        <label for="setting_value">Setting Value <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" class="form-control @error('setting_value') is-invalid @enderror" 
                               id="setting_value" name="setting_value" value="{{ old('setting_value') }}" required>
                        @error('setting_value')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="description" name="description" rows="3">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-check">
                        <label class="form-check-label">
                            <input type="checkbox" class="form-check-input" name="is_active" value="1" 
                                   {{ old('is_active', true) ? 'checked' : '' }}>
                            Active
                        </label>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="mdi mdi-content-save"></i> Create Setting
                        </button>
                        <a href="{{ route('admin.mixed-use-settings.index') }}" class="btn btn-light">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
