@extends('admin.layout')

@section('title', 'CPI Inspection Form')

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
                                <i class="mdi mdi-clipboard-check me-2"></i>CPI Property Inspection Form
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

            <form action="{{ route('inspections.store') }}" method="POST" enctype="multipart/form-data" id="cpiInspectionForm">
                @csrf
                <input type="hidden" name="property_id" value="{{ $property->id }}">

                <!-- SECTION 1: Inspection Overview & Property Details -->
                <div class="card mb-4">
                    <div class="card-header" style="background: #5b67ca; color: white;">
                        <h5 class="mb-0">
                            <i class="mdi mdi-information me-2"></i>Section 1: Inspection Overview & Property Details
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
                                    <label>Inspector</label>
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

                        <hr class="my-4">
                        <h6 class="text-primary">Property Owner Information</h6>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Owner Name</label>
                                    <input type="text" class="form-control" value="{{ $property->user->name }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Owner Email</label>
                                    <input type="email" class="form-control" value="{{ $property->user->email }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Owner Phone</label>
                                    <input type="text" class="form-control" value="{{ $property->user->phone ?? 'N/A' }}" readonly>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">
                        <h6 class="text-primary">Property Information</h6>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Property Type</label>
                                    <input type="text" class="form-control" value="{{ ucfirst(str_replace('_', ' ', $property->type)) }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Property Year Built <span class="text-danger">*</span></label>
                                    <input type="number" name="property_year_built" class="form-control" 
                                           value="{{ old('property_year_built', $property->year_built ?? date('Y')) }}" 
                                           min="1800" max="{{ date('Y') }}" required>
                                    <small class="text-muted">Used for age calculation</small>
                                </div>
                            </div>
                            @if($property->type === 'residential' || $property->type === 'mixed_use')
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Residential Units</label>
                                    <input type="number" class="form-control" value="{{ $property->residential_units }}" readonly>
                                </div>
                            </div>
                            @endif
                            @if($property->type === 'commercial' || $property->type === 'mixed_use')
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Commercial SqFt</label>
                                    <input type="number" class="form-control" value="{{ $property->square_footage_interior }}" readonly>
                                </div>
                            </div>
                            @endif
                            @if($property->type === 'mixed_use')
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Commercial Weight (%)</label>
                                    <input type="number" class="form-control" value="{{ $property->mixed_use_commercial_weight }}" readonly>
                                </div>
                            </div>
                            @endif
                        </div>

                        <hr class="my-4">
                        <h6 class="text-primary">Service Package Selection</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Select Service Package <span class="text-danger">*</span></label>
                                    <select name="service_package_id" id="servicePackage" class="form-control" required>
                                        <option value="">-- Select Package --</option>
                                        @foreach($pricingPackages as $package)
                                            @php
                                                $resPrice = $package->getPriceForPropertyType(1); // 1 = residential
                                                $comPrice = $package->getPriceForPropertyType(2); // 2 = commercial
                                            @endphp
                                            <option value="{{ $package->id }}" 
                                                    data-res-price="{{ $resPrice }}"
                                                    data-com-price="{{ $comPrice }}">
                                                {{ $package->package_name }} 
                                                (Res: ${{ number_format($resPrice, 2) }} | 
                                                 Com: ${{ number_format($comPrice, 2) }})
                                            </option>
                                        @endforeach
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

                <!-- CPI DOMAINS: Dynamically Generated from Database -->
                @foreach($cpiDomains as $domain)
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="mdi mdi-clipboard-text me-2 text-primary"></i>Domain {{ $domain->domain_number }}: {{ $domain->domain_name }} 
                            (Max {{ $domain->max_possible_points }} pts)
                            @if($domain->calculation_method !== 'sum')
                                <span class="badge bg-warning text-dark ms-2">{{ strtoupper($domain->calculation_method) }}</span>
                            @endif
                        </h5>
                    </div>
                    <div class="card-body">
                        @if($domain->description)
                            <div class="alert alert-warning mb-3">
                                <i class="mdi mdi-information me-2"></i>
                                <strong>Note:</strong> {{ $domain->description }}
                            </div>
                        @endif

                        <div class="row">
                            @foreach($domain->activeFactors as $factor)
                                <div class="col-md-6 mb-3">
                                    <div class="form-group">
                                        <label>
                                            {{ $factor->factor_label }}
                                            @if($factor->is_required)
                                                <span class="text-danger">*</span>
                                            @endif
                                        </label>

                                        @if($factor->field_type === 'yes_no')
                                            {{-- Yes/No Radio Buttons --}}
                                            <div>
                                                @php
                                                    $rules = $factor->calculation_rule ?? [];
                                                    $yesScore = $rules['yes'] ?? 0;
                                                    $noScore = $rules['no'] ?? 0;
                                                @endphp
                                                <div class="form-check form-check-inline">
                                                    <input type="radio" 
                                                           name="factor_{{ $factor->id }}" 
                                                           value="yes" 
                                                           id="factor_{{ $factor->id }}_yes" 
                                                           class="form-check-input cpi-factor" 
                                                           data-domain="{{ $domain->domain_number }}"
                                                           data-score="{{ $yesScore }}"
                                                           {{ $factor->is_required ? 'required' : '' }}>
                                                    <label for="factor_{{ $factor->id }}_yes" class="form-check-label">
                                                        Yes ({{ $yesScore }} pts)
                                                    </label>
                                                </div>
                                                <div class="form-check form-check-inline">
                                                    <input type="radio" 
                                                           name="factor_{{ $factor->id }}" 
                                                           value="no" 
                                                           id="factor_{{ $factor->id }}_no" 
                                                           class="form-check-input cpi-factor" 
                                                           data-domain="{{ $domain->domain_number }}"
                                                           data-score="{{ $noScore }}">
                                                    <label for="factor_{{ $factor->id }}_no" class="form-check-label">
                                                        No ({{ $noScore }} pts)
                                                    </label>
                                                </div>
                                            </div>

                                        @elseif($factor->field_type === 'lookup' && $factor->lookup_table)
                                            {{-- Lookup Dropdown --}}
                                            @php
                                                $lookupData = null;
                                                switch($factor->lookup_table) {
                                                    case 'supply_line_materials': $lookupData = $supplyMaterials; break;
                                                    case 'age_brackets': $lookupData = $ageBrackets; break;
                                                    case 'containment_categories': $lookupData = $containmentCategories; break;
                                                    case 'crawl_access_categories': $lookupData = $crawlAccessCategories; break;
                                                    case 'roof_access_categories': $lookupData = $roofAccessCategories; break;
                                                    case 'equipment_requirements': $lookupData = $equipmentRequirements; break;
                                                    case 'complexity_categories': $lookupData = $complexityCategories; break;
                                                }
                                            @endphp
                                            <select name="factor_{{ $factor->id }}" 
                                                    id="factor_{{ $factor->id }}" 
                                                    class="form-control cpi-factor" 
                                                    data-domain="{{ $domain->domain_number }}"
                                                    {{ $factor->is_required ? 'required' : '' }}>
                                                <option value="">-- Select {{ ucfirst(str_replace('_', ' ', $factor->lookup_table)) }} --</option>
                                                @if($lookupData)
                                                    @foreach($lookupData as $item)
                                                        <option value="{{ $item->id }}" data-score="{{ $item->score_points ?? 0 }}">
                                                            {{ $item->material_name ?? $item->category_name ?? $item->requirement_name ?? $item->bracket_name ?? 'Item' }}
                                                            ({{ $item->score_points ?? 0 }} pts)
                                                        </option>
                                                    @endforeach
                                                @endif
                                            </select>

                                        @elseif($factor->field_type === 'numeric')
                                            {{-- Numeric Input --}}
                                            <input type="number" 
                                                   name="factor_{{ $factor->id }}" 
                                                   id="factor_{{ $factor->id }}" 
                                                   class="form-control cpi-factor" 
                                                   data-domain="{{ $domain->domain_number }}"
                                                   data-max-points="{{ $factor->max_points }}"
                                                   data-calc-rule="{{ json_encode($factor->calculation_rule) }}"
                                                   placeholder="Enter value" 
                                                   min="0"
                                                   {{ $factor->is_required ? 'required' : '' }}>

                                        @elseif($factor->field_type === 'calculated')
                                            {{-- Calculated/Readonly Field --}}
                                            <input type="text" 
                                                   name="factor_{{ $factor->id }}" 
                                                   id="factor_{{ $factor->id }}" 
                                                   class="form-control cpi-factor-calculated" 
                                                   data-domain="{{ $domain->domain_number }}"
                                                   readonly 
                                                   style="background: #f0f0f0;">
                                        @endif

                                        @if($factor->help_text)
                                            <small class="text-muted">{{ $factor->help_text }}</small>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="form-group mt-3">
                            <label>Domain {{ $domain->domain_number }} Notes</label>
                            <textarea name="domain_{{ $domain->domain_number }}_notes" 
                                      class="form-control" 
                                      rows="2" 
                                      placeholder="Additional observations for {{ $domain->domain_name }}..."></textarea>
                        </div>

                        <div class="alert alert-info mt-3">
                            <strong>Domain {{ $domain->domain_number }} Score:</strong> 
                            <span id="domain{{ $domain->domain_number }}Score" class="fw-bold">0</span> / {{ $domain->max_possible_points }} points
                        </div>
                    </div>
                </div>
                @endforeach

                <!-- SECTION 8: CPI Outputs & Pricing Preview -->
                <div class="card mb-4 border-success">
                    <div class="card-header" style="background: #4caf50; color: white;">
                        <h5 class="mb-0">
                            <i class="mdi mdi-chart-line me-2"></i>CPI Outputs & Pricing Calculation
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center mb-4">
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="text-muted">CPI Total Score</h6>
                                        <h2 class="mb-0 text-primary" id="cpiTotalScore">0</h2>
                                        <small class="text-muted">points</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="text-muted">CPI Band</h6>
                                        <h4 class="mb-0"><span class="badge bg-info" id="cpiBand">CPI-0</span></h4>
                                        <small class="text-muted" id="cpiBandName">Excellent</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="text-muted">CPI Multiplier</h6>
                                        <h2 class="mb-0 text-danger" id="cpiMultiplier">1.00</h2>
                                        <small class="text-muted">x</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <h6 class="text-success mb-3">Pricing Breakdown</h6>
                        <table class="table table-bordered">
                            <tr>
                                <td class="fw-bold">Base Service Cost (Monthly)</td>
                                <td class="text-end"><span id="basePrice">$0.00</span></td>
                            </tr>
                            <tr>
                                <td class="fw-bold">Size Factor</td>
                                <td class="text-end"><span id="sizeFactor">1.00</span>x</td>
                            </tr>
                            <tr>
                                <td class="fw-bold">CPI Multiplier</td>
                                <td class="text-end"><span id="displayMultiplier">1.00</span>x</td>
                            </tr>
                            <tr class="table-success">
                                <td class="fw-bold fs-5">FINAL MONTHLY COST</td>
                                <td class="text-end fw-bold fs-5"><span id="finalMonthly">$0.00</span></td>
                            </tr>
                            <tr class="table-success">
                                <td class="fw-bold fs-5">FINAL ANNUAL COST</td>
                                <td class="text-end fw-bold fs-5"><span id="finalAnnual">$0.00</span></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- SECTION 9: Photos & Documentation -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="mdi mdi-camera me-2 text-primary"></i>Photos & Documentation
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>Upload Inspection Photos (Max 20 photos, 10MB each)</label>
                            <input type="file" name="inspection_photos[]" class="form-control" multiple accept="image/*" id="photoUpload">
                            <small class="text-muted">Accepted formats: JPG, PNG, HEIC</small>
                        </div>
                        <div class="form-group">
                            <label>Photo Upload Notes</label>
                            <textarea name="photo_notes" class="form-control" rows="2" 
                                      placeholder="Description of uploaded photos and documentation..."></textarea>
                        </div>
                        <div id="photoPreview" class="row mt-3"></div>
                    </div>
                </div>

                <!-- SECTION 10: Overall Assessment -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="mdi mdi-clipboard-text me-2 text-primary"></i>Overall Assessment
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Overall Property Condition <span class="text-danger">*</span></label>
                                    <select name="overall_condition" class="form-control" required>
                                        <option value="">-- Select Condition --</option>
                                        <option value="excellent">Excellent</option>
                                        <option value="good">Good</option>
                                        <option value="fair">Fair</option>
                                        <option value="poor">Poor</option>
                                        <option value="critical">Critical</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Inspector Notes</label>
                            <textarea name="inspector_notes" class="form-control" rows="3" 
                                      placeholder="Additional observations not covered in domain-specific notes..."></textarea>
                        </div>
                        <div class="form-group">
                            <label>Recommendations</label>
                            <textarea name="recommendations" class="form-control" rows="3" 
                                      placeholder="Action items and follow-ups..."></textarea>
                        </div>
                        <div class="form-group">
                            <label>Risk Summary</label>
                            <textarea name="risk_summary" class="form-control" rows="3" 
                                      placeholder="Summary of major risks identified during inspection..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('inspections.index') }}" class="btn btn-secondary">
                                <i class="mdi mdi-arrow-left me-1"></i>Cancel
                            </a>
                            <div>
                                <button type="submit" name="status" value="in_progress" class="btn btn-warning me-2">
                                    <i class="mdi mdi-content-save me-1"></i>Save as Draft
                                </button>
                                <button type="submit" name="status" value="completed" class="btn btn-success">
                                    <i class="mdi mdi-check-circle me-1"></i>Complete Inspection
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Store property data for calculations
const propertyData = {
    type: '{{ $property->type }}',
    residentialUnits: {{ $property->residential_units ?? 0 }},
    commercialSqft: {{ $property->square_footage_interior ?? 0 }},
    mixedUseWeight: {{ $property->mixed_use_commercial_weight ?? 0 }}
};

