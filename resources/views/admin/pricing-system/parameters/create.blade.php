@extends('admin.layout')

@section('content')
<div class="row">
    <div class="col-lg-8 mx-auto grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Create CPI Parameter</h4>

                <form action="{{ route('admin.parameters.store') }}" method="POST" class="forms-sample">
                    @csrf

                    <div class="form-group">
                        <label for="parameter_key">Parameter Key <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('parameter_key') is-invalid @enderror"
                               id="parameter_key" name="parameter_key" value="{{ old('parameter_key') }}" required>
                        @error('parameter_key')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Example: RES_ESS_FACTOR</small>
                    </div>

                    <div class="form-group">
                        <label for="parameter_value">Parameter Value <span class="text-danger">*</span></label>
                        <input type="number" step="0.000001" class="form-control @error('parameter_value') is-invalid @enderror"
                               id="parameter_value" name="parameter_value" value="{{ old('parameter_value') }}" required>
                        @error('parameter_value')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="group_name">Group</label>
                        <input type="text" class="form-control @error('group_name') is-invalid @enderror"
                               id="group_name" name="group_name" value="{{ old('group_name', 'base_service_pricing') }}">
                        @error('group_name')
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
                            <input type="checkbox" class="form-check-input" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                            Active
                        </label>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="mdi mdi-content-save"></i> Create Parameter
                        </button>
                        <a href="{{ route('admin.parameters.index') }}" class="btn btn-light">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
