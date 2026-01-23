@extends('admin.layout')

@section('content')
<div class="row">
    <div class="col-lg-8 mx-auto grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Edit Crawl Access Category</h4>
                
                <form action="{{ route('admin.crawl-access.update', $crawlAccess) }}" method="POST" class="forms-sample">
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label for="category_code">Category Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('category_code') is-invalid @enderror" 
                               id="category_code" name="category_code" value="{{ old('category_code', $crawlAccess->category_code) }}" required>
                        @error('category_code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Unique identifier (e.g., CRAWL_01)</small>
                    </div>

                    <div class="form-group">
                        <label for="category_name">Category Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('category_name') is-invalid @enderror" 
                               id="category_name" name="category_name" value="{{ old('category_name', $crawlAccess->category_name) }}" required>
                        @error('category_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="score_points">Score Points <span class="text-danger">*</span></label>
                        <input type="number" class="form-control @error('score_points') is-invalid @enderror" 
                               id="score_points" name="score_points" value="{{ old('score_points', $crawlAccess->score_points) }}" required>
                        @error('score_points')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Points contributed to CPI score</small>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="description" name="description" rows="3">{{ old('description', $crawlAccess->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-check">
                        <label class="form-check-label">
                            <input type="checkbox" class="form-check-input" name="is_active" value="1" 
                                   {{ old('is_active', $crawlAccess->is_active) ? 'checked' : '' }}>
                            Active
                        </label>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="mdi mdi-content-save"></i> Update Category
                        </button>
                        <a href="{{ route('admin.crawl-access.index') }}" class="btn btn-light">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
