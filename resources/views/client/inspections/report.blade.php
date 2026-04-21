@extends($adminPreview ?? false ? 'admin.layout' : 'client.layout')

@section('title', 'Inspection Report & Pricing Breakdown')

@section('content')
@if($adminPreview ?? false)
<div class="alert alert-warning border-warning mb-3 no-print" role="alert" style="border-left:4px solid #f0ad4e;">
    <i class="mdi mdi-eye me-2"></i>
    <strong>ADMIN PREVIEW MODE</strong> — This is how the client will see their report. No client actions are active.
</div>
@endif
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
                        @if(isset($adminPreview) && $adminPreview)
                            <a href="{{ route('inspections.phar-data', $inspection->id) }}" class="btn btn-light btn-sm no-print">
                                <i class="mdi mdi-arrow-left me-1"></i>Back to PHAR Dashboard
                            </a>
                        @else
                            <a href="{{ route('client.inspections.index') }}" class="btn btn-light btn-sm no-print">
                                <i class="mdi mdi-arrow-left me-1"></i>Back to Inspections
                            </a>
                        @endif
                        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm no-print">
                            <i class="mdi mdi-printer me-1"></i>Print Report
                        </button>
                    </div>
                </div>

                @if(session('success'))
                    <div class="alert alert-success no-print">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger no-print">{{ session('error') }}</div>
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
                                    <tr>
                                        <th>Work Payment:</th>
                                        <td>
                                            @if(($inspection->work_payment_status ?? 'pending') === 'paid')
                                                <span class="badge bg-success">Paid ({{ $inspection->work_payment_cadence === 'per_visit' ? 'Per Visit' : 'In Full' }})</span>
                                            @else
                                                <span class="badge bg-warning text-dark">Pending</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Planned Start:</th>
                                        <td>{{ optional($inspection->planned_start_date)->format('M d, Y') ?? 'Pending' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Target Completion:</th>
                                        <td>{{ optional($inspection->target_completion_date)->format('M d, Y') ?? 'Pending' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Etogo Countersign:</th>
                                        <td>
                                            @if($inspection->etogo_signed_at)
                                                <span class="badge bg-success">Signed</span>
                                            @else
                                                <span class="badge bg-secondary">Awaiting</span>
                                            @endif
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
                    $indexedFindings = collect($inlineFindingsRaw)
                        ->values()
                        ->map(function ($finding, $idx) {
                            $finding['__finding_index'] = $idx;
                            return $finding;
                        });

                    $normalizePhotoPaths = function ($value) {
                        if (is_array($value)) {
                            return array_values(array_filter($value, fn($p) => is_string($p) && trim($p) !== ''));
                        }
                        if (is_string($value) && trim($value) !== '') {
                            $decoded = json_decode($value, true);
                            if (is_array($decoded)) {
                                return array_values(array_filter($decoded, fn($p) => is_string($p) && trim($p) !== ''));
                            }
                        }
                        return [];
                    };

                    $findingMatchKey = function ($issueOrTask, $category) {
                        $left = strtolower(trim((string) $issueOrTask));
                        $right = strtolower(trim((string) $category));
                        return $left . '|' . $right;
                    };

                    // Fallback photo source from relational PHAR findings in case the
                    // inline JSON entry has no finding_photos but photo_ids exist.
                    $pharPhotoFallbackByIndex = collect($findings ?? [])
                        ->values()
                        ->mapWithKeys(function ($f, $idx) use ($normalizePhotoPaths) {
                            $paths = $normalizePhotoPaths($f->photo_ids ?? []);
                            return [$idx => $paths];
                        })
                        ->all();

                    // Primary fallback map: match by task/issue + category instead of index.
                    $pharPhotoFallbackByKey = [];
                    foreach (collect($findings ?? [])->values() as $f) {
                        $key = $findingMatchKey($f->task_question ?? '', $f->category ?? '');
                        if ($key === '|') {
                            continue;
                        }
                        $paths = $normalizePhotoPaths($f->photo_ids ?? []);
                        if (!empty($paths)) {
                            $pharPhotoFallbackByKey[$key] = array_values(array_unique(array_merge(
                                $pharPhotoFallbackByKey[$key] ?? [],
                                $paths
                            )));
                        }
                    }

                    $groupedFindings = $indexedFindings->groupBy('severity');
                    $totalLabourHrs  = $indexedFindings->sum('phar_labour_hours');
                    $totalMatCost    = $indexedFindings->sum(fn($f) =>
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
                                        $pharMaterials  = $finding['phar_materials'] ?? [];
                                        $firstMat       = $pharMaterials[0] ?? null;
                                        $extraMats      = array_slice($pharMaterials, 1);
                                        $findingMatCost = collect($pharMaterials)->sum(fn($m) => (float)($m['line_total'] ?? 0));
                                        $recommendations = $finding['recommendations'] ?? [];
                                        $findingPhotos = $normalizePhotoPaths($finding['finding_photos'] ?? []);
                                        $rowKey = $findingMatchKey(
                                            $finding['task_question'] ?? ($finding['issue'] ?? ''),
                                            $finding['phar_category'] ?? ($finding['category'] ?? '')
                                        );
                                        if (empty($findingPhotos)) {
                                            $findingPhotos = $pharPhotoFallbackByKey[$rowKey] ?? [];
                                        }
                                        if (empty($findingPhotos)) {
                                            $findingPhotos = $pharPhotoFallbackByIndex[$finding['__finding_index']] ?? [];
                                        }
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
                                            @if(!empty($findingPhotos))
                                                <div class="d-flex flex-wrap gap-1 mt-2">
                                                    @foreach($findingPhotos as $fp)
                                                        <a href="{{ $inspection->getStorageUrl($fp) }}" target="_blank" title="View photo">
                                                            <img src="{{ $inspection->getStorageUrl($fp) }}" style="height:52px;width:52px;object-fit:cover;border-radius:4px;border:1px solid #dee2e6;" alt="Finding photo">
                                                        </a>
                                                    @endforeach
                                                </div>
                                            @endif
                                            @if(empty($findingPhotos))
                                                <small class="text-muted d-block mt-2">No inspection photo uploaded for this finding.</small>
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
                                                <strong>${{ number_format($inspection->trc_annual ?? 0, 2) }}</strong>
                                                <div class="fs-6">total</div>
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
                                            <h6 class="text-muted mb-1">ARP (Total Remediation Cost)</h6>
                                            <div class="fs-4 text-primary">${{ number_format($inspection->trc_annual ?? 0, 2) }}</div>
                                        </div>
                                    </div>
                                </div>
                                @if(false) {{-- CPI Score hidden --}}
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
                                @endif {{-- end hidden CPI --}}
                            </div>

                            @if(false) {{-- ASI/TUS scores hidden --}}
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
                            @endif {{-- end hidden ASI/TUS --}}
                        </div>

                        <!-- Per-Unit Breakdown (Multi-Unit Properties) -->
                        @include('shared.inspections._per-unit-breakdown', ['inspection' => $inspection])

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

                <!-- Inspector Assessment -->
                @php
                    $clientRecommendationItems = [];
                    $rawClientRecommendations = $inspection->recommendations;

                    if (is_array($rawClientRecommendations)) {
                        $clientRecommendationItems = $rawClientRecommendations;
                    } elseif (is_string($rawClientRecommendations) && trim($rawClientRecommendations) !== '') {
                        $decoded = json_decode($rawClientRecommendations, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                            $clientRecommendationItems = $decoded;
                        } else {
                            $clientRecommendationItems = preg_split('/\r\n|\r|\n|\|/', $rawClientRecommendations) ?: [];
                        }
                    }

                    $clientRecommendationItems = collect($clientRecommendationItems)
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

                        @if($clientRecommendationItems->isNotEmpty())
                        <div class="mb-3">
                            <h6 class="text-primary">Recommendations:</h6>
                            <ul class="mb-0 ps-3">
                                @foreach($clientRecommendationItems as $item)
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

                <!-- Work Payment (if not yet paid) -->
                @if($inspection->status === 'completed' && ($inspection->work_payment_status ?? 'pending') !== 'paid')
                @php
                    $rptFullAmt  = (float) ($inspection->trc_annual ?? $inspection->arp_equivalent_final ?? 0);
                    $rptVisits   = max(1, (int) ($inspection->bdc_visits_per_year ?? 1));
                    $rptPerVisit = (float) ($inspection->trc_per_visit ?? ($rptVisits > 0 ? round($rptFullAmt / $rptVisits, 2) : 0));
                @endphp
                <div class="card mb-4 border-success">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="mdi mdi-credit-card me-2"></i>Start Remediation Work</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-2">Your inspection is complete. Total project cost: <strong>${{ number_format($rptFullAmt, 2) }}</strong> across <strong>{{ $rptVisits }} visit(s)</strong>.</p>
                        <div class="d-flex gap-2 flex-wrap no-print">
                            <a href="{{ route('client.inspections.work-payment', ['inspection' => $inspection->id, 'plan' => 'full']) }}" class="btn btn-success">
                                <i class="mdi mdi-cash-check me-1"></i>Pay in Full (${{ number_format($rptFullAmt, 2) }})
                            </a>
                            <a href="{{ route('client.inspections.work-payment', ['inspection' => $inspection->id, 'plan' => 'per_visit']) }}" class="btn btn-outline-primary">
                                <i class="mdi mdi-calendar-check me-1"></i>Pay Per Visit (${{ number_format($rptPerVisit, 2) }}/visit &times; {{ $rptVisits }})
                            </a>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Per-visit payment progress tracker (shown after work starts on per_visit plan) --}}
                @if(
                    ($inspection->work_payment_status ?? 'pending') === 'paid'
                    && ($inspection->payment_plan ?? 'full') === 'per_visit'
                    && !$inspection->arp_fully_paid_at
                )
                @php
                    $instPaid   = (int) ($inspection->installments_paid ?? 0);
                    $instTotal  = (int) ($inspection->installment_months ?? 1);
                    $instAmt    = (float) ($inspection->installment_amount ?? 0);
                    $instArpTot = (float) ($inspection->arp_total_locked ?? 0);
                    $instPaidAmt = round($instAmt * $instPaid, 2);
                    $instRemaining = max(0, $instArpTot - $instPaidAmt);
                    $instPct = $instTotal > 0 ? round(($instPaid / $instTotal) * 100) : 0;
                @endphp
                <div class="card mb-4 border-primary">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="mdi mdi-calendar-clock me-2"></i>Visit Payment Progress</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between small text-muted mb-1">
                            <span>{{ $instPaid }} of {{ $instTotal }} visits paid</span>
                            <span>${{ number_format($instPaidAmt, 2) }} of ${{ number_format($instArpTot, 2) }}</span>
                        </div>
                        <div class="progress mb-3" style="height:12px;">
                            <div class="progress-bar bg-primary" style="width:{{ $instPct }}%;"></div>
                        </div>
                        <div class="row text-center mb-3">
                            <div class="col-4">
                                <div class="fw-bold text-success">${{ number_format($instPaidAmt, 2) }}</div>
                                <small class="text-muted">Paid so far</small>
                            </div>
                            <div class="col-4">
                                <div class="fw-bold text-primary">${{ number_format($instAmt, 2) }}</div>
                                <small class="text-muted">Per visit</small>
                            </div>
                            <div class="col-4">
                                <div class="fw-bold text-danger">${{ number_format($instRemaining, 2) }}</div>
                                <small class="text-muted">Remaining</small>
                            </div>
                        </div>
                        <div class="no-print">
                            <a href="{{ route('client.inspections.pay-installment', $inspection->id) }}" class="btn btn-primary">
                                <i class="mdi mdi-credit-card me-1"></i>
                                Pay Visit {{ $instPaid + 1 }} of {{ $instTotal }} (${{ number_format($instAmt, 2) }})
                            </a>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Fully paid badge --}}
                @if(($inspection->work_payment_status ?? 'pending') === 'paid' && $inspection->arp_fully_paid_at)
                <div class="alert alert-success mb-4">
                    <i class="mdi mdi-check-circle me-2"></i>
                    <strong>Project Cost Fully Paid</strong> — Settled on
                    {{ \Carbon\Carbon::parse($inspection->arp_fully_paid_at)->format('M d, Y') }}.
                </div>
                @endif

                {{-- Work Visit Schedule --}}
                @php
                    $clientSchedule = collect($inspection->work_schedule ?? [])->sortBy('date')->values();
                @endphp
                @if($clientSchedule->isNotEmpty())
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="mdi mdi-calendar-check me-2"></i>Work Visit Schedule</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">
                            All visits are scheduled <strong>Monday – Saturday, 7:00 AM – 6:00 PM</strong>.
                            Please ensure site access and utilities are available on each visit date.
                        </p>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Date</th>
                                        <th>Day</th>
                                        <th>Hours</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($clientSchedule as $csIdx => $csVisit)
                                    @php $csStatus = $csVisit['status'] ?? 'scheduled'; @endphp
                                    <tr class="{{ $csStatus === 'completed' ? 'table-success' : '' }}">
                                        <td>{{ $csIdx + 1 }}</td>
                                        <td>{{ \Carbon\Carbon::parse($csVisit['date'])->format('M d, Y') }}</td>
                                        <td>{{ \Carbon\Carbon::parse($csVisit['date'])->format('l') }}</td>
                                        <td>7:00 AM – 6:00 PM</td>
                                        <td>
                                            <span class="badge bg-{{ $csStatus === 'completed' ? 'success' : ($csStatus === 'cancelled' ? 'danger' : 'secondary') }} text-capitalize">
                                                {{ $csStatus }}
                                            </span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @elseif($inspection->etogo_signed_at)
                <div class="alert alert-info mb-4">
                    <i class="mdi mdi-calendar-clock me-2"></i>
                    <strong>Visit Schedule Pending</strong> — Your work visit dates are being finalised by the Etogo team. You will see them here once confirmed.
                </div>
                @endif

                <div class="card mb-4">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class="mdi mdi-file-document-outline me-2"></i>Client Job Approval &amp; Service Agreement</h5>
                    </div>
                    <div class="card-body">
                        @include('shared.inspection-job-approval-agreement', ['inspection' => $inspection])
                    </div>
                </div>

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
