@extends('admin.layout')

@section('title', $product->product_name)

@section('content')
<div class="content-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h4 class="card-title mb-0">{{ $product->product_name }}</h4>
                            <p class="text-muted small mb-0"><code>{{ $product->product_code }}</code></p>
                        </div>
                        <div class="btn-group">
                            <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary btn-sm">
                                <i class="mdi mdi-arrow-left"></i> Back
                            </a>
                            <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-warning btn-sm">
                                <i class="mdi mdi-pencil"></i> Edit
                            </a>
                        </div>
                    </div>

                    @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    @endif

                    <div class="row">
                        <div class="col-md-8">
                            <h5>Product Details</h5>
                            <table class="table table-bordered">
                                <tr>
                                    <th width="200">Category</th>
                                    <td>
                                        <span class="badge badge-{{ 
                                            $product->category === 'subscription_package' ? 'primary' :
                                            ($product->category === 'emergency' ? 'danger' :
                                            ($product->category === 'preventive' ? 'success' : 'secondary'))
                                        }}">
                                            {{ ucfirst(str_replace('_', ' ', $product->category)) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Pricing Type</th>
                                    <td>{{ ucfirst(str_replace('_', ' ', $product->pricing_type)) }}</td>
                                </tr>
                                <tr>
                                    <th>Base Price</th>
                                    <td>${{ number_format($product->base_price, 2) }}</td>
                                </tr>
                                <tr>
                                    <th>Total Price (with components)</th>
                                    <td><strong>${{ number_format($product->calculateTotalPrice(), 2) }}</strong></td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td>
                                        @if($product->is_active)
                                        <span class="badge badge-success">Active</span>
                                        @else
                                        <span class="badge badge-secondary">Inactive</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Customizable</th>
                                    <td>{{ $product->is_customizable ? 'Yes' : 'No' }}</td>
                                </tr>
                                <tr>
                                    <th>Description</th>
                                    <td>{{ $product->description ?? 'No description' }}</td>
                                </tr>
                            </table>
                        </div>

                        <div class="col-md-4">
                            <h5>Statistics</h5>
                            <div class="card bg-light">
                                <div class="card-body">
                                    <p><strong>Components:</strong> {{ $product->components->count() }}</p>
                                    <p><strong>Custom Products:</strong> {{ $product->customProducts->count() }}</p>
                                    <p><strong>Created:</strong> {{ $product->created_at->format('M d, Y') }}</p>
                                    <p class="mb-0"><strong>Created By:</strong> {{ $product->creator->name ?? 'Unknown' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Components ({{ $product->components->count() }})</h5>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addComponentModal">
                            <i class="mdi mdi-plus"></i> Add Component
                        </button>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Component Name</th>
                                    <th>Type</th>
                                    <th>Parameter</th>
                                    <th>Value</th>
                                    <th>Unit Cost</th>
                                    <th>Calculated Cost</th>
                                    <th>Required</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($product->components as $component)
                                <tr>
                                    <td>
                                        <strong>{{ $component->component_name }}</strong>
                                        @if($component->description)
                                        <br><small class="text-muted">{{ $component->description }}</small>
                                        @endif
                                    </td>
                                    <td><span class="badge badge-info">{{ ucfirst($component->calculation_type) }}</span></td>
                                    <td>{{ $component->parameter_name ?? '-' }}</td>
                                    <td>{{ $component->parameter_value }}</td>
                                    <td>${{ number_format($component->unit_cost, 2) }}</td>
                                    <td><strong>${{ number_format($component->calculated_cost, 2) }}</strong></td>
                                    <td>
                                        @if($component->is_required)
                                        <i class="mdi mdi-check text-success"></i>
                                        @else
                                        <i class="mdi mdi-close text-danger"></i>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <p class="text-muted mb-0">No components added yet. Click "Add Component" to get started.</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($product->components->count() > 0)
                    <div class="alert alert-info mt-3">
                        <i class="mdi mdi-information"></i>
                        <strong>Note:</strong> Component costs are automatically calculated based on their type and parameters.
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Component Modal -->
<div class="modal fade" id="addComponentModal" tabindex="-1" aria-labelledby="addComponentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="background-color: #ffffff;">
            <div class="modal-header" style="background-color: #ffffff;">
                <h5 class="modal-title" id="addComponentModalLabel" style="color: #2d3748;">Add Component to {{ $product->product_name }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.products.add-component', $product) }}" method="POST">
                @csrf
                <div class="modal-body" style="background-color: #ffffff;">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="component_name" class="form-label">Component Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="component_name" name="component_name" required>
                            <small class="text-muted">E.g., Labor Cost, Materials, Equipment Rental</small>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="calculation_type" class="form-label">Calculation Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="calculation_type" name="calculation_type" required>
                                <option value="">Select Type</option>
                                <option value="fixed">Fixed - Use unit cost as-is</option>
                                <option value="multiply">Multiply - Value × Unit Cost</option>
                                <option value="hourly">Hourly - Hours × Hourly Rate</option>
                                <option value="percentage">Percentage - (Value %) of Unit Cost</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="unit_cost" class="form-label">Unit Cost ($) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" id="unit_cost" name="unit_cost" required>
                            <small class="text-muted">Base cost for calculations</small>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="parameter_name" class="form-label">Parameter Name</label>
                            <input type="text" class="form-control" id="parameter_name" name="parameter_name">
                            <small class="text-muted">E.g., Hours, Quantity, Square Feet</small>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="parameter_value" class="form-label">Parameter Value <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" id="parameter_value" name="parameter_value" required value="1">
                            <small class="text-muted">Used in calculation (default: 1)</small>
                        </div>

                        <div class="col-md-12 mb-3">
                            <div class="alert alert-light">
                                <strong>Calculation Preview:</strong>
                                <div id="calculationPreview" class="mt-2">
                                    Enter values to see calculation
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_required" name="is_required" checked>
                                <label class="form-check-label" for="is_required">
                                    Required Component
                                </label>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_customizable" name="is_customizable">
                                <label class="form-check-label" for="is_customizable">
                                    Client Can Customize
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="background-color: #ffffff;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="mdi mdi-plus"></i> Add Component
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Real-time calculation preview
    function updateCalculationPreview() {
        const type = document.getElementById('calculation_type').value;
        const unitCost = parseFloat(document.getElementById('unit_cost').value) || 0;
        const paramValue = parseFloat(document.getElementById('parameter_value').value) || 1;
        const preview = document.getElementById('calculationPreview');
        
        let result = 0;
        let formula = '';
        
        switch(type) {
            case 'fixed':
                result = unitCost;
                formula = `Fixed Cost = $${unitCost.toFixed(2)}`;
                break;
            case 'multiply':
                result = paramValue * unitCost;
                formula = `${paramValue} × $${unitCost.toFixed(2)} = $${result.toFixed(2)}`;
                break;
            case 'hourly':
                result = paramValue * unitCost;
                formula = `${paramValue} hours × $${unitCost.toFixed(2)}/hour = $${result.toFixed(2)}`;
                break;
            case 'percentage':
                result = (paramValue / 100) * unitCost;
                formula = `${paramValue}% of $${unitCost.toFixed(2)} = $${result.toFixed(2)}`;
                break;
            default:
                formula = 'Select calculation type to see preview';
        }
        
        preview.innerHTML = `<code>${formula}</code>`;
    }
    
    // Attach event listeners
    document.getElementById('calculation_type').addEventListener('change', updateCalculationPreview);
    document.getElementById('unit_cost').addEventListener('input', updateCalculationPreview);
    document.getElementById('parameter_value').addEventListener('input', updateCalculationPreview);
    
    // Update parameter name placeholder based on calculation type
    document.getElementById('calculation_type').addEventListener('change', function() {
        const paramNameField = document.getElementById('parameter_name');
        const type = this.value;
        
        switch(type) {
            case 'hourly':
                paramNameField.placeholder = 'Hours';
                break;
            case 'multiply':
                paramNameField.placeholder = 'Quantity';
                break;
            case 'percentage':
                paramNameField.placeholder = 'Percentage';
                break;
            default:
                paramNameField.placeholder = 'Parameter Name';
        }
    });
</script>
@endpush
