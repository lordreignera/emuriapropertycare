<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1f2937; }
        .header { margin-bottom: 20px; }
        .title { font-size: 20px; font-weight: bold; margin: 0 0 6px 0; }
        .meta { margin: 2px 0; }
        .card { border: 1px solid #d1d5db; border-radius: 6px; margin-bottom: 16px; }
        .card-header { background: #f3f4f6; padding: 10px 12px; font-weight: bold; }
        .card-body { padding: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 8px; }
        th { background: #f3f4f6; text-align: left; }
        .text-right { text-align: right; }
        .total-row td { font-weight: bold; background: #eef2ff; }
        .muted { color: #6b7280; }
    </style>
</head>
<body>
    @php
        $isInspectionFeeInvoice = ($invoice->type === 'additional');
    @endphp
    <div class="header">
        <p class="title">Invoice {{ $invoice->invoice_number }}</p>
        <p class="meta"><strong>Property:</strong> {{ $invoice->project?->property?->property_name ?? 'N/A' }}</p>
        <p class="meta"><strong>Property Code:</strong> {{ $invoice->project?->property?->property_code ?? 'N/A' }}</p>
        @if($isInspectionFeeInvoice)
            <p class="meta"><strong>Selected Inspection Date:</strong> {{ optional($inspection?->scheduled_date)->format('M d, Y h:i A') ?? 'N/A' }}</p>
            <p class="meta"><strong>Paid By (Client):</strong> {{ $invoice->user?->name ?? ($invoice->project?->property?->user?->name ?? 'N/A') }}</p>
        @endif
        <p class="meta"><strong>Issue Date:</strong> {{ optional($invoice->issue_date)->format('M d, Y') ?? '-' }}</p>
        <p class="meta"><strong>Due Date:</strong> {{ optional($invoice->due_date)->format('M d, Y') ?? '-' }}</p>
        <p class="meta"><strong>Status:</strong> {{ ucfirst($invoice->status ?? 'sent') }}</p>
    </div>

    <div class="card">
        <div class="card-header">{{ $isInspectionFeeInvoice ? 'Inspection Fee Breakdown' : 'Amount Breakdown (Monthly)' }}</div>
        <div class="card-body">
            <table>
                <thead>
                    <tr>
                        <th>Component</th>
                        <th class="text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @if($isInspectionFeeInvoice)
                        <tr>
                            <td>Pre-Inspection Fee</td>
                            <td class="text-right">${{ number_format($invoiceTotal, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Inspection Scheduling / Assessment Booking</td>
                            <td class="text-right">Included</td>
                        </tr>
                    @else
                        <tr>
                            <td>BDC (Baseline Deterioration Cost)</td>
                            <td class="text-right">${{ number_format($bdcMonthly, 2) }}</td>
                        </tr>
                        <tr>
                            <td>FRLC (Findings Remediation Labour Cost)</td>
                            <td class="text-right">${{ number_format($frlcMonthly, 2) }}</td>
                        </tr>
                        <tr>
                            <td>FMC (Findings Material Cost)</td>
                            <td class="text-right">${{ number_format($fmcMonthly, 2) }}</td>
                        </tr>
                        <tr>
                            <td><strong>TRC (BDC + FRLC + FMC)</strong></td>
                            <td class="text-right"><strong>${{ number_format($trcMonthly, 2) }}</strong></td>
                        </tr>
                        <tr>
                            <td>Other / Adjustment</td>
                            <td class="text-right">${{ number_format($otherAdjustment, 2) }}</td>
                        </tr>
                    @endif
                    <tr class="total-row">
                        <td>Invoice Total</td>
                        <td class="text-right">${{ number_format($invoiceTotal, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <p class="muted">Generated on {{ now()->format('M d, Y H:i') }}</p>
</body>
</html>