// Database-driven domain configuration
const domainConfigs = {!! json_encode($cpiDomains->map(function($domain) {
    return [
        'number' => $domain->domain_number,
        'max_points' => $domain->max_possible_points,
        'calculation_method' => $domain->calculation_method
    ];
})->keyBy('number')) !!};

// CPI Band ranges FROM DATABASE
const cpiBandRanges = {!! json_encode($cpiBandRanges->map(function($band) {
    return [
        'code' => $band->band_code,
        'name' => $band->band_name,
        'min' => $band->min_score,
        'max' => $band->max_score ?? 999,
        'multiplier' => $band->multiplier ? floatval($band->multiplier->multiplier) : 1.00
    ];
})->values()) !!};

// Age brackets FROM DATABASE
const ageBrackets = {!! json_encode($ageBrackets->map(function($bracket) {
    return [
        'min' => $bracket->min_age,
        'max' => $bracket->max_age ?? 999,
        'score' => $bracket->score_points
    ];
})->values()) !!};

// Residential size tiers FROM DATABASE
const residentialSizeTiers = {!! json_encode($residentialSizeTiers->map(function($tier) {
    return [
        'min' => $tier->min_units,
        'max' => $tier->max_units ?? 999,
        'multiplier' => floatval($tier->size_multiplier)
    ];
})->values()) !!};

