@extends('admin.layout')

@section('content')
<div class="row">
    <div class="col-lg-8 mx-auto grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Add Scoring Factor to Domain #{{ $cpiDomain->domain_number }}</h4>
                <p class="card-description">Domain: <strong>{{ $cpiDomain->domain_name }}</strong></p>
                
                <form action="{{ route('admin.cpi-domains.factors.store', $cpiDomain) }}" method="POST" class="forms-sample">
                    @csrf

                    <div class="form-group">
                        <label for="factor_code">Factor Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('factor_code') is-invalid @enderror" 
                               id="factor_code" name="factor_code" value="{{ old('factor_code') }}" required>
                        @error('factor_code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">e.g., unit_shutoffs, supply_material</small>
                    </div>

                    <div class="form-group">
                        <label for="factor_label">Factor Label <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('factor_label') is-invalid @enderror" 
                               id="factor_label" name="factor_label" value="{{ old('factor_label') }}" required>
                        @error('factor_label')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">e.g., Unit-level water shut-offs present? (Yes/No)</small>
                    </div>

                    <div class="form-group">
                        <label for="field_type">Field Type <span class="text-danger">*</span></label>
                        <select class="form-control @error('field_type') is-invalid @enderror" 
                                id="field_type" name="field_type" required>
                            <option value="">-- Select Type --</option>
                            <option value="yes_no" {{ old('field_type') == 'yes_no' ? 'selected' : '' }}>Yes/No</option>
                            <option value="lookup" {{ old('field_type') == 'lookup' ? 'selected' : '' }}>Lookup (Dropdown)</option>
                            <option value="numeric" {{ old('field_type') == 'numeric' ? 'selected' : '' }}>Numeric Input</option>
                            <option value="calculated" {{ old('field_type') == 'calculated' ? 'selected' : '' }}>Calculated</option>
                        </select>
                        @error('field_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="lookup_table">Lookup Table</label>
                        <input type="text" class="form-control @error('lookup_table') is-invalid @enderror" 
                               id="lookup_table" name="lookup_table" value="{{ old('lookup_table') }}">
                        @error('lookup_table')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Required if field type is "lookup". e.g., supply_line_materials, age_brackets</small>
                    </div>

                    <div class="form-group">
                        <label for="max_points">Maximum Points <span class="text-danger">*</span></label>
                        <input type="number" class="form-control @error('max_points') is-invalid @enderror" 
                               id="max_points" name="max_points" value="{{ old('max_points', 0) }}" required>
                        @error('max_points')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="calculation_rule">Calculation Rule (JSON)</label>
                        <textarea class="form-control @error('calculation_rule') is-invalid @enderror" 
                                  id="calculation_rule" name="calculation_rule" rows="3">{{ old('calculation_rule') }}</textarea>
                        @error('calculation_rule')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">e.g., {"no": 3, "yes": 0} or {"source": "lookup_score"}</small>
                    </div>

                    <div class="form-group">
                        <label for="sort_order">Sort Order</label>
                        <input type="number" class="form-control @error('sort_order') is-invalid @enderror" 
                               id="sort_order" name="sort_order" value="{{ old('sort_order', 0) }}">
                        @error('sort_order')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="help_text">Help Text</label>
                        <textarea class="form-control @error('help_text') is-invalid @enderror" 
                                  id="help_text" name="help_text" rows="2">{{ old('help_text') }}</textarea>
                        @error('help_text')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-check mb-3">
                        <label class="form-check-label">
                            <input type="checkbox" class="form-check-input" name="is_required" value="1" 
                                   {{ old('is_required', true) ? 'checked' : '' }}>
                            Required Field
                        </label>
                    </div>

                    <div class="form-check mb-3">
                        <label class="form-check-label">
                            <input type="checkbox" class="form-check-input" name="is_active" value="1" 
                                   {{ old('is_active', true) ? 'checked' : '' }}>
                            Active
                        </label>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="mdi mdi-content-save"></i> Add Factor
                        </button>
                        <a href="{{ route('admin.cpi-domains.show', $cpiDomain) }}" class="btn btn-light">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
