@extends('admin.layout')

@section('content')
<div class="row">
    <div class="col-lg-8 mx-auto grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Edit Scoring Factor</h4>
                <p class="card-description">Domain #{{ $cpiDomain->domain_number }}: <strong>{{ $cpiDomain->domain_name }}</strong></p>
                
                <form action="{{ route('admin.cpi-domains.factors.update', [$cpiDomain, $factor]) }}" method="POST" class="forms-sample">
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label for="factor_code">Factor Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('factor_code') is-invalid @enderror" 
                               id="factor_code" name="factor_code" value="{{ old('factor_code', $factor->factor_code) }}" required>
                        @error('factor_code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">e.g., unit_shutoffs, supply_material</small>
                    </div>

                    <div class="form-group">
                        <label for="factor_label">Factor Label <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('factor_label') is-invalid @enderror" 
                               id="factor_label" name="factor_label" value="{{ old('factor_label', $factor->factor_label) }}" required>
                        @error('factor_label')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">e.g., Unit-level water shut-offs present? (Yes/No)</small>
                    </div>

                    <div class="form-group">
                        <label for="field_type">Field Type <span class="text-danger">*</span></label>
                        <select class="form-control @error('field_type') is-invalid @enderror" 
                                id="field_type" name="field_type" required>
                            <option value="">-- Select Type --</option>
                            <option value="yes_no" {{ old('field_type', $factor->field_type) == 'yes_no' ? 'selected' : '' }}>Yes/No</option>
                            <option value="lookup" {{ old('field_type', $factor->field_type) == 'lookup' ? 'selected' : '' }}>Lookup (Dropdown)</option>
                            <option value="numeric" {{ old('field_type', $factor->field_type) == 'numeric' ? 'selected' : '' }}>Numeric Input</option>
                            <option value="calculated" {{ old('field_type', $factor->field_type) == 'calculated' ? 'selected' : '' }}>Calculated</option>
                        </select>
                        @error('field_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group" id="lookup_table_group" style="display: none;">
                        <label for="lookup_table">Lookup Table <span class="text-danger">*</span></label>
                        <select class="form-control @error('lookup_table') is-invalid @enderror" 
                                id="lookup_table" name="lookup_table">
                            <option value="">-- Select Lookup Table --</option>
                            <option value="supply_line_materials" {{ old('lookup_table', $factor->lookup_table) == 'supply_line_materials' ? 'selected' : '' }}>
                                Supply Line Materials (Max: 4 pts)
                            </option>
                            <option value="age_brackets" {{ old('lookup_table', $factor->lookup_table) == 'age_brackets' ? 'selected' : '' }}>
                                Age Brackets (Max: 4 pts)
                            </option>
                            <option value="containment_categories" {{ old('lookup_table', $factor->lookup_table) == 'containment_categories' ? 'selected' : '' }}>
                                Containment Categories (Max: 3 pts)
                            </option>
                            <option value="crawl_access_categories" {{ old('lookup_table', $factor->lookup_table) == 'crawl_access_categories' ? 'selected' : '' }}>
                                Crawl Access Categories (Max: 4 pts)
                            </option>
                            <option value="roof_access_categories" {{ old('lookup_table', $factor->lookup_table) == 'roof_access_categories' ? 'selected' : '' }}>
                                Roof Access Categories (Max: 3 pts)
                            </option>
                            <option value="equipment_requirements" {{ old('lookup_table', $factor->lookup_table) == 'equipment_requirements' ? 'selected' : '' }}>
                                Equipment Requirements (Max: 3 pts)
                            </option>
                            <option value="complexity_categories" {{ old('lookup_table', $factor->lookup_table) == 'complexity_categories' ? 'selected' : '' }}>
                                Complexity Categories (Max: 3 pts)
                            </option>
                        </select>
                        @error('lookup_table')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Select which lookup table to use for dropdown options</small>
                        <div id="lookup_table_info"></div>
                    </div>

                    <div class="form-group">
                        <label for="max_points">Maximum Points <span class="text-danger">*</span></label>
                        <input type="number" class="form-control @error('max_points') is-invalid @enderror" 
                               id="max_points" name="max_points" value="{{ old('max_points', $factor->max_points) }}" required>
                        @error('max_points')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="calculation_rule">Calculation Rule</label>
                        
                        {{-- Hidden field to store JSON --}}
                        <input type="hidden" id="calculation_rule" name="calculation_rule" 
                               value="{{ old('calculation_rule', is_array($factor->calculation_rule) ? json_encode($factor->calculation_rule) : $factor->calculation_rule) }}">
                        
                        {{-- Yes/No Scoring Inputs --}}
                        <div id="yes_no_scoring" style="display: none;">
                            <div class="card bg-light p-3">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label>Points when answer is "No"</label>
                                        <input type="number" class="form-control" id="score_no" min="0" placeholder="e.g., 3">
                                        <small class="text-muted">Higher risk = more points</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Points when answer is "Yes"</label>
                                        <input type="number" class="form-control" id="score_yes" min="0" placeholder="e.g., 0">
                                        <small class="text-muted">Lower risk = fewer points</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        {{-- Lookup Scoring --}}
                        <div id="lookup_scoring" style="display: none;">
                            <div class="alert alert-info">
                                <i class="mdi mdi-information-outline"></i>
                                <strong>Lookup Scoring:</strong> Points come from the selected lookup table's score_points column.
                                <input type="hidden" id="lookup_source" value="lookup_score">
                            </div>
                        </div>
                        
                        {{-- Numeric Threshold Scoring --}}
                        <div id="numeric_threshold_scoring" style="display: none;">
                            <div class="card bg-light p-3">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label>Threshold Value</label>
                                        <input type="number" class="form-control" id="threshold_value" placeholder="e.g., 90">
                                        <small class="text-muted">If value exceeds this...</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Points when exceeded</label>
                                        <input type="number" class="form-control" id="threshold_points" min="0" placeholder="e.g., 2">
                                        <small class="text-muted">Award this many points</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        {{-- Numeric Age Lookup --}}
                        <div id="numeric_age_scoring" style="display: none;">
                            <div class="alert alert-info">
                                <i class="mdi mdi-information-outline"></i>
                                <strong>Age Bracket Scoring:</strong> Age value will be looked up in age_brackets table to get score.
                                <input type="hidden" id="age_lookup" value="true">
                            </div>
                        </div>
                        
                        {{-- Manual JSON (fallback) --}}
                        <div id="manual_json_scoring" style="display: none;">
                            <textarea class="form-control @error('calculation_rule') is-invalid @enderror" 
                                      id="manual_rule" rows="3" placeholder='{"key": "value"}'></textarea>
                            <small class="form-text text-muted">Advanced: Enter custom JSON calculation rule</small>
                        </div>
                        
                        @error('calculation_rule')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="sort_order">Sort Order</label>
                        <input type="number" class="form-control @error('sort_order') is-invalid @enderror" 
                               id="sort_order" name="sort_order" value="{{ old('sort_order', $factor->sort_order) }}">
                        @error('sort_order')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="help_text">Help Text</label>
                        <textarea class="form-control @error('help_text') is-invalid @enderror" 
                                  id="help_text" name="help_text" rows="2">{{ old('help_text', $factor->help_text) }}</textarea>
                        @error('help_text')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-check mb-3">
                        <label class="form-check-label">
                            <input type="checkbox" class="form-check-input" name="is_required" value="1" 
                                   {{ old('is_required', $factor->is_required) ? 'checked' : '' }}>
                            Required Field
                        </label>
                    </div>

                    <div class="form-check mb-3">
                        <label class="form-check-label">
                            <input type="checkbox" class="form-check-input" name="is_active" value="1" 
                                   {{ old('is_active', $factor->is_active) ? 'checked' : '' }}>
                            Active
                        </label>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="mdi mdi-content-save"></i> Update Factor
                        </button>
                        <a href="{{ route('admin.cpi-domains.show', $cpiDomain) }}" class="btn btn-light">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fieldTypeSelect = document.getElementById('field_type');
    const lookupTableGroup = document.getElementById('lookup_table_group');
    const lookupTableSelect = document.getElementById('lookup_table');
    const calculationRuleInput = document.getElementById('calculation_rule');
    
    // Scoring input sections
    const yesNoScoring = document.getElementById('yes_no_scoring');
    const lookupScoring = document.getElementById('lookup_scoring');
    const numericThresholdScoring = document.getElementById('numeric_threshold_scoring');
    const numericAgeScoring = document.getElementById('numeric_age_scoring');
    const manualJsonScoring = document.getElementById('manual_json_scoring');
    
    // Individual inputs
    const scoreNo = document.getElementById('score_no');
    const scoreYes = document.getElementById('score_yes');
    const thresholdValue = document.getElementById('threshold_value');
    const thresholdPoints = document.getElementById('threshold_points');
    const manualRule = document.getElementById('manual_rule');
    
    // Parse existing calculation rule
    function parseExistingRule() {
        const ruleValue = calculationRuleInput.value;
        if (!ruleValue) return null;
        
        try {
            return JSON.parse(ruleValue);
        } catch (e) {
            return null;
        }
    }
    
    // Update UI based on field type
    function updateFormFields() {
        const fieldType = fieldTypeSelect.value;
        const existingRule = parseExistingRule();
        
        // Hide all sections first
        lookupTableGroup.style.display = 'none';
        yesNoScoring.style.display = 'none';
        lookupScoring.style.display = 'none';
        numericThresholdScoring.style.display = 'none';
        numericAgeScoring.style.display = 'none';
        manualJsonScoring.style.display = 'none';
        
        // Make lookup table not required by default
        lookupTableSelect.removeAttribute('required');
        
        if (fieldType === 'yes_no') {
            yesNoScoring.style.display = 'block';
            if (existingRule && existingRule.no !== undefined) {
                scoreNo.value = existingRule.no;
                scoreYes.value = existingRule.yes || 0;
            }
        } else if (fieldType === 'lookup') {
            lookupTableGroup.style.display = 'block';
            lookupScoring.style.display = 'block';
            lookupTableSelect.setAttribute('required', 'required');
        } else if (fieldType === 'numeric') {
            // Check if it's threshold or age lookup
            if (existingRule && existingRule.lookup_by_age) {
                numericAgeScoring.style.display = 'block';
                lookupTableGroup.style.display = 'block';
                lookupTableSelect.value = 'age_brackets';
            } else if (existingRule && existingRule.range) {
                numericThresholdScoring.style.display = 'block';
                thresholdValue.value = existingRule.range[0];
                thresholdPoints.value = existingRule.points;
            } else {
                // Show options for numeric type
                numericThresholdScoring.style.display = 'block';
            }
        } else if (fieldType === 'calculated') {
            manualJsonScoring.style.display = 'block';
            if (existingRule) {
                manualRule.value = JSON.stringify(existingRule, null, 2);
            }
        }
    }
    
    // Build JSON from UI inputs
    function buildCalculationRule() {
        const fieldType = fieldTypeSelect.value;
        let rule = {};
        
        if (fieldType === 'yes_no') {
            rule = {
                no: parseInt(scoreNo.value) || 0,
                yes: parseInt(scoreYes.value) || 0
            };
        } else if (fieldType === 'lookup') {
            rule = {
                source: 'lookup_score'
            };
        } else if (fieldType === 'numeric') {
            // Check if age lookup is selected
            if (lookupTableSelect.value === 'age_brackets') {
                rule = {
                    lookup_by_age: true
                };
            } else if (thresholdValue.value) {
                rule = {
                    range: [parseInt(thresholdValue.value), 999],
                    points: parseInt(thresholdPoints.value) || 0
                };
            }
        } else if (fieldType === 'calculated' && manualRule.value) {
            try {
                rule = JSON.parse(manualRule.value);
            } catch (e) {
                console.error('Invalid JSON in manual rule');
            }
        }
        
        calculationRuleInput.value = JSON.stringify(rule);
    }
    
    // Lookup table max points mapping
    const lookupTableMaxPoints = {
        'supply_line_materials': 4,
        'age_brackets': 4,
        'containment_categories': 3,
        'crawl_access_categories': 4,
        'roof_access_categories': 3,
        'equipment_requirements': 3,
        'complexity_categories': 3
    };
    
    // Event listeners
    fieldTypeSelect.addEventListener('change', updateFormFields);
    lookupTableSelect.addEventListener('change', function() {
        if (fieldTypeSelect.value === 'numeric' && this.value === 'age_brackets') {
            numericThresholdScoring.style.display = 'none';
            numericAgeScoring.style.display = 'block';
        }
        
        // Auto-populate max_points based on selected lookup table
        if (this.value && lookupTableMaxPoints[this.value]) {
            document.getElementById('max_points').value = lookupTableMaxPoints[this.value];
            // Show info message
            showLookupInfo(this.value, lookupTableMaxPoints[this.value]);
        }
    });
    
    // Show info about selected lookup table
    function showLookupInfo(tableName, maxPoints) {
        const infoDiv = document.getElementById('lookup_table_info');
        if (infoDiv) {
            infoDiv.innerHTML = `<div class="alert alert-success mt-2">
                <i class="mdi mdi-check-circle"></i>
                <strong>${tableName.replace(/_/g, ' ')}:</strong> Maximum ${maxPoints} points from this lookup table
            </div>`;
        }
    }
    
    // Update JSON before form submission
    document.querySelector('form').addEventListener('submit', function(e) {
        buildCalculationRule();
    });
    
    // Update JSON when inputs change
    [scoreNo, scoreYes, thresholdValue, thresholdPoints, manualRule].forEach(input => {
        if (input) {
            input.addEventListener('input', buildCalculationRule);
        }
    });
    
    // Initialize on page load
    updateFormFields();
});
</script>
@endsection
