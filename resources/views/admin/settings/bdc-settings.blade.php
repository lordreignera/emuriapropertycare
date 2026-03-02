@extends('admin.layout')

@section('title', 'BDC Calibration Engine Settings')

@section('content')
<div class="content-wrapper">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="card mb-3 border-0 shadow-sm" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="card-body text-white p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-2 fw-bold">
                                <i class="mdi mdi-calculator me-2"></i>BDC Calibration Engine
                            </h3>
                            <p class="mb-0 opacity-75">
                                Base Deployment Cost - Configure operational cost parameters
                            </p>
                        </div>
                        <div>
                            <form action="{{ route('admin.settings.bdc.reset') }}" method="POST" 
                                  onsubmit="return confirm('Reset all settings to default values?');">
                                @csrf
                                <button type="submit" class="btn btn-light">
                                    <i class="mdi mdi-refresh me-1"></i>Reset to Defaults
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="mdi mdi-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="mdi mdi-alert-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            <div class="row">
                <!-- Settings Form -->
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="mdi mdi-cog me-2"></i>Configuration Parameters
                            </h5>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('admin.settings.bdc.update') }}" method="POST" id="bdcSettingsForm">
                                @csrf
                                @method('PUT')

                                @foreach($settings as $index => $setting)
                                <div class="form-group mb-4">
                                    <label class="fw-bold">
                                        {{ $setting->setting_label }}
                                        <span class="badge bg-info ms-2">{{ $setting->unit }}</span>
                                    </label>
                                    
                                    @if($setting->setting_description)
                                    <div class="text-muted small mb-2">
                                        <i class="mdi mdi-information"></i> {{ $setting->setting_description }}
                                    </div>
                                    @endif

                                    <input type="hidden" name="settings[{{ $index }}][id]" value="{{ $setting->id }}">
                                    
                                    <div class="input-group">
                                        @if($setting->unit === '$/hr' || $setting->unit === '$')
                                        <span class="input-group-text">$</span>
                                        @endif
                                        
                                        <input type="number" 
                                               name="settings[{{ $index }}][setting_value]" 
                                               class="form-control bdc-input" 
                                               value="{{ $setting->setting_value }}" 
                                               step="{{ $setting->setting_type === 'percentage' ? '0.01' : '0.5' }}"
                                               min="0"
                                               data-key="{{ $setting->setting_key }}"
                                               required>
                                        
                                        @if($setting->unit === '%')
                                        <span class="input-group-text">
                                            ({{ number_format($setting->setting_value * 100, 0) }}%)
                                        </span>
                                        @elseif($setting->unit && $setting->unit !== '$/hr' && $setting->unit !== '$')
                                        <span class="input-group-text">{{ $setting->unit }}</span>
                                        @endif
                                    </div>
                                </div>
                                @endforeach

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="mdi mdi-content-save me-2"></i>Save Settings
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Info Card -->
                    <div class="card mt-3">
                        <div class="card-body bg-light">
                            <h6 class="text-primary mb-2">
                                <i class="mdi mdi-information-outline me-2"></i>About BDC
                            </h6>
                            <p class="mb-1 small">
                                <strong>Base Deployment Cost (BDC)</strong> represents the baseline annual operational cost 
                                to service a property before any remediation findings are added.
                            </p>
                            <p class="mb-0 small">
                                BDC includes: Direct labour + Infrastructure overhead + Administrative overhead
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Live Calculation Preview -->
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">
                                <i class="mdi mdi-chart-bar me-2"></i>Live Calculation Preview
                            </h5>
                        </div>
                        <div class="card-body" id="calculationPreview">
                            <div class="mb-4">
                                <h6 class="text-muted mb-3">Calculation Steps</h6>
                                
                                <div class="calculation-step mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-muted">Labour Hours per Year</span>
                                        <span class="fw-bold" id="labour_hours_per_year">{{ $calculation['labour_hours_per_year'] }}</span>
                                    </div>
                                    <small class="text-muted">
                                        = Visits per Year × Hours per Visit
                                    </small>
                                </div>

                                <div class="calculation-step mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-muted">Labour Cost per Year</span>
                                        <span class="fw-bold" id="labour_cost_per_year">${{ number_format($calculation['labour_cost_per_year'], 2) }}</span>
                                    </div>
                                    <small class="text-muted">
                                        = Labour Hours × Loaded Hourly Rate
                                    </small>
                                </div>

                                <div class="calculation-step mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-muted">Infrastructure Cost</span>
                                        <span class="fw-bold" id="infrastructure_cost">${{ number_format($calculation['infrastructure_cost'], 2) }}</span>
                                    </div>
                                    <small class="text-muted">
                                        = Labour Cost × Infrastructure %
                                    </small>
                                </div>

                                <div class="calculation-step mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-muted">Administration Cost</span>
                                        <span class="fw-bold" id="administration_cost">${{ number_format($calculation['administration_cost'], 2) }}</span>
                                    </div>
                                    <small class="text-muted">
                                        = Labour Cost × Administration %
                                    </small>
                                </div>

                                <hr class="my-4">

                                <div class="calculation-result">
                                    <div class="alert alert-success mb-2">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="h6 mb-0">Base Deployment Cost (Annual)</span>
                                            <span class="h4 mb-0" id="bdc_annual">${{ number_format($calculation['bdc_annual'], 2) }}</span>
                                        </div>
                                    </div>
                                    
                                    <div class="alert alert-info mb-0">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="h6 mb-0">BDC (Monthly)</span>
                                            <span class="h4 mb-0" id="bdc_monthly">${{ number_format($calculation['bdc_monthly'], 2) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Breakdown Table -->
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Component</th>
                                            <th class="text-end">Annual ($)</th>
                                            <th class="text-end">Monthly ($)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Labour</td>
                                            <td class="text-end" id="table_labour_annual">{{ number_format($calculation['labour_cost_per_year'], 2) }}</td>
                                            <td class="text-end" id="table_labour_monthly">{{ number_format($calculation['labour_cost_per_year'] / 12, 2) }}</td>
                                        </tr>
                                        <tr>
                                            <td>Infrastructure</td>
                                            <td class="text-end" id="table_infra_annual">{{ number_format($calculation['infrastructure_cost'], 2) }}</td>
                                            <td class="text-end" id="table_infra_monthly">{{ number_format($calculation['infrastructure_cost'] / 12, 2) }}</td>
                                        </tr>
                                        <tr>
                                            <td>Administration</td>
                                            <td class="text-end" id="table_admin_annual">{{ number_format($calculation['administration_cost'], 2) }}</td>
                                            <td class="text-end" id="table_admin_monthly">{{ number_format($calculation['administration_cost'] / 12, 2) }}</td>
                                        </tr>
                                        <tr class="table-success fw-bold">
                                            <td>TOTAL BDC</td>
                                            <td class="text-end" id="table_total_annual">{{ number_format($calculation['bdc_annual'], 2) }}</td>
                                            <td class="text-end" id="table_total_monthly">{{ number_format($calculation['bdc_monthly'], 2) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.calculation-step {
    padding: 10px;
    background: #f8f9fa;
    border-radius: 5px;
}

.calculation-result .alert {
    border-left: 4px solid;
}

.alert-success {
    border-left-color: #28a745 !important;
}

.alert-info {
    border-left-color: #17a2b8 !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Real-time calculation preview
    const inputs = document.querySelectorAll('.bdc-input');
    
    inputs.forEach(input => {
        input.addEventListener('input', debounce(calculatePreview, 500));
    });

    function calculatePreview() {
        const data = {};
        inputs.forEach(input => {
            const key = input.dataset.key;
            data[key] = parseFloat(input.value) || 0;
        });

        // Calculate locally (same logic as backend)
        const labourHours = data.visits_per_year * data.hours_per_visit;
        const labourCost = labourHours * data.loaded_hourly_rate;
        const infraCost = labourCost * data.infrastructure_percentage;
        const adminCost = labourCost * data.administration_percentage;
        const bdcAnnual = labourCost + infraCost + adminCost;
        const bdcMonthly = bdcAnnual / 12;

        // Update display
        updateDisplay({
            labour_hours_per_year: labourHours.toFixed(2),
            labour_cost_per_year: labourCost.toFixed(2),
            infrastructure_cost: infraCost.toFixed(2),
            administration_cost: adminCost.toFixed(2),
            bdc_annual: bdcAnnual.toFixed(2),
            bdc_monthly: bdcMonthly.toFixed(2)
        });
    }

    function updateDisplay(calc) {
        document.getElementById('labour_hours_per_year').textContent = calc.labour_hours_per_year;
        document.getElementById('labour_cost_per_year').textContent = '$' + numberFormat(calc.labour_cost_per_year);
        document.getElementById('infrastructure_cost').textContent = '$' + numberFormat(calc.infrastructure_cost);
        document.getElementById('administration_cost').textContent = '$' + numberFormat(calc.administration_cost);
        document.getElementById('bdc_annual').textContent = '$' + numberFormat(calc.bdc_annual);
        document.getElementById('bdc_monthly').textContent = '$' + numberFormat(calc.bdc_monthly);

        // Update table
        document.getElementById('table_labour_annual').textContent = numberFormat(calc.labour_cost_per_year);
        document.getElementById('table_labour_monthly').textContent = numberFormat((calc.labour_cost_per_year / 12).toFixed(2));
        document.getElementById('table_infra_annual').textContent = numberFormat(calc.infrastructure_cost);
        document.getElementById('table_infra_monthly').textContent = numberFormat((calc.infrastructure_cost / 12).toFixed(2));
        document.getElementById('table_admin_annual').textContent = numberFormat(calc.administration_cost);
        document.getElementById('table_admin_monthly').textContent = numberFormat((calc.administration_cost / 12).toFixed(2));
        document.getElementById('table_total_annual').textContent = numberFormat(calc.bdc_annual);
        document.getElementById('table_total_monthly').textContent = numberFormat(calc.bdc_monthly);
    }

    function numberFormat(num) {
        return parseFloat(num).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
});
</script>
@endsection
