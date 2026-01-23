@extends('admin.layout')

@section('content')
<div class="row">
    <div class="col-lg-8 mx-auto grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Edit Equipment Requirement</h4>
                
                <form action="{{ route('admin.equipment-requirements.update', $equipmentRequirement) }}" method="POST" class="forms-sample">
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label for="requirement_code">Requirement Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('requirement_code') is-invalid @enderror" 
                               id="requirement_code" name="requirement_code" value="{{ old('requirement_code', $equipmentRequirement->requirement_code) }}" required>
                        @error('requirement_code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Unique identifier (e.g., EQUIP_01)</small>
                    </div>

                    <div class="form-group">
                        <label for="requirement_name">Requirement Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('requirement_name') is-invalid @enderror" 
                               id="requirement_name" name="requirement_name" value="{{ old('requirement_name', $equipmentRequirement->requirement_name) }}" required>
                        @error('requirement_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="score_points">Score Points <span class="text-danger">*</span></label>
                        <input type="number" class="form-control @error('score_points') is-invalid @enderror" 
                               id="score_points" name="score_points" value="{{ old('score_points', $equipmentRequirement->score_points) }}" required>
                        @error('score_points')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Points contributed to CPI score</small>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="description" name="description" rows="3">{{ old('description', $equipmentRequirement->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-check">
                        <label class="form-check-label">
                            <input type="checkbox" class="form-check-input" name="is_active" value="1" 
                                   {{ old('is_active', $equipmentRequirement->is_active) ? 'checked' : '' }}>
                            Active
                        </label>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="mdi mdi-content-save"></i> Update Requirement
                        </button>
                        <a href="{{ route('admin.equipment-requirements.index') }}" class="btn btn-light">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
