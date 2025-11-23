@extends('admin.layout')

@section('title', 'Create Product')

@section('content')
<div class="content-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h4 class="card-title mb-0">Create New Product</h4>
                            <p class="text-muted small mb-0">Create a base product template for custom client offers</p>
                        </div>
                        <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="mdi mdi-arrow-left"></i> Back to Products
                        </a>
                    </div>

                    @if ($errors->any())
                    <div class="alert alert-danger">
                        <strong>Validation Errors:</strong>
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    <form action="{{ route('admin.products.store') }}" method="POST">
                        @csrf

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="product_code" class="form-label">Product Code <span class="text-danger">*</span></label>
                                    <input type="text" 
                                           class="form-control @error('product_code') is-invalid @enderror" 
                                           id="product_code" 
                                           name="product_code" 
                                           value="{{ old('product_code', 'PROD-' . substr(time(), 0, 10)) }}"
                                           readonly
                                           style="background-color: #f8f9fa;">
                                    @error('product_code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Auto-generated unique identifier</small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="product_name" class="form-label">Product Name <span class="text-danger">*</span></label>
                                    <input type="text" 
                                           class="form-control @error('product_name') is-invalid @enderror" 
                                           id="product_name" 
                                           name="product_name" 
                                           value="{{ old('product_name') }}"
                                           placeholder="e.g., ETOGO - 2 Visits Per Year"
                                           required>
                                    @error('product_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" 
                                      name="description" 
                                      rows="3"
                                      placeholder="Detailed description of this product and what it includes">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="category" class="form-label">Category <span class="text-danger">*</span></label>
                                    <select class="form-select @error('category') is-invalid @enderror" 
                                            id="category" 
                                            name="category" 
                                            required>
                                        <option value="">Select Category</option>
                                        <option value="maintenance" {{ old('category') == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                                        <option value="inspection" {{ old('category') == 'inspection' ? 'selected' : '' }}>Inspection</option>
                                        <option value="repair" {{ old('category') == 'repair' ? 'selected' : '' }}>Repair</option>
                                        <option value="emergency" {{ old('category') == 'emergency' ? 'selected' : '' }}>Emergency</option>
                                        <option value="preventive" {{ old('category') == 'preventive' ? 'selected' : '' }}>Preventive</option>
                                        <option value="subscription_package" {{ old('category') == 'subscription_package' ? 'selected' : '' }}>Subscription Package</option>
                                        <option value="custom" {{ old('category') == 'custom' ? 'selected' : '' }}>Custom</option>
                                    </select>
                                    @error('category')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="pricing_type" class="form-label">Pricing Type <span class="text-danger">*</span></label>
                                    <select class="form-select @error('pricing_type') is-invalid @enderror" 
                                            id="pricing_type" 
                                            name="pricing_type" 
                                            required>
                                        <option value="">Select Pricing Type</option>
                                        <option value="fixed" {{ old('pricing_type') == 'fixed' ? 'selected' : '' }}>Fixed Price</option>
                                        <option value="component_based" {{ old('pricing_type', 'component_based') == 'component_based' ? 'selected' : '' }}>Component Based (Recommended)</option>
                                        <option value="subscription" {{ old('pricing_type') == 'subscription' ? 'selected' : '' }}>Subscription</option>
                                        <option value="pay_per_use" {{ old('pricing_type') == 'pay_per_use' ? 'selected' : '' }}>Pay Per Use</option>
                                    </select>
                                    @error('pricing_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="base_price" class="form-label">Base Price ($) <span class="text-danger">*</span></label>
                                    <input type="number" 
                                           class="form-control @error('base_price') is-invalid @enderror" 
                                           id="base_price" 
                                           name="base_price" 
                                           value="{{ old('base_price', '0.00') }}"
                                           step="0.01"
                                           min="0"
                                           required>
                                    @error('base_price')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Base price before components</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           id="is_active" 
                                           name="is_active" 
                                           value="1"
                                           {{ old('is_active', true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">
                                        <strong>Active</strong>
                                        <small class="d-block text-muted">Make this product available for use</small>
                                    </label>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           id="is_customizable" 
                                           name="is_customizable" 
                                           value="1"
                                           {{ old('is_customizable', true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_customizable">
                                        <strong>Customizable</strong>
                                        <small class="d-block text-muted">Allow creating custom versions for clients</small>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <i class="mdi mdi-information"></i>
                            <strong>Next Step:</strong> After creating the product, you'll be able to add components (visits, complexity multipliers, access adjustments, etc.) to build the ETOGO pricing formula.
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('admin.products.index') }}" class="btn btn-light">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="mdi mdi-check"></i> Create Product
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Auto-generate product code on page load with current timestamp
    document.addEventListener('DOMContentLoaded', function() {
        const productCodeInput = document.getElementById('product_code');
        const timestamp = Math.floor(Date.now() / 1000); // 10-digit Unix timestamp
        productCodeInput.value = 'PROD-' + timestamp;
    });

    document.getElementById('pricing_type').addEventListener('change', function() {
        const basePriceInput = document.getElementById('base_price');
        const basePriceHelp = basePriceInput.nextElementSibling.nextElementSibling;
        
        if (this.value === 'component_based') {
            basePriceHelp.textContent = 'Base price before components (can be $0 if fully component-based)';
        } else if (this.value === 'fixed') {
            basePriceHelp.textContent = 'Total fixed price for this product';
        } else {
            basePriceHelp.textContent = 'Base price before components';
        }
    });
</script>
@endpush
