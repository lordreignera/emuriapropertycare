@extends('admin.layout')

@section('title', 'PHAR Data Collection - Inspection')

@section('content')
<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="mdi mdi-file-document-edit me-2"></i>PHAR Data Collection
                    </h4>
                    <p class="mb-0 mt-2">Property: <strong>{{ $property->property_address }}</strong></p>
                    <p class="mb-0">Inspector: <strong>{{ auth()->user()->name }}</strong></p>
                </div>

                <div class="card-body">
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

                    <form action="{{ route('inspections.store-phar-data', $inspection->id) }}" method="POST">
                        @csrf

                        <!-- SECTION 1: PHAR Inputs -->
                        <div class="card mb-4 border-info">
                            <div class="card-header" style="background: #17a2b8; color: white;">
                                <h5 class="mb-0">
                                    <i class="mdi mdi-calculator me-2"></i>PHAR Calculation Parameters
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info">
                                    <i class="mdi mdi-information me-2"></i>
                                    <strong>Instructions:</strong> These values drive the calculation engine. Complete all fields before continuing.
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Property Size (Total Finished PSF) <span class="text-danger">*</span></label>
                                            <input type="number" name="property_size_psf" class="form-control" 
                                                  value="{{ old('property_size_psf', $inspection->property_size_psf ?? $defaultPropertySizePsf ?? '') }}" 
                                                   placeholder="e.g., 2800" step="0.01" min="0" required />
                                            <small class="text-muted">Total finished square footage (above/below grade)</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Estimated Task Hours (ETH) <span class="text-danger">*</span></label>
                                            <input type="number" name="estimated_task_hours" class="form-control" 
                                                   value="{{ old('estimated_task_hours', $inspection->estimated_task_hours ?? '') }}" 
                                                   placeholder="e.g., 4.5" step="0.1" min="0" required />
                                            <small class="text-muted">Hands-on work hours per visit</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Minimum Required Hours (MRH) <span class="text-danger">*</span></label>
                                            <input type="number" name="minimum_required_hours" class="form-control" 
                                                   value="{{ old('minimum_required_hours', $inspection->minimum_required_hours ?? 3) }}" 
                                                   placeholder="3.0" step="0.1" min="0" required />
                                            <small class="text-muted">Minimum stewardship window (default: 3 hours)</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Labour Hourly Rate ($) <span class="text-danger">*</span></label>
                                            <input type="number" name="labour_hourly_rate" class="form-control" 
                                                   value="{{ old('labour_hourly_rate', 165) }}" 
                                                   placeholder="165" step="0.01" min="0" required />
                                            <small class="text-muted">Loaded labour rate (default: $165/hr)</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="alert alert-secondary">
                                            <strong>PHAR Condition Score:</strong> 
                                            <span class="fs-4 text-primary" id="pharConditionScore">
                                                {{ $inspection->condition_score ?? $inspection->cpi_total_score ?? (($inspection->domain_1_score ?? 0) + ($inspection->domain_2_score ?? 0) + ($inspection->domain_3_score ?? 0) + ($inspection->domain_4_score ?? 0) + ($inspection->domain_5_score ?? 0) + ($inspection->domain_6_score ?? 0)) }}
                                            </span>
                                            <small class="text-muted ms-2">(From CPI scoring)</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="alert alert-primary mb-0">
                                            <strong>Selected Service Package:</strong>
                                            <span class="ms-1">{{ $selectedServicePackage->package_name ?? 'Not selected' }}</span>
                                            <span class="ms-3">
                                                <strong>Base Monthly Floor:</strong>
                                                <span class="text-success" id="selectedPackageFloorDisplay">${{ number_format($selectedServicePackagePrice ?? 0, 2) }}/month</span>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SECTION 2: Findings Remediation -->
                        <div class="card mb-4 border-warning">
                            <div class="card-header" style="background: #ff9800; color: white;">
                                <h5 class="mb-0">
                                    <i class="mdi mdi-alert-circle-outline me-2"></i>Findings Remediation (Labour)
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-warning">
                                    <i class="mdi mdi-information me-2"></i>
                                    <strong>Instructions:</strong> Document all findings requiring remediation. Labour hours drive the FRLC calculation.
                                </div>

                                <!-- Findings Container -->
                                <div id="findingsContainer">
                                    <!-- Initial Finding Row -->
                                    <div class="finding-row mb-4 p-3 border rounded" data-index="0">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h6 class="text-warning mb-0">
                                                <i class="mdi mdi-clipboard-alert me-1"></i>Finding #<span class="finding-number">1</span>
                                            </h6>
                                            <button type="button" class="btn btn-sm btn-danger remove-finding" style="display: none;">
                                                <i class="mdi mdi-delete"></i> Remove
                                            </button>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Finding / Question <span class="text-danger">*</span></label>
                                                    <input type="text" name="findings[0][task_question]" class="form-control" 
                                                           placeholder="e.g., Gutter cleaning required" required />
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>Est. Labour Hours <span class="text-danger">*</span></label>
                                                    <input type="number" name="findings[0][labour_hours]" class="form-control finding-labour-hours" 
                                                           placeholder="0.0" step="0.1" min="0" value="0" required />
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>Labour Cost ($)</label>
                                                    <input type="text" class="form-control finding-labour-cost" 
                                                           placeholder="$0.00" readonly />
                                                    <small class="text-muted">Auto-calculated</small>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label>Priority <span class="text-danger">*</span></label>
                                                    <select name="findings[0][priority]" class="form-control" required>
                                                        <option value="1">1 - High</option>
                                                        <option value="2" selected>2 - Medium</option>
                                                        <option value="3">3 - Low</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label>Included? <span class="text-danger">*</span></label>
                                                    <select name="findings[0][included_yn]" class="form-control" required>
                                                        <option value="1" selected>Yes</option>
                                                        <option value="0">No</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Category <span class="text-danger">*</span></label>
                                                    <select name="findings[0][category]" class="form-control" required>
                                                        <option value="">-- Select Category --</option>
                                                        @foreach(($pharCategories ?? []) as $category)
                                                            <option value="{{ $category }}">{{ $category }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Notes</label>
                                                    <input type="text" name="findings[0][notes]" class="form-control" 
                                                           placeholder="Additional details..." />
                                                </div>
                                            </div>
                                        </div>

                                        <input type="hidden" name="findings[0][property_id]" value="{{ $property->id }}" />
                                    </div>
                                </div>

                                <!-- Add Finding Button -->
                                <button type="button" class="btn btn-success" id="addFinding">
                                    <i class="mdi mdi-plus me-1"></i>Add Another Finding
                                </button>

                                <!-- Findings Summary -->
                                <div class="alert alert-secondary mt-3">
                                    <div class="row text-center">
                                        <div class="col-md-4">
                                            <strong>Total Findings:</strong>
                                            <div class="fs-4 text-primary" id="totalFindings">1</div>
                                        </div>
                                        <div class="col-md-4">
                                            <strong>Total Labour Hours:</strong>
                                            <div class="fs-4 text-info" id="totalLabourHours">0.0</div>
                                        </div>
                                        <div class="col-md-4">
                                            <strong>Total FR Labour Cost (FRLC):</strong>
                                            <div class="fs-4 text-warning" id="totalFRLC">$0.00</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SECTION 3: Materials Itemization -->
                        <div class="card mb-4 border-success">
                            <div class="card-header" style="background: #28a745; color: white;">
                                <h5 class="mb-0">
                                    <i class="mdi mdi-package-variant me-2"></i>Materials Itemization
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-success">
                                    <i class="mdi mdi-information me-2"></i>
                                    <strong>Instructions:</strong> List all materials, parts, and supplies required. Quantities and costs drive the FMC calculation.
                                </div>

                                <!-- Materials Container -->
                                <div id="materialsContainer">
                                    <!-- Initial Material Row -->
                                    <div class="material-row mb-4 p-3 border rounded" data-index="0">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h6 class="text-success mb-0">
                                                <i class="mdi mdi-package me-1"></i>Material #<span class="material-number">1</span>
                                            </h6>
                                            <button type="button" class="btn btn-sm btn-danger remove-material" style="display: none;">
                                                <i class="mdi mdi-delete"></i> Remove
                                            </button>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-5">
                                                <div class="form-group">
                                                    <label>Material / Part Description <span class="text-danger">*</span></label>
                                                    <input type="text" name="materials[0][material_name]" class="form-control" 
                                                           placeholder="e.g., Bathroom caulk (silicone)" required />
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label>Quantity <span class="text-danger">*</span></label>
                                                    <input type="number" name="materials[0][quantity]" class="form-control material-quantity" 
                                                           placeholder="1" step="0.01" min="0" value="1" required />
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label>Unit <span class="text-danger">*</span></label>
                                                    <select name="materials[0][unit]" class="form-control" required>
                                                        @foreach(($materialUnits ?? []) as $index => $unit)
                                                            <option value="{{ $unit }}" {{ $index === 0 ? 'selected' : '' }}>{{ ucwords($unit) }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label>Unit Cost ($) <span class="text-danger">*</span></label>
                                                    <input type="number" name="materials[0][unit_cost]" class="form-control material-unit-cost" 
                                                           placeholder="0.00" step="0.01" min="0" value="0" required />
                                                </div>
                                            </div>
                                            <div class="col-md-1">
                                                <div class="form-group">
                                                    <label>Line Total</label>
                                                    <input type="text" class="form-control material-line-total" 
                                                           placeholder="$0.00" readonly />
                                                    <input type="hidden" name="materials[0][line_total]" class="material-line-total-hidden" value="0" />
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Category <span class="text-danger">*</span></label>
                                                    <select name="materials[0][category]" class="form-control" required>
                                                        <option value="">-- Select Category --</option>
                                                        @foreach(($pharCategories ?? []) as $category)
                                                            <option value="{{ $category }}">{{ $category }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-8">
                                                <div class="form-group">
                                                    <label>Notes / Description</label>
                                                    <input type="text" name="materials[0][notes]" class="form-control" 
                                                           placeholder="Additional details about this material..." />
                                                </div>
                                            </div>
                                        </div>

                                        <input type="hidden" name="materials[0][property_id]" value="{{ $property->id }}" />
                                    </div>
                                </div>

                                <!-- Add Material Button -->
                                <button type="button" class="btn btn-success" id="addMaterial">
                                    <i class="mdi mdi-plus me-1"></i>Add Another Material
                                </button>

                                <!-- Materials Summary -->
                                <div class="alert alert-secondary mt-3">
                                    <div class="row text-center">
                                        <div class="col-md-4">
                                            <strong>Total Materials:</strong>
                                            <div class="fs-4 text-primary" id="totalMaterials">1</div>
                                        </div>
                                        <div class="col-md-4">
                                            <strong>Total Items:</strong>
                                            <div class="fs-4 text-info" id="totalItemsQty">1</div>
                                        </div>
                                        <div class="col-md-4">
                                            <strong>Total Material Cost (FMC):</strong>
                                            <div class="fs-4 text-success" id="totalFMC">$0.00</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SECTION 4: Live Calculation Summary -->
                        <div class="card mb-4 border-dark">
                            <div class="card-header bg-dark text-white">
                                <h5 class="mb-0">
                                    <i class="mdi mdi-calculator-variant me-2"></i>Calculated Cost Summary
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Component</th>
                                                <th class="text-end">Annual Cost</th>
                                                <th class="text-end">Monthly Cost</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><strong>Base Deployment Cost (BDC)</strong></td>
                                                <td class="text-end" id="bdcAnnual">$0.00</td>
                                                <td class="text-end" id="bdcMonthly">$0.00</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Findings Remediation Labour Cost (FRLC)</strong></td>
                                                <td class="text-end" id="frlcAnnual">$0.00</td>
                                                <td class="text-end" id="frlcMonthly">$0.00</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Findings Material Cost (FMC)</strong></td>
                                                <td class="text-end" id="fmcAnnual">$0.00</td>
                                                <td class="text-end" id="fmcMonthly">$0.00</td>
                                            </tr>
                                            <tr class="table-primary">
                                                <td><strong>Total Remediation Cost (TRC)</strong></td>
                                                <td class="text-end" id="trcAnnual"><strong>$0.00</strong></td>
                                                <td class="text-end" id="trcMonthly"><strong>$0.00</strong></td>
                                            </tr>
                                            <tr class="table-success">
                                                <td><strong>Annual Recurring Price (ARP)</strong></td>
                                                <td class="text-end" colspan="2" id="arpMonthly">
                                                    <strong class="fs-4">$0.00/month</strong>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><strong>Selected Package Floor Price</strong></td>
                                                <td class="text-end">—</td>
                                                <td class="text-end" id="selectedPackageFloor">$0.00</td>
                                            </tr>
                                            <tr class="table-success">
                                                <td><strong>Preview Final Monthly (max of ARP and floor)</strong></td>
                                                <td class="text-end">—</td>
                                                <td class="text-end" id="previewFinalMonthly"><strong>$0.00</strong></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="alert alert-info">
                                    <i class="mdi mdi-information me-2"></i>
                                    <strong>Note:</strong> These are preliminary calculations. Final pricing includes tier multipliers applied during system processing.
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <a href="{{ route('inspections.edit', $inspection->id) }}" class="btn btn-secondary">
                                        <i class="mdi mdi-arrow-left me-1"></i>Previous: CPI Scoring
                                    </a>
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="mdi mdi-content-save me-1"></i>Save & Calculate Final Pricing
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const PHAR_CATEGORIES = @json($pharCategories ?? []);
    const MATERIAL_UNITS = @json($materialUnits ?? []);

    function buildOptions(options, placeholder = null, defaultValue = null) {
        let html = '';

        if (placeholder !== null) {
            html += `<option value="">${placeholder}</option>`;
        }

        options.forEach((option, index) => {
            const selected = defaultValue !== null
                ? option === defaultValue
                : (placeholder === null && index === 0);
            html += `<option value="${option}" ${selected ? 'selected' : ''}>${option.replace(/\b\w/g, c => c.toUpperCase())}</option>`;
        });

        return html;
    }

    // ===== CONFIGURATION =====
    let findingIndex = 1;
    let materialIndex = 1;
    const labourRateInput = document.querySelector('input[name="labour_hourly_rate"]');

    function getLabourRate() {
        return parseFloat(labourRateInput?.value) || 165;
    }

    function updateFindingRowLabourCosts() {
        document.querySelectorAll('.finding-row').forEach(row => {
            const hours = parseFloat(row.querySelector('.finding-labour-hours')?.value) || 0;
            const cost = hours * getLabourRate();
            const labourCostField = row.querySelector('.finding-labour-cost');
            if (labourCostField) {
                labourCostField.value = '$' + cost.toFixed(2);
            }
        });
    }
    
    // ===== FINDINGS MANAGEMENT =====
    
    // Add new finding row
    document.getElementById('addFinding').addEventListener('click', function() {
        const container = document.getElementById('findingsContainer');
        const newRow = createFindingRow(findingIndex);
        container.insertAdjacentHTML('beforeend', newRow);
        findingIndex++;
        updateFindingSummary();
        updateRemoveButtons('finding');
    });
    
    // Remove finding row
    document.getElementById('findingsContainer').addEventListener('click', function(e) {
        if (e.target.closest('.remove-finding')) {
            e.target.closest('.finding-row').remove();
            updateFindingNumbers();
            updateFindingSummary();
            updateRemoveButtons('finding');
        }
    });
    
    // Update finding labour cost in real-time
    document.getElementById('findingsContainer').addEventListener('input', function(e) {
        if (e.target.classList.contains('finding-labour-hours')) {
            const row = e.target.closest('.finding-row');
            const hours = parseFloat(e.target.value) || 0;
            const cost = hours * getLabourRate();
            row.querySelector('.finding-labour-cost').value = '$' + cost.toFixed(2);
            updateFindingSummary();
        }
    });

    labourRateInput?.addEventListener('input', function() {
        updateFindingRowLabourCosts();
        updateFindingSummary();
    });
    
    // Create finding row HTML
    function createFindingRow(index) {
        return `
            <div class="finding-row mb-4 p-3 border rounded" data-index="${index}">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="text-warning mb-0">
                        <i class="mdi mdi-clipboard-alert me-1"></i>Finding #<span class="finding-number">${index + 1}</span>
                    </h6>
                    <button type="button" class="btn btn-sm btn-danger remove-finding">
                        <i class="mdi mdi-delete"></i> Remove
                    </button>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Finding / Question <span class="text-danger">*</span></label>
                            <input type="text" name="findings[${index}][task_question]" class="form-control" 
                                   placeholder="e.g., Gutter cleaning required" required />
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Est. Labour Hours <span class="text-danger">*</span></label>
                            <input type="number" name="findings[${index}][labour_hours]" class="form-control finding-labour-hours" 
                                   placeholder="0.0" step="0.1" min="0" value="0" required />
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Labour Cost ($)</label>
                            <input type="text" class="form-control finding-labour-cost" 
                                   placeholder="$0.00" readonly />
                            <small class="text-muted">Auto-calculated</small>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Priority <span class="text-danger">*</span></label>
                            <select name="findings[${index}][priority]" class="form-control" required>
                                <option value="1">1 - High</option>
                                <option value="2" selected>2 - Medium</option>
                                <option value="3">3 - Low</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Included? <span class="text-danger">*</span></label>
                            <select name="findings[${index}][included_yn]" class="form-control" required>
                                <option value="1" selected>Yes</option>
                                <option value="0">No</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Category <span class="text-danger">*</span></label>
                            <select name="findings[${index}][category]" class="form-control" required>
                                ${buildOptions(PHAR_CATEGORIES, '-- Select Category --')}
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Notes</label>
                            <input type="text" name="findings[${index}][notes]" class="form-control" 
                                   placeholder="Additional details..." />
                        </div>
                    </div>
                </div>

                <input type="hidden" name="findings[${index}][property_id]" value="{{ $property->id }}" />
            </div>
        `;
    }
    
    function updateFindingNumbers() {
        document.querySelectorAll('.finding-row').forEach((row, index) => {
            row.querySelector('.finding-number').textContent = index + 1;
        });
    }
    
    function updateFindingSummary() {
        const rows = document.querySelectorAll('.finding-row');
        let totalLabour = 0;
        
        rows.forEach(row => {
            const hours = parseFloat(row.querySelector('.finding-labour-hours').value) || 0;
            totalLabour += hours;
        });
        
        const totalFRLC = totalLabour * getLabourRate();
        
        document.getElementById('totalFindings').textContent = rows.length;
        document.getElementById('totalLabourHours').textContent = totalLabour.toFixed(1);
        document.getElementById('totalFRLC').textContent = '$' + totalFRLC.toFixed(2);
        
        // Update calculation summary
        updateCalculationSummary();
    }
    
    // ===== MATERIALS MANAGEMENT =====
    
    // Add new material row
    document.getElementById('addMaterial').addEventListener('click', function() {
        const container = document.getElementById('materialsContainer');
        const newRow = createMaterialRow(materialIndex);
        container.insertAdjacentHTML('beforeend', newRow);
        materialIndex++;
        updateMaterialsSummary();
        updateRemoveButtons('material');
    });
    
    // Remove material row
    document.getElementById('materialsContainer').addEventListener('click', function(e) {
        if (e.target.closest('.remove-material')) {
            e.target.closest('.material-row').remove();
            updateMaterialNumbers();
            updateMaterialsSummary();
            updateRemoveButtons('material');
        }
    });
    
    // Update material line total in real-time
    document.getElementById('materialsContainer').addEventListener('input', function(e) {
        if (e.target.classList.contains('material-quantity') || 
            e.target.classList.contains('material-unit-cost')) {
            const row = e.target.closest('.material-row');
            const qty = parseFloat(row.querySelector('.material-quantity').value) || 0;
            const unitCost = parseFloat(row.querySelector('.material-unit-cost').value) || 0;
            const lineTotal = qty * unitCost;
            
            row.querySelector('.material-line-total').value = '$' + lineTotal.toFixed(2);
            row.querySelector('.material-line-total-hidden').value = lineTotal.toFixed(2);
            updateMaterialsSummary();
        }
    });
    
    // Create material row HTML
    function createMaterialRow(index) {
        return `
            <div class="material-row mb-4 p-3 border rounded" data-index="${index}">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="text-success mb-0">
                        <i class="mdi mdi-package me-1"></i>Material #<span class="material-number">${index + 1}</span>
                    </h6>
                    <button type="button" class="btn btn-sm btn-danger remove-material">
                        <i class="mdi mdi-delete"></i> Remove
                    </button>
                </div>

                <div class="row">
                    <div class="col-md-5">
                        <div class="form-group">
                            <label>Material / Part Description <span class="text-danger">*</span></label>
                            <input type="text" name="materials[${index}][material_name]" class="form-control" 
                                   placeholder="e.g., Bathroom caulk (silicone)" required />
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Quantity <span class="text-danger">*</span></label>
                            <input type="number" name="materials[${index}][quantity]" class="form-control material-quantity" 
                                   placeholder="1" step="0.01" min="0" value="1" required />
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Unit <span class="text-danger">*</span></label>
                            <select name="materials[${index}][unit]" class="form-control" required>
                                ${buildOptions(MATERIAL_UNITS)}
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Unit Cost ($) <span class="text-danger">*</span></label>
                            <input type="number" name="materials[${index}][unit_cost]" class="form-control material-unit-cost" 
                                   placeholder="0.00" step="0.01" min="0" value="0" required />
                        </div>
                    </div>
                    <div class="col-md-1">
                        <div class="form-group">
                            <label>Line Total</label>
                            <input type="text" class="form-control material-line-total" 
                                   placeholder="$0.00" readonly />
                            <input type="hidden" name="materials[${index}][line_total]" class="material-line-total-hidden" value="0" />
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Category <span class="text-danger">*</span></label>
                            <select name="materials[${index}][category]" class="form-control" required>
                                ${buildOptions(PHAR_CATEGORIES, '-- Select Category --')}
                            </select>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="form-group">
                            <label>Notes / Description</label>
                            <input type="text" name="materials[${index}][notes]" class="form-control" 
                                   placeholder="Additional details about this material..." />
                        </div>
                    </div>
                </div>

                <input type="hidden" name="materials[${index}][property_id]" value="{{ $property->id }}" />
            </div>
        `;
    }
    
    function updateMaterialNumbers() {
        document.querySelectorAll('.material-row').forEach((row, index) => {
            row.querySelector('.material-number').textContent = index + 1;
        });
    }
    
    function updateMaterialsSummary() {
        const rows = document.querySelectorAll('.material-row');
        let totalQty = 0;
        let totalCost = 0;
        
        rows.forEach(row => {
            const qty = parseFloat(row.querySelector('.material-quantity').value) || 0;
            const lineTotal = parseFloat(row.querySelector('.material-line-total-hidden').value) || 0;
            totalQty += qty;
            totalCost += lineTotal;
        });
        
        document.getElementById('totalMaterials').textContent = rows.length;
        document.getElementById('totalItemsQty').textContent = totalQty.toFixed(0);
        document.getElementById('totalFMC').textContent = '$' + totalCost.toFixed(2);
        
        // Update calculation summary
        updateCalculationSummary();
    }
    
    // ===== REMOVE BUTTON VISIBILITY =====
    function updateRemoveButtons(type) {
        const selector = type === 'finding' ? '.finding-row' : '.material-row';
        const rows = document.querySelectorAll(selector);
        rows.forEach(row => {
            const removeBtn = row.querySelector(type === 'finding' ? '.remove-finding' : '.remove-material');
            removeBtn.style.display = rows.length > 1 ? 'inline-block' : 'none';
        });
    }
    
    // ===== CALCULATION SUMMARY =====
    function updateCalculationSummary() {
        // Get FRLC from findings
        const frlc = parseFloat(document.getElementById('totalFRLC').textContent.replace('$', '').replace(',', '')) || 0;
        
        // Get FMC from materials
        const fmc = parseFloat(document.getElementById('totalFMC').textContent.replace('$', '').replace(',', '')) || 0;
        
        // BDC: Hardcoded from settings (you can fetch via AJAX or pass from backend)
        const bdcAnnual = {{ $bdcSettings['bdc_annual'] ?? 8434.80 }};
        const bdcMonthly = bdcAnnual / 12;
        
        // Calculate annualized FRLC (assume one-time cost, divide by 12 for monthly)
        const frlcAnnual = frlc;
        const frlcMonthly = frlc / 12;
        
        // Calculate annualized FMC (assume one-time cost, divide by 12 for monthly)
        const fmcAnnual = fmc;
        const fmcMonthly = fmc / 12;
        
        // TRC = BDC + FRLC + FMC
        const trcAnnual = bdcAnnual + frlcAnnual + fmcAnnual;
        const trcMonthly = bdcMonthly + frlcMonthly + fmcMonthly;
        
        // ARP = TRC Monthly
        const arpMonthly = trcMonthly;

        // Selected package floor (from CPI-selected package)
        const selectedPackageFloor = {{ (float)($selectedServicePackagePrice ?? 0) }};
        const previewFinalMonthly = Math.max(arpMonthly, selectedPackageFloor);
        
        // Update display
        document.getElementById('bdcAnnual').textContent = '$' + bdcAnnual.toFixed(2);
        document.getElementById('bdcMonthly').textContent = '$' + bdcMonthly.toFixed(2);
        document.getElementById('frlcAnnual').textContent = '$' + frlcAnnual.toFixed(2);
        document.getElementById('frlcMonthly').textContent = '$' + frlcMonthly.toFixed(2);
        document.getElementById('fmcAnnual').textContent = '$' + fmcAnnual.toFixed(2);
        document.getElementById('fmcMonthly').textContent = '$' + fmcMonthly.toFixed(2);
        document.getElementById('trcAnnual').textContent = '$' + trcAnnual.toFixed(2);
        document.getElementById('trcMonthly').textContent = '$' + trcMonthly.toFixed(2);
        document.getElementById('arpMonthly').textContent = '$' + arpMonthly.toFixed(2) + '/month';
        document.getElementById('selectedPackageFloor').textContent = '$' + selectedPackageFloor.toFixed(2);
        document.getElementById('previewFinalMonthly').textContent = '$' + previewFinalMonthly.toFixed(2);
    }
    
    // ===== INITIALIZATION =====
    updateRemoveButtons('finding');
    updateRemoveButtons('material');
    updateFindingRowLabourCosts();
    updateFindingSummary();
    updateMaterialsSummary();
    updateCalculationSummary();
});
</script>
@endsection
