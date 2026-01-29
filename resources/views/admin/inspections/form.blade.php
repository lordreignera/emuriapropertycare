@extends('admin.layout')

@section('title', 'Property Inspection Form')

@section('content')
<div class="content-wrapper">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="card mb-3 border-0 shadow-sm" style="background: linear-gradient(135deg, #5b67ca 0%, #4854b8 100%);">
                <div class="card-body text-white p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-2 fw-bold">
                                <i class="mdi mdi-clipboard-check me-2"></i>Property Inspection Form
                            </h3>
                            <p class="mb-1 opacity-75">
                                <span class="badge bg-light text-dark me-2">{{ $property->property_code }}</span>
                                {{ $property->property_name }}
                            </p>
                            <p class="mb-0 opacity-75">
                                <i class="mdi mdi-map-marker me-1"></i>{{ $property->property_address }}, {{ $property->city }}
                            </p>
                        </div>
                        <div>
                            <span class="badge bg-warning text-dark fs-6 px-3 py-2">
                                {{ ucfirst(str_replace('_', ' ', $property->type)) }} Property
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <form action="{{ route('inspections.store') }}" method="POST" enctype="multipart/form-data" id="inspectionForm">
                @csrf
                <input type="hidden" name="property_id" value="{{ $property->id }}">
                <input type="hidden" name="property_type" value="{{ $property->type }}">

                <!-- Inspection Overview -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="mdi mdi-information text-primary me-2"></i>Inspection Overview
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Inspection Date & Time <span class="text-danger">*</span></label>
                                    <input type="datetime-local" name="inspection_date" class="form-control" 
                                           value="{{ old('inspection_date', $inspection->scheduled_date ?? now()->format('Y-m-d\TH:i')) }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Inspector <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" value="{{ Auth::user()->name }}" readonly>
                                    <input type="hidden" name="inspector_id" value="{{ Auth::id() }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Weather Conditions</label>
                                    <select name="weather_conditions" class="form-control">
                                        <option value="clear">Clear</option>
                                        <option value="cloudy">Cloudy</option>
                                        <option value="rainy">Rainy</option>
                                        <option value="snowy">Snowy</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>General Summary/Overview</label>
                            <textarea name="summary" class="form-control" rows="3" 
                                      placeholder="Provide a brief overview of the property condition...">{{ old('summary') }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Inspection Categories - Dynamic Based on Property Type -->
                @php
                    $categories = [
                        'interior_walls_trim_paint' => [
                            'title' => 'Interior Walls, Trim & Paint',
                            'icon' => 'mdi-wall',
                            'description' => 'Inspect walls, baseboards, trim, and paint condition',
                            'all_types' => true
                        ],
                        'windows_trim' => [
                            'title' => 'Windows & Trim',
                            'icon' => 'mdi-window-closed-variant',
                            'description' => 'Check window frames, glass, seals, and operability',
                            'all_types' => true
                        ],
                        'doors_hardware' => [
                            'title' => 'Doors & Hardware',
                            'icon' => 'mdi-door',
                            'description' => 'Inspect doors, handles, locks, and hinges',
                            'all_types' => true
                        ],
                        'floors' => [
                            'title' => 'Floors',
                            'icon' => 'mdi-floor-plan',
                            'description' => 'Check flooring materials, condition, and levelness',
                            'all_types' => true
                        ],
                        'bathrooms' => [
                            'title' => 'Bathrooms',
                            'icon' => 'mdi-shower',
                            'description' => 'Inspect fixtures, tiles, ventilation, and water pressure',
                            'residential' => true
                        ],
                        'kitchen' => [
                            'title' => 'Kitchen',
                            'icon' => 'mdi-stove',
                            'description' => 'Check appliances, cabinets, countertops, and plumbing',
                            'residential' => true
                        ],
                        'electrical' => [
                            'title' => 'Electrical Systems',
                            'icon' => 'mdi-lightning-bolt',
                            'description' => 'Inspect outlets, switches, panel, and wiring',
                            'all_types' => true
                        ],
                        'plumbing' => [
                            'title' => 'Plumbing Systems',
                            'icon' => 'mdi-pipe',
                            'description' => 'Check pipes, fixtures, water heater, and drainage',
                            'all_types' => true
                        ],
                        'ventilation' => [
                            'title' => 'HVAC & Ventilation',
                            'icon' => 'mdi-fan',
                            'description' => 'Inspect heating, cooling, and ventilation systems',
                            'all_types' => true
                        ],
                        'exterior' => [
                            'title' => 'Exterior Walls & Siding',
                            'icon' => 'mdi-home-siding',
                            'description' => 'Check exterior walls, siding, and finishes',
                            'all_types' => true
                        ],
                        'roof_drainage' => [
                            'title' => 'Roof & Drainage',
                            'icon' => 'mdi-home-roof',
                            'description' => 'Inspect roof condition, gutters, and drainage',
                            'all_types' => true
                        ],
                        'deck_stairs' => [
                            'title' => 'Deck, Stairs & Railings',
                            'icon' => 'mdi-stairs',
                            'description' => 'Check decks, stairs, railings, and safety',
                            'residential' => true
                        ],
                        'landscaping_pruning' => [
                            'title' => 'Landscaping & Grounds',
                            'icon' => 'mdi-tree',
                            'description' => 'Inspect grounds, trees, and landscaping',
                            'all_types' => true
                        ],
                        'accessibility' => [
                            'title' => 'Accessibility Features',
                            'icon' => 'mdi-wheelchair-accessibility',
                            'description' => 'Check accessibility compliance and features',
                            'commercial' => true
                        ],
                        'garage' => [
                            'title' => 'Garage & Storage',
                            'icon' => 'mdi-garage',
                            'description' => 'Inspect garage doors, storage areas, and structure',
                            'residential' => true
                        ],
                        'foundation_sump' => [
                            'title' => 'Foundation & Basement',
                            'icon' => 'mdi-foundation',
                            'description' => 'Check foundation, basement, and sump pump',
                            'all_types' => true
                        ],
                        'improvement_projects' => [
                            'title' => 'Improvement Opportunities',
                            'icon' => 'mdi-lightbulb-on',
                            'description' => 'Identify potential upgrades and improvements',
                            'all_types' => true
                        ],
                    ];

                    // Filter categories based on property type
                    $propertyType = $property->type;
                    $visibleCategories = [];
                    foreach ($categories as $key => $category) {
                        if (isset($category['all_types']) && $category['all_types']) {
                            $visibleCategories[$key] = $category;
                        } elseif ($propertyType === 'residential' && isset($category['residential'])) {
                            $visibleCategories[$key] = $category;
                        } elseif ($propertyType === 'commercial' && isset($category['commercial'])) {
                            $visibleCategories[$key] = $category;
                        } elseif ($propertyType === 'mixed_use') {
                            // Mixed use gets all categories
                            $visibleCategories[$key] = $category;
                        }
                    }
                @endphp

                <!-- Category Accordion -->
                <div class="accordion" id="inspectionAccordion">
                    @foreach($visibleCategories as $categoryKey => $category)
                    <div class="card mb-3">
                        <div class="card-header" id="heading-{{ $categoryKey }}">
                            <h5 class="mb-0">
                                <button class="btn btn-link w-100 text-start d-flex justify-content-between align-items-center" 
                                        type="button" 
                                        data-bs-toggle="collapse" 
                                        data-bs-target="#collapse-{{ $categoryKey }}" 
                                        aria-expanded="false">
                                    <span>
                                        <i class="mdi {{ $category['icon'] }} text-primary me-2"></i>
                                        <strong>{{ $category['title'] }}</strong>
                                        <small class="text-muted ms-2">{{ $category['description'] }}</small>
                                    </span>
                                    <span class="badge bg-secondary items-count-{{ $categoryKey }}">0 items</span>
                                </button>
                            </h5>
                        </div>
                        <div id="collapse-{{ $categoryKey }}" class="collapse" data-bs-parent="#inspectionAccordion">
                            <div class="card-body">
                                <!-- Items Container -->
                                <div id="items-{{ $categoryKey }}" class="inspection-items">
                                    <!-- Items will be added here dynamically -->
                                </div>
                                
                                <!-- Add Item Button -->
                                <button type="button" class="btn btn-outline-primary btn-sm mt-2" 
                                        onclick="addInspectionItem('{{ $categoryKey }}', '{{ $category['title'] }}')">
                                    <i class="mdi mdi-plus me-1"></i>Add {{ $category['title'] }} Item
                                </button>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <!-- Overall Assessment -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="mdi mdi-clipboard-text text-primary me-2"></i>Overall Assessment
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Overall Property Condition <span class="text-danger">*</span></label>
                                    <select name="overall_condition" class="form-control" required>
                                        <option value="">Select Condition</option>
                                        <option value="excellent">Excellent</option>
                                        <option value="good">Good</option>
                                        <option value="fair">Fair</option>
                                        <option value="poor">Poor</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Estimated Total Repair Cost</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" name="estimated_cost" class="form-control" 
                                               placeholder="0.00" step="0.01" readonly id="totalEstimatedCost">
                                    </div>
                                    <small class="text-muted">Auto-calculated from selected recommendations</small>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Final Recommendations</label>
                            <textarea name="final_recommendations" class="form-control" rows="4" 
                                      placeholder="Provide final recommendations and priority actions..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- Photos Upload -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="mdi mdi-camera text-primary me-2"></i>Inspection Photos
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>Upload Photos (Max 20 photos, 10MB each)</label>
                            <input type="file" name="photos[]" class="form-control" multiple accept="image/*" id="photoUpload">
                            <small class="text-muted">Select multiple photos from your inspection</small>
                        </div>
                        <div id="photoPreview" class="row mt-3"></div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('inspections.index') }}" class="btn btn-secondary">
                                <i class="mdi mdi-arrow-left me-2"></i>Cancel
                            </a>
                            <div>
                                <button type="submit" name="status" value="in_progress" class="btn btn-warning me-2">
                                    <i class="mdi mdi-content-save me-2"></i>Save as Draft
                                </button>
                                <button type="submit" name="status" value="completed" class="btn btn-success">
                                    <i class="mdi mdi-check-circle me-2"></i>Complete Inspection
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Item Template (Hidden) -->
<template id="inspection-item-template">
    <div class="inspection-item border rounded p-3 mb-3">
        <div class="d-flex justify-content-between align-items-start mb-2">
            <h6 class="mb-0">Item <span class="item-number"></span></h6>
            <button type="button" class="btn btn-sm btn-danger" onclick="removeInspectionItem(this)">
                <i class="mdi mdi-delete"></i>
            </button>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Issue/Problem Description <span class="text-danger">*</span></label>
                    <input type="text" name="CATEGORY[INDEX][issue]" class="form-control" 
                           placeholder="e.g., Cracked wall, Leaking faucet..." required>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Location/Room <span class="text-danger">*</span></label>
                    <input type="text" name="CATEGORY[INDEX][location]" class="form-control" 
                           placeholder="e.g., Living Room, Bedroom 1" required>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Specific Spot</label>
                    <input type="text" name="CATEGORY[INDEX][spot]" class="form-control" 
                           placeholder="e.g., North wall, Near window">
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label>Severity <span class="text-danger">*</span></label>
                    <select name="CATEGORY[INDEX][severity]" class="form-control" required>
                        <option value="">Select Severity</option>
                        <option value="minor">Minor</option>
                        <option value="moderate">Moderate</option>
                        <option value="severe">Severe</option>
                        <option value="critical">Critical</option>
                    </select>
                </div>
            </div>
            <div class="col-md-8">
                <div class="form-group">
                    <label>Notes/Details</label>
                    <textarea name="CATEGORY[INDEX][notes]" class="form-control" rows="2" 
                              placeholder="Additional observations..."></textarea>
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <label>Recommended Actions (Select from CPI Items)</label>
            <button type="button" class="btn btn-sm btn-primary mb-2" onclick="openCPISelector(this, 'CATEGORY', 'INDEX')">
                <i class="mdi mdi-plus me-1"></i>Add Recommendation
            </button>
            <div class="recommendations-container"></div>
        </div>
        
        <div class="form-group">
            <label>Item Photo (Optional)</label>
            <input type="file" name="CATEGORY[INDEX][photo]" class="form-control" accept="image/*">
        </div>
    </div>
