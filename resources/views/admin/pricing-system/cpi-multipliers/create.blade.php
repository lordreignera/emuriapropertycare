@extends('admin.layout')

@section('content')
<div class="row">
    <div class="col-lg-8 mx-auto grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Create CPI Multiplier</h4>
                
                <form action="{{ route('admin.cpi-multipliers.store') }}" method="POST" class="forms-sample">
                    @csrf

                    <div class="form-group">
                        <label for="cpi_band_range_id">CPI Band <span class="text-danger">*</span></label>
                        <select class="form-control @error('cpi_band_range_id') is-invalid @enderror" 
                                id="cpi_band_range_id" name="cpi_band_range_id" required>
                            <option value="">Select CPI Band</option>
                            @foreach($cpiBands as $band)
                                <option value="{{ $band->id }}" {{ old('cpi_band_range_id') == $band->id ? 'selected' : '' }}>
                                    {{ $band->band_name }} ({{ $band->min_score }}-{{ $band->max_score }} points)
                                </option>
                            @endforeach
                        </select>
                        @error('cpi_band_range_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="multiplier_value">Multiplier Value <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" class="form-control @error('multiplier_value') is-invalid @enderror" 
                               id="multiplier_value" name="multiplier_value" value="{{ old('multiplier_value') }}" required>
                        @error('multiplier_value')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">e.g., 1.00, 1.08, 1.18, 1.35, 1.55</small>
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
                            <i class="mdi mdi-content-save"></i> Create Multiplier
                        </button>
                        <a href="{{ route('admin.cpi-multipliers.index') }}" class="btn btn-light">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
