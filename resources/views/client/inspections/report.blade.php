@extends($adminPreview ?? false ? 'admin.layout' : 'client.layout')

@section('title', 'Inspection Report & Work Scope Breakdown')

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
                            Inspection Report & Work Scope Breakdown
                        </h4>
                        <p class="text-muted small mb-0">Complete breakdown of inspection findings, materials, and labour</p>
                    </div>
                    <div>
                        @if(isset($adminPreview) && $adminPreview)
                            @if(($inspection->status ?? null) === 'completed')
                                <a href="{{ route('inspections.show', $inspection->id) }}" class="btn btn-light btn-sm no-print">
                                    <i class="mdi mdi-arrow-left me-1"></i>Back to Inspection Record
                                </a>
                            @else
                                <a href="{{ route('inspections.phar-data', $inspection->id) }}" class="btn btn-light btn-sm no-print">
                                    <i class="mdi mdi-arrow-left me-1"></i>Back to PHAR Dashboard
                                </a>
                            @endif
                            @if(($inspection->status ?? null) === 'completed')
                                <a href="{{ route('inspections.download-invoice', $inspection->id) }}" class="btn btn-success btn-sm no-print" title="Download Final Report PDF">
                                    <i class="mdi mdi-download me-1"></i>Download PDF
                                </a>
                            @endif
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

                @if(!($adminPreview ?? false) && in_array(($inspection->quotation_status ?? ''), ['shared', 'client_reviewing', 'client_responded'], true))
                    <div class="alert alert-info d-flex flex-wrap justify-content-between align-items-center gap-2 no-print">
                        <div>
                            <strong>Your quotation is ready for review.</strong>
                            Select the findings you want approved before assessment completion.
                        </div>
                        <a href="{{ route('client.inspections.quotation', $inspection->id) }}" class="btn btn-primary btn-sm">
                            <i class="mdi mdi-file-check-outline me-1"></i>Review Quotation
                        </a>
                    </div>
                @endif

                @php
                    $quotationSnapshot = collect($activeQuotation->findings_snapshot ?? []);
                    $quotationApprovedIds = collect($activeQuotation->approved_finding_ids ?? [])->map(fn($id) => (int) $id);
                    $quotationDeferredIds = collect($activeQuotation->deferred_finding_ids ?? [])->map(fn($id) => (int) $id);
                    $quotationDeferredFindings = $quotationSnapshot
                        ->filter(fn($f) => $quotationDeferredIds->contains((int) ($f['id'] ?? 0)))
                        ->values();
                @endphp

                @if(!($adminPreview ?? false) && $activeQuotation && ($inspection->quotation_status ?? null) === 'approved')
                    <div class="card mb-4 border-success no-print">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="mdi mdi-check-decagram-outline me-2"></i>Approved Quotation Summary</h5>
                        </div>
                        <div class="card-body">
                            <p class="mb-2">
                                Quote <strong>{{ $activeQuotation->quote_number }}</strong> approved with
                                <strong>{{ $quotationApprovedIds->count() }}</strong> selected finding(s).
                            </p>
                            <p class="mb-2 text-muted">
                                Deferred finding(s): <strong>{{ $quotationDeferredIds->count() }}</strong>
                            </p>

                            @if($quotationDeferredFindings->isNotEmpty())
                                <div class="alert alert-warning py-2 mb-0">
                                    <strong>Deferred findings kept for future quotation:</strong>
                                    <ul class="mb-0 mt-1 ps-3">
                                        @foreach($quotationDeferredFindings as $df)
                                            <li>
                                                {{ $df['task_question'] ?? 'Untitled finding' }}
                                                @if(!empty($df['category']))
                                                    <span class="text-muted">({{ $df['category'] }})</span>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
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
                                        <td>{{ $inspection->residential_units_snapshot ?: ($inspection->property?->number_of_units ?: ($inspection->property?->residential_units ?? 'N/A')) }}</td>
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
                                                @php
                                                    $paymentLabel = match ($inspection->payment_plan ?? 'full') {
                                                        'per_visit' => 'Per Visit',
                                                        'installment' => '50% Deposit Plan',
                                                        default => 'In Full',
                                                    };
                                                @endphp
                                                <span class="badge bg-success">Paid ({{ $paymentLabel }})</span>
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

                    $makeFindingKey = function ($issueOrTask, $category) {
                        $left = strtolower(trim((string) $issueOrTask));
                        $right = strtolower(trim((string) $category));
                        return $left . '|' . $right;
                    };

                    // The report always shows ALL findings because the client paid for the inspection.
                    // Per-finding status badges indicate approved / deferred / noted.

                    // Build approved & deferred key lookups from the quotation snapshot
                    $approvedIdFlip  = $quotationApprovedIds->flip();
                    $deferredIdFlip  = $quotationDeferredIds->flip();

                    $approvedKeySet  = $quotationSnapshot
                        ->filter(fn($f) => $quotationApprovedIds->contains((int) ($f['id'] ?? 0)))
                        ->mapWithKeys(fn($f) => [
                            $makeFindingKey($f['task_question'] ?? ($f['issue'] ?? ''), $f['category'] ?? '') => true
                        ]);
                    $deferredKeySet  = $quotationSnapshot
                        ->filter(fn($f) => $quotationDeferredIds->contains((int) ($f['id'] ?? 0)))
                        ->mapWithKeys(fn($f) => [
                            $makeFindingKey($f['task_question'] ?? ($f['issue'] ?? ''), $f['category'] ?? '') => true
                        ]);

                    // Backfill descriptive fields from quotation snapshot for legacy findings
                    // where issue_description / recommendation_details were not persisted inline.
                    $snapshotById = $quotationSnapshot
                        ->filter(fn($f) => !empty($f['id']))
                        ->mapWithKeys(fn($f) => [(int) $f['id'] => $f]);

                    $snapshotByKey = $quotationSnapshot
                        ->mapWithKeys(function ($f) use ($makeFindingKey) {
                            $key = $makeFindingKey(
                                $f['task_question'] ?? ($f['issue'] ?? ''),
                                $f['category'] ?? ''
                            );
                            return $key !== '|' ? [$key => $f] : [];
                        });

                    $inlineFindingsRaw = collect($inlineFindingsRaw)
                        ->map(function ($finding) use ($snapshotById, $snapshotByKey, $makeFindingKey) {
                            $snapshotFinding = null;
                            $findingId = (int) ($finding['id'] ?? 0);

                            if ($findingId > 0 && $snapshotById->has($findingId)) {
                                $snapshotFinding = $snapshotById->get($findingId);
                            } else {
                                $key = $makeFindingKey(
                                    $finding['task_question'] ?? ($finding['issue'] ?? ''),
                                    $finding['phar_category'] ?? ($finding['category'] ?? '')
                                );
                                $snapshotFinding = $snapshotByKey->get($key);
                            }

                            if (!$snapshotFinding) {
                                return $finding;
                            }

                            if (empty($finding['issue_description']) && !empty($snapshotFinding['issue_description'])) {
                                $finding['issue_description'] = $snapshotFinding['issue_description'];
                            }
                            if (empty($finding['recommendation_details']) && !empty($snapshotFinding['recommendation_details'])) {
                                $finding['recommendation_details'] = $snapshotFinding['recommendation_details'];
                            }
                            if (empty($finding['recommendations']) && !empty($snapshotFinding['recommendations']) && is_array($snapshotFinding['recommendations'])) {
                                $finding['recommendations'] = $snapshotFinding['recommendations'];
                            }

                            return $finding;
                        })
                        ->values()
                        ->all();

                    $severityOrder = ['critical','high','noi_protection','medium','low'];
                    $severityMeta  = [
                        'critical'       => ['label' => 'Urgent — Safety Critical',  'color' => '#dc3545', 'icon' => '🔴'],
                        'high'           => ['label' => 'Health & Safety Risk',       'color' => '#fd7e14', 'icon' => '🟠'],
                        'noi_protection' => ['label' => 'NOI Protection',             'color' => '#6f42c1', 'icon' => '🟣'],
                        'medium'         => ['label' => 'Value Depreciation',         'color' => '#d4a017', 'icon' => '🟡'],
                        'low'            => ['label' => 'Non-Urgent',                 'color' => '#198754', 'icon' => '🟢'],
                    ];

                    $isApprovedQuotation = !empty($activeQuotation)
                        && (($activeQuotation->status ?? null) === 'approved');

                    $indexedFindings = collect($inlineFindingsRaw)
                        ->values()
                        ->map(function ($finding, $idx) use ($makeFindingKey, $activeQuotation, $isApprovedQuotation, $approvedIdFlip, $deferredIdFlip, $approvedKeySet, $deferredKeySet) {
                            $finding['__finding_index'] = $idx;

                            $rowKey = $makeFindingKey(
                                $finding['task_question'] ?? ($finding['issue'] ?? ''),
                                $finding['phar_category'] ?? ($finding['category'] ?? '')
                            );
                            $fid = (int) ($finding['id'] ?? 0);

                            if ($isApprovedQuotation && ($approvedIdFlip->has($fid) || ($fid === 0 && $approvedKeySet->has($rowKey)))) {
                                $finding['__finding_quote_status'] = 'approved';
                            } elseif ($activeQuotation && ($deferredIdFlip->has($fid) || ($fid === 0 && $deferredKeySet->has($rowKey)))) {
                                $finding['__finding_quote_status'] = 'deferred';
                            } else {
                                $finding['__finding_quote_status'] = 'noted';
                            }

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

                    // Pricing scope: if quotation is approved, charge only approved findings.
                    $pricedFindings = $isApprovedQuotation
                        ? $indexedFindings->filter(fn($f) => ($f['__finding_quote_status'] ?? 'noted') === 'approved')->values()
                        : $indexedFindings;

                    if ($isApprovedQuotation && $pricedFindings->isEmpty()) {
                        // Safety fallback for legacy edge cases where approved matching fails.
                        $pricedFindings = $indexedFindings;
                    }

                    $pricingScopeApprovedOnly = $isApprovedQuotation && $pricedFindings->isNotEmpty();

                    $totalLabourHrs  = $pricedFindings->sum('phar_labour_hours');
                    $hourlyRateForClient = (float) ($inspection->labour_hourly_rate ?? 165);
                    $totalLabourCost = round($totalLabourHrs * $hourlyRateForClient, 2);
                    $totalMatCost    = $pricedFindings->sum(fn($f) =>
                        collect($f['phar_materials'] ?? [])->sum(fn($m) => (float)($m['line_total'] ?? 0))
                    );
                    $clientVisibleTotal = round($totalLabourCost + $totalMatCost, 2);
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
                                        <th width="43%">Finding Details</th>
                                        <th width="15%" class="text-end">Labour</th>
                                        <th width="29%">Materials Breakdown</th>
                                        <th width="10%" class="text-end">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @php $rowNum = 0; @endphp
                                @foreach($severityOrder as $sev)
                                    @if($groupedFindings->has($sev))
                                    @php $meta = $severityMeta[$sev]; @endphp
                                    <tr>
                                        <td colspan="5" class="fw-bold text-white py-2 px-3" style="background:{{ $meta['color'] }};">
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
                                        $findingMatCost = collect($pharMaterials)->sum(fn($m) => (float)($m['line_total'] ?? 0));
                                        $findingLabourHours = (float)($finding['phar_labour_hours'] ?? 0);
                                        $findingLabourCost = round($findingLabourHours * $hourlyRateForClient, 2);
                                        $findingSubtotal = round($findingLabourCost + $findingMatCost, 2);
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
                                        $findingQuoteStatus = $finding['__finding_quote_status'] ?? 'noted';
                                    @endphp
                                    <tr>
                                        <td class="text-muted small align-top">{{ $rowNum }}</td>
                                        <td class="align-top">
                                            <strong>{{ $finding['issue'] ?? '-' }}</strong>
                                            @if($activeQuotation)
                                                @if($findingQuoteStatus === 'approved')
                                                    <span class="badge ms-1" style="background:#198754;font-size:.7rem;">✔ Approved</span>
                                                @elseif($findingQuoteStatus === 'deferred')
                                                    <span class="badge ms-1" style="background:#6c757d;font-size:.7rem;">⏳ Deferred</span>
                                                @else
                                                    <span class="badge ms-1" style="background:#0d6efd;font-size:.7rem;">📋 Noted</span>
                                                @endif
                                            @endif
                                            <div class="mt-1">
                                                <span class="badge bg-secondary">{{ $finding['phar_category'] ?? $finding['type'] ?? 'General' }}</span>
                                            </div>
                                            @if(!empty($finding['system']))
                                                <br><small class="text-muted">{{ $finding['system'] }}{{ !empty($finding['subsystem']) ? ' › '.$finding['subsystem'] : '' }}</small>
                                            @endif
                                            @if(!empty($finding['location']) || !empty($finding['spot']))
                                                <div class="mt-1" style="font-size:.8rem;color:#555;">
                                                    <i class="mdi mdi-map-marker-outline"></i>
                                                    {{ implode(' — ', array_filter([$finding['location'] ?? null, $finding['spot'] ?? null])) }}
                                                </div>
                                            @endif
                                            @if(!empty($finding['issue_description']))
                                                <div class="mt-1" style="font-size:.8rem;color:#444;">
                                                    <strong>Issue Description:</strong> {{ $finding['issue_description'] }}
                                                </div>
                                            @endif
                                            @if(!empty($recommendations))
                                                <ul class="mb-0 mt-1 ps-3" style="font-size:.78rem;color:#444;">
                                                    @foreach($recommendations as $rec)
                                                        <li>{{ $rec }}</li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                            @if(!empty($finding['recommendation_details']))
                                                <div class="mt-1" style="font-size:.8rem;color:#444;">
                                                    <strong>Recommendation Description:</strong> {{ $finding['recommendation_details'] }}
                                                </div>
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
                                        <td class="text-end align-top">
                                            <div><small class="text-muted d-block">Hours</small><strong>{{ number_format($findingLabourHours, 1) }} hrs</strong></div>
                                            <div class="mt-1"><small class="text-muted d-block">Cost</small>
                                                @if($findingLabourCost > 0)
                                                    <strong>${{ number_format($findingLabourCost, 2) }}</strong>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="align-top">
                                            @if(!empty($pharMaterials))
                                                <div class="small text-muted mb-1">{{ count($pharMaterials) }} item(s)</div>
                                                <div class="d-flex flex-column gap-1">
                                                    @foreach($pharMaterials as $mat)
                                                        <div class="border rounded px-2 py-1" style="font-size:.78rem;">
                                                            <div class="fw-semibold">{{ $mat['material_name'] ?? 'Unnamed material' }}</div>
                                                            <div class="text-muted">
                                                                {{ number_format((float)($mat['quantity'] ?? 0), 2) }} {{ $mat['unit'] ?? 'ea' }}
                                                                &times; ${{ number_format((float)($mat['unit_cost'] ?? 0), 2) }}
                                                            </div>
                                                            <div class="text-end fw-semibold">${{ number_format((float)($mat['line_total'] ?? 0), 2) }}</div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <span class="text-muted">No materials assigned</span>
                                            @endif
                                        </td>
                                        <td class="text-end align-top">
                                            @if($findingSubtotal > 0)
                                                <strong class="fs-6">${{ number_format($findingSubtotal, 2) }}</strong>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                            <div class="small text-muted mt-1">Labour + Materials</div>
                                        </td>
                                    </tr>
                                    @endforeach
                                    @endif
                                @endforeach
                                </tbody>
                                <tfoot class="table-secondary fw-bold">
                                    <tr>
                                        <th colspan="2" class="text-end">{{ $pricingScopeApprovedOnly ? 'APPROVED TOTALS:' : 'TOTALS:' }}</th>
                                        <th class="text-end">{{ number_format($totalLabourHrs, 1) }} hrs / ${{ number_format($totalLabourCost, 2) }}</th>
                                        <th class="text-end">${{ number_format($totalMatCost, 2) }}</th>
                                        <th class="text-end">${{ number_format($clientVisibleTotal, 2) }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Client Pricing Summary -->
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="mdi mdi-calculator me-2"></i>Client Pricing Summary
                        </h5>
                    </div>
                    <div class="card-body">

                        <div class="row">
                            <div class="col-md-4">
                                <div class="card border-primary">
                                    <div class="card-body">
                                        <h6 class="text-muted mb-1">Labour</h6>
                                        <div class="fs-4 text-primary">${{ number_format($totalLabourCost, 2) }}</div>
                                        <small class="text-muted">{{ number_format($totalLabourHrs, 1) }} hrs @ ${{ number_format($hourlyRateForClient, 2) }}/hr</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-primary">
                                    <div class="card-body">
                                        <h6 class="text-muted mb-1">Materials</h6>
                                        <div class="fs-4 text-primary">${{ number_format($totalMatCost, 2) }}</div>
                                        <small class="text-muted">
                                            {{ $pricingScopeApprovedOnly ? 'Total assigned materials across approved findings' : 'Total assigned materials across all findings' }}
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-success bg-light">
                                    <div class="card-body">
                                        <h6 class="text-muted mb-1">Total</h6>
                                        <div class="fs-3 text-success"><strong>${{ number_format($clientVisibleTotal, 2) }}</strong></div>
                                        <small class="text-muted">Labour + Materials</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3">
                            <small class="text-muted">
                                Pricing shown to client includes only labour and materials by finding.
                                {{ $pricingScopeApprovedOnly ? 'Totals above are scoped to approved quotation findings only.' : 'Totals above reflect all findings currently listed.' }}
                            </small>
                        </div>
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
                    $rptFullAmt  = (float) ($clientVisibleTotal ?? 0);
                    if ($rptFullAmt <= 0) {
                        $rptFullAmt = (float) ($inspection->frlc_annual ?? 0) + (float) ($inspection->fmc_annual ?? 0);
                    }
                    if ($rptFullAmt <= 0) {
                        $rptFullAmt = (float) ($inspection->trc_annual ?? $inspection->arp_equivalent_final ?? 0);
                    }
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
                            <a href="{{ route('client.inspections.work-payment', ['inspection' => $inspection->id, 'plan' => 'installment']) }}" class="btn btn-outline-warning">
                                <i class="mdi mdi-percent me-1"></i>Pay 50% Deposit (${{ number_format($rptFullAmt * 0.5, 2) }} now)
                            </a>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Per-visit payment progress tracker (shown after work starts on per_visit plan) --}}
                @if(
                    ($inspection->work_payment_status ?? 'pending') === 'paid'
                    && in_array(($inspection->payment_plan ?? 'full'), ['per_visit', 'installment'], true)
                    && !$inspection->arp_fully_paid_at
                )
                @php
                    $isPerVisitPlan = ($inspection->payment_plan ?? 'full') === 'per_visit';
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
                        <h5 class="mb-0"><i class="mdi mdi-calendar-clock me-2"></i>{{ $isPerVisitPlan ? 'Visit Payment Progress' : 'Installment Payment Progress' }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between small text-muted mb-1">
                            <span>{{ $instPaid }} of {{ $instTotal }} {{ $isPerVisitPlan ? 'visits' : 'installments' }} paid</span>
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
                                <small class="text-muted">{{ $isPerVisitPlan ? 'Per visit' : 'Per installment' }}</small>
                            </div>
                            <div class="col-4">
                                <div class="fw-bold text-danger">${{ number_format($instRemaining, 2) }}</div>
                                <small class="text-muted">Remaining</small>
                            </div>
                        </div>
                        <div class="no-print">
                            <a href="{{ route('client.inspections.pay-installment', $inspection->id) }}" class="btn btn-primary">
                                <i class="mdi mdi-credit-card me-1"></i>
                                {{ $isPerVisitPlan ? 'Pay Visit' : 'Pay Installment' }} {{ $instPaid + 1 }} of {{ $instTotal }} (${{ number_format($instAmt, 2) }})
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

            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .no-print, .btn, .alert, nav, .navbar, .sidebar, #sidebar {
        display: none !important;
    }

    html, body {
        background: #ffffff !important;
        font-size: 10.5px;
        line-height: 1.35;
    }

    .row, .col-lg-12, .col-md-3, .col-md-4, .col-md-6, .col-md-8, .col-xl-10 {
        display: block !important;
        width: 100% !important;
        max-width: 100% !important;
        flex: none !important;
    }

    .card {
        page-break-inside: avoid;
        box-shadow: none !important;
        border: 1px solid #dee2e6 !important;
        margin-bottom: 12px !important;
    }

    .card-body {
        padding: 10px !important;
    }

    .card-header {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
        padding: 8px 10px !important;
    }

    .table-responsive {
        overflow: visible !important;
    }

    #findingsTable {
        width: 100% !important;
        table-layout: fixed;
    }

    #findingsTable th,
    #findingsTable td {
        font-size: 9px;
        padding: 4px 5px;
        white-space: normal !important;
        word-break: break-word;
        overflow-wrap: anywhere;
        vertical-align: top;
    }

    #findingsTable img {
        height: 34px !important;
        width: 34px !important;
    }

    .badge {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
        border: 1px solid #999;
    }

    h4, h5, h6 {
        page-break-after: avoid;
    }

    .findings-card {
        page-break-before: auto;
    }

    @page {
        margin: 10mm;
        size: A4 portrait;
    }
}
</style>
@endsection
