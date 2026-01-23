@extends('admin.layout')

@section('content')
<div class="row">
    <div class="col-lg-8 mx-auto grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Edit Supply Line Material</h4>
                
                <form action="{{ route('admin.supply-materials.update', $supplyMaterial) }}" method="POST" class="forms-sample">
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label for="material_code">Material Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('material_code') is-invalid @enderror" 
                               id="material_code" name="material_code" value="{{ old('material_code', $supplyMaterial->material_code) }}" required>
                        @error('material_code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Unique identifier (e.g., COPPER, PEX, CPVC)</small>
                    </div>

                    <div class="form-group">
                        <label for="material_name">Material Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('material_name') is-invalid @enderror" 
                               id="material_name" name="material_name" value="{{ old('material_name', $supplyMaterial->material_name) }}" required>
                        @error('material_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="score_points">Score Points <span class="text-danger">*</span></label>
                        <input type="number" class="form-control @error('score_points') is-invalid @enderror" 
                               id="score_points" name="score_points" value="{{ old('score_points', $supplyMaterial->score_points) }}" required>
                        @error('score_points')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Points contributed to CPI score</small>
                    </div>

                    <div class="form-group">
                        <label for="risk_level">Risk Level</label>
                        <select class="form-control @error('risk_level') is-invalid @enderror" 
                                id="risk_level" name="risk_level">
                            <option value="">Select Risk Level</option>
                            <option value="low" {{ old('risk_level', $supplyMaterial->risk_level) == 'low' ? 'selected' : '' }}>Low</option>
                            <option value="medium" {{ old('risk_level', $supplyMaterial->risk_level) == 'medium' ? 'selected' : '' }}>Medium</option>
                            <option value="high" {{ old('risk_level', $supplyMaterial->risk_level) == 'high' ? 'selected' : '' }}>High</option>
                        </select>
                        @error('risk_level')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="description" name="description" rows="3">{{ old('description', $supplyMaterial->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-check">
                        <label class="form-check-label">
                            <input type="checkbox" class="form-check-input" name="is_active" value="1" 
                                   {{ old('is_active', $supplyMaterial->is_active) ? 'checked' : '' }}>
                            Active
                        </label>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="mdi mdi-content-save"></i> Update Material
                        </button>
                        <a href="{{ route('admin.supply-materials.index') }}" class="btn btn-light">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