// Service package prices FROM DATABASE
const packagePrices = {
    @foreach($pricingPackages as $package)
        '{{ $package->id }}': {
            residential: {{ $package->getPriceForPropertyType(1) ?? 0 }},
            commercial: {{ $package->getPriceForPropertyType(2) ?? 0 }}
        }{{ !$loop->last ? ',' : '' }}
    @endforeach
};

// Dynamic domain score calculation
function calculateDomainScore(domainNumber) {
    const config = domainConfigs[domainNumber];
    if (!config) return 0;
    
    const factors = document.querySelectorAll(`.cpi-factor[data-domain="${domainNumber}"]`);
    let scores = [];
    
    factors.forEach(factor => {
        let score = 0;
        
        if (factor.type === 'radio' && factor.checked) {
            score = parseInt(factor.dataset.score) || 0;
        } else if (factor.tagName === 'SELECT' && factor.value) {
            const selectedOption = factor.options[factor.selectedIndex];
            score = parseInt(selectedOption.dataset.score) || 0;
        } else if (factor.type === 'number' && factor.value) {
            const value = parseFloat(factor.value);
            const calcRule = JSON.parse(factor.dataset.calcRule || '{}');
            
            // Handle numeric calculation rules (e.g., threshold-based)
            if (calcRule.threshold && value > calcRule.threshold) {
                score = parseInt(calcRule.points) || 0;
            }
        }
        
        scores.push(score);
    });
    
    // Apply calculation method
    let totalScore = 0;
    if (config.calculation_method === 'max') {
        totalScore = Math.max(...scores, 0);
        // Apply cap if specified
        if (config.max_points) {
            totalScore = Math.min(totalScore, config.max_points);
        }
    } else {
        // Default to sum
        totalScore = scores.reduce((a, b) => a + b, 0);
    }
    
    // Update display
    const scoreElement = document.getElementById(`domain${domainNumber}Score`);
    if (scoreElement) {
        scoreElement.textContent = totalScore;
    }
    
    return totalScore;
}