</template>

@endsection

@push('scripts')
<script>
let itemCounters = {};

function addInspectionItem(category, title) {
    if (!itemCounters[category]) {
        itemCounters[category] = 0;
    }
    itemCounters[category]++;
    
    const template = document.getElementById('inspection-item-template');
    const clone = template.content.cloneNode(true);
    
    // Replace placeholders
    const html = clone.firstElementChild.outerHTML
        .replaceAll('CATEGORY', category)
        .replaceAll('INDEX', itemCounters[category]);
    
    // Create temp div to parse HTML
    const temp = document.createElement('div');
    temp.innerHTML = html;
    const newItem = temp.firstElementChild;
    
    // Update item number
    newItem.querySelector('.item-number').textContent = itemCounters[category];
    
    // Add to container
    document.getElementById('items-' + category).appendChild(newItem);
    
    // Update counter badge
    updateItemCount(category);
}

function removeInspectionItem(button) {
    const item = button.closest('.inspection-item');
    const container = item.closest('.inspection-items');
    const category = container.id.replace('items-', '');
    
    item.remove();
    itemCounters[category]--;
    updateItemCount(category);
    updateTotalCost();
}

function updateItemCount(category) {
    const count = itemCounters[category] || 0;
    document.querySelector('.items-count-' + category).textContent = count + ' item' + (count !== 1 ? 's' : '');
}

