<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Inspection Invoice - {{ $inspection->property?->property_code }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
        }
        .header {
            background: #2c3e50;
            color: white;
            padding: 20px;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 24px;
            margin-bottom: 5px;
        }
        .header p {
            font-size: 12px;
            opacity: 0.9;
        }
        .info-section {
            margin-bottom: 15px;
            border: 1px solid #ddd;
            padding: 10px;
        }
        .info-section h3 {
            font-size: 14px;
            color: #2c3e50;
            margin-bottom: 8px;
            border-bottom: 2px solid #3498db;
            padding-bottom: 5px;
        }
        .info-grid {
            display: table;
            width: 100%;
        }
        .info-row {
            display: table-row;
        }
        .info-label {
            display: table-cell;
            width: 35%;
            font-weight: bold;
            padding: 4px 8px;
            background: #f8f9fa;
        }
        .info-value {
            display: table-cell;
            padding: 4px 8px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        th {
            background: #34495e;
            color: white;
            padding: 8px;
            text-align: left;
            font-size: 10px;
            font-weight: bold;
        }
        td {
            padding: 6px 8px;
            border-bottom: 1px solid #ddd;
            font-size: 10px;
        }
        tr:nth-child(even) {
            background: #f8f9fa;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
        }
        .badge-danger {
            background: #e74c3c;
            color: white;
        }
        .badge-warning {
            background: #f39c12;
            color: white;
        }
        .badge-info {
            background: #3498db;
            color: white;
        }
        .badge-success {
            background: #27ae60;
            color: white;
        }
        .cost-box {
            background: #ecf0f1;
            padding: 10px;
            margin: 10px 0;
            border-left: 4px solid #3498db;
        }
        .cost-box h4 {
            font-size: 12px;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        .cost-box .amount {
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
        }
        .cost-box .note {
            font-size: 9px;
            color: #7f8c8d;
            margin-top: 3px;
        }
        .final-price {
            background: #27ae60;
            color: white;
            padding: 15px;
            margin: 15px 0;
            text-align: center;
        }
        .final-price h3 {
            font-size: 16px;
            margin-bottom: 5px;
        }
        .final-price .amount {
            font-size: 28px;
            font-weight: bold;
        }
        .final-price .period {
            font-size: 12px;
            opacity: 0.9;
        }
        .breakdown-grid {
            display: table;
            width: 100%;
            margin: 10px 0;
        }
        .breakdown-row {
            display: table-row;
        }
        .breakdown-cell {
            display: table-cell;
            width: 33.33%;
            padding: 5px;
        }
        .tier-box {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
            height: 100%;
        }
        .tier-box h5 {
            font-size: 11px;
            margin-bottom: 5px;
            color: #7f8c8d;
        }
        .tier-box .tier-value {
            font-size: 16px;
            font-weight: bold;
            color: #2c3e50;
        }
        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 2px solid #ddd;
            font-size: 9px;
            color: #7f8c8d;
            text-align: center;
        }
        tfoot {
            background: #34495e;
            color: white;
            font-weight: bold;
        }
        tfoot td {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>INSPECTION INVOICE</h1>
        <p>Property Care Pricing Breakdown & Assessment Report</p>
    </div>

    <!-- Property Information -->
    <div class="info-section">
        <h3>Property Information</h3>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Property Name:</div>
                <div class="info-value"><strong>{{ $inspection->property?->property_name ?? 'N/A' }}</strong></div>
            </div>
            <div class="info-row">
                <div class="info-label">Property Code:</div>
                <div class="info-value">{{ $inspection->property?->property_code ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Property Type:</div>
                <div class="info-value">{{ $inspection->property?->type ? ucfirst(str_replace('_', ' ', $inspection->property->type)) : 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Units:</div>
                <div class="info-value">{{ $inspection->property?->residential_units ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Inspector:</div>
                <div class="info-value">{{ $inspection->inspector?->name ?? 'Not Assigned' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Inspection Date:</div>
                <div class="info-value">{{ $inspection->scheduled_date?->format('M d, Y') ?? 'Not Scheduled' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Completed Date:</div>
                <div class="info-value">{{ $inspection->completed_date?->format('M d, Y h:i A') ?? '-' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Status:</div>
                <div class="info-value"><span class="badge badge-success">{{ ucfirst($inspection->status) }}</span></div>
            </div>
        </div>
    </div>

    <!-- Findings Summary -->
    @if($findings->count() > 0)
    <div class="info-section">
        <h3>Findings Summary ({{ $findings->count() }} items)</h3>
        <table>
            <thead>
                <tr>
                    <th style="width: 5%">#</th>
                    <th style="width: 30%">Task / Issue</th>
                    <th style="width: 15%">Category</th>
                    <th style="width: 10%">Priority</th>
                    <th style="width: 12%">Labour Hours</th>
                    <th style="width: 14%">Material Cost</th>
                    <th style="width: 14%">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($findings as $finding)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $finding->task_question ?? '-' }}</td>
                    <td>{{ $finding->category ?? 'General' }}</td>
                    <td class="text-center">
                        @if($finding->priority == '1')
                            <span class="badge badge-danger">High</span>
                        @elseif($finding->priority == '2')
                            <span class="badge badge-warning">Medium</span>
                        @else
                            <span class="badge badge-info">Low</span>
                        @endif
                    </td>
                    <td class="text-right">{{ number_format($finding->labour_hours, 1) }} hrs</td>
                    <td class="text-right">${{ number_format($finding->material_cost, 2) }}</td>
                    <td class="text-right"><strong>${{ number_format($finding->labour_cost + $finding->material_cost, 2) }}</strong></td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="text-right"><strong>TOTALS:</strong></td>
                    <td class="text-right"><strong>{{ number_format($findings->sum('labour_hours'), 1) }} hrs</strong></td>
                    <td class="text-right"><strong>${{ number_format($findings->sum('material_cost'), 2) }}</strong></td>
                    <td class="text-right"><strong>${{ number_format($findings->sum('labour_cost') + $findings->sum('material_cost'), 2) }}</strong></td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endif

    <!-- Cost Components -->
    <div class="info-section">
        <h3>Cost Components (Annual)</h3>
        <div class="breakdown-grid">
            <div class="breakdown-row">
                <div class="breakdown-cell">
                    <div class="cost-box">
                        <h4>Base Deployment Cost (BDC)</h4>
                        <div class="amount">${{ number_format($inspection->bdc_annual ?? 0, 2) }}</div>
                        <div class="note">Operational baseline</div>
                    </div>
                </div>
                <div class="breakdown-cell">
                    <div class="cost-box">
                        <h4>Findings Remediation Labour (FRLC)</h4>
                        <div class="amount">${{ number_format($inspection->frlc_annual ?? 0, 2) }}</div>
                        <div class="note">{{ number_format($findings->sum('labour_hours'), 1) }} hrs @ ${{ number_format($inspection->labour_hourly_rate ?? 165, 2) }}/hr</div>
                    </div>
                </div>
                <div class="breakdown-cell">
                    <div class="cost-box">
                        <h4>Findings Material Cost (FMC)</h4>
                        <div class="amount">${{ number_format($inspection->fmc_annual ?? 0, 2) }}</div>
                        <div class="note">Materials for remediation</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TRC Calculation -->
    <div class="info-section">
        <h3>Total Remediation Cost (TRC)</h3>
        <div class="cost-box" style="border-left-color: #3498db;">
            <h4>TRC = BDC + FRLC + FMC</h4>
            <p style="font-size: 11px; margin: 5px 0;">Annual: ${{ number_format($inspection->trc_annual ?? 0, 2) }}</p>
            <div class="amount" style="color: #3498db;">${{ number_format($inspection->trc_monthly ?? 0, 2) }} <span style="font-size: 12px;">per month</span></div>
        </div>
    </div>

    <!-- ARP & Condition -->
    <div class="info-section">
        <h3>Annual Recurring Price (ARP) & Condition Assessment</h3>
        <div class="breakdown-grid">
            <div class="breakdown-row">
                <div class="breakdown-cell">
                    <div class="cost-box">
                        <h4>ARP (Monthly TRC)</h4>
                        <div class="amount" style="color: #3498db;">${{ number_format($inspection->arp_monthly ?? 0, 2) }}</div>
                    </div>
                </div>
                <div class="breakdown-cell">
                    <div class="cost-box">
                        <h4>Condition Score (from CPI)</h4>
                        <div class="amount" style="color: #3498db;">{{ $inspection->condition_score ?? 0 }}/100</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Dual-Gate Tier Assignment -->
    <div class="info-section">
        <h3>Dual-Gate Tier Assignment</h3>
        <div class="breakdown-grid">
            <div class="breakdown-row">
                <div class="breakdown-cell">
                    <div class="tier-box">
                        <h5>Gate 1: Condition-Based</h5>
                        <div class="tier-value">{{ $inspection->tier_score ?? 'N/A' }}</div>
                    </div>
                </div>
                <div class="breakdown-cell">
                    <div class="tier-box">
                        <h5>Gate 2: ARP Cost Pressure</h5>
                        <div class="tier-value">{{ $inspection->tier_arp ?? 'N/A' }}</div>
                    </div>
                </div>
                <div class="breakdown-cell">
                    <div class="tier-box" style="border-color: #27ae60; border-width: 2px;">
                        <h5>Final Tier (Max)</h5>
                        <div class="tier-value" style="color: #27ae60;">{{ $inspection->tier_final ?? 'N/A' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Final Pricing -->
    <div class="info-section">
        <h3>Final Pricing with Multiplier</h3>
        <div class="final-price">
            <h3>ARP × Tier Multiplier ({{ number_format($inspection->multiplier_final ?? 1, 2) }})</h3>
            <div class="amount">${{ number_format($inspection->arp_equivalent_final ?? 0, 2) }}</div>
            <div class="period">per month</div>
            <p style="font-size: 10px; margin-top: 8px; opacity: 0.9;">Floor Price: ${{ number_format($inspection->base_package_price_snapshot ?? 0, 2) }}/month</p>
        </div>
    </div>

    <!-- Per-Unit Breakdown -->
    @if($inspection->units_for_calculation > 1)
    <div class="info-section">
        <h3>Per-Unit Cost Breakdown ({{ $inspection->units_for_calculation }} Units)</h3>
        <table>
            <thead>
                <tr>
                    <th>Cost Component</th>
                    <th class="text-right">Total Annual</th>
                    <th class="text-right">Per Unit Annual</th>
                    <th class="text-right">Per Unit Monthly</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>BDC</strong></td>
                    <td class="text-right">${{ number_format($inspection->bdc_annual ?? 0, 2) }}</td>
                    <td class="text-right">${{ number_format($inspection->bdc_per_unit_annual ?? 0, 2) }}</td>
                    <td class="text-right">${{ number_format(($inspection->bdc_per_unit_annual ?? 0) / 12, 2) }}</td>
                </tr>
                <tr>
                    <td><strong>FRLC</strong></td>
                    <td class="text-right">${{ number_format($inspection->frlc_annual ?? 0, 2) }}</td>
                    <td class="text-right">${{ number_format($inspection->frlc_per_unit_annual ?? 0, 2) }}</td>
                    <td class="text-right">${{ number_format(($inspection->frlc_per_unit_annual ?? 0) / 12, 2) }}</td>
                </tr>
                <tr>
                    <td><strong>FMC</strong></td>
                    <td class="text-right">${{ number_format($inspection->fmc_annual ?? 0, 2) }}</td>
                    <td class="text-right">${{ number_format($inspection->fmc_per_unit_annual ?? 0, 2) }}</td>
                    <td class="text-right">${{ number_format(($inspection->fmc_per_unit_annual ?? 0) / 12, 2) }}</td>
                </tr>
                <tr style="background: #ecf0f1;">
                    <td><strong>TRC</strong></td>
                    <td class="text-right"><strong>${{ number_format($inspection->trc_annual ?? 0, 2) }}</strong></td>
                    <td class="text-right"><strong>${{ number_format($inspection->trc_per_unit_annual ?? 0, 2) }}</strong></td>
                    <td class="text-right"><strong>${{ number_format(($inspection->trc_per_unit_annual ?? 0) / 12, 2) }}</strong></td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <td><strong>Final Price (with multiplier)</strong></td>
                    <td class="text-right"><strong>${{ number_format(($inspection->arp_equivalent_final ?? 0) * 12, 2) }}</strong></td>
                    <td class="text-right"><strong>${{ number_format((($inspection->arp_equivalent_final ?? 0) * 12) / $inspection->units_for_calculation, 2) }}</strong></td>
                    <td class="text-right"><strong>${{ number_format($inspection->final_monthly_per_unit ?? 0, 2) }}</strong></td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endif

    <!-- Assessment Notes -->
    @if($inspection->summary || $inspection->recommendations || $inspection->risk_summary)
    <div class="info-section">
        <h3>Inspector Assessment</h3>
        @if($inspection->summary)
        <div style="margin-bottom: 10px;">
            <h4 style="font-size: 11px; color: #2c3e50; margin-bottom: 5px;">Notes:</h4>
            <p style="font-size: 10px;">{{ $inspection->summary }}</p>
        </div>
        @endif
        
        @if($inspection->recommendations)
        <div style="margin-bottom: 10px;">
            <h4 style="font-size: 11px; color: #2c3e50; margin-bottom: 5px;">Recommendations:</h4>
            <p style="font-size: 10px;">{{ $inspection->recommendations }}</p>
        </div>
        @endif
        
        @if($inspection->risk_summary)
        <div style="margin-bottom: 10px;">
            <h4 style="font-size: 11px; color: #e74c3c; margin-bottom: 5px;">Risk Summary:</h4>
            <p style="font-size: 10px;">{{ $inspection->risk_summary }}</p>
        </div>
        @endif
    </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <p><strong>EMURIA Regenerative Property Care</strong></p>
        <p>Generated on {{ date('F d, Y \a\t h:i A') }} | Invoice #{{ $inspection->property?->property_code }}-{{ date('Ymd') }}</p>
        <p>This document contains proprietary pricing calculations and should be kept confidential.</p>
    </div>
</body>
</html>
