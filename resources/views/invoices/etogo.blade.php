<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>ETOGO Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body { margin: 0; font-family: DejaVu Sans, Arial, sans-serif; color: #071428; font-size: 12px; background: #fff; }
        .invoice-page { width: 100%; }
        .brand-bar { background: #163b64; color: #fff; padding: 18px 22px; }
        .brand { font-size: 28px; font-weight: 800; letter-spacing: .5px; }
        .subbar { background: #327bb5; color: #fff; padding: 8px 22px; font-weight: 800; text-transform: uppercase; }
        .section-title { background: #d9ebf7; color: #073763; font-weight: 800; padding: 5px 8px; border: 1px solid #b9d2e5; text-transform: uppercase; }
        .grid { width: 100%; border-collapse: collapse; }
        .grid td, .grid th { border: 1px solid #c8d5df; padding: 6px 8px; vertical-align: top; }
        .no-border td { border: 0; }
        .muted-fill { background: #fff2c8; }
        .green-fill { background: #e2f0d9; }
        .dark-head th { background: #163b64; color: #fff; font-weight: 800; text-align: left; }
        .right { text-align: right; }
        .center { text-align: center; }
        .bold { font-weight: 800; }
        .muted { color: #667085; }
        .italic { font-style: italic; }
        .totals td { border-color: #c8d5df; }
        .balance { background: #e2f0d9; color: #073763; font-size: 16px; font-weight: 800; }
        .mt { margin-top: 16px; }
        .preview-actions { padding: 14px 22px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; gap: 8px; justify-content: flex-end; }
        .preview-actions a, .preview-actions button { border: 1px solid #cbd5e1; background: #fff; color: #0f172a; padding: 8px 12px; border-radius: 6px; text-decoration: none; font-weight: 700; cursor: pointer; }
        .preview-actions .primary { background: #163b64; color: #fff; border-color: #163b64; }
        @media print { .preview-actions { display: none; } }
    </style>
</head>
<body>
@php
    $property = $invoice->project?->property;
    $clientName = $invoice->user?->name ?? $property?->owner_first_name ?? 'Client';
    $clientEmail = $invoice->user?->email ?? $property?->owner_email ?? '';
    $propertyAddress = trim(collect([$property?->property_address, $property?->city, $property?->province, $property?->postal_code])->filter()->implode(', '));
    $subtotal = (float) ($invoice->subtotal ?? 0);
    $tax = (float) ($invoice->tax ?? 0);
    $total = (float) ($invoice->total ?? 0);
    $paid = (float) ($invoice->paid_amount ?? 0);
    $balance = (float) ($invoice->balance ?? max(0, $total - $paid));
@endphp

@if(($mode ?? 'preview') === 'preview')
    <div class="preview-actions">
        <a href="{{ route('properties.show', $property) }}">Back to Property</a>
        <a href="{{ route('properties.process-invoice.download', $property) }}">Download PDF</a>
        <form method="POST" action="{{ route('properties.process-invoice.share', $property) }}" style="margin:0;">
            @csrf
            <button type="submit" class="primary">Share with Client</button>
        </form>
    </div>
@elseif(($mode ?? null) === 'client')
    <div class="preview-actions">
        <a href="{{ route('client.invoices.download', $invoice) }}">Download PDF</a>
        @if($balance > 0)
            <a href="{{ route('client.invoices.payment', $invoice) }}" class="primary">Pay Invoice</a>
        @endif
    </div>
@endif

<div class="invoice-page">
    <div class="brand-bar">
        <div class="brand">ETOGO</div>
    </div>
    <div class="subbar">Proactive Property Stewardship - Service Invoice</div>

    <table class="grid no-border" style="margin-top: 16px;">
        <tr>
            <td style="width: 68%;">
                <div class="section-title">From</div>
                <div style="padding: 6px 2px;">
                    <div class="bold">ETOGO</div>
                    <div>Koinonia Applied Investments Ltd.</div>
                    <div>Email: admin@etogo.ca</div>
                    <div>GST/HST: 70515 7899 RT0001</div>
                    <div class="italic">Every Property Deserves a Steward.</div>
                </div>
            </td>
            <td>
                <div class="section-title">Invoice Details</div>
                <table class="grid">
                    <tr><td class="bold">Invoice Number</td><td class="muted-fill">{{ $invoice->invoice_number }}</td></tr>
                    <tr><td class="bold">Invoice Date</td><td class="muted-fill right">{{ optional($invoice->issue_date)->format('M j, Y') }}</td></tr>
                    <tr><td class="bold">Payment Due</td><td class="muted-fill right">{{ optional($invoice->due_date)->format('M j, Y') }}</td></tr>
                    <tr><td class="bold">Currency</td><td class="muted-fill">CAD</td></tr>
                    <tr><td class="bold">Status</td><td class="muted-fill">{{ ucfirst($invoice->status) }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="section-title mt">Bill To</div>
    <table class="grid">
        <tr><td class="bold" style="width: 180px;">Client Name</td><td class="muted-fill">{{ $clientName }}</td></tr>
        <tr><td class="bold">Property Address</td><td class="muted-fill">{{ $propertyAddress ?: 'Property address pending' }}</td></tr>
        <tr><td class="bold">Email / Telephone</td><td class="muted-fill">{{ trim($clientEmail . ' ' . ($property?->owner_phone ?? '')) ?: 'Contact pending' }}</td></tr>
        <tr><td class="bold">Project Reference</td><td class="muted-fill">{{ $property?->property_code ?? $invoice->project?->project_number }}</td></tr>
    </table>

    <table class="grid mt">
        <thead class="dark-head">
            <tr>
                <th style="width: 34px;">Item</th>
                <th>Service</th>
                <th>Deliverable</th>
                <th>Selected Deliverable</th>
                <th>Description</th>
                <th class="right">Qty</th>
                <th>Unit</th>
                <th class="right">Rate</th>
                <th class="right">Tax</th>
                <th class="right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->line_items ?? [] as $item)
                <tr>
                    <td>{{ $item['item'] ?? $loop->iteration }}</td>
                    <td>{{ $item['service'] ?? ($item['description'] ?? 'Service') }}</td>
                    <td class="green-fill">{{ $item['deliverable'] ?? '-' }}</td>
                    <td class="green-fill">{{ $item['selected_deliverable'] ?? '-' }}</td>
                    <td>{{ $item['description'] ?? '-' }}</td>
                    <td class="right muted-fill">{{ number_format((float) ($item['quantity'] ?? 1), 2) }}</td>
                    <td class="muted-fill">{{ $item['unit'] ?? 'Service' }}</td>
                    <td class="right muted-fill">${{ number_format((float) ($item['rate'] ?? $item['unit_price'] ?? 0), 2) }}</td>
                    <td class="right muted-fill">{{ (int) round(((float) ($item['tax_rate'] ?? 0.05)) * 100) }}%</td>
                    <td class="right">${{ number_format((float) ($item['amount'] ?? $item['total'] ?? 0), 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="grid mt">
        <tr>
            <td style="width: 78%;">
                <div class="section-title">Service Authorization / Notes</div>
                <p class="bold">BILLING RULE - Finding -> Qualified Deliverable -> Property Fact</p>
                <p>A finding is an analytical observation and must not be confused with a Deliverable. Individual findings do not constitute billable services. Collecting, validating, organizing and presenting the Findings Report is a billable Diagnosis service. Once completed and verified, each Qualified Deliverable becomes a Property Fact in the Property Facts Registry.</p>
                @if($invoice->notes)
                    <p class="muted">{{ $invoice->notes }}</p>
                @endif
            </td>
            <td>
                <table class="grid totals">
                    <tr><td>Subtotal</td><td class="right">${{ number_format($subtotal, 2) }}</td></tr>
                    <tr><td>GST (5%)</td><td class="right">${{ number_format($tax, 2) }}</td></tr>
                    <tr><td>Other Approved Taxes</td><td class="right">$0.00</td></tr>
                    <tr><td>Payments / Credits</td><td class="right">${{ number_format($paid, 2) }}</td></tr>
                    <tr class="balance"><td>Balance Due</td><td class="right">${{ number_format($balance, 2) }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="section-title mt">Payment Instructions</div>
    <div style="padding: 10px 2px;">
        <div>E-transfer: admin@etogo.ca</div>
        <div>Cheque payable to: Koinonia Applied Investments Ltd.</div>
        <div>Please include the invoice number with your payment.</div>
    </div>
</div>
</body>
</html>
