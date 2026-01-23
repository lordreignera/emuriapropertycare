@extends('admin.layout')

@section('content')
<div class="row">
    <div class="col-lg-8 mx-auto grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Create Residential Size Tier</h4>
                
                <form action="{{ route('admin.residential-tiers.store') }}" method="POST" class="forms-sample">
                    @csrf

                    <div class="form-group">
                        <label for="tier_name">Tier Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('tier_name') is-invalid @enderror" 
                               id="tier_name" name="tier_name" value="{{ old('tier_name') }}" required>
                        @error('tier_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="min_units">Minimum Units <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('min_units') is-invalid @enderror" 
                                       id="min_units" name="min_units" value="{{ old('min_units') }}" required>
                                @error('min_units')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="max_units">Maximum Units</label>
                                <input type="number" class="form-control @error('max_units') is-invalid @enderror" 
                                       id="max_units" name="max_units" value="{{ old('max_units') }}">
                                @error('max_units')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">Leave empty for 51+</small>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="size_factor">Size Factor <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" class="form-control @error('size_factor') is-invalid @enderror" 
                               id="size_factor" name="size_factor" value="{{ old('size_factor') }}" required>
                        @error('size_factor')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Multiplier applied to base price (e.g., 1.0, 2.5, 5.0, 10.0)</small>
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
                            <i class="mdi mdi-content-save"></i> Create Tier
                        </button>
                        <a href="{{ route('admin.residential-tiers.index') }}" class="btn btn-light">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
