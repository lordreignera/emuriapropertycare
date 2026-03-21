@extends('admin.layout')

@section('title', 'PHAR Data Collection – Step 2 of 2')

@push('styles')
<style>
/* ─── Finding Cards ──────────────────────────────────────────── */
.finding-row {
    background: #fff;
    box-shadow: 0 1px 4px rgba(0,0,0,.08);
}
.finding-row .finding-header-bar {
    background: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
    padding: .5rem .75rem;
    border-radius: .375rem .375rem 0 0;
    margin: -.75rem -.75rem .75rem;
}

/* ─── Issue info block ───────────────────────────────────────── */
.finding-row .issue-info {
    background: #eef2ff;
    border: 1px solid #c7d2fe;
    border-radius: .375rem;
    padding: .6rem .85rem;
    margin-bottom: .75rem;
    font-size: .875rem;
    color: #1e293b;
    line-height: 1.5;
}
.finding-row .issue-info strong {
    color: #3730a3;
    font-weight: 600;
}
.finding-row .issue-info .issue-meta {
    display: flex;
    flex-wrap: wrap;
    gap: .75rem;
}

/* ─── PHAR field labels ──────────────────────────────────────── */
.phar-fields .form-label {
    font-size: .8rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: .25rem;
    text-transform: uppercase;
    letter-spacing: .03em;
}
.phar-fields .form-control,
.phar-fields .form-select {
    font-size: .9rem;
    color: #111827;
}
.phar-fields .form-control[readonly] {
    background: #f0fdf4;
    color: #166534;
    font-weight: 600;
}

/* ─── Materials section ──────────────────────────────────────── */
.materials-header {
    background: #ecfdf5;
    border: 1px solid #6ee7b7;
    border-radius: .375rem;
    padding: .4rem .75rem;
    margin-bottom: .5rem;
}
.material-row {
    background: #fafafa !important;
    border: 1px solid #e5e7eb !important;
}
.material-row .mat-label {
    font-size: .72rem;
    font-weight: 700;
    color: #4b5563;
    text-transform: uppercase;
    letter-spacing: .04em;
    margin-bottom: .2rem;
    display: block;
}
.material-row .form-control,
.material-row .form-select {
    font-size: .875rem;
    color: #111827;
}
.material-row .form-control[readonly] {
    background: #f0fdf4;
    color: #166534;
    font-weight: 600;
    border-color: #86efac;
}

/* ─── Summary bar ────────────────────────────────────────────── */
#pharSummaryBar {
    background: #1e293b;
    color: #fff;
    border-radius: .5rem;
    padding: 1rem 1.5rem;
}
#pharSummaryBar strong { color: #cbd5e1; font-size: .8rem; text-transform: uppercase; letter-spacing: .06em; }
#pharSummaryBar .sum-val { font-size: 1.75rem; font-weight: 700; line-height: 1.1; }
</style>
@endpush

