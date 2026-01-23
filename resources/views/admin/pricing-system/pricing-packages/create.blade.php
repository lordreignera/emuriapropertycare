@extends('admin.layout')

@section('content')
<div class="row">
    <div class="col-lg-10 mx-auto grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Create Pricing Package</h4>
                <p class="card-description">Add a new care package with pricing for each property type</p>

                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <form action="{{ route('admin.pricing-packages.store') }}" method="POST" class="forms-sample">
                    @csrf

                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="package_name">Package Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('package_name') is-invalid @enderror" 
                                       id="package_name" name="package_name" value="{{ old('package_name') }}" required>
                                @error('package_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="sort_order">Sort Order</label>
                                <input type="number" class="form-control @error('sort_order') is-invalid @enderror" 
                                       id="sort_order" name="sort_order" value="{{ old('sort_order', 0) }}">
                                @error('sort_order')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="description" name="description" rows="3">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="features">Features (one per line)</label>
                        <textarea class="form-control @error('features') is-invalid @enderror" 
                                  id="features" name="features" rows="5">{{ old('features') }}</textarea>
                        @error('features')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Enter each feature on a new line</small>
                    </div>

                    <hr class="my-4">

                    <h5 class="mb-3">Pricing by Property Type</h5>
                    <p class="text-muted small">Set the base monthly price for each property type. Leave blank if not applicable.</p>

                    <div class="row">
                        @foreach($propertyTypes as $type)
                            <div class="col-md-4 mb-3">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <label for="price_{{ $type->id }}" class="form-label">
                                            <strong>{{ $type->type_name }}</strong>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" 
                                                   step="0.01" 
                                                   class="form-control @error('prices.'.$type->id) is-invalid @enderror" 
                                                   id="price_{{ $type->id }}" 
                                                   name="prices[{{ $type->id }}]" 
                                                   value="{{ old('prices.'.$type->id) }}"
                                                   placeholder="0.00">
                                        </div>
                                        @error('prices.'.$type->id)
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                        <small class="text-muted">Base price per month</small>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="form-check mt-3">
                        <label class="form-check-label">
                            <input type="checkbox" class="form-check-input" name="is_active" value="1" 
                                   {{ old('is_active', true) ? 'checked' : '' }}>
                            Active
                        </label>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="mdi mdi-content-save"></i> Create Package
                        </button>
                        <a href="{{ route('admin.pricing-packages.index') }}" class="btn btn-light">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
