@extends('admin.layout')

@section('title', 'Inspection Report & Pricing Breakdown')

@section('content')
<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="card-title mb-0">
                            <i class="mdi mdi-clipboard-check-outline me-2 text-success"></i>
                            Inspection Report & Pricing Breakdown
                        </h4>
                        <p class="text-muted small mb-0">Complete breakdown of inspection findings and pricing calculation</p>
                    </div>
                    <div>
                        <a href="{{ route('inspections.index') }}" class="btn btn-light btn-sm no-print">
                            <i class="mdi mdi-arrow-left me-1"></i>Back to Inspections
                        </a>
                        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm no-print">
                            <i class="mdi mdi-printer me-1"></i>Print Report
                        </button>
                        @if($inspection->status === 'completed')
                        <a href="{{ route('inspections.download-invoice', $inspection->id) }}" class="btn btn-success btn-sm no-print">
                            <i class="mdi mdi-download me-1"></i>Download Full Report PDF
                        </a>
                        @endif
                    </div>
                </div>

                @if($inspection->bdc_annual === null || $inspection->bdc_annual == 0)
                <div class="alert alert-warning alert-dismissible fade show">
                    <i class="mdi mdi-alert-circle me-2"></i>
                    <strong>Calculations are missing!</strong> This inspection may have been created before the pricing calculation system was implemented.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                @endif

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="mdi mdi-check-circle me-2"></i>{{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

            <!-- Property & Inspection Summary -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="mdi mdi-home-outline me-2"></i>Property & Inspection Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <th width="40%">Property Name:</th>
                                    <td><strong>{{ $inspection->property?->property_name ?? 'N/A' }}</strong></td>
                                </tr>
                                <tr>
                                    <th>Property Code:</th>
                                    <td>{{ $inspection->property?->property_code ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Property Type:</th>
                                    <td class="text-capitalize">{{ $inspection->property?->type ? str_replace('_', ' ', $inspection->property->type) : 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Units:</th>
                                    <td>{{ $inspection->property?->residential_units ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Owner Name:</th>
                                    <td><strong>{{ $inspection->owner_name ?? $inspection->property?->user?->name ?? 'N/A' }}</strong></td>
                                </tr>
                                <tr>
                                    <th>Owner Phone:</th>
                                    <td>{{ $inspection->owner_phone ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Owner Email:</th>
                                    <td>{{ $inspection->owner_email ?? 'N/A' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <th width="40%">Inspector:</th>
                                    <td>{{ $inspection->inspector?->name ?? 'Not Assigned' }}</td>
                                </tr>
                                <tr>
                                    <th>Inspection Date:</th>
                                    <td>{{ $inspection->scheduled_date?->format('M d, Y') ?? 'Not Scheduled' }}</td>
                                </tr>
                                <tr>
                                    <th>Completed Date:</th>
                                    <td>{{ $inspection->completed_date?->format('M d, Y h:i A') ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Status:</th>
                                    <td>
                                        <span class="badge 
                                            @if($inspection->status === 'completed') bg-success
                                            @elseif($inspection->status === 'in_progress') bg-warning
                                            @else bg-secondary
                                            @endif">
                                            {{ ucfirst($inspection->status) }}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Findings Summary — grouped by severity -->
            @php
                $inlineFindingsRaw = is_array($inspection->findings)
                    ? $inspection->findings
                    : (json_decode($inspection->getRawOriginal('findings') ?? '[]', true) ?? []);

                $severityOrder = ['critical','high','noi_protection','medium','low'];
                $severityMeta  = [
                    'critical'       => ['label' => 'Urgent — Safety Critical',  'color' => '#dc3545', 'icon' => '🔴'],
                    'high'           => ['label' => 'Health & Safety Risk',       'color' => '#fd7e14', 'icon' => '🟠'],
                    'noi_protection' => ['label' => 'NOI Protection',             'color' => '#6f42c1', 'icon' => '🟣'],
                    'medium'         => ['label' => 'Value Depreciation',         'color' => '#d4a017', 'icon' => '🟡'],
                    'low'            => ['label' => 'Non-Urgent',                 'color' => '#198754', 'icon' => '🟢'],
                ];
                $groupedFindings = collect($inlineFindingsRaw)->groupBy('severity');
                $totalLabourHrs  = collect($inlineFindingsRaw)->sum('phar_labour_hours');
                $totalMatCost    = collect($inlineFindingsRaw)->sum(fn($f) =>
                    collect($f['phar_materials'] ?? [])->sum(fn($m) => (float)($m['line_total'] ?? 0))
                );
            @endphp
            @if(count($inlineFindingsRaw) > 0)
            <div class="card mb-4 findings-card">
                <div class="card-header" style="background:#ff9800;color:white;">
                    <h5 class="mb-0">
                        <i class="mdi mdi-clipboard-text me-2"></i>Findings Summary ({{ count($inlineFindingsRaw) }} items)
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0" id="findingsTable">
                            <thead class="table-dark">
                                <tr>
                                    <th width="3%">#</th>
                                    <th width="30%">Issue / Task</th>
                                    <th width="12%">Category</th>
                                    <th width="8%" class="text-end">Labour Hrs</th>
                                    <th width="16%">Material Used</th>
                                    <th width="10%" class="text-center">Qty &amp; Unit</th>
                                    <th width="9%" class="text-end">Unit Cost</th>
                                    <th width="12%" class="text-end">Material Cost</th>
                                </tr>
                            </thead>
                            <tbody>
                            @php $rowNum = 0; @endphp
                            @foreach($severityOrder as $sev)
                                @if($groupedFindings->has($sev))
                                @php $meta = $severityMeta[$sev]; @endphp
                                <tr>
                                    <td colspan="8" class="fw-bold text-white py-2 px-3" style="background:{{ $meta['color'] }};">
                                        {{ $meta['icon'] }} {{ $meta['label'] }}
                                        <span class="badge bg-white ms-2" style="color:{{ $meta['color'] }};">
                                            {{ $groupedFindings[$sev]->count() }}
                                        </span>
                                    </td>
                                </tr>
                                @foreach($groupedFindings[$sev] as $finding)
                                @php
                                    $rowNum++;
                                    $pharMaterials = $finding['phar_materials'] ?? [];
                                    $firstMat      = $pharMaterials[0] ?? null;
                                    $extraMats     = array_slice($pharMaterials, 1);
                                    $findingMatCost = collect($pharMaterials)->sum(fn($m) => (float)($m['line_total'] ?? 0));
                                    $recommendations = $finding['recommendations'] ?? [];
                                @endphp
                                <tr>
                                    <td class="text-muted small align-top">{{ $rowNum }}</td>
                                    <td class="align-top">
                                        <strong>{{ $finding['issue'] ?? '-' }}</strong>
                                        @if(!empty($finding['system']))
                                            <br><small class="text-muted">{{ $finding['system'] }}{{ !empty($finding['subsystem']) ? ' › '.$finding['subsystem'] : '' }}</small>
                                        @endif
                                        @if(!empty($finding['location']) || !empty($finding['spot']))
                                            <div class="mt-1" style="font-size:.8rem;color:#555;">
                                                <i class="mdi mdi-map-marker-outline"></i>
                                                {{ implode(' — ', array_filter([$finding['location'] ?? null, $finding['spot'] ?? null])) }}
                                            </div>
                                        @endif
                                        @if(!empty($recommendations))
                                            <ul class="mb-0 mt-1 ps-3" style="font-size:.78rem;color:#444;">
                                                @foreach($recommendations as $rec)
                                                    <li>{{ $rec }}</li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </td>
                                    <td class="align-top">
                                        <span class="badge bg-secondary">{{ $finding['phar_category'] ?? $finding['type'] ?? 'General' }}</span>
                                    </td>
                                    <td class="text-end align-top">{{ number_format((float)($finding['phar_labour_hours'] ?? 0), 1) }} hrs</td>
                                    <td class="align-top">{{ $firstMat['material_name'] ?? '—' }}</td>
                                    <td class="text-center align-top">
                                        @if($firstMat)
                                            {{ $firstMat['quantity'] ?? '1' }} {{ $firstMat['unit'] ?? '' }}
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-end align-top">
                                        @if($firstMat && (float)($firstMat['unit_cost'] ?? 0) > 0)
                                            ${{ number_format((float)$firstMat['unit_cost'], 2) }}
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-end align-top">
                                        @if($findingMatCost > 0)
                                            <strong>${{ number_format($findingMatCost, 2) }}</strong>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                                @foreach($extraMats as $extraMat)
                                <tr class="table-light">
                                    <td></td>
                                    <td colspan="2" class="text-muted small ps-4" style="font-size:.8rem;">↳ additional material</td>
                                    <td></td>
                                    <td class="small">{{ $extraMat['material_name'] ?? '—' }}</td>
                                    <td class="text-center small">{{ $extraMat['quantity'] ?? '1' }} {{ $extraMat['unit'] ?? '' }}</td>
                                    <td class="text-end small">
                                        @if((float)($extraMat['unit_cost'] ?? 0) > 0)
                                            ${{ number_format((float)$extraMat['unit_cost'], 2) }}
                                        @endif
                                    </td>
                                    <td class="text-end small">${{ number_format((float)($extraMat['line_total'] ?? 0), 2) }}</td>
                                </tr>
                                @endforeach
                                @endforeach
                                @endif
                            @endforeach
                            </tbody>
                            <tfoot class="table-secondary fw-bold">
                                <tr>
                                    <th colspan="3" class="text-end">TOTALS:</th>
                                    <th class="text-end">{{ number_format($totalLabourHrs, 1) }} hrs</th>
                                    <th colspan="3"></th>
                                    <th class="text-end">${{ number_format($totalMatCost, 2) }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            @endif



            <!-- Pricing Breakdown -->
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="mdi mdi-calculator me-2"></i>Pricing Calculation Breakdown
                    </h5>
                </div>
                <div class="card-body">
                    
                    <!-- Step 1: Cost Components -->
                    <div class="mb-4">
                        <h6 class="text-primary border-bottom pb-2">
                            <i class="mdi mdi-numeric-1-circle me-2"></i>Cost Components (Annual)
                        </h6>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="text-muted mb-1">Base Deployment Cost (BDC)</h6>
                                        <div class="fs-4">${{ number_format($inspection->bdc_annual ?? 0, 2) }}</div>
                                        <small class="text-muted">Operational baseline for property care</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="text-muted mb-1">Findings Remediation Labour (FRLC)</h6>
                                        <div class="fs-4">${{ number_format($inspection->frlc_annual ?? 0, 2) }}</div>
                                        <small class="text-muted">{{ number_format($findings->sum('labour_hours'), 1) }} hrs @ ${{ number_format($inspection->labour_hourly_rate ?? 165, 2) }}/hr</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="text-muted mb-1">Findings Material Cost (FMC)</h6>
                                        <div class="fs-4">${{ number_format($inspection->fmc_annual ?? 0, 2) }}</div>
                                        <small class="text-muted">Materials for remediation work</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Total Remediation Cost -->
                    <div class="mb-4">
                        <h6 class="text-primary border-bottom pb-2">
                            <i class="mdi mdi-numeric-2-circle me-2"></i>Total Remediation Cost (TRC)
                        </h6>
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <h5 class="mb-1">TRC = BDC + FRLC + FMC</h5>
                                        <small>Annual: ${{ number_format($inspection->trc_annual ?? 0, 2) }}</small>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <div class="fs-3">
                                            <strong>${{ number_format($inspection->trc_monthly ?? 0, 2) }}</strong>
                                            <div class="fs-6">per month</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: ARP & Condition -->
                    <div class="mb-4">
                        <h6 class="text-primary border-bottom pb-2">
                            <i class="mdi mdi-numeric-3-circle me-2"></i>Annual Recurring Price (ARP) & Condition Assessment
                        </h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card border-primary">
                                    <div class="card-body">
                                        <h6 class="text-muted mb-1">ARP (Monthly TRC)</h6>
                                        <div class="fs-4 text-primary">${{ number_format($inspection->arp_monthly ?? 0, 2) }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border-primary">
                                    <div class="card-body">
                                        <h6 class="text-muted mb-1">CPI Score</h6>
                                        <div class="fs-4 text-primary">{{ number_format($inspection->cpi_total_score ?? 0, 1) }}/100</div>
                                        @if($inspection->cpi_rating)
                                            <span class="badge
                                                @php
                                                    $cpiR = $inspection->cpi_rating;
                                                    echo match(true) {
                                                        $cpiR === 'Excellent' => 'bg-success',
                                                        $cpiR === 'Good'      => 'bg-info text-dark',
                                                        $cpiR === 'Fair'      => 'bg-warning text-dark',
                                                        $cpiR === 'Poor'      => 'bg-orange text-white',
                                                        default               => 'bg-danger',
                                                    };
                                                @endphp
                                            ">{{ $inspection->cpi_rating }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-6">
                                <div class="card border-success">
                                    <div class="card-body">
                                        <h6 class="text-muted mb-1">ASI Score <small class="text-muted">(CPI×60% + TUS×40%)</small></h6>
                                        <div class="fs-4 text-success">{{ number_format($inspection->asi_score ?? 0, 1) }}/100</div>
                                        @if($inspection->asi_rating)
                                            <span class="badge
                                                @php
                                                    $asiR = $inspection->asi_rating;
                                                    echo match(true) {
                                                        $asiR === 'Highly stable asset'  => 'bg-success',
                                                        $asiR === 'Stable asset'          => 'bg-info text-dark',
                                                        $asiR === 'Moderate stability'    => 'bg-warning text-dark',
                                                        $asiR === 'Vulnerable stability'  => 'bg-orange text-white',
                                                        $asiR === 'Unstable asset'        => 'bg-danger',
                                                        default                           => 'bg-dark',
                                                    };
                                                @endphp
                                            ">{{ $inspection->asi_rating }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border-secondary">
                                    <div class="card-body">
                                        <h6 class="text-muted mb-1">TUS Score <small class="text-muted">(Tenant Underwriting)</small></h6>
                                        <div class="fs-4 text-secondary">{{ number_format($inspection->tus_score ?? 75, 1) }}/100</div>
                                        <small class="text-muted">Inspector-entered per inspection</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Per-Unit Breakdown (Multi-Unit Properties) -->
                    @if(($inspection->units_for_calculation ?? 1) > 1)
                    @php
                        $arpMonthlyTotal = (float)($inspection->arp_monthly ?? $inspection->trc_monthly ?? 0);
                    @endphp
                    <div class="mb-4">
                        <h6 class="text-primary border-bottom pb-2">
                            <i class="mdi mdi-home-group me-2"></i>Per-Unit Cost Breakdown ({{ $inspection->units_for_calculation }} Units)
                        </h6>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Cost Component</th>
                                        <th class="text-end">Annual Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>BDC</td>
                                        <td class="text-end">${{ number_format($inspection->bdc_annual ?? 0, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td>FRLC</td>
                                        <td class="text-end">${{ number_format($inspection->frlc_annual ?? 0, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td>FMC</td>
                                        <td class="text-end">${{ number_format($inspection->fmc_annual ?? 0, 2) }}</td>
                                    </tr>
                                    <tr class="table-secondary">
                                        <td><strong>TRC <small class="text-muted fw-normal">(BDC+FRLC+FMC)</small></strong></td>
                                        <td class="text-end"><strong>${{ number_format($inspection->trc_annual ?? 0, 2) }}</strong></td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr style="background:#198754;color:white;">
                                        <td><strong>ARP <small style="font-weight:normal;opacity:.85;">(TRC ÷ 12)</small></strong></td>
                                        <td class="text-end"><strong>${{ number_format($arpMonthlyTotal, 2) }}/mo</strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <p class="text-muted small mt-1"><strong>ARP</strong> = Annual Recurring Price = TRC ÷ 12. This is the monthly amount the client pays.</p>
                    </div>
                    @endif

                </div>
            </div>

            <!-- Inspection Photos -->
            @php $inspectionPhotos = is_array($inspection->photos) ? $inspection->photos : (json_decode($inspection->getRawOriginal('photos') ?? '[]', true) ?? []); @endphp
            @if(count($inspectionPhotos) > 0)
            <div class="card mb-4">
                <div class="card-header" style="background:#495057;color:white;">
                    <h5 class="mb-0"><i class="mdi mdi-camera me-2"></i>Inspection Photos ({{ count($inspectionPhotos) }})</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @foreach($inspectionPhotos as $photo)
                        <div class="col-6 col-md-3">
                            <a href="{{ $inspection->getStorageUrl($photo) }}" target="_blank">
                                <img src="{{ $inspection->getStorageUrl($photo) }}"
                                     alt="Inspection photo"
                                     class="img-fluid rounded border"
                                     style="width:100%;height:180px;object-fit:cover;">
                            </a>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <!-- Assessment Notes -->
            @php
                $recommendationItems = [];
                $rawRecommendations = $inspection->recommendations;

                if (is_array($rawRecommendations)) {
                    $recommendationItems = $rawRecommendations;
                } elseif (is_string($rawRecommendations) && trim($rawRecommendations) !== '') {
                    $decoded = json_decode($rawRecommendations, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $recommendationItems = $decoded;
                    } else {
                        $recommendationItems = preg_split('/\r\n|\r|\n|\|/', $rawRecommendations) ?: [];
                    }
                }

                $recommendationItems = collect($recommendationItems)
                    ->map(fn ($item) => trim((string) $item))
                    ->filter()
                    ->values();
            @endphp
            @if($inspection->summary || $inspection->recommendations || $inspection->risk_summary)
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="mdi mdi-text-box-outline me-2 text-primary"></i>Inspector Assessment
                    </h5>
                </div>
                <div class="card-body">
                    @if($inspection->summary)
                    <div class="mb-3">
                        <h6 class="text-primary">Notes:</h6>
                        <p>{{ $inspection->summary }}</p>
                    </div>
                    @endif
                    
                    @if($recommendationItems->isNotEmpty())
                    <div class="mb-3">
                        <h6 class="text-primary">Recommendations:</h6>
                        <ul class="mb-0 ps-3">
                            @foreach($recommendationItems as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                    
                    @if($inspection->risk_summary)
                    <div class="mb-3">
                        <h6 class="text-danger">Risk Summary:</h6>
                        <p>{{ $inspection->risk_summary }}</p>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .no-print, .btn, .alert, nav, .navbar, .sidebar, #sidebar { display: none !important; }
    .card { page-break-inside: avoid; box-shadow: none !important; border: 1px solid #dee2e6 !important; }
    .card-header { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    body { background: white !important; font-size: 11px; }
    .col-md-4, .col-md-6, .col-md-8 { width: 33.33% !important; float: left; }
    #findingsTable td, #findingsTable th { font-size: 10px; padding: 4px 6px; }
    .badge { -webkit-print-color-adjust: exact; print-color-adjust: exact; border: 1px solid #999; }
    h4, h5, h6 { page-break-after: avoid; }
    .findings-card { page-break-before: auto; }
    @page { margin: 15mm; size: A4 landscape; }
}
</style>
@endsection