@section('content')
<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">

            {{-- Progress / stage banner --}}
            <div class="card mb-3 border-0" style="background:linear-gradient(135deg,#5b67ca 0%,#4854b8 100%);">
                <div class="card-body text-white py-3">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h5 class="mb-1 fw-bold">
                                <i class="mdi mdi-home-city-outline me-2"></i>{{ $property->property_name }}
                                <span class="badge bg-warning text-dark ms-2">IN PROGRESS</span>
                            </h5>
                            <div class="opacity-75 small">{{ $property->property_address }}, {{ $property->city }} &mdash; Inspector: {{ auth()->user()->name }}</div>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            {{-- Stage 1 done --}}
                            <span class="badge bg-success px-3 py-2"><i class="mdi mdi-check me-1"></i>Step 1: Systems Inspection</span>
                            {{-- Stage 2 active --}}
                            <span class="badge bg-warning text-dark px-3 py-2"><i class="mdi mdi-circle-slice-4 me-1"></i>Step 2: PHAR Data (current)</span>
                            {{-- Back link --}}
                            <a href="{{ route('inspections.create', ['property_id' => $property->id]) }}" class="btn btn-sm btn-light">
                                <i class="mdi mdi-arrow-left me-1"></i>Back to Step 1
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
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
                                            <label>Visits per Year (Property-specific) <span class="text-danger">*</span></label>
                                            <input type="number" name="bdc_visits_per_year" id="bdcVisitsPerYear" class="form-control"
                                                   value="{{ old('bdc_visits_per_year', $inspection->bdc_visits_per_year ?? ($bdcSettings['visits_per_year'] ?? 8)) }}"
                                                   placeholder="e.g., 8" step="0.1" min="0" required />
                                            <small class="text-muted">This can change per property inspection</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Hours per Visit (Property-specific) <span class="text-danger">*</span></label>
                                            <input type="number" name="estimated_task_hours" id="hoursPerVisit" class="form-control" 
                                                   value="{{ old('estimated_task_hours', $inspection->estimated_task_hours ?? '') }}" 
                                                   placeholder="e.g., 4.5" step="0.1" min="0" required />
                                            <small class="text-muted">This can change per property inspection</small>
                                        </div>
                                    </div>
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
                                            <label>Loaded Hourly Rate ($/hr) <span class="text-danger">*</span></label>
                                            <input type="number" name="labour_hourly_rate" id="labourHourlyRate" class="form-control" 
                                                   value="{{ old('labour_hourly_rate', $inspection->labour_hourly_rate ?? ($bdcSettings['loaded_hourly_rate'] ?? 165)) }}" 
                                                  placeholder="165" step="0.01" min="0" required readonly />
                                            <small class="text-muted">Static setting value (managed in BDC Settings)</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Tenant Underwriting Score (TUS) <span class="text-danger">*</span></label>
                                            <input type="number" name="tus_score" class="form-control"
                                                   value="{{ old('tus_score', $inspection->tus_score ?? ($bdcSettings['tus_input_default'] ?? 75)) }}"
                                                   placeholder="75" step="0.1" min="0" max="100" required />
                                            <small class="text-muted">Tenant risk score (0–100). Default: 75. Used in ASI = CPI × 60% + TUS × 40%.</small>
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


                            </div>
                        </div>

                        <!-- SECTION 2: Findings Remediation – auto-populated from Step 1 issues -->
                        <div class="card mb-4 border-warning">
                            <div class="card-header" style="background:#ff9800;color:white;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><i class="mdi mdi-alert-circle-outline me-2"></i>Findings Remediation (Labour)</h5>
                                    <span class="badge bg-light text-dark">{{ count($sortedFindings) }} issue(s) from Step 1</span>
                                </div>
                            </div>
                            <div class="card-body">

                                @if(count($sortedFindings) === 0)
                                    <div class="alert alert-warning">
                                        <i class="mdi mdi-information me-2"></i>
                                        No issues were recorded in Step 1. <a href="{{ route('inspections.create', ['property_id' => $property->id]) }}">Go back and add issues</a> to systems before completing PHAR data.
                                    </div>
                                @else
                                    <div class="alert alert-info mb-3">
                                        <i class="mdi mdi-information me-2"></i>
                                        Issues are listed below in priority order (most critical first). Enter <strong>estimated labour hours</strong> for each, select a category, and mark whether it is included in the service tier. The labour cost is calculated automatically.
                                    </div>

                                    {{-- Severity legend --}}
                                    <div class="d-flex gap-2 mb-3 flex-wrap">
                                        <span class="badge px-2 py-1" style="background:#dc3545;">&#x1F534; Urgent (critical)</span>
                                        <span class="badge px-2 py-1" style="background:#fd7e14;">&#x1F7E0; Health &amp; Safety Threatening (high)</span>
                                        <span class="badge px-2 py-1" style="background:#ffc107;color:#212529;">&#x1F7E1; Value Depreciation (medium)</span>
                                        <span class="badge px-2 py-1" style="background:#198754;">&#x1F7E2; Non-Urgent (low)</span>
                                    </div>

                                    @php
                        $severityColors = [
                            'critical'       => '#dc3545',
                            'high'           => '#fd7e14',
                            'noi_protection' => '#7c3aed',
                            'medium'         => '#ffc107',
                            'low'            => '#198754',
                        ];
                        $severityLabels = [
                            'critical'       => 'Safety & Health',
                            'high'           => 'Urgent',
                            'noi_protection' => 'NOI Protection',
                            'medium'         => 'Value Depreciation',
                            'low'            => 'Non-Urgent',
                        ];
                        $priorityMap = [
                            'critical'       => 1,
                            'high'           => 1,
                            'noi_protection' => 2,
                            'medium'         => 2,
                            'low'            => 3,
                        ];
                        @endphp

                                    <div id="findingsContainer">
                                    @foreach($sortedFindings as $fi => $finding)
                                        @php
                                            $sev   = $finding['severity'] ?? 'low';
                                            $color = $severityColors[$sev] ?? '#6c757d';
                                            $label = $severityLabels[$sev] ?? ucfirst($sev);
                                            $prio  = $finding['priority'] ?? ($priorityMap[$sev] ?? 3);
                                        @endphp
                                        <div class="finding-row mb-3 p-3 rounded border" style="border-left:4px solid {{ $color }} !important;" data-subsystem-id="{{ $finding['subsystem_id'] ?? '' }}">
                                            <div class="finding-header-bar d-flex justify-content-between align-items-center">
                                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                                    <span class="badge" style="background:{{ $color }};{{ $sev==='medium' ? 'color:#212529;' : '' }}">{{ $label }}</span>
                                                    <strong>{{ $finding['system'] ?? 'System' }}</strong>
                                                    @if(!empty($finding['subsystem']))
                                                        <span class="text-muted"> &rsaquo; {{ $finding['subsystem'] }}</span>
                                                    @endif
                                                    @php $sysWeight = $systemWeightsMap[$finding['system'] ?? ''] ?? null; @endphp
                                                    @if($sysWeight)
                                                        <span class="badge bg-secondary" title="System Weight">W{{ $sysWeight }}</span>
                                                    @endif
                                                </div>
                                                <span class="text-muted small fw-semibold">Finding #{{ $fi + 1 }}</span>
                                            </div>

                                            {{-- Issue description (read-only from phase 1) --}}
                                            <div class="issue-info">
                                                <div class="issue-meta">
                                                    <span><strong>Issue:</strong> {{ $finding['issue'] ?? '—' }}</span>
                                                    <span><strong>Location:</strong> {{ $finding['location'] ?? '—' }}</span>
                                                    <span><strong>Spot:</strong> {{ $finding['spot'] ?? '—' }}</span>
                                                </div>
                                                @if(!empty($finding['notes']))
                                                    <div class="mt-1"><strong>Notes:</strong> {{ $finding['notes'] }}</div>
                                                @endif
                                                @if(!empty($finding['recommendations']))
                                                    <div class="mt-1"><strong>Recommendations:</strong> {{ implode(', ', (array)$finding['recommendations']) }}</div>
                                                @endif
                                            </div>

                                            {{-- Hidden carry-over fields --}}
                                            <input type="hidden" name="findings[{{ $fi }}][task_question]" value="{{ $finding['issue'] ?? '' }}">
                                            <input type="hidden" name="findings[{{ $fi }}][property_id]" value="{{ $property->id }}">
                                            <input type="hidden" name="findings[{{ $fi }}][priority]" value="{{ $prio }}">

                                            {{-- Editable PHAR fields (pre-filled from saved draft) --}}
                                            <div class="row g-2 mt-1 phar-fields">
                                                <div class="col-md-2">
                                                    <label class="form-label">Est. Labour Hours</label>
                                                    <input type="number" name="findings[{{ $fi }}][labour_hours]" class="form-control form-control-sm finding-labour-hours"
                                                           placeholder="0.0" step="0.1" min="0"
                                                           value="{{ old("findings.$fi.labour_hours", $finding['phar_labour_hours'] ?? 0) }}">
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label">Labour Cost</label>
                                                    <input type="text" class="form-control form-control-sm finding-labour-cost" placeholder="$0.00" readonly>
                                                    <div class="text-muted" style="font-size:.75rem;">Auto-calculated</div>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">Category</label>
                                                    <select name="findings[{{ $fi }}][category]" class="form-select form-select-sm">
                                                        <option value="">-- Select --</option>
                                                        @foreach(($pharCategories ?? []) as $cat)
                                                            <option value="{{ $cat }}"
                                                                {{ old("findings.$fi.category", $finding['phar_category'] ?? '') === $cat ? 'selected' : '' }}>
                                                                {{ $cat }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label">Included in tier?</label>
                                                    @php $includedVal = old("findings.$fi.included_yn", $finding['phar_included_yn'] ?? true); @endphp
                                                    <select name="findings[{{ $fi }}][included_yn]" class="form-select form-select-sm">
                                                        <option value="1" {{ $includedVal ? 'selected' : '' }}>Yes</option>
                                                        <option value="0" {{ !$includedVal ? 'selected' : '' }}>No</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">Additional Notes</label>
                                                    <input type="text" name="findings[{{ $fi }}][notes]" class="form-control form-control-sm"
                                                           placeholder="Optional"
                                                           value="{{ old("findings.$fi.notes", $finding['phar_notes'] ?? '') }}">
                                                </div>
                                            </div>

                                            {{-- Per-finding materials --}}
                                            <div class="mt-3">
                                                <div class="materials-header d-flex justify-content-between align-items-center">
                                                    <span class="fw-bold text-success" style="font-size:.85rem;"><i class="mdi mdi-package-variant me-1"></i>Materials for this finding</span>
                                                    <button type="button" class="btn btn-sm btn-success add-finding-material py-1 px-3" data-fi="{{ $fi }}">
                                                        <i class="mdi mdi-plus me-1"></i>Add Material
                                                    </button>
                                                </div>
                                                <div class="finding-materials-container" id="materials-fi-{{ $fi }}" data-fi="{{ $fi }}">
                                                    @php $preMats = $finding['phar_materials'] ?? []; @endphp
                                                    @if(empty($preMats))
                                                        <p class="text-muted small mb-1 no-materials-msg">No materials yet. Click "Add Material" to attach parts/supplies to this finding.</p>
                                                    @else
                                                        @foreach($preMats as $mi => $mat)
                                                        <div class="material-row border rounded p-2 mb-1 bg-white">
                                                            <div class="d-flex justify-content-end mb-1">
                                                                <button type="button" class="btn btn-sm btn-outline-danger remove-finding-material py-0 px-1">
                                                                    <i class="mdi mdi-delete-outline"></i> Remove
                                                                </button>
                                                            </div>
                                                            <div class="row g-2">
                                                                <div class="col-md-1">
                                                                    <label class="mat-label">Preset</label>
                                                                    <select class="form-select form-select-sm material-template">
                                                                        <option value="">Custom</option>
                                                                        @foreach(($fmcMaterialSettings ?? []) as $ms)
                                                                            <option value="{{ $ms->material_name }}"
                                                                                    data-unit="{{ $ms->default_unit }}"
                                                                                    data-cost="{{ number_format((float)$ms->default_unit_cost,2,'.','')}}"
                                                                                    {{ $mat['material_name'] === $ms->material_name ? 'selected' : '' }}>
                                                                                {{ $ms->material_name }}
                                                                            </option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                                <div class="col-md-3">
                                                                    <label class="mat-label">Description <span class="text-danger">*</span></label>
                                                                    <input type="text" name="findings[{{ $fi }}][materials][{{ $mi }}][material_name]"
                                                                           class="form-control form-control-sm"
                                                                           value="{{ $mat['material_name'] ?? '' }}" required>
                                                                </div>
                                                                <div class="col-md-1">
                                                                    <label class="mat-label">Qty</label>
                                                                    <input type="number" name="findings[{{ $fi }}][materials][{{ $mi }}][quantity]"
                                                                           class="form-control form-control-sm material-quantity"
                                                                           min="0" step="0.01" value="{{ $mat['quantity'] ?? 1 }}" required>
                                                                </div>
                                                                <div class="col-md-2">
                                                                    <label class="mat-label">Unit</label>
                                                                    <select name="findings[{{ $fi }}][materials][{{ $mi }}][unit]" class="form-select form-select-sm" required>
                                                                        @foreach(($materialUnits ?? []) as $u)
                                                                            <option value="{{ $u }}" {{ ($mat['unit'] ?? '') === $u ? 'selected' : '' }}>{{ ucwords($u) }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                                <div class="col-md-2">
                                                                    <label class="mat-label">Unit Cost ($)</label>
                                                                    <input type="number" name="findings[{{ $fi }}][materials][{{ $mi }}][unit_cost]"
                                                                           class="form-control form-control-sm material-unit-cost"
                                                                           min="0" step="0.01" value="{{ $mat['unit_cost'] ?? 0 }}" required>
                                                                </div>
                                                                <div class="col-md-2">
                                                                    <label class="mat-label">Line Total</label>
                                                                    <input type="text" class="form-control form-control-sm material-line-total" readonly
                                                                           value="${{ number_format(($mat['line_total'] ?? 0), 2) }}">
                                                                    <input type="hidden" name="findings[{{ $fi }}][materials][{{ $mi }}][line_total]"
                                                                           class="material-line-total-hidden" value="{{ $mat['line_total'] ?? 0 }}">
                                                                </div>
                                                                <div class="col-md-1">
                                                                    <label class="mat-label">Notes</label>
                                                                    <input type="text" name="findings[{{ $fi }}][materials][{{ $mi }}][notes]"
                                                                           class="form-control form-control-sm" value="{{ $mat['notes'] ?? '' }}">
                                                                </div>
                                                            </div>
                                                            <input type="hidden" name="findings[{{ $fi }}][materials][{{ $mi }}][property_id]" value="{{ $property->id }}">
                                                        </div>
                                                        @endforeach
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                    </div>

                                    {{-- Summary bar --}}
                                    @php
                                        $sevCount = ['critical'=>0,'high'=>0,'noi_protection'=>0,'medium'=>0,'low'=>0];
                                        foreach($sortedFindings as $sf) {
                                            $sv = $sf['severity'] ?? 'low';
                                            if (isset($sevCount[$sv])) $sevCount[$sv]++;
                                            else $sevCount['low']++;
                                        }
                                    @endphp
                                    <div id="pharSummaryBar" class="mt-3">
                                        {{-- Severity breakdown row --}}
                                        <div class="row text-center mb-2 pb-2" style="border-bottom:1px solid rgba(255,255,255,.15);">
                                            <div class="col text-center">
                                                <div class="small fw-semibold mb-1" style="color:#fc8181;">&#x1F534; Safety &amp; Health</div>
                                                <div class="fw-bold fs-5" style="color:#fc8181;">{{ $sevCount['critical'] }}</div>
                                            </div>
                                            <div class="col text-center">
                                                <div class="small fw-semibold mb-1" style="color:#fbd38d;">&#x1F7E0; Urgency</div>
                                                <div class="fw-bold fs-5" style="color:#fbd38d;">{{ $sevCount['high'] }}</div>
                                            </div>
                                            <div class="col text-center">
                                                <div class="small fw-semibold mb-1" style="color:#d6bcfa;">&#x1F7E3; NOI Protection</div>
                                                <div class="fw-bold fs-5" style="color:#d6bcfa;">{{ $sevCount['noi_protection'] }}</div>
                                            </div>
                                            <div class="col text-center">
                                                <div class="small fw-semibold mb-1" style="color:#fef08a;">&#x1F7E1; Value Depreciation</div>
                                                <div class="fw-bold fs-5" style="color:#fef08a;">{{ $sevCount['medium'] }}</div>
                                            </div>
                                            <div class="col text-center">
                                                <div class="small fw-semibold mb-1" style="color:#86efac;">&#x1F7E2; Non-Urgent</div>
                                                <div class="fw-bold fs-5" style="color:#86efac;">{{ $sevCount['low'] }}</div>
                                            </div>
                                        </div>
                                        {{-- Totals row --}}
                                        <div class="row text-center">
                                            <div class="col-md-4">
                                                <strong>Total Findings</strong>
                                                <div class="sum-val" style="color:#93c5fd;">{{ count($sortedFindings) }}</div>
                                            </div>
                                            <div class="col-md-4">
                                                <strong>Total Labour Hours</strong>
                                                <div class="sum-val" style="color:#67e8f9;" id="totalLabourHours">0.0</div>
                                            </div>
                                            <div class="col-md-4">
                                                <strong>Total FR Labour Cost (FRLC)</strong>
                                                <div class="sum-val" style="color:#fcd34d;" id="totalFRLC">$0.00</div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Materials FMC Summary (aggregated from per-finding materials above) -->
                        <div class="alert alert-secondary mb-4">
                            <div class="row text-center">
                                <div class="col-md-6">
                                    <strong>Total Material Items:</strong>
                                    <div class="fs-4 text-info" id="totalItemsQty">0</div>
                                </div>
                                <div class="col-md-6">
                                    <strong>Total Material Cost (FMC):</strong>
                                    <div class="fs-4 text-success" id="totalFMC">$0.00</div>
                                </div>
                            </div>
                        </div>

                        <!-- ============================================================ -->
                        <!-- SECTION 4: PHAR DASHBOARD — Condition Indices + Pricing     -->
                        <!-- ============================================================ -->

                        {{-- ── Panel A: CPI / ASI / TUS Condition Indices ─────────────── --}}
                        @php
                            $cpi      = $inspection->cpi_total_score;
                            $asi      = $inspection->asi_score;
                            $tus      = $inspection->tus_score ?? ($bdcSettings['tus_input_default'] ?? 75);
                            $cpiRat   = $inspection->cpi_rating ?? '—';
                            $asiRat   = $inspection->asi_rating ?? '—';

                            $cpiColor = '#6c757d';
                            if ($cpi !== null) {
                                if ($cpi >= 90)      $cpiColor = '#198754';
                                elseif ($cpi >= 75)  $cpiColor = '#0dcaf0';
                                elseif ($cpi >= 60)  $cpiColor = '#ffc107';
                                elseif ($cpi >= 40)  $cpiColor = '#fd7e14';
                                else                 $cpiColor = '#dc3545';
                            }
                            $asiColor = '#6c757d';
                            if ($asi !== null) {
                                if ($asi >= 85)      $asiColor = '#198754';
                                elseif ($asi >= 70)  $asiColor = '#0dcaf0';
                                elseif ($asi >= 55)  $asiColor = '#ffc107';
                                elseif ($asi >= 40)  $asiColor = '#fd7e14';
                                else                 $asiColor = '#dc3545';
                            }
                            $tusColor = '#198754';
                            if ($tus < 40)       $tusColor = '#dc3545';
                            elseif ($tus < 60)   $tusColor = '#fd7e14';
                            elseif ($tus < 80)   $tusColor = '#ffc107';

                            $syScores = $inspection->system_scores ?? [];
                        @endphp

                        <div class="card mb-4" style="border:2px solid #5b67ca;">
                            <div class="card-header text-white d-flex justify-content-between align-items-center" style="background:linear-gradient(135deg,#5b67ca,#3d4ba8);">
                                <h5 class="mb-0"><i class="mdi mdi-chart-donut me-2"></i>Condition Indices</h5>
                                <small class="opacity-75">Calculated from Step 1 inspection findings</small>
                            </div>
                            <div class="card-body">

                                {{-- Three index cards --}}
                                <div class="row g-3 mb-3">
                                    {{-- CPI --}}
                                    <div class="col-md-4">
                                        <div class="p-3 rounded text-center h-100" style="background:#f0f4ff;border:2px solid {{ $cpiColor }};">
                                            <div class="text-muted small fw-semibold mb-1 text-uppercase" style="letter-spacing:.05em;">CPI — Composite Property Index</div>
                                            @if($cpi !== null)
                                                <div class="fw-bold" style="font-size:2.8rem;color:{{ $cpiColor }};line-height:1;">{{ number_format($cpi, 1) }}</div>
                                                <span class="badge mt-2 px-3 py-2" style="background:{{ $cpiColor }};font-size:.85rem;">{{ $cpiRat }}</span>
                                            @else
                                                <div class="text-muted my-2" style="font-size:2rem;">—</div>
                                                <small class="text-muted">Complete Step 1 to compute</small>
                                            @endif
                                            <div class="text-muted mt-2" style="font-size:.72rem;">Σ(SystemScore × Weight) / 197</div>
                                        </div>
                                    </div>

                                    {{-- TUS --}}
                                    <div class="col-md-4">
                                        <div class="p-3 rounded text-center h-100" style="background:#f0fff4;border:2px solid {{ $tusColor }};">
                                            <div class="text-muted small fw-semibold mb-1 text-uppercase" style="letter-spacing:.05em;">TUS — Tenant Underwriting Score</div>
                                            <div class="fw-bold" style="font-size:2.8rem;color:{{ $tusColor }};line-height:1;">{{ number_format($tus, 1) }}</div>
                                            <span class="badge mt-2 px-3 py-2" style="background:{{ $tusColor }};font-size:.85rem;">
                                                @if($tus >= 80) Low Risk
                                                @elseif($tus >= 60) Moderate Risk
                                                @elseif($tus >= 40) Elevated Risk
                                                @else High Risk
                                                @endif
                                            </span>
                                            <div class="text-muted mt-2" style="font-size:.72rem;">Input by inspector (0–100 scale)</div>
                                        </div>
                                    </div>

                                    {{-- ASI --}}
                                    <div class="col-md-4">
                                        <div class="p-3 rounded text-center h-100" style="background:#fff8f0;border:2px solid {{ $asiColor }};">
                                            <div class="text-muted small fw-semibold mb-1 text-uppercase" style="letter-spacing:.05em;">ASI — Asset Stability Index</div>
                                            @if($asi !== null)
                                                <div class="fw-bold" style="font-size:2.8rem;color:{{ $asiColor }};line-height:1;">{{ number_format($asi, 1) }}</div>
                                                <span class="badge mt-2 px-3 py-2" style="background:{{ $asiColor }};font-size:.85rem;">{{ $asiRat }}</span>
                                            @else
                                                <div class="text-muted my-2" style="font-size:2rem;">—</div>
                                                <small class="text-muted">Computed after Step 1</small>
                                            @endif
                                            <div class="text-muted mt-2" style="font-size:.72rem;">CPI × 60% + TUS × 40%</div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Per-system score breakdown (collapsible) --}}
                                @if(!empty($syScores))
                                <div>
                                    <button type="button" class="btn btn-sm btn-outline-secondary mb-2" id="sysBreakdownToggle"
                                            onclick="var el=document.getElementById('sysBreakdownTable');var vis=el.style.display!=='none';el.style.display=vis?'none':'';this.innerHTML=vis?'<i class=\'mdi mdi-chevron-right me-1\'></i>Show System Score Breakdown':'<i class=\'mdi mdi-chevron-down me-1\'></i>Hide System Score Breakdown';">
                                        <i class="mdi mdi-chevron-down me-1"></i>Hide System Score Breakdown
                                    </button>
                                    <div id="sysBreakdownTable">
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered mb-0">
                                                <thead class="table-dark">
                                                    <tr>
                                                        <th>System</th>
                                                        <th class="text-center">Weight</th>
                                                        <th class="text-center">CPI Deduction</th>
                                                        <th class="text-center">System Score</th>
                                                        <th class="text-center">CPI Points</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @php $syCpiTotal = 0; @endphp
                                                    @foreach($syScores as $sysId => $sysData)
                                                        @php
                                                            $syName   = $sysData['name']      ?? ('System '.$sysId);
                                                            $syW      = (int)($sysData['weight']     ?? 0);
                                                            $sySS     = (float)($sysData['score']    ?? 0);
                                                            $syDed    = (float)($sysData['deduction'] ?? 0);
                                                            $syCpiPts = round($sySS * $syW / 197, 2);
                                                            $syCpiTotal += $syCpiPts;
                                                            $syRow = ($sySS >= 90) ? 'table-success' : (($sySS >= 60) ? '' : (($sySS >= 30) ? 'table-warning' : 'table-danger'));
                                                        @endphp
                                                        <tr class="{{ $syRow }}">
                                                            <td class="fw-semibold">{{ $syName }}</td>
                                                            <td class="text-center"><span class="badge bg-secondary">W{{ $syW }}</span></td>
                                                            <td class="text-center {{ $syDed > 0 ? 'text-danger fw-semibold' : 'text-muted' }}">{{ $syDed > 0 ? number_format($syDed, 1) : '—' }}</td>
                                                            <td class="text-center fw-semibold">{{ number_format($sySS, 1) }}</td>
                                                            <td class="text-center">{{ number_format($syCpiPts, 2) }}</td>
                                                        </tr>
                                                    @endforeach
                                                    <tr class="table-dark fw-bold">
                                                        <td colspan="4" class="text-end">CPI = Σ(score × weight) / 197 =</td>
                                                        <td class="text-center">{{ $cpi !== null ? number_format($cpi, 1) : '—' }}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>

                        {{-- ── Panel B: Live Cost Preview (JS-updated) ─────────────────── --}}
                        <div class="card mb-4 border-dark">
                            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="mdi mdi-calculator-variant me-2"></i>Live Cost Preview</h5>
                                <small class="text-white-50">Updates as you enter labour hours and materials above</small>
                            </div>
                            <div class="card-body">

                                {{-- Cost components row --}}
                                <div class="row g-3 mb-3">
                                    <div class="col-md-3">
                                        <div class="p-3 rounded text-center" style="background:#e8f4fd;border:1px solid #0d6efd;">
                                            <div class="text-muted small fw-semibold">Base Deployment Cost</div>
                                            <div id="bdcAnnual" class="fw-bold fs-5 text-primary">$0.00</div>
                                            <div id="bdcMonthly" class="text-muted" style="font-size:.8rem;">$0.00/mo</div>
                                            <div class="text-muted" style="font-size:.7rem;">visits × hrs × rate × 1.42</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="p-3 rounded text-center" style="background:#fff3e0;border:1px solid #fd7e14;">
                                            <div class="text-muted small fw-semibold">Findings Remediation Labour</div>
                                            <div id="frlcAnnual" class="fw-bold fs-5 text-warning">$0.00</div>
                                            <div id="frlcMonthly" class="text-muted" style="font-size:.8rem;">$0.00/mo</div>
                                            <div class="text-muted" style="font-size:.7rem;">Σ(finding labour hrs) × rate</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="p-3 rounded text-center" style="background:#e8f8f0;border:1px solid #198754;">
                                            <div class="text-muted small fw-semibold">Findings Material Cost</div>
                                            <div id="fmcAnnual" class="fw-bold fs-5 text-success">$0.00</div>
                                            <div id="fmcMonthly" class="text-muted" style="font-size:.8rem;">$0.00/mo</div>
                                            <div class="text-muted" style="font-size:.7rem;">Σ(material line totals)</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="p-3 rounded text-center" style="background:#f0e8ff;border:2px solid #7c3aed;">
                                            <div class="text-muted small fw-semibold">Total Remediation Cost</div>
                                            <div id="trcAnnual" class="fw-bold fs-5" style="color:#7c3aed;">$0.00</div>
                                            <div id="trcMonthly" class="text-muted" style="font-size:.8rem;">$0.00/mo</div>
                                            <div class="text-muted" style="font-size:.7rem;">BDC + FRLC + FMC</div>
                                        </div>
                                    </div>
                                </div>

                                {{-- ARP + floor + preview final row --}}
                                <div class="row g-3 mb-3">
                                    <div class="col-md-4">
                                        <div class="p-3 rounded text-center" style="background:#fff8e1;border:2px solid #ffc107;">
                                            <div class="text-muted small fw-semibold">ARP Monthly</div>
                                            <div id="arpMonthly" class="fw-bold" style="font-size:1.8rem;color:#856404;">$0.00/month</div>
                                            <div class="text-muted" style="font-size:.75rem;">= TRC / 12</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="p-3 rounded text-center" style="background:#e8f0fe;border:3px solid #3d4ba8;">
                                            <div class="text-muted small fw-semibold">Preview ARP Monthly</div>
                                            <div id="previewFinalMonthly" class="fw-bold" style="font-size:1.8rem;color:#3d4ba8;">$0.00</div>
                                            <div class="text-muted" style="font-size:.75rem;">= TRC / 12 — before tier multiplier</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="alert alert-info mb-0">
                                    <i class="mdi mdi-information me-2"></i>
                                    <strong>Preview only.</strong> Final pricing applies CPI/ASI tier multipliers computed during system processing. Click <strong>Save &amp; Calculate Final Pricing</strong> below to see the full result.
                                </div>
                            </div>
                        </div>

                        {{-- ── Panel C: Final Calculated PHAR Dashboard (post-submission) ─ --}}
                        @if(($inspection->bdc_annual ?? 0) > 0)
                        @php
                            $scipFinal  = (float)($inspection->arp_equivalent_final ?? 0);
                            $tierFinal  = $inspection->tier_final ?? '—';
                            $tierColor  = '#6c757d';
                            if ($tierFinal === 'Essentials')         $tierColor = '#198754';
                            elseif ($tierFinal === 'Premium')        $tierColor = '#ffc107';
                            elseif ($tierFinal === 'White-Glove')    $tierColor = '#fd7e14';
                            elseif ($tierFinal === 'Critical Care')  $tierColor = '#dc3545';
                            $calcUnits  = (int)($inspection->units_for_calculation ?? 1);
                        @endphp

                        <div class="card mb-4" style="border:3px solid #198754;">
                            <div class="card-header text-white d-flex justify-content-between align-items-center" style="background:linear-gradient(135deg,#198754,#146c43);">
                                <h5 class="mb-0"><i class="mdi mdi-check-decagram me-2"></i>Final PHAR Pricing Dashboard</h5>
                                <span class="badge bg-light text-success fs-6 px-3">Calculated</span>
                            </div>
                            <div class="card-body">

                                {{-- Row 1: Cost components --}}
                                <h6 class="text-muted fw-bold text-uppercase mb-2 border-bottom pb-1" style="font-size:.75rem;letter-spacing:.08em;">Cost Components</h6>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-3">
                                        <div class="p-3 rounded text-center" style="background:#e8f4fd;border:1px solid #0d6efd;">
                                            <div class="text-muted small">Base Deployment Cost (BDC)</div>
                                            <div class="fw-bold fs-5 text-primary">${{ number_format($inspection->bdc_annual ?? 0, 2) }}/yr</div>
                                            <div class="text-muted small">${{ number_format($inspection->bdc_monthly ?? 0, 2) }}/mo</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="p-3 rounded text-center" style="background:#fff3e0;border:1px solid #fd7e14;">
                                            <div class="text-muted small">Findings Remediation Labour (FRLC)</div>
                                            <div class="fw-bold fs-5 text-warning">${{ number_format($inspection->frlc_annual ?? 0, 2) }}/yr</div>
                                            <div class="text-muted small">${{ number_format($inspection->frlc_monthly ?? 0, 2) }}/mo</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="p-3 rounded text-center" style="background:#e8f8f0;border:1px solid #198754;">
                                            <div class="text-muted small">Findings Material Cost (FMC)</div>
                                            <div class="fw-bold fs-5 text-success">${{ number_format($inspection->fmc_annual ?? 0, 2) }}/yr</div>
                                            <div class="text-muted small">${{ number_format($inspection->fmc_monthly ?? 0, 2) }}/mo</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="p-3 rounded text-center" style="background:#f0e8ff;border:2px solid #7c3aed;">
                                            <div class="text-muted small fw-semibold">Total Remediation Cost (TRC)</div>
                                            <div class="fw-bold fs-5" style="color:#7c3aed;">${{ number_format($inspection->trc_annual ?? 0, 2) }}/yr</div>
                                            <div class="text-muted small fw-semibold">${{ number_format($inspection->trc_monthly ?? 0, 2) }}/mo</div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Row 2: Tier assessment --}}
                                <h6 class="text-muted fw-bold text-uppercase mb-2 border-bottom pb-1" style="font-size:.75rem;letter-spacing:.08em;">Tier Assessment (Dual-Gate)</h6>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-3">
                                        <div class="p-3 rounded text-center" style="background:#fff8e1;border:2px solid #ffc107;">
                                            <div class="text-muted small fw-semibold">ARP Monthly</div>
                                            <div class="fw-bold" style="font-size:1.6rem;color:#856404;">${{ number_format($inspection->arp_monthly ?? 0, 2) }}</div>
                                            <div class="text-muted" style="font-size:.75rem;">= TRC / 12</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="p-3 rounded text-center border">
                                            <div class="text-muted small">Gate 1 — CPI Tier Score</div>
                                            <div class="fw-bold fs-4 text-secondary">{{ $inspection->tier_score ?? '—' }}</div>
                                            <div class="text-muted" style="font-size:.75rem;">CPI = {{ number_format($inspection->cpi_total_score ?? 0, 1) }}</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="p-3 rounded text-center border">
                                            <div class="text-muted small">Gate 2 — ARP Tier</div>
                                            <div class="fw-bold fs-4 text-secondary">{{ $inspection->tier_arp ?? '—' }}</div>
                                            <div class="text-muted" style="font-size:.75rem;">ARP = ${{ number_format($inspection->arp_monthly ?? 0, 0) }}/mo</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="p-3 rounded text-center text-white" style="background:{{ $tierColor }};border:2px solid {{ $tierColor }};">
                                            <div class="fw-semibold small opacity-75">Final Tier (max of gates)</div>
                                            <div class="fw-bold" style="font-size:1rem;line-height:1.3;">{{ $tierFinal }}</div>
                                            <div class="fw-bold mt-1" style="font-size:1.8rem;line-height:1;">× {{ $inspection->multiplier_final ?? '1.00' }}</div>
                                            <div class="opacity-75" style="font-size:.72rem;">multiplier applied to ARP</div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Row 3: Final pricing --}}
                                <h6 class="text-muted fw-bold text-uppercase mb-2 border-bottom pb-1" style="font-size:.75rem;letter-spacing:.08em;">Final Pricing</h6>
                                <div class="row g-3 mb-3">
                                    <div class="col-md-4">
                                        <div class="p-3 rounded text-center" style="background:#f8f9fa;border:1px solid #0d6efd;">
                                            <div class="text-muted small">ARP × Multiplier (ARP Equivalent)</div>
                                            <div class="fw-bold fs-4 text-primary">${{ number_format($inspection->arp_equivalent_final ?? 0, 2) }}</div>
                                            <div class="text-muted" style="font-size:.75rem;">per month</div>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="p-4 rounded text-center text-white h-100 d-flex flex-column justify-content-center" style="background:linear-gradient(135deg,#198754,#146c43);border:3px solid #0f5132;">
                                            <div class="fw-semibold opacity-75 small text-uppercase" style="letter-spacing:.05em;">Scientific Final Monthly</div>
                                            <div class="fw-bold" style="font-size:2.4rem;line-height:1.1;">${{ number_format($scipFinal, 2) }}</div>
                                            <div class="opacity-75" style="font-size:.75rem;">= TRC / 12</div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Per-unit breakdown --}}
                                @if($calcUnits > 1)
                                <div class="alert alert-secondary mb-3">
                                    <h6 class="mb-2"><i class="mdi mdi-home-group me-2"></i>Per-Unit Breakdown — {{ $calcUnits }} units</h6>
                                    <div class="row text-center">
                                        <div class="col-md-3">
                                            <small class="text-muted d-block">BDC / Unit / Year</small>
                                            <strong>${{ number_format($inspection->bdc_per_unit_annual ?? 0, 2) }}</strong>
                                        </div>
                                        <div class="col-md-3">
                                            <small class="text-muted d-block">FRLC / Unit / Year</small>
                                            <strong>${{ number_format($inspection->frlc_per_unit_annual ?? 0, 2) }}</strong>
                                        </div>
                                        <div class="col-md-3">
                                            <small class="text-muted d-block">FMC / Unit / Year</small>
                                            <strong>${{ number_format($inspection->fmc_per_unit_annual ?? 0, 2) }}</strong>
                                        </div>
                                        <div class="col-md-3">
                                            <small class="text-muted d-block">Final Price / Unit / Month</small>
                                            <strong>${{ number_format($inspection->final_monthly_per_unit ?? 0, 2) }}</strong>
                                        </div>
                                    </div>
                                </div>
                                @endif

                                {{-- Bottom formula strip --}}
                                <div class="p-3 rounded" style="background:#1e293b;color:#e2e8f0;font-size:.78rem;overflow-x:auto;">
                                    <div class="d-flex flex-nowrap align-items-center gap-0">
                                        <div class="text-center px-3 border-end border-secondary">
                                            <div style="color:#93c5fd;">BDC</div>
                                            <div class="fw-bold">${{ number_format($inspection->bdc_monthly ?? 0, 0) }}/mo</div>
                                        </div>
                                        <div class="text-center px-2 text-white opacity-50">+</div>
                                        <div class="text-center px-3 border-end border-secondary">
                                            <div style="color:#fcd34d;">FRLC</div>
                                            <div class="fw-bold">${{ number_format($inspection->frlc_monthly ?? 0, 0) }}/mo</div>
                                        </div>
                                        <div class="text-center px-2 text-white opacity-50">+</div>
                                        <div class="text-center px-3 border-end border-secondary">
                                            <div style="color:#86efac;">FMC</div>
                                            <div class="fw-bold">${{ number_format($inspection->fmc_monthly ?? 0, 0) }}/mo</div>
                                        </div>
                                        <div class="text-center px-2 text-white opacity-50">=</div>
                                        <div class="text-center px-3 border-end border-secondary">
                                            <div style="color:#c4b5fd;">TRC</div>
                                            <div class="fw-bold">${{ number_format($inspection->trc_monthly ?? 0, 0) }}/mo</div>
                                        </div>
                                        <div class="text-center px-2 text-white opacity-50">÷12=</div>
                                        <div class="text-center px-3 border-end border-secondary">
                                            <div style="color:#fde68a;">ARP</div>
                                            <div class="fw-bold">${{ number_format($inspection->arp_monthly ?? 0, 0) }}/mo</div>
                                        </div>
                                        <div class="text-center px-2 text-white opacity-50">×</div>
                                        <div class="text-center px-3 border-end border-secondary">
                                            <div style="color:#a78bfa;">Multiplier</div>
                                            <div class="fw-bold">{{ $inspection->multiplier_final ?? '1.00' }}</div>
                                        </div>
                                        <div class="text-center px-2 text-white opacity-50">=</div>
                                        <div class="text-center px-3 border-end border-secondary">
                                            <div style="color:#6ee7b7;">ARP Equiv.</div>
                                            <div class="fw-bold">${{ number_format($inspection->arp_equivalent_final ?? 0, 0) }}/mo</div>
                                        </div>
                                        <div class="text-center px-2 text-white opacity-50">→ max →</div>
                                        <div class="text-center px-3">
                                            <div style="color:#fbbf24;font-weight:900;">FINAL</div>
                                            <div class="fw-bold" style="color:#fbbf24;font-size:1.1rem;">${{ number_format($scipFinal, 0) }}/mo</div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>

                        @else
                        <div class="alert alert-warning mb-4">
                            <i class="mdi mdi-calculator-variant me-2"></i>
                            <strong>Pricing not yet calculated.</strong> Enter labour hours and materials above, then click <strong>Save &amp; Calculate Final Pricing</strong> to run the PHAR engine and see tier-adjusted pricing here.
                        </div>
                        @endif

                        <!-- Form Actions -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <p class="text-muted small mb-3">
                                    <i class="mdi mdi-information-outline me-1"></i>
                                    <strong>Save Draft &amp; Back</strong> saves your labour hours and materials, then returns you to Step 1 so you can review or amend the findings list. Come back to Step 2 any time — your data will be pre-filled.
                                </p>
                                <div class="d-flex justify-content-between">
                                    <button type="submit" name="action" value="save_draft_back" class="btn btn-secondary">
                                        <i class="mdi mdi-content-save me-1"></i>Save Draft &amp; Back to Step 1
                                    </button>
                                    <button type="submit" name="action" value="save_final" class="btn btn-primary btn-lg">
                                        <i class="mdi mdi-check-circle me-1"></i>Save &amp; Calculate Final Pricing
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

