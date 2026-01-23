@extends('admin.layout')

@section('content')
<div class="row">
    <div class="col-lg-8 mx-auto grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Edit Age Bracket</h4>
                
                <form action="{{ route('admin.age-brackets.update', $ageBracket) }}" method="POST" class="forms-sample">
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label for="bracket_name">Bracket Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('bracket_name') is-invalid @enderror" 
                               id="bracket_name" name="bracket_name" value="{{ old('bracket_name', $ageBracket->bracket_name) }}" required>
                        @error('bracket_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="min_age">Minimum Age (years) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('min_age') is-invalid @enderror" 
                                       id="min_age" name="min_age" value="{{ old('min_age', $ageBracket->min_age) }}" required>
                                @error('min_age')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="max_age">Maximum Age (years)</label>
                                <input type="number" class="form-control @error('max_age') is-invalid @enderror" 
                                       id="max_age" name="max_age" value="{{ old('max_age', $ageBracket->max_age) }}">
                                @error('max_age')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">Leave empty for 50+</small>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="score_points">Score Points <span class="text-danger">*</span></label>
                        <input type="number" class="form-control @error('score_points') is-invalid @enderror" 
                               id="score_points" name="score_points" value="{{ old('score_points', $ageBracket->score_points) }}" required>
                        @error('score_points')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Points contributed to CPI score</small>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="description" name="description" rows="3">{{ old('description', $ageBracket->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-check">
                        <label class="form-check-label">
                            <input type="checkbox" class="form-check-input" name="is_active" value="1" 
                                   {{ old('is_active', $ageBracket->is_active) ? 'checked' : '' }}>
                            Active
                        </label>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="mdi mdi-content-save"></i> Update Bracket
                        </button>
                        <a href="{{ route('admin.age-brackets.index') }}" class="btn btn-light">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
