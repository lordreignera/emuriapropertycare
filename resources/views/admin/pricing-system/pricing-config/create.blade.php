@extends('admin.layout')

@section('content')
<div class="row">
    <div class="col-lg-8 mx-auto grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Create Pricing System Configuration</h4>
                
                <form action="{{ route('admin.pricing-config.store') }}" method="POST" class="forms-sample">
                    @csrf

                    <div class="form-group">
                        <label for="config_key">Configuration Key <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('config_key') is-invalid @enderror" 
                               id="config_key" name="config_key" value="{{ old('config_key') }}" required>
                        @error('config_key')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">e.g., max_cpi_score, default_inspection_fee</small>
                    </div>

                    <div class="form-group">
                        <label for="config_value">Configuration Value <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('config_value') is-invalid @enderror" 
                               id="config_value" name="config_value" value="{{ old('config_value') }}" required>
                        @error('config_value')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="value_type">Value Type <span class="text-danger">*</span></label>
                        <select class="form-control @error('value_type') is-invalid @enderror" 
                                id="value_type" name="value_type" required>
                            <option value="">Select Type</option>
                            <option value="string" {{ old('value_type') == 'string' ? 'selected' : '' }}>String</option>
                            <option value="integer" {{ old('value_type') == 'integer' ? 'selected' : '' }}>Integer</option>
                            <option value="decimal" {{ old('value_type') == 'decimal' ? 'selected' : '' }}>Decimal</option>
                            <option value="boolean" {{ old('value_type') == 'boolean' ? 'selected' : '' }}>Boolean</option>
                        </select>
                        @error('value_type')
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
                            <i class="mdi mdi-content-save"></i> Create Configuration
                        </button>
                        <a href="{{ route('admin.pricing-config.index') }}" class="btn btn-light">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
