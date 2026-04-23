<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Inspection Report - {{ $inspection->property?->property_code }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10px;
            line-height: 1.4;
            color: #333;
        }
        /* ── Header ── */
        .header {
            background: #2c3e50;
            color: white;
            padding: 16px 20px;
            margin-bottom: 14px;
        }
        .header h1 { font-size: 20px; margin-bottom: 3px; }
        .header p  { font-size: 10px; opacity: .85; }
        .header-meta { font-size: 9px; opacity: .7; margin-top: 4px; }

        /* ── Section wrapper ── */
        .section {
            border: 1px solid #dde;
            margin-bottom: 12px;
            page-break-inside: avoid;
        }
        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: white;
            padding: 7px 10px;
            background: #34495e;
        }
        .section-body { padding: 8px 10px; }

        /* ── Info grid ── */
        .info-grid { width: 100%; }
        .info-grid td { padding: 3px 6px; font-size: 10px; width: 50%; vertical-align: top; }
        .info-grid .lbl { font-weight: bold; color: #555; width: 40%; }

        /* ── Generic table ── */
        table.data {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5px;
        }
        table.data th {
            background: #2c3e50;
            color: white;
            padding: 6px 7px;
            text-align: left;
            font-size: 9px;
        }
        table.data th.r, table.data td.r { text-align: right; }
        table.data th.c, table.data td.c { text-align: center; }
        table.data td { padding: 5px 7px; border-bottom: 1px solid #e5e5e5; vertical-align: top; }
        table.data tfoot td { background: #ecf0f1; font-weight: bold; border-top: 2px solid #bbb; border-bottom: none; }

        /* ── Severity group header row ── */
        .sev-header td {
            font-weight: bold;
            color: white;
            padding: 5px 8px;
            font-size: 10px;
        }
        .sev-dot {
            display: inline-block;
            width: 9px; height: 9px;
            border-radius: 50%;
            margin-right: 5px;
            vertical-align: middle;
        }
        .sev-count {
            display: inline-block;
            background: rgba(255,255,255,0.3);
            border-radius: 8px;
            padding: 1px 6px;
            font-size: 8px;
            margin-left: 5px;
        }

        /* ── Issue cell detail ── */
        .issue-system { font-size: 8.5px; color: #888; margin-top: 2px; }
        .issue-location { font-size: 8.5px; color: #555; margin-top: 3px; }
        .issue-recs { font-size: 8px; color: #444; margin-top: 3px; padding-left: 10px; }
        .issue-recs li { margin-bottom: 1px; }

        /* ── Summary badge ── */
        .badge {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
            color: white;
        }

        /* ── Cost boxes ── */
        .cost-boxes { width: 100%; }
        .cost-boxes td { width: 33.33%; padding: 5px; vertical-align: top; }
        .cost-box {
            background: #f4f6f8;
            border-left: 4px solid #3498db;
            padding: 7px 8px;
        }
        .cost-box h4 { font-size: 9px; color: #555; margin-bottom: 4px; }
        .cost-box .amount { font-size: 15px; font-weight: bold; color: #2c3e50; }
        .cost-box .note { font-size: 8px; color: #999; margin-top: 2px; }

        /* ── TRC banner ── */
        .trc-banner {
            background: #2980b9;
            color: white;
            padding: 10px 14px;
            margin: 6px 0;
        }
        .trc-banner .label { font-size: 10px; opacity: .85; }
        .trc-banner .amount { font-size: 22px; font-weight: bold; }
        .trc-banner .sub { font-size: 9px; opacity: .8; }

        /* ── Final monthly ── */
        .final-monthly {
            background: #27ae60;
            color: white;
            padding: 10px 14px;
        }
        .final-monthly .label { font-size: 10px; opacity: .85; }
        .final-monthly .amount { font-size: 22px; font-weight: bold; }
        .final-monthly .sub { font-size: 9px; opacity: .8; }

        /* ── Assessment notes ── */
        .notes-block { font-size: 9.5px; }
        .notes-block h4 { font-size: 10px; color: #2c3e50; margin-bottom: 3px; margin-top: 6px; border-bottom: 1px solid #eee; padding-bottom: 2px; }
        .notes-block ul { margin-left: 14px; }
        .notes-block li { margin-bottom: 2px; }
        .notes-block .risk { color: #c0392b; font-size: 9.5px; }

        /* ── Footer ── */
        .footer {
            margin-top: 14px;
            padding-top: 8px;
            border-top: 1px solid #ccc;
            font-size: 8px;
            color: #999;
            text-align: center;
        }
    </style>
</head>
<body>

@php
    /* ── Build findings from inspection JSON (has embedded phar_materials) ── */
    $inlineFindingsRaw = is_array($inspection->findings)
        ? $inspection->findings
        : (json_decode($inspection->getRawOriginal('findings') ?? '[]', true) ?? []);

    $quotationSnapshot = collect($activeQuotation->findings_snapshot ?? [])->values();
    $quotationApprovedIds = collect($activeQuotation->approved_finding_ids ?? [])->map(fn($id) => (int) $id);

    $makeFindingKey = function ($issueOrTask, $category) {
        $left = strtolower(trim((string) $issueOrTask));
        $right = strtolower(trim((string) $category));
        return $left . '|' . $right;
    };

    $showApprovedScopeOnly =
        !empty($activeQuotation) &&
        (($activeQuotation->status ?? null) === 'approved') &&
        (
            (($inspection->quotation_status ?? null) === 'approved') ||
            (($inspection->quotation_status ?? null) === 'shared')
        );

    if ($showApprovedScopeOnly) {
        $allInline = collect($inlineFindingsRaw)->values();
        $approvedIdMap = $quotationApprovedIds->flip();

        $filteredById = $allInline
            ->filter(fn ($f) => $approvedIdMap->has((int) ($f['id'] ?? 0)))
            ->values();

        if ($filteredById->isNotEmpty()) {
            $inlineFindingsRaw = $filteredById->all();
        } else {
            $approvedScopeKeys = $quotationSnapshot
                ->filter(fn($f) => $quotationApprovedIds->contains((int) ($f['id'] ?? 0)))
                ->map(fn($f) => $makeFindingKey(
                    $f['task_question'] ?? ($f['issue'] ?? ''),
                    $f['category'] ?? ''
                ))
                ->filter(fn($k) => $k !== '|')
                ->unique()
                ->values();

            $inlineFindingsRaw = $allInline
                ->filter(function ($f) use ($approvedScopeKeys, $makeFindingKey) {
                    $key = $makeFindingKey(
                        $f['task_question'] ?? ($f['issue'] ?? ''),
                        $f['phar_category'] ?? ($f['category'] ?? '')
                    );
                    return $approvedScopeKeys->contains($key);
                })
                ->values()
                ->all();
        }
    }

    $severityOrder = ['critical','high','noi_protection','medium','low'];
    $severityMeta  = [
        'critical'       => ['label' => 'Urgent — Safety Critical', 'color' => '#dc3545'],
        'high'           => ['label' => 'Health & Safety Risk',      'color' => '#fd7e14'],
        'noi_protection' => ['label' => 'NOI Protection',            'color' => '#6f42c1'],
        'medium'         => ['label' => 'Value Depreciation',        'color' => '#d4a017'],
        'low'            => ['label' => 'Non-Urgent',                'color' => '#198754'],
    ];
    $groupedFindings = collect($inlineFindingsRaw)->groupBy('severity');
    $totalLabourHrs  = collect($inlineFindingsRaw)->sum('phar_labour_hours');
    $totalMatCost    = collect($inlineFindingsRaw)->sum(fn($f) =>
        collect($f['phar_materials'] ?? [])->sum(fn($m) => (float)($m['line_total'] ?? 0))
    );

    /* ── Recommendations from inspector assessment ── */
    $recommendationItems = [];
    $rawRec = $inspection->recommendations;
    if (is_array($rawRec)) {
        $recommendationItems = $rawRec;
    } elseif (is_string($rawRec) && trim($rawRec) !== '') {
        $dec = json_decode($rawRec, true);
        $recommendationItems = (json_last_error() === JSON_ERROR_NONE && is_array($dec))
            ? $dec
            : (preg_split('/\r\n|\r|\n|\|/', $rawRec) ?: []);
    }
    $recommendationItems = collect($recommendationItems)->map(fn($i)=>trim((string)$i))->filter()->values();
@endphp

<!-- ════ HEADER ════ -->
<div class="header">
    <h1>INSPECTION REPORT</h1>
    <p>Property Condition Assessment &amp; Pricing Breakdown</p>
    <div class="header-meta">
        Generated {{ date('F d, Y \a\t h:i A') }}
        &nbsp;|&nbsp; Report Ref: {{ $inspection->property?->property_code }}-{{ date('Ymd') }}
    </div>
</div>

<!-- ════ PROPERTY INFORMATION ════ -->
<div class="section">
    <div class="section-title">Property &amp; Inspection Information</div>
    <div class="section-body">
        <table class="info-grid">
            <tr>
                <td class="lbl">Property Name:</td>
                <td><strong>{{ $inspection->property?->property_name ?? 'N/A' }}</strong></td>
                <td class="lbl">Inspector:</td>
                <td>{{ $inspection->inspector?->name ?? 'Not Assigned' }}</td>
            </tr>
            <tr>
                <td class="lbl">Property Code:</td>
                <td>{{ $inspection->property?->property_code ?? 'N/A' }}</td>
                <td class="lbl">Project Manager:</td>
                <td>{{ $inspection->property?->projectManager?->name ?? $inspection->project?->manager?->name ?? '—' }}</td>
            </tr>
            <tr>
                <td class="lbl">Property Type:</td>
                <td>{{ $inspection->property?->type ? ucfirst(str_replace('_',' ',$inspection->property->type)) : 'N/A' }}</td>
                <td class="lbl">Inspection Date:</td>
                <td>{{ $inspection->scheduled_date?->format('M d, Y') ?? 'Not scheduled' }}</td>
            </tr>
            <tr>
                <td class="lbl">Units:</td>
                <td>{{ $inspection->property?->residential_units ?? 'N/A' }}</td>
                <td class="lbl">Completed:</td>
                <td>{{ $inspection->completed_date?->format('M d, Y h:i A') ?? '—' }}</td>
            </tr>
            <tr>
                <td class="lbl">Owner Name:</td>
                <td><strong>{{ $inspection->owner_name ?? $inspection->property?->user?->name ?? 'N/A' }}</strong></td>
                <td class="lbl">Owner Phone:</td>
                <td>{{ $inspection->owner_phone ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="lbl">Owner Email:</td>
                <td colspan="3">{{ $inspection->owner_email ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="lbl">Status:</td>
                <td colspan="3">
                    <span class="badge" style="background:
                        @if($inspection->status==='completed') #27ae60
                        @elseif($inspection->status==='in_progress') #e67e22
                        @else #7f8c8d @endif;">
                        {{ ucfirst(str_replace('_',' ',$inspection->status)) }}
                    </span>
                    &nbsp;
                    <span style="font-size:9px;color:#666;">
                        CPI: <strong>{{ number_format($inspection->cpi_total_score ?? 0, 1) }}</strong>/100
                        &nbsp;|&nbsp;
                        ASI: <strong>{{ number_format($inspection->asi_score ?? 0, 1) }}</strong>/100
                        {{ $inspection->asi_rating ? '('.$inspection->asi_rating.')' : '' }}
                        &nbsp;|&nbsp;
                        TUS: <strong>{{ number_format($inspection->tus_score ?? 0, 1) }}</strong>/100
                    </span>
                </td>
            </tr>
        </table>
    </div>
</div>

<!-- ════ FINDINGS SUMMARY — grouped by severity ════ -->
@if(count($inlineFindingsRaw) > 0)
<div class="section">
    <div class="section-title">Findings Summary ({{ count($inlineFindingsRaw) }} items)</div>
    <div class="section-body" style="padding:0;">
        <table class="data">
            <thead>
                <tr>
                    <th style="width:3%">#</th>
                    <th style="width:32%">Issue / Task</th>
                    <th style="width:12%">Category</th>
                    <th class="r" style="width:9%">Labour Hrs</th>
                    <th style="width:16%">Material Used</th>
                    <th class="c" style="width:10%">Qty &amp; Unit</th>
                    <th class="r" style="width:9%">Unit Cost</th>
                    <th class="r" style="width:9%">Mat. Cost</th>
                </tr>
            </thead>
            <tbody>
            @php $rowNum = 0; @endphp
            @foreach($severityOrder as $sev)
                @if($groupedFindings->has($sev))
                @php $meta = $severityMeta[$sev]; @endphp
                <!-- severity group header -->
                <tr class="sev-header">
                    <td colspan="8" style="background:{{ $meta['color'] }};">
                        <span class="sev-dot" style="background:rgba(255,255,255,0.5);"></span>
                        {{ $meta['label'] }}
                        <span class="sev-count">{{ $groupedFindings[$sev]->count() }}</span>
                    </td>
                </tr>
                @foreach($groupedFindings[$sev] as $fi => $finding)
                @php
                    $rowNum++;
                    $pharMaterials  = $finding['phar_materials'] ?? [];
                    $firstMat       = $pharMaterials[0] ?? null;
                    $extraMats      = array_slice($pharMaterials, 1);
                    $findingMatCost = collect($pharMaterials)->sum(fn($m) => (float)($m['line_total'] ?? 0));
                    $recs           = $finding['recommendations'] ?? [];
                    $fpUrls         = $findingPhotoUrls[$fi] ?? [];
                @endphp
                <tr style="{{ $rowNum % 2 === 0 ? 'background:#fafafa;' : '' }}">
                    <td style="color:#999;font-size:8.5px;">{{ $rowNum }}</td>
                    <td>
                        <strong>{{ $finding['issue'] ?? '—' }}</strong>
                        @if(!empty($finding['system']))
                            <div class="issue-system">
                                {{ $finding['system'] }}{{ !empty($finding['subsystem']) ? ' › '.$finding['subsystem'] : '' }}
                            </div>
                        @endif
                        @if(!empty($finding['location']) || !empty($finding['spot']))
                            <div class="issue-location">
                                &#9679; {{ implode(' — ', array_filter([$finding['location'] ?? null, $finding['spot'] ?? null])) }}
                            </div>
                        @endif
                        @if(!empty($recs))
                            <ul class="issue-recs">
                                @foreach($recs as $rec)<li>{{ $rec }}</li>@endforeach
                            </ul>
                        @endif
                        @if(!empty($fpUrls))
                            <div style="display:flex;flex-wrap:wrap;gap:3px;margin-top:4px;">
                                @foreach($fpUrls as $fpUrl)
                                    <img src="{{ $fpUrl }}" style="height:40px;width:40px;object-fit:cover;border-radius:3px;border:1px solid #dee2e6;" alt="">
                                @endforeach
                            </div>
                        @endif
                    </td>
                    <td>
                        <span class="badge" style="background:#6c757d;">
                            {{ $finding['phar_category'] ?? $finding['type'] ?? 'General' }}
                        </span>
                    </td>
                    <td class="r">{{ number_format((float)($finding['phar_labour_hours'] ?? 0), 1) }} hrs</td>
                    <td>{{ $firstMat['material_name'] ?? '—' }}</td>
                    <td class="c">
                        @if($firstMat){{ $firstMat['quantity'] ?? '1' }} {{ $firstMat['unit'] ?? '' }}
                        @else —@endif
                    </td>
                    <td class="r">
                        @if($firstMat && (float)($firstMat['unit_cost'] ?? 0) > 0)
                            ${{ number_format((float)$firstMat['unit_cost'], 2) }}
                        @else —@endif
                    </td>
                    <td class="r">
                        @if($findingMatCost > 0)<strong>${{ number_format($findingMatCost, 2) }}</strong>
                        @else —@endif
                    </td>
                </tr>
                @foreach($extraMats as $em)
                <tr style="background:#f7f7f7;">
                    <td></td>
                    <td colspan="2" style="font-size:8px;color:#888;padding-left:14px;">&#8627; additional material</td>
                    <td></td>
                    <td style="font-size:8.5px;">{{ $em['material_name'] ?? '—' }}</td>
                    <td class="c" style="font-size:8.5px;">{{ $em['quantity'] ?? '1' }} {{ $em['unit'] ?? '' }}</td>
                    <td class="r" style="font-size:8.5px;">
                        @if((float)($em['unit_cost'] ?? 0) > 0)${{ number_format((float)$em['unit_cost'], 2) }}@else —@endif
                    </td>
                    <td class="r" style="font-size:8.5px;">${{ number_format((float)($em['line_total'] ?? 0), 2) }}</td>
                </tr>
                @endforeach
                @endforeach
                @endif
            @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="r">TOTALS:</td>
                    <td class="r">{{ number_format($totalLabourHrs, 1) }} hrs</td>
                    <td colspan="3"></td>
                    <td class="r">${{ number_format($totalMatCost, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endif

<!-- ════ PRICING BREAKDOWN ════ -->
<div class="section">
    <div class="section-title">Pricing Calculation Breakdown</div>
    <div class="section-body">

        <!-- Cost Components -->
        <div style="font-size:10px;font-weight:bold;color:#2c3e50;border-bottom:1px solid #ddd;padding-bottom:4px;margin-bottom:6px;">
            Cost Components (Annual)
        </div>
        <table class="cost-boxes">
            <tr>
                <td>
                    <div class="cost-box" style="border-left-color:#3498db;">
                        <h4>Base Deployment Cost (BDC)</h4>
                        <div class="amount">${{ number_format($inspection->bdc_annual ?? 0, 2) }}</div>
                        <div class="note">Operational baseline for property care</div>
                    </div>
                </td>
                <td>
                    <div class="cost-box" style="border-left-color:#e67e22;">
                        <h4>Findings Remediation Labour (FRLC)</h4>
                        <div class="amount">${{ number_format($inspection->frlc_annual ?? 0, 2) }}</div>
                        <div class="note">{{ number_format($totalLabourHrs, 1) }} hrs @ ${{ number_format($inspection->labour_hourly_rate ?? 165, 2) }}/hr</div>
                    </div>
                </td>
                <td>
                    <div class="cost-box" style="border-left-color:#27ae60;">
                        <h4>Findings Material Cost (FMC)</h4>
                        <div class="amount">${{ number_format($inspection->fmc_annual ?? 0, 2) }}</div>
                        <div class="note">Materials for remediation work</div>
                    </div>
                </td>
            </tr>
        </table>

        <!-- TRC -->
        <div style="margin-top:8px;">
            <div style="font-size:10px;font-weight:bold;color:#2c3e50;border-bottom:1px solid #ddd;padding-bottom:4px;margin-bottom:6px;">
                Total Remediation Cost (TRC) = BDC + FRLC + FMC
            </div>
            <div class="trc-banner">
                <div class="label">Annual: ${{ number_format($inspection->trc_annual ?? 0, 2) }}</div>
                <div class="amount">${{ number_format($inspection->trc_annual ?? 0, 2) }}</div>
                <div class="sub">total</div>
            </div>
        </div>

        <!-- ARP / Scores -->
        <div style="margin-top:8px;">
            <div style="font-size:10px;font-weight:bold;color:#2c3e50;border-bottom:1px solid #ddd;padding-bottom:4px;margin-bottom:6px;">
                Annual Recurring Price (ARP) &amp; Condition
            </div>
            <table class="cost-boxes">
                <tr>
                    <td>
                        <div class="cost-box" style="border-left-color:#3498db;">
                            <h4>ARP</h4>
                            <div class="amount">${{ number_format($inspection->trc_annual ?? 0, 2) }}</div>
                            <div class="note">= TRC</div>
                        </div>
                    </td>
                    {{-- CPI Score hidden --}}
                    {{-- ASI Score hidden --}}
                </tr>
            </table>
        </div>

        <!-- Final Charge -->
        @php
            $pdfPaymentMode = $inspection->work_payment_cadence === 'per_visit' ? 'per_visit' : 'lump_sum';
            $pdfVisits      = max(1, (int)($inspection->bdc_visits_per_year ?? 1));
            $pdfFinalCharge = $pdfPaymentMode === 'per_visit'
                ? (float)($inspection->trc_per_visit ?? round(($inspection->trc_annual ?? 0) / $pdfVisits, 2))
                : (float)($inspection->trc_annual ?? 0);
            $pdfChargeLabel = $pdfPaymentMode === 'per_visit'
                ? 'Per Visit Payment (×'.$pdfVisits.' visits)'
                : 'Lump Sum Payment (Full TRC)';
        @endphp
        <div style="margin-top:8px;">
                <div class="final-monthly">
                <div class="label">{{ $pdfChargeLabel }}</div>
                <div class="amount">${{ number_format($pdfFinalCharge, 2) }}</div>
                <div class="sub">{{ $pdfPaymentMode === 'per_visit' ? 'per visit · '.$pdfVisits.' visits total' : 'total charge' }}</div>
            </div>
        </div>

        <!-- Per-Unit (multi-unit properties) -->
        @if(($inspection->units_for_calculation ?? 1) > 1)
        <div style="margin-top:8px;">
            <div style="font-size:10px;font-weight:bold;color:#2c3e50;border-bottom:1px solid #ddd;padding-bottom:4px;margin-bottom:6px;">
                Per-Unit Cost Breakdown ({{ $inspection->units_for_calculation }} Units)
            </div>
            @php
                $pdfArpMonthlyTotal = (float)($inspection->trc_annual ?? 0);
            @endphp
            <table class="data">
                <thead>
                    <tr>
                        <th>Cost Component</th>
                        <th class="r">Annual Total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>BDC</td>
                        <td class="r">${{ number_format($inspection->bdc_annual ?? 0, 2) }}</td>
                    </tr>
                    <tr>
                        <td>FRLC</td>
                        <td class="r">${{ number_format($inspection->frlc_annual ?? 0, 2) }}</td>
                    </tr>
                    <tr>
                        <td>FMC</td>
                        <td class="r">${{ number_format($inspection->fmc_annual ?? 0, 2) }}</td>
                    </tr>
                    <tr style="background:#ecf0f1;">
                        <td><strong>TRC</strong> <span style="font-size:8px;color:#555;">(BDC+FRLC+FMC)</span></td>
                        <td class="r"><strong>${{ number_format($inspection->trc_annual ?? 0, 2) }}</strong></td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <td><strong>ARP</strong> <span style="font-size:8px;font-weight:normal;">(= TRC)</span></td>
                        <td class="r"><strong>${{ number_format($pdfArpMonthlyTotal, 2) }}</strong></td>
                    </tr>
                </tfoot>
            </table>
            <p style="font-size:8px;color:#666;margin-top:4px;"><strong>ARP</strong> = Annual Recurring Price = TRC. This is the total amount the client pays.</p>
        </div>
        @endif

    </div>
</div>

<!-- ════ INSPECTION PHOTOS ════ -->
@php
    $pdfResolvedUrls = $photoUrls ?? [];
@endphp
@if(count($pdfResolvedUrls) > 0)
<div class="section">
    <div class="section-title">Inspection Photos ({{ count($pdfResolvedUrls) }})</div>
    <div class="section-body">
        <table style="width:100%;border-collapse:collapse;">
            @php $pdfPhotoChunks = array_chunk($pdfResolvedUrls, 4); @endphp
            @foreach($pdfPhotoChunks as $pdfRow)
            <tr>
                @foreach($pdfRow as $pdfPhotoIdx => $pdfUrl)
                <td style="width:25%;padding:4px;vertical-align:top;text-align:center;">
                    <img src="{{ $pdfUrl }}" style="max-width:100%;height:140px;object-fit:cover;border:1px solid #ddd;" alt="Photo">
                    <div style="font-size:8px;color:#666;margin-top:2px;">Photo {{ ($loop->parent->index * 4) + $pdfPhotoIdx + 1 }}</div>
                </td>
                @endforeach
                @for($pdfFill = count($pdfRow); $pdfFill < 4; $pdfFill++)
                <td style="width:25%;"></td>
                @endfor
            </tr>
            @endforeach
        </table>
    </div>
</div>
@endif

<!-- ════ INSPECTOR ASSESSMENT ════ -->
@if($inspection->summary || $recommendationItems->isNotEmpty() || $inspection->risk_summary)
<div class="section">
    <div class="section-title">Inspector Assessment</div>
    <div class="section-body">
        <div class="notes-block">
            @if($inspection->summary)
                <h4>Notes</h4>
                <p>{{ $inspection->summary }}</p>
            @endif
            @if($recommendationItems->isNotEmpty())
                <h4>Recommendations</h4>
                <ul>@foreach($recommendationItems as $item)<li>{{ $item }}</li>@endforeach</ul>
            @endif
            @if($inspection->risk_summary)
                <h4 class="risk">Risk Summary</h4>
                <p class="risk">{{ $inspection->risk_summary }}</p>
            @endif
        </div>
    </div>
</div>
@endif

<div class="section">
    <div class="section-title">Client Job Approval &amp; Service Agreement</div>
    <div class="section-body">
        @include('shared.inspection-job-approval-agreement', ['inspection' => $inspection, 'pdfMode' => true])
    </div>
</div>

<!-- ════ FOOTER ════ -->
<div class="footer">
    <strong>EMURIA Regenerative Property Care</strong> &mdash;
    Generated {{ date('F d, Y \a\t h:i A') }} &mdash;
    Report #{{ $inspection->property?->property_code }}-{{ date('Ymd') }}<br>
    This document contains proprietary pricing calculations and should be kept confidential.
</div>

</body>
</html>