// Calculate CPI Total and Pricing using DATABASE CPI bands
function calculateCPITotal() {
    // Dynamically calculate all domain scores
    let totalScore = 0;
    Object.keys(domainConfigs).forEach(domainNum => {
        totalScore += calculateDomainScore(parseInt(domainNum));
    });
    
    document.getElementById('cpiTotalScore').textContent = totalScore;
    
    // Determine CPI Band using DATABASE ranges
    let band = cpiBandRanges[0] || {code: 'CPI-0', name: 'Excellent', multiplier: 1.00};
    for (const range of cpiBandRanges) {
        if (totalScore >= range.min && totalScore <= range.max) {
            band = range;
            break;
        }
    }
    
    document.getElementById('cpiBand').textContent = band.code;
    document.getElementById('cpiBandName').textContent = band.name || band.code;
    document.getElementById('cpiMultiplier').textContent = band.multiplier.toFixed(2);
    document.getElementById('displayMultiplier').textContent = band.multiplier.toFixed(2);
    
    calculatePricing(band.multiplier);
}

// Calculate Pricing using DATABASE values
function calculatePricing(cpiMultiplier) {
    const packageSelect = document.getElementById('servicePackage');
    if (!packageSelect.value) return;
    
    const packageId = packageSelect.value;
    const prices = packagePrices[packageId];
    
    if (!prices) return;
    
    let basePrice = 0;
    
    if (propertyData.type === 'residential') {
        basePrice = prices.residential;
    } else if (propertyData.type === 'commercial') {
        basePrice = prices.commercial;
    } else if (propertyData.type === 'mixed_use') {
        const weight = propertyData.mixedUseWeight / 100;
        basePrice = (prices.residential * (1 - weight)) + (prices.commercial * weight);
    }
    
    // Calculate size factor using DATABASE residential size tiers
    let sizeFactor = 1.0;
    if (propertyData.type === 'residential' || propertyData.type === 'mixed_use') {
        const units = propertyData.residentialUnits;
        for (const tier of residentialSizeTiers) {
            if (units >= tier.min && units <= tier.max) {
                sizeFactor = tier.multiplier;
                break;
            }
        }
    }
    
    if (propertyData.type === 'commercial') {
        sizeFactor = Math.max(1.0, propertyData.commercialSqft / 10000);
    }
    
    const finalMonthly = basePrice * sizeFactor * cpiMultiplier;
    const finalAnnual = finalMonthly * 12;
    
    document.getElementById('basePrice').textContent = '$' + basePrice.toFixed(2);
    document.getElementById('sizeFactor').textContent = sizeFactor.toFixed(2);
    document.getElementById('finalMonthly').textContent = '$' + finalMonthly.toFixed(2);
    document.getElementById('finalAnnual').textContent = '$' + finalAnnual.toFixed(2);
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Attach listeners to ALL CPI factor inputs dynamically
    document.querySelectorAll('.cpi-factor').forEach(factor => {
        if (factor.type === 'radio' || factor.tagName === 'SELECT') {
            factor.addEventListener('change', calculateCPITotal);
        } else if (factor.type === 'number') {
            factor.addEventListener('input', calculateCPITotal);
        }
    });
    
    // Service package
    const servicePackageEl = document.getElementById('servicePackage');
    if (servicePackageEl) {
        servicePackageEl.addEventListener('change', calculateCPITotal);
    }
    
    // Photo preview
    const photoUploadEl = document.getElementById('photoUpload');
    if (photoUploadEl) {
        photoUploadEl.addEventListener('change', function(e) {
            const preview = document.getElementById('photoPreview');
            preview.innerHTML = '';
            
            Array.from(e.target.files).slice(0, 20).forEach(file => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const col = document.createElement('div');
                    col.className = 'col-md-3 mb-3';
                    col.innerHTML = `<img src="${e.target.result}" class="img-fluid rounded" style="max-height: 150px; object-fit: cover;">`;
                    preview.appendChild(col);
                };
                reader.readAsDataURL(file);
            });
        });
    }
});
</script>
@endsection
