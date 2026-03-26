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

            @php
                $sevColors2 = ['critical'=>'#dc3545','high'=>'#fd7e14','noi_protection'=>'#7c3aed','medium'=>'#ffc107','low'=>'#198754'];
                $sevLabels2 = ['critical'=>'Safety & Health','high'=>'Urgency','noi_protection'=>'NOI Protection','medium'=>'Value Depreciation','low'=>'Non-Urgent'];
                $prioMap2   = ['critical'=>1,'high'=>1,'noi_protection'=>2,'medium'=>2,'low'=>3];
                $loadedRate = (float)($bdcSettings['loaded_hourly_rate'] ?? 165);
                $totalLabourHrs2 = 0; $totalFRLC2 = 0; $totalMatCost2 = 0; $totalMatItems2 = 0;
                $sevCount2 = ['critical'=>0,'high'=>0,'noi_protection'=>0,'medium'=>0,'low'=>0];
                foreach ($sortedFindings as $sf2) {
                    $sv2 = $sf2['severity'] ?? 'low';
                    if (isset($sevCount2[$sv2])) $sevCount2[$sv2]++; else $sevCount2['low']++;
                    $hrs2 = (float)($sf2['phar_labour_hours'] ?? 0);
                    $totalLabourHrs2 += $hrs2;
                    $totalFRLC2 += $hrs2 * $loadedRate;
                    foreach (($sf2['phar_materials'] ?? []) as $m2) {
                        $totalMatItems2++;
                        $totalMatCost2 += (float)($m2['line_total'] ?? 0);
                    }
                }
            @endphp

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

            {{-- ── Findings Stats Panel ──────────────────────────────────── --}}
            <div class="mb-3 rounded" style="background:#1e293b;color:#fff;padding:1rem 1.5rem;">
                <div class="row text-center mb-3 pb-3" style="border-bottom:1px solid rgba(255,255,255,.15);">
                    <div class="col">
                        <div class="small fw-semibold mb-1" style="color:#fc8181;">&#x1F534; Safety &amp; Health</div>
                        <div class="fw-bold" style="font-size:1.75rem;color:#fc8181;line-height:1.1;">{{ $sevCount2['critical'] }}</div>
                    </div>
                    <div class="col">
                        <div class="small fw-semibold mb-1" style="color:#fbd38d;">&#x1F7E0; Urgency</div>
                        <div class="fw-bold" style="font-size:1.75rem;color:#fbd38d;line-height:1.1;">{{ $sevCount2['high'] }}</div>
                    </div>
                    <div class="col">
                        <div class="small fw-semibold mb-1" style="color:#d6bcfa;">&#x1F7E3; NOI Protection</div>
                        <div class="fw-bold" style="font-size:1.75rem;color:#d6bcfa;line-height:1.1;">{{ $sevCount2['noi_protection'] }}</div>
                    </div>
                    <div class="col">
                        <div class="small fw-semibold mb-1" style="color:#fef08a;">&#x1F7E1; Value Depreciation</div>
                        <div class="fw-bold" style="font-size:1.75rem;color:#fef08a;line-height:1.1;">{{ $sevCount2['medium'] }}</div>
                    </div>
                    <div class="col">
                        <div class="small fw-semibold mb-1" style="color:#86efac;">&#x1F7E2; Non-Urgent</div>
                        <div class="fw-bold" style="font-size:1.75rem;color:#86efac;line-height:1.1;">{{ $sevCount2['low'] }}</div>
                    </div>
                </div>
                <div class="row text-center mb-2 pb-2" style="border-bottom:1px solid rgba(255,255,255,.12);">
                    <div class="col-md-4">
                        <div class="small fw-semibold mb-1" style="color:#cbd5e1;">TOTAL FINDINGS</div>
                        <div class="fw-bold" style="font-size:1.75rem;color:#93c5fd;line-height:1.1;">{{ count($sortedFindings) }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="small fw-semibold mb-1" style="color:#cbd5e1;">TOTAL LABOUR HOURS</div>
                        <div class="fw-bold" style="font-size:1.75rem;color:#67e8f9;line-height:1.1;">{{ number_format($totalLabourHrs2, 1) }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="small fw-semibold mb-1" style="color:#cbd5e1;">TOTAL FR LABOUR COST (FRLC)</div>
                        <div class="fw-bold" style="font-size:1.75rem;color:#fcd34d;line-height:1.1;">${{ number_format($totalFRLC2, 2) }}</div>
                    </div>
                </div>
                <div class="row text-center pt-1">
                    <div class="col-md-6">
                        <div class="small fw-semibold mb-1" style="color:#cbd5e1;">TOTAL MATERIAL ITEMS</div>
                        <div class="fw-bold" style="font-size:1.75rem;color:#67e8f9;line-height:1.1;">{{ $totalMatItems2 }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="small fw-semibold mb-1" style="color:#cbd5e1;">TOTAL MATERIAL COST (FMC)</div>
                        <div class="fw-bold" style="font-size:1.75rem;color:#4ade80;line-height:1.1;">${{ number_format($totalMatCost2, 2) }}</div>
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
                                                   value="{{ old('bdc_visits_per_year', $inspection->bdc_visits_per_year ?? '') }}"
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

                        <!-- SECTION 2: Findings Table – read-only preview from Step 1 -->
                        <div class="card mb-4" style="border:1px solid #dee2e6;box-shadow:0 2px 8px rgba(0,0,0,.06);">
                            <div class="card-header d-flex justify-content-between align-items-center" style="background:#2d3a5e;color:white;">
                                <h5 class="mb-0"><i class="mdi mdi-clipboard-list-outline me-2"></i>Findings Summary <small class="opacity-75 fw-normal">(from Step 1)</small></h5>
                                <span class="badge bg-light text-dark">{{ count($sortedFindings) }} finding(s)</span>
                            </div>
                            <div class="card-body p-0">
                                @if(count($sortedFindings) === 0)
                                    <div class="alert alert-warning m-3">
                                        <i class="mdi mdi-information me-2"></i>
                                        No findings recorded in Step 1. <a href="{{ route('inspections.create', ['property_id' => $property->id]) }}">Go back and add findings</a>.
                                    </div>
                                @else
                                    {{-- Hidden carry-over fields so storePharData still runs correctly --}}
                                    @foreach($sortedFindings as $fi => $finding)
                                        @php $prio2 = $finding['priority'] ?? ($prioMap2[$finding['severity'] ?? 'low'] ?? 3); @endphp
                                        <input type="hidden" name="findings[{{ $fi }}][task_question]" value="{{ $finding['issue'] ?? '' }}">
                                        <input type="hidden" name="findings[{{ $fi }}][property_id]" value="{{ $property->id }}">
                                        <input type="hidden" name="findings[{{ $fi }}][priority]" value="{{ $prio2 }}">
                                    @endforeach

                                    <div class="table-responsive">
                                        <table class="table table-bordered table-hover mb-0 align-middle" style="font-size:.875rem;">
                                            <thead style="background:#f1f5f9;">
                                                <tr>
                                                    <th class="text-center" style="width:40px;">#</th>
                                                    <th style="width:90px;">Severity</th>
                                                    <th>System</th>
                                                    <th>Subsystem</th>
                                                    <th>Issue / Finding</th>
                                                    <th>Risk / Impact</th>
                                                    <th>Recommendations &amp; Notes</th>
                                                    <th class="text-end" style="width:130px;">Est. Labour Cost</th>
                                                    <th>Materials &amp; Cost</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($sortedFindings as $fi => $finding)
                                                    @php
                                                        $sev2      = $finding['severity'] ?? 'low';
                                                        $color2    = $sevColors2[$sev2] ?? '#6c757d';
                                                        $label2    = $sevLabels2[$sev2] ?? ucfirst($sev2);
                                                        $hrs2f     = (float)($finding['phar_labour_hours'] ?? 0);
                                                        $lc2       = $hrs2f * $loadedRate;
                                                        $mats2     = $finding['phar_materials'] ?? [];
                                                        $matTotal2 = array_sum(array_column($mats2, 'line_total'));
                                                        $recs2     = is_array($finding['recommendations'] ?? null)
                                                                        ? $finding['recommendations']
                                                                        : array_filter(array_map('trim', explode('|', (string)($finding['recommendations'] ?? ''))));
                                                    @endphp
                                                    <tr style="border-left:4px solid {{ $color2 }};">
                                                        <td class="text-center text-muted fw-semibold">{{ $fi + 1 }}</td>
                                                        <td>
                                                            <span class="badge" style="background:{{ $color2 }};{{ $sev2==='medium'?'color:#212529;':'' }}font-size:.72rem;">{{ $label2 }}</span>
                                                        </td>
                                                        <td class="fw-semibold">{{ $finding['system'] ?? '—' }}</td>
                                                        <td class="text-muted">{{ $finding['subsystem'] ?? '—' }}</td>
                                                        <td>
                                                            <div class="fw-semibold">{{ $finding['issue'] ?? '—' }}</div>
                                                            @if(!empty($finding['location']) || !empty($finding['spot']))
                                                                <div class="text-muted" style="font-size:.78rem;">
                                                                    {{ $finding['location'] ?? '' }}{{ !empty($finding['location']) && !empty($finding['spot']) ? ' · ' : '' }}{{ $finding['spot'] ?? '' }}
                                                                </div>
                                                            @endif
                                                        </td>
                                                        <td class="text-muted" style="font-size:.82rem;">{{ $finding['risk_impact'] ?? '—' }}</td>
                                                        <td style="font-size:.82rem;">
                                                            @if(!empty($recs2))
                                                                <ul class="mb-0 ps-3">
                                                                    @foreach($recs2 as $rec2)
                                                                        <li>{{ $rec2 }}</li>
                                                                    @endforeach
                                                                </ul>
                                                            @endif
                                                            @if(!empty($finding['phar_notes']))
                                                                <div class="text-muted mt-1" style="font-size:.8rem;border-top:{{ !empty($recs2) ? '1px dashed #dee2e6;padding-top:.25rem;' : '' }}">
                                                                    <span class="fw-semibold text-secondary" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.04em;">Note:</span> {{ $finding['phar_notes'] }}
                                                                </div>
                                                            @endif
                                                            @if(empty($recs2) && empty($finding['phar_notes']))
                                                                <span class="text-muted fst-italic">—</span>
                                                            @endif
                                                        </td>
                                                        <td class="text-end fw-semibold {{ $lc2 > 0 ? 'text-success' : 'text-muted' }}">
                                                            ${{ number_format($lc2, 2) }}
                                                            @if($hrs2f > 0)
                                                                <div class="text-muted fw-normal" style="font-size:.73rem;">{{ number_format($hrs2f, 1) }} hrs</div>
                                                            @endif
                                                        </td>
                                                        <td style="font-size:.8rem;">
                                                            @if(!empty($mats2))
                                                                <ul class="mb-0 ps-3">
                                                                    @foreach($mats2 as $mat2)
                                                                        <li>{{ $mat2['material_name'] ?? '—' }} &times;{{ $mat2['quantity'] ?? 1 }} {{ $mat2['unit'] ?? '' }}</li>
                                                                    @endforeach
                                                                </ul>
                                                                <div class="fw-semibold text-success mt-1" style="font-size:.8rem;border-top:1px dashed #dee2e6;padding-top:.25rem;">
                                                                    Total: ${{ number_format($matTotal2, 2) }}
                                                                </div>
                                                            @else
                                                                <span class="text-muted fst-italic">None</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                            <tfoot style="background:#f8fafc;">
                                                <tr>
                                                    <td colspan="6" class="text-end fw-bold" style="font-size:.9rem;">Totals</td>
                                                    <td class="text-end fw-bold text-success" style="font-size:.95rem;">${{ number_format($totalFRLC2, 2) }}</td>
                                                    <td class="fw-bold text-success" style="font-size:.9rem;">{{ $totalMatItems2 }} item(s) &mdash; ${{ number_format($totalMatCost2, 2) }}</td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                @endif
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
                            <div class="card-header text-white d-flex justify-content-between align-items-center" style="background:linear-gradient(135deg,#5b67ca,#3d4ba8);cursor:pointer;" onclick="var el=document.getElementById('conditionIndicesBody');var vis=el.style.display!=='none';el.style.display=vis?'none':'';this.querySelector('.ci-chevron').className='ci-chevron mdi '+(vis?'mdi-chevron-right':'mdi-chevron-down')+' me-1';">
                                <h5 class="mb-0"><i class="mdi mdi-chart-donut me-2"></i>Condition Indices</h5>
                                <div class="d-flex align-items-center gap-3">
                                    <small class="opacity-75">Calculated from Step 1 inspection findings</small>
                                    <i class="ci-chevron mdi mdi-chevron-right me-1" style="font-size:1.25rem;"></i>
                                </div>
                            </div>
                            <div id="conditionIndicesBody" class="card-body" style="display:none;">

                                {{-- Three index cards --}}
                                <div class="row g-2 mb-3">
                                    {{-- CPI --}}
                                    <div class="col-md-4">
                                        <div class="px-3 py-2 rounded text-center h-100" style="background:#f0f4ff;border:1px solid {{ $cpiColor }};">
                                            <div class="text-muted fw-semibold text-uppercase mb-1" style="font-size:.68rem;letter-spacing:.05em;">CPI — Composite Property Index</div>
                                            @if($cpi !== null)
                                                <div class="fw-bold" style="font-size:1.75rem;color:{{ $cpiColor }};line-height:1.1;">{{ number_format($cpi, 1) }}</div>
                                                <span class="badge mt-1 px-2 py-1" style="background:{{ $cpiColor }};font-size:.75rem;">{{ $cpiRat }}</span>
                                            @else
                                                <div class="text-muted my-1" style="font-size:1.5rem;">—</div>
                                                <small class="text-muted">Complete Step 1 to compute</small>
                                            @endif
                                            <div class="text-muted mt-1" style="font-size:.68rem;">Σ(SystemScore × Weight) / 197</div>
                                        </div>
                                    </div>

                                    {{-- TUS --}}
                                    <div class="col-md-4">
                                        <div class="px-3 py-2 rounded text-center h-100" style="background:#f0fff4;border:1px solid {{ $tusColor }};">
                                            <div class="text-muted fw-semibold text-uppercase mb-1" style="font-size:.68rem;letter-spacing:.05em;">TUS — Tenant Underwriting Score</div>
                                            <div class="fw-bold" style="font-size:1.75rem;color:{{ $tusColor }};line-height:1.1;">{{ number_format($tus, 1) }}</div>
                                            <span class="badge mt-1 px-2 py-1" style="background:{{ $tusColor }};font-size:.75rem;">
                                                @if($tus >= 80) Low Risk
                                                @elseif($tus >= 60) Moderate Risk
                                                @elseif($tus >= 40) Elevated Risk
                                                @else High Risk
                                                @endif
                                            </span>
                                            <div class="text-muted mt-1" style="font-size:.68rem;">Input by inspector (0–100 scale)</div>
                                        </div>
                                    </div>

                                    {{-- ASI --}}
                                    <div class="col-md-4">
                                        <div class="px-3 py-2 rounded text-center h-100" style="background:#fff8f0;border:1px solid {{ $asiColor }};">
                                            <div class="text-muted fw-semibold text-uppercase mb-1" style="font-size:.68rem;letter-spacing:.05em;">ASI — Asset Stability Index</div>
                                            @if($asi !== null)
                                                <div class="fw-bold" style="font-size:1.75rem;color:{{ $asiColor }};line-height:1.1;">{{ number_format($asi, 1) }}</div>
                                                <span class="badge mt-1 px-2 py-1" style="background:{{ $asiColor }};font-size:.75rem;">{{ $asiRat }}</span>
                                            @else
                                                <div class="text-muted my-1" style="font-size:1.5rem;">—</div>
                                                <small class="text-muted">Computed after Step 1</small>
                                            @endif
                                            <div class="text-muted mt-1" style="font-size:.68rem;">CPI × 60% + TUS × 40%</div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Per-system score breakdown (collapsible) --}}
                                @if(!empty($syScores))
                                <div>
                                    <button type="button" class="btn btn-sm btn-outline-secondary mb-2" id="sysBreakdownToggle"
                                            onclick="var el=document.getElementById('sysBreakdownTable');var vis=el.style.display!=='none';el.style.display=vis?'none':'';this.innerHTML=vis?'<i class=\'mdi mdi-chevron-right me-1\'></i>Show System Score Breakdown':'<i class=\'mdi mdi-chevron-down me-1\'></i>Hide System Score Breakdown';">
                                        <i class="mdi mdi-chevron-right me-1"></i>Show System Score Breakdown
                                    </button>
                                    <div id="sysBreakdownTable" style="display:none;">
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

                                {{-- ARP preview row --}}
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <div class="p-3 rounded text-center" style="background:#fff8e1;border:2px solid #ffc107;">
                                            <div class="text-muted small fw-semibold">ARP Monthly</div>
                                            <div id="arpMonthly" class="fw-bold" style="font-size:1.8rem;color:#856404;">$0.00/month</div>
                                            <div class="text-muted" style="font-size:.75rem;">= TRC / 12</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="alert alert-info mb-0">
                                    <i class="mdi mdi-information me-2"></i>
                                    <strong>Preview only.</strong> Click <strong>Save &amp; Preview Pricing</strong> to run the PHAR engine and lock in the ARP, then <strong>Complete Assessment</strong> when ready.
                                </div>
                            </div>
                        </div>

                        {{-- ── Panel C: Final Calculated PHAR Dashboard (post-submission) ─ --}}
                        @if(($inspection->bdc_annual ?? 0) > 0)
                        @php
                            $calcUnits = (int)($inspection->units_for_calculation ?? 1);
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

                                {{-- ARP Monthly --}}
                                <h6 class="text-muted fw-bold text-uppercase mb-2 border-bottom pb-1" style="font-size:.75rem;letter-spacing:.08em;">ARP Monthly</h6>
                                <div class="row g-3 mb-3">
                                    <div class="col-12">
                                        <div class="p-4 rounded text-center text-white d-flex flex-column justify-content-center" style="background:linear-gradient(135deg,#198754,#146c43);border:3px solid #0f5132;">
                                            <div class="fw-semibold opacity-75 small text-uppercase" style="letter-spacing:.05em;">ARP Monthly</div>
                                            <div class="fw-bold" style="font-size:2.4rem;line-height:1.1;">${{ number_format($inspection->arp_monthly ?? 0, 2) }}</div>
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
                                        <div class="text-center px-3">
                                            <div style="color:#fbbf24;font-weight:900;">ARP</div>
                                            <div class="fw-bold" style="color:#fbbf24;font-size:1.1rem;">${{ number_format($inspection->arp_monthly ?? 0, 0) }}/mo</div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>

                        @else
                        @endif

                        <!-- Form Actions -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <p class="text-muted small mb-3">
                                    <i class="mdi mdi-information-outline me-1"></i>
                                    <strong>Save Draft &amp; Back</strong> returns you to Step 1 to review findings.
                                    <strong>Save &amp; Preview</strong> calculates pricing so you can review it — the assessment stays <em>in progress</em>.
                                    Once you are satisfied, click <strong>Complete Assessment</strong> to lock it in.
                                </p>
                                <div class="d-flex justify-content-between align-items-center gap-2">
                                    <button type="submit" name="action" value="save_draft_back" class="btn btn-secondary">
                                        <i class="mdi mdi-content-save me-1"></i>Save Draft &amp; Back to Step 1
                                    </button>
                                    <button type="submit" name="action" value="save_preview" class="btn btn-primary btn-lg">
                                        <i class="mdi mdi-calculator me-1"></i>Save &amp; Preview Pricing
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>

                    {{-- Complete Assessment — separate POST, only shown once pricing has been calculated --}}
                    @if(($inspection->bdc_annual ?? 0) > 0 && $inspection->status !== 'completed')
                    <div class="card border-success mb-4">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <strong class="text-success"><i class="mdi mdi-check-decagram me-1"></i>Ready to Complete?</strong>
                                <p class="text-muted small mb-0">Pricing has been calculated. Click the button to mark this assessment as complete.</p>
                            </div>
                            <form method="POST" action="{{ route('inspections.complete-assessment', $inspection->id) }}">
                                @csrf
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="mdi mdi-flag-checkered me-1"></i>Complete Assessment
                                </button>
                            </form>
                        </div>
                    </div>
                    @endif
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

    // ===== CONFIGURATION =====
    const labourRateInput = document.querySelector('input[name="labour_hourly_rate"]');
    const visitsPerYearInput = document.getElementById('bdcVisitsPerYear');
    const hoursPerVisitInput = document.getElementById('hoursPerVisit');
    const infrastructurePercentage = {{ (float)($bdcSettings['infrastructure_percentage'] ?? 0.30) }};
    const administrationPercentage = {{ (float)($bdcSettings['administration_percentage'] ?? 0.12) }};

    // Server-side pre-computed totals from Step 1 findings
    const SERVER_FRLC = {{ $totalFRLC2 }};
    const SERVER_FMC  = {{ $totalMatCost2 }};

    function getLabourRate() {
        return parseFloat(labourRateInput?.value) || 165;
    }

    labourRateInput?.addEventListener('input', function() {
        updateCalculationSummary();
    });

    visitsPerYearInput?.addEventListener('input', function() {
        updateCalculationSummary();
    });

    hoursPerVisitInput?.addEventListener('input', function() {
        updateCalculationSummary();
    });


    // ===== CALCULATION SUMMARY =====
    function updateCalculationSummary() {
        // Use server-computed totals from Step 1
        const frlc = SERVER_FRLC;
        const fmc  = SERVER_FMC;
        
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
    }
    
    // ===== INITIALIZATION =====
    updateCalculationSummary();
});
</script>
@endsection