@php
    $findingTemplateSettingsJson = ($findingTemplateSettings ?? collect())->map(function($item) {
        return [
            'task_question'       => $item->task_question,
            'category'            => $item->category,
            'default_included'    => (bool) $item->default_included,
            'default_notes'       => $item->default_notes,
        ];
    })->values()->all();

    $fmcMaterialSettingsJson = ($fmcMaterialSettings ?? collect())->map(function($item) {
        return [
            'material_name'    => $item->material_name,
            'default_unit'     => $item->default_unit,
            'default_unit_cost'=> (float) $item->default_unit_cost,
            'subsystem_id'     => $item->subsystem_id,
        ];
    })->values()->all();
@endphp

<script>
document.addEventListener('DOMContentLoaded', function() {
    const PHAR_CATEGORIES = @json($pharCategories ?? []);
    const MATERIAL_UNITS = @json($materialUnits ?? []);
    const FINDING_TEMPLATE_SETTINGS = @json($findingTemplateSettingsJson);
    const FMC_MATERIAL_SETTINGS = @json($fmcMaterialSettingsJson);

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

    function buildMaterialPresetOptions(subsystemId = null) {
        let html = '<option value="">Custom / Manual</option>';
        FMC_MATERIAL_SETTINGS.forEach((setting) => {
            // Filter by subsystem if specified
            if (subsystemId !== null && setting.subsystem_id !== null && setting.subsystem_id !== subsystemId) {
                return; // Skip this material
            }
            const safeName = String(setting.material_name ?? '');
            const safeUnit = String(setting.default_unit ?? 'ea');
            const safeCost = Number(setting.default_unit_cost ?? 0).toFixed(2);
            html += `<option value="${safeName}" data-unit="${safeUnit}" data-cost="${safeCost}">${safeName}</option>`;
        });
        return html;
    }

    function buildFindingPresetOptions() {
        let html = '<option value="">Custom / Manual</option>';
        FINDING_TEMPLATE_SETTINGS.forEach((setting) => {
            const question = String(setting.task_question ?? '');
            const category = String(setting.category ?? '');
            const included = setting.default_included ? '1' : '0';
            const notes = String(setting.default_notes ?? '');
            html += `<option value="${question}" data-category="${category}" data-included="${included}" data-notes="${notes}">${question}</option>`;
        });
        return html;
    }

    // ===== CONFIGURATION =====
    const labourRateInput = document.querySelector('input[name="labour_hourly_rate"]');
    const visitsPerYearInput = document.getElementById('bdcVisitsPerYear');
    const hoursPerVisitInput = document.getElementById('hoursPerVisit');
    const infrastructurePercentage = {{ (float)($bdcSettings['infrastructure_percentage'] ?? 0.30) }};
    const administrationPercentage = {{ (float)($bdcSettings['administration_percentage'] ?? 0.12) }};

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
    // Findings are pre-populated from Phase 1 issues — no add/remove needed.

    // Update finding labour cost in real-time
    document.getElementById('findingsContainer')?.addEventListener('input', function(e) {
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

    visitsPerYearInput?.addEventListener('input', function() {
        updateCalculationSummary();
    });

    hoursPerVisitInput?.addEventListener('input', function() {
        updateCalculationSummary();
    });

    function updateFindingSummary() {
        const rows = document.querySelectorAll('.finding-row');
        let totalLabour = 0;
        rows.forEach(row => {
            const hours = parseFloat(row.querySelector('.finding-labour-hours')?.value) || 0;
            totalLabour += hours;
        });
        const totalFRLC = totalLabour * getLabourRate();
        const labourEl  = document.getElementById('totalLabourHours');
        const frlcEl    = document.getElementById('totalFRLC');
        if (labourEl) labourEl.textContent = totalLabour.toFixed(1);
        if (frlcEl)   frlcEl.textContent   = '$' + totalFRLC.toFixed(2);
        updateCalculationSummary();
    }
    
    // ===== PER-FINDING MATERIALS MANAGEMENT =====
    const PROPERTY_ID = {{ $property->id }};

    // Counter: how many material rows have been added per finding (so new rows get unique names)
    // Initialize from any pre-populated rows
    const materialCounters = {};
    document.querySelectorAll('.finding-materials-container').forEach(container => {
        const fi = container.dataset.fi;
        materialCounters[fi] = container.querySelectorAll('.material-row').length;
    });

    // Delegated click: Add / Remove material per finding
    document.getElementById('findingsContainer')?.addEventListener('click', function(e) {
        // Add
        const addBtn = e.target.closest('.add-finding-material');
        if (addBtn) {
            const fi = addBtn.dataset.fi;
            const findingRow = addBtn.closest('.finding-row');
            const subsystemId = findingRow ? parseInt(findingRow.dataset.subsystemId, 10) || null : null;
            if (materialCounters[fi] === undefined) materialCounters[fi] = 0;
            const mi = materialCounters[fi]++;
            const container = document.getElementById(`materials-fi-${fi}`);
            container.querySelector('.no-materials-msg')?.remove();
            container.insertAdjacentHTML('beforeend', createFindingMaterialRow(fi, mi, subsystemId));
            updateMaterialsSummary();
            return;
        }
        // Remove
        const removeBtn = e.target.closest('.remove-finding-material');
        if (removeBtn) {
            const matRow = removeBtn.closest('.material-row');
            const container = matRow.closest('.finding-materials-container');
            matRow.remove();
            if (!container.querySelector('.material-row')) {
                container.insertAdjacentHTML('beforeend',
                    '<p class="text-muted small mb-1 no-materials-msg">No materials yet. Click "Add Material" to attach parts/supplies to this finding.</p>');
            }
            updateMaterialsSummary();
        }
    });

    // Delegated input: recalculate line total
    document.getElementById('findingsContainer')?.addEventListener('input', function(e) {
        if (e.target.matches('.material-quantity, .material-unit-cost')) {
            const row = e.target.closest('.material-row');
            const qty  = parseFloat(row.querySelector('.material-quantity').value) || 0;
            const cost = parseFloat(row.querySelector('.material-unit-cost').value) || 0;
            const total = qty * cost;
            row.querySelector('.material-line-total').value = '$' + total.toFixed(2);
            row.querySelector('.material-line-total-hidden').value = total.toFixed(2);
            updateMaterialsSummary();
        }
    });

    // Delegated change: material preset auto-fill
    document.getElementById('findingsContainer')?.addEventListener('change', function(e) {
        if (e.target.classList.contains('material-template')) {
            const select = e.target;
            const row = select.closest('.material-row');
            const opt  = select.options[select.selectedIndex];
            if (opt && opt.value) {
                const nameInput = row.querySelector('input[name$="[material_name]"]');
                const unitSelect = row.querySelector('select[name$="[unit]"]');
                const costInput  = row.querySelector('input[name$="[unit_cost]"]');
                if (nameInput) nameInput.value = opt.value;
                if (unitSelect) {
                    const selectedUnit = opt.dataset.unit || 'ea';
                    if (![...unitSelect.options].some(o => o.value === selectedUnit)) {
                        unitSelect.insertAdjacentHTML('beforeend', `<option value="${selectedUnit}">${selectedUnit}</option>`);
                    }
                    unitSelect.value = selectedUnit;
                }
                if (costInput) {
                    costInput.value = parseFloat(opt.dataset.cost || '0').toFixed(2);
                    costInput.dispatchEvent(new Event('input', { bubbles: true }));
                }
            }
        }
    });

    function createFindingMaterialRow(fi, mi, subsystemId = null) {
        return `<div class="material-row border rounded p-2 mb-1 bg-white">
            <div class="d-flex justify-content-end mb-1">
                <button type="button" class="btn btn-sm btn-outline-danger remove-finding-material py-0 px-1">
                    <i class="mdi mdi-delete-outline"></i> Remove
                </button>
            </div>
            <div class="row g-2">
                <div class="col-md-1">
                    <label class="mat-label">Preset</label>
                    <select class="form-select form-select-sm material-template">
                        ${buildMaterialPresetOptions(subsystemId)}
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="mat-label">Description <span class="text-danger">*</span></label>
                    <input type="text" name="findings[${fi}][materials][${mi}][material_name]" class="form-control form-control-sm" required>
                </div>
                <div class="col-md-1">
                    <label class="mat-label">Qty</label>
                    <input type="number" name="findings[${fi}][materials][${mi}][quantity]" class="form-control form-control-sm material-quantity" min="0" step="0.01" value="1" required>
                </div>
                <div class="col-md-2">
                    <label class="mat-label">Unit</label>
                    <select name="findings[${fi}][materials][${mi}][unit]" class="form-select form-select-sm" required>
                        ${buildOptions(MATERIAL_UNITS)}
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="mat-label">Unit Cost ($)</label>
                    <input type="number" name="findings[${fi}][materials][${mi}][unit_cost]" class="form-control form-control-sm material-unit-cost" min="0" step="0.01" value="0" required>
                </div>
                <div class="col-md-2">
                    <label class="mat-label">Line Total</label>
                    <input type="text" class="form-control form-control-sm material-line-total" readonly value="$0.00">
                    <input type="hidden" name="findings[${fi}][materials][${mi}][line_total]" class="material-line-total-hidden" value="0">
                </div>
                <div class="col-md-1">
                    <label class="mat-label">Notes</label>
                    <input type="text" name="findings[${fi}][materials][${mi}][notes]" class="form-control form-control-sm">
                </div>
            </div>
            <input type="hidden" name="findings[${fi}][materials][${mi}][property_id]" value="${PROPERTY_ID}">
        </div>`;
    }

    function updateMaterialsSummary() {
        let totalQty  = 0;
        let totalCost = 0;
        document.querySelectorAll('.material-row').forEach(row => {
            totalQty  += parseFloat(row.querySelector('.material-quantity')?.value) || 0;
            totalCost += parseFloat(row.querySelector('.material-line-total-hidden')?.value) || 0;
        });
        const itemsEl = document.getElementById('totalItemsQty');
        const fmcEl   = document.getElementById('totalFMC');
        if (itemsEl) itemsEl.textContent = totalQty.toFixed(0);
        if (fmcEl)   fmcEl.textContent   = '$' + totalCost.toFixed(2);
        updateCalculationSummary();
    }
    
    // ===== CALCULATION SUMMARY =====
    function updateCalculationSummary() {
        // Get FRLC from findings
        const frlc = parseFloat(document.getElementById('totalFRLC').textContent.replace(/[$,]/g, '')) || 0;
        
        // Get FMC from materials
        const fmc = parseFloat(document.getElementById('totalFMC').textContent.replace(/[$,]/g, '')) || 0;
        
        const visitsPerYear = parseFloat(visitsPerYearInput?.value) || 0;
        const hoursPerVisit = parseFloat(hoursPerVisitInput?.value) || 0;
        const labourRate = getLabourRate();

        const labourHoursPerYear = visitsPerYear * hoursPerVisit;
        const labourCostPerYear = labourHoursPerYear * labourRate;
        const infrastructureCost = labourCostPerYear * infrastructurePercentage;
        const administrationCost = labourCostPerYear * administrationPercentage;
        const bdcAnnual = labourCostPerYear + infrastructureCost + administrationCost;
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
        const previewFinalMonthly = arpMonthly;
        
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
        document.getElementById('previewFinalMonthly').textContent = '$' + previewFinalMonthly.toFixed(2);
    }
    
    // ===== INITIALIZATION =====
    updateFindingRowLabourCosts();
    updateFindingSummary();
    updateMaterialsSummary();
    updateCalculationSummary();
});
</script>
@endsection
