@extends('admin.layout')

@section('content')
<div class="row">
    <div class="col-lg-8 mx-auto grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Create CPI Band Range</h4>
                
                <form action="{{ route('admin.cpi-bands.store') }}" method="POST" class="forms-sample">
                    @csrf

                    <div class="form-group">
                        <label for="band_name">Band Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('band_name') is-invalid @enderror" 
                               id="band_name" name="band_name" value="{{ old('band_name') }}" required>
                        @error('band_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="band_slug">Band Slug <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('band_slug') is-invalid @enderror" 
                               id="band_slug" name="band_slug" value="{{ old('band_slug') }}" required>
                        @error('band_slug')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">e.g., CPI-0, CPI-1, CPI-2</small>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="min_score">Minimum Score <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('min_score') is-invalid @enderror" 
                                       id="min_score" name="min_score" value="{{ old('min_score') }}" required>
                                @error('min_score')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="max_score">Maximum Score <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('max_score') is-invalid @enderror" 
                                       id="max_score" name="max_score" value="{{ old('max_score') }}" required>
                                @error('max_score')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="display_name">Display Name</label>
                        <input type="text" class="form-control @error('display_name') is-invalid @enderror" 
                               id="display_name" name="display_name" value="{{ old('display_name') }}">
                        @error('display_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">e.g., Excellent, Good, Fair, Poor</small>
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
                            <i class="mdi mdi-content-save"></i> Create Band
                        </button>
                        <a href="{{ route('admin.cpi-bands.index') }}" class="btn btn-light">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
