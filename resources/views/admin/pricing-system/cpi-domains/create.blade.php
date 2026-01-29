@extends('admin.layout')

@section('content')
<div class="row">
    <div class="col-lg-8 mx-auto grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Create CPI Domain</h4>
                
                <form action="{{ route('admin.cpi-domains.store') }}" method="POST" class="forms-sample">
                    @csrf

                    <div class="form-group">
                        <label for="domain_number">Domain Number <span class="text-danger">*</span></label>
                        <input type="number" class="form-control @error('domain_number') is-invalid @enderror" 
                               id="domain_number" name="domain_number" value="{{ old('domain_number') }}" required>
                        @error('domain_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">e.g., 1, 2, 3</small>
                    </div>

                    <div class="form-group">
                        <label for="domain_name">Domain Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('domain_name') is-invalid @enderror" 
                               id="domain_name" name="domain_name" value="{{ old('domain_name') }}" required>
                        @error('domain_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="domain_code">Domain Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('domain_code') is-invalid @enderror" 
                               id="domain_code" name="domain_code" value="{{ old('domain_code') }}" required>
                        @error('domain_code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">e.g., system_design, materials, age</small>
                    </div>

                    <div class="form-group">
                        <label for="max_possible_points">Maximum Points <span class="text-danger">*</span></label>
                        <input type="number" class="form-control @error('max_possible_points') is-invalid @enderror" 
                               id="max_possible_points" name="max_possible_points" value="{{ old('max_possible_points') }}" required>
                        @error('max_possible_points')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Total possible points for this domain</small>
                    </div>

                    <div class="form-group">
                        <label for="calculation_method">Calculation Method <span class="text-danger">*</span></label>
                        <select class="form-control @error('calculation_method') is-invalid @enderror" 
                                id="calculation_method" name="calculation_method" required>
                            <option value="">-- Select Method --</option>
                            <option value="sum" {{ old('calculation_method') == 'sum' ? 'selected' : '' }}>Sum (Add all scores)</option>
                            <option value="max" {{ old('calculation_method') == 'max' ? 'selected' : '' }}>Max (Highest score)</option>
                            <option value="lookup" {{ old('calculation_method') == 'lookup' ? 'selected' : '' }}>Lookup (From table)</option>
                            <option value="formula" {{ old('calculation_method') == 'formula' ? 'selected' : '' }}>Formula (Custom)</option>
                        </select>
                        @error('calculation_method')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
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
                            <i class="mdi mdi-content-save"></i> Create Domain
                        </button>
                        <a href="{{ route('admin.cpi-domains.index') }}" class="btn btn-light">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
