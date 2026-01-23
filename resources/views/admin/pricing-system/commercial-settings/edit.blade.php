@extends('admin.layout')

@section('content')
<div class="row">
    <div class="col-lg-8 mx-auto grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Edit Commercial Size Setting</h4>
                
                <form action="{{ route('admin.commercial-settings.update', $commercialSetting) }}" method="POST" class="forms-sample">
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label for="setting_name">Setting Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('setting_name') is-invalid @enderror" 
                               id="setting_name" name="setting_name" value="{{ old('setting_name', $commercialSetting->setting_name) }}" required>
                        @error('setting_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">e.g., base_sqft_divisor, min_factor, max_factor</small>
                    </div>

                    <div class="form-group">
                        <label for="setting_value">Setting Value</label>
                        <input type="number" step="0.01" class="form-control @error('setting_value') is-invalid @enderror" 
                               id="setting_value" name="setting_value" value="{{ old('setting_value', $commercialSetting->setting_value) }}">
                        @error('setting_value')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Leave blank for null values (like max_factor with no cap)</small>
                    </div>

                    <div class="form-group">
                        <label for="data_type">Data Type <span class="text-danger">*</span></label>
                        <select class="form-control @error('data_type') is-invalid @enderror" 
                                id="data_type" name="data_type" required>
                            <option value="decimal" {{ old('data_type', $commercialSetting->data_type) == 'decimal' ? 'selected' : '' }}>Decimal</option>
                            <option value="integer" {{ old('data_type', $commercialSetting->data_type) == 'integer' ? 'selected' : '' }}>Integer</option>
                            <option value="string" {{ old('data_type', $commercialSetting->data_type) == 'string' ? 'selected' : '' }}>String</option>
                        </select>
                        @error('data_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="description" name="description" rows="3">{{ old('description', $commercialSetting->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="mdi mdi-content-save"></i> Update Setting
                        </button>
                        <a href="{{ route('admin.commercial-settings.index') }}" class="btn btn-light">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
