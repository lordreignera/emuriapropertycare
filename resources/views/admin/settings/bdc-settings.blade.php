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
                                Base Deployment Cost - Configure static operational cost parameters
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
                                               step="0.01"
                                               min="0"
                                               data-key="{{ $setting->setting_key }}"
                                               required>
                                        
                                        @if($setting->unit && !in_array($setting->unit, ['$/hr', '$']))
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
                                BDC = Labour cost (visits × hours × hourly rate) for the default calculation, or travel-based (km rate + time rate) per inspection.
                            </p>
                            <p class="mb-0 mt-2 small text-muted">
                                Visits per Year and Hours per Visit are now set per inspection on the PHAR form.
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

                            <!-- Travel-Based BDC (Primary) -->
                            <h6 class="fw-bold mb-1">Travel-Based BDC <span class="badge bg-success ms-1">Primary</span></h6>
                            <p class="text-muted small mb-3">Formula: (km × Rate/km + min × Rate/min) × visits/year</p>

                            <div class="row g-2 mb-3">
                                <div class="col-4">
                                    <label class="form-label small mb-1">Example Distance (km)</label>
                                    <input type="number" id="ex_km" class="form-control form-control-sm" value="50" min="0" step="1">
                                </div>
                                <div class="col-4">
                                    <label class="form-label small mb-1">Example Time (min)</label>
                                    <input type="number" id="ex_min" class="form-control form-control-sm" value="45" min="0" step="1">
                                </div>
                                <div class="col-4">
                                    <label class="form-label small mb-1">Visits / Year</label>
                                    <input type="number" id="ex_visits" class="form-control form-control-sm" value="12" min="1" step="1">
                                </div>
                            </div>

                            <div class="calculation-step mb-2">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted small">Distance cost / visit</span>
                                    <span class="fw-bold" id="travel_dist_cost">—</span>
                                </div>
                                <small class="text-muted">km × Rate/km</small>
                            </div>
                            <div class="calculation-step mb-2">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted small">Time cost / visit</span>
                                    <span class="fw-bold" id="travel_time_cost">—</span>
                                </div>
                                <small class="text-muted">min × Rate/min</small>
                            </div>
                            <div class="calculation-step mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted small">BDC per visit</span>
                                    <span class="fw-bold" id="bdc_per_visit">—</span>
                                </div>
                                <small class="text-muted">Distance cost + Time cost</small>
                            </div>

                            <hr class="my-3">

                            <div class="alert alert-success mb-2 py-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold">BDC Annual</span>
                                    <span class="h5 mb-0" id="bdc_annual">—</span>
                                </div>
                                <small class="opacity-75">BDC/visit × visits/year</small>
                            </div>
                            <div class="alert alert-info mb-3 py-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold">BDC Monthly</span>
                                    <span class="h5 mb-0" id="bdc_monthly">—</span>
                                </div>
                                <small class="opacity-75">BDC Annual ÷ 12</small>
                            </div>

                            <!-- Breakdown Table -->
                            <div class="table-responsive mb-4">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Component</th>
                                            <th class="text-end">Per Visit ($)</th>
                                            <th class="text-end">Annual ($)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Distance (<span id="tbl_km">50</span> km × <span id="tbl_rate_km">{{ number_format($calculation['rate_per_km'] ?? 1.50, 2) }}</span>)</td>
                                            <td class="text-end" id="tbl_dist_visit">—</td>
                                            <td class="text-end" id="tbl_dist_annual">—</td>
                                        </tr>
                                        <tr>
                                            <td>Time (<span id="tbl_min">45</span> min × <span id="tbl_rate_min">{{ number_format($calculation['rate_per_minute'] ?? 1.65, 2) }}</span>)</td>
                                            <td class="text-end" id="tbl_time_visit">—</td>
                                            <td class="text-end" id="tbl_time_annual">—</td>
                                        </tr>
                                        <tr class="table-success fw-bold">
                                            <td>TOTAL BDC (<span id="tbl_visits">12</span> visits)</td>
                                            <td class="text-end" id="tbl_total_visit">—</td>
                                            <td class="text-end" id="tbl_total_annual">—</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Labour Fallback Reference -->
                            <div class="card bg-light border-0">
                                <div class="card-body py-2 px-3">
                                    <h6 class="text-secondary small mb-1"><i class="mdi mdi-information-outline me-1"></i>Labour Fallback (no travel data)</h6>
                                    <p class="small mb-1 text-muted">Used automatically when distance &amp; time are not entered on the PHAR form.</p>
                                    <p class="small mb-0">Formula: Loaded Hourly Rate × Hours/Visit × Visits/Year</p>
                                    <p class="small mb-0">= $<span id="fallback_rate">{{ number_format($calculation['loaded_hourly_rate'] ?? 165, 2) }}</span>/hr × <span id="fallback_bdc">—</span> labour hours/yr</p>
                                </div>
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
    const fixedHoursPerVisit = {{ (float)($calculation['hours_per_visit'] ?? 4.5) }};

    // Rate inputs (from the settings form)
    const settingsInputs = document.querySelectorAll('.bdc-input');
    // Example inputs in the preview panel
    const exKm     = document.getElementById('ex_km');
    const exMin    = document.getElementById('ex_min');
    const exVisits = document.getElementById('ex_visits');

    settingsInputs.forEach(input => {
        input.addEventListener('input', debounce(calculatePreview, 400));
    });
    [exKm, exMin, exVisits].forEach(el => {
        el.addEventListener('input', calculatePreview);
    });

    function getRates() {
        const data = {};
        settingsInputs.forEach(input => {
            data[input.dataset.key] = parseFloat(input.value) || 0;
        });
        return data;
    }

    function calculatePreview() {
        const rates   = getRates();
        const rateKm  = rates.rate_per_km  || 1.50;
        const rateMin = rates.rate_per_minute || 1.65;
        const hourlyRate = rates.loaded_hourly_rate || 165;

        const km     = parseFloat(exKm.value)     || 0;
        const min    = parseFloat(exMin.value)    || 0;
        const visits = parseFloat(exVisits.value) || 1;

        // Travel-based BDC
        const distCostPerVisit = km  * rateKm;
        const timeCostPerVisit = min * rateMin;
        const bdcPerVisit      = distCostPerVisit + timeCostPerVisit;
        const bdcAnnual        = bdcPerVisit * visits;
        const bdcMonthly       = bdcAnnual / 12;

        // Labour fallback reference (uses hours from settings)
        const labourHoursPerYear = visits * fixedHoursPerVisit;

        // Update preview cards
        document.getElementById('travel_dist_cost').textContent = '$' + fmt(distCostPerVisit);
        document.getElementById('travel_time_cost').textContent = '$' + fmt(timeCostPerVisit);
        document.getElementById('bdc_per_visit').textContent    = '$' + fmt(bdcPerVisit);
        document.getElementById('bdc_annual').textContent       = '$' + fmt(bdcAnnual);
        document.getElementById('bdc_monthly').textContent      = '$' + fmt(bdcMonthly);

        // Update table header spans
        document.getElementById('tbl_km').textContent      = km;
        document.getElementById('tbl_min').textContent     = min;
        document.getElementById('tbl_visits').textContent  = visits;
        document.getElementById('tbl_rate_km').textContent  = fmt(rateKm);
        document.getElementById('tbl_rate_min').textContent = fmt(rateMin);

        // Update table values
        document.getElementById('tbl_dist_visit').textContent  = fmt(distCostPerVisit);
        document.getElementById('tbl_dist_annual').textContent = fmt(distCostPerVisit * visits);
        document.getElementById('tbl_time_visit').textContent  = fmt(timeCostPerVisit);
        document.getElementById('tbl_time_annual').textContent = fmt(timeCostPerVisit * visits);
        document.getElementById('tbl_total_visit').textContent = fmt(bdcPerVisit);
        document.getElementById('tbl_total_annual').textContent= fmt(bdcAnnual);

        // Update labour fallback
        document.getElementById('fallback_rate').textContent = fmt(hourlyRate);
        document.getElementById('fallback_bdc').textContent  = fmt(labourHoursPerYear, 1);
    }

    function fmt(num, decimals = 2) {
        return parseFloat(num).toLocaleString('en-US', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        });
    }

    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => { clearTimeout(timeout); func(...args); };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Initial render
    calculatePreview();
});
</script>
@endsection