function openCPISelector(button, category, index) {
    // TODO: Open modal with CPI items filtered by domain
    // For now, show a simple prompt
    const cpiItem = prompt('Enter CPI Item (e.g., Paint interior walls, Replace door handle):');
    const cost = prompt('Enter estimated cost ($):');
    
    if (cpiItem && cost) {
        const container = button.nextElementSibling;
        const recDiv = document.createElement('div');
        recDiv.className = 'alert alert-info d-flex justify-content-between align-items-center mb-1';
        recDiv.innerHTML = `
            <span>${cpiItem} - $${cost}</span>
            <button type="button" class="btn-close btn-sm" onclick="this.parentElement.remove(); updateTotalCost();"></button>
            <input type="hidden" name="${category}[${index}][recommendations][]" value="${cpiItem}|${cost}">
        `;
        container.appendChild(recDiv);
        updateTotalCost();
    }
}

function updateTotalCost() {
    let total = 0;
    document.querySelectorAll('.recommendations-container input[type="hidden"]').forEach(input => {
        const parts = input.value.split('|');
        if (parts.length === 2) {
            total += parseFloat(parts[1]) || 0;
        }
    });
    document.getElementById('totalEstimatedCost').value = total.toFixed(2);
}

// Photo preview
document.getElementById('photoUpload')?.addEventListener('change', function(e) {
    const preview = document.getElementById('photoPreview');
    preview.innerHTML = '';
    
    Array.from(e.target.files).forEach((file, index) => {
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const col = document.createElement('div');
                col.className = 'col-md-2 mb-2';
                col.innerHTML = `<img src="${e.target.result}" class="img-thumbnail" style="height: 120px; object-fit: cover;">`;
                preview.appendChild(col);
            };
            reader.readAsDataURL(file);
        }
    });
});
</script>
@endpush
