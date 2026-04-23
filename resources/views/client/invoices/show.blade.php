@extends('client.layout')

@section('title', 'Invoice Details')

@section('header', 'Invoice Details')

@section('breadcrumbs')
<li class="breadcrumb-item"><a href="{{ route('client.invoices.index') }}">Invoices</a></li>
<li class="breadcrumb-item active" aria-current="page">{{ $invoice->invoice_number }}</li>
@endsection

@section('content')
@php
    $isInspectionFeeInvoice = ($invoice->type === 'additional');
    $isProjectInvoice = ! $isInspectionFeeInvoice;
@endphp
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            <div class="card-body text-white p-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h3 class="fw-bold mb-1">{{ $invoice->invoice_number }}</h3>
                        <p class="mb-0 opacity-75">{{ $isInspectionFeeInvoice ? 'Pre-inspection fee invoice' : 'Project work invoice breakdown' }}</p>
                    </div>
                    @php
                        $canStartWorkPayment = $isProjectInvoice
                            && in_array($invoice->status, ['draft', 'sent', 'overdue'], true)
                            && $inspection
                            && ($inspection->status === 'completed')
                            && (($inspection->work_payment_status ?? 'pending') !== 'paid');
                        $canPayInstallment = $isProjectInvoice
                            && $inspection
                            && in_array(($inspection->payment_plan ?? 'full'), ['per_visit', 'installment'], true)
                            && (($inspection->work_payment_status ?? 'pending') === 'paid')
                            && ((int) ($inspection->installments_paid ?? 0) < (int) ($inspection->installment_months ?? 1));
                    @endphp
                    <div class="text-end d-flex flex-column align-items-end gap-2">
                        <div class="badge bg-light text-dark fs-5 px-3 py-2">
                            Total: ${{ number_format($invoiceTotal, 2) }}
                        </div>
                        <div class="d-flex gap-2">
                            @if($canStartWorkPayment)
                                <a href="{{ route('client.inspections.work-payment', $inspection) }}" class="btn btn-sm btn-success">
                                    <i class="mdi mdi-credit-card-outline me-1"></i>Pay Now
                                </a>
                            @endif
                            @if($canPayInstallment)
                                <a href="{{ route('client.inspections.pay-installment', $inspection) }}" class="btn btn-sm btn-primary">
                                    <i class="mdi mdi-calendar-check me-1"></i>{{ (($inspection->payment_plan ?? 'full') === 'per_visit') ? 'Pay Next Visit' : 'Pay Remaining Balance' }}
                                </a>
                            @endif
                            <a href="{{ route('client.invoices.download', $invoice) }}" class="btn btn-sm btn-light">
                                <i class="mdi mdi-file-pdf-box me-1"></i>Download PDF
                            </a>
                            <button type="button" onclick="window.print()" class="btn btn-sm btn-outline-light">
                                <i class="mdi mdi-printer me-1"></i>Print
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-light border-0 py-3">
                <h5 class="mb-0 fw-semibold">{{ $isInspectionFeeInvoice ? 'Inspection Fee Breakdown' : 'Invoice Amount Breakdown (Annual Project Cost)' }}</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0 align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th class="text-uppercase small fw-semibold">Component</th>
                                <th class="text-uppercase small fw-semibold text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if($isInspectionFeeInvoice)
                                <tr>
                                    <td>Pre-Inspection Fee</td>
                                    <td class="text-end">${{ number_format($invoiceTotal, 2) }}</td>
                                </tr>
                                <tr>
                                    <td>Inspection Scheduling / Assessment Booking</td>
                                    <td class="text-end">Included</td>
                                </tr>
                            @else
                                <tr>
                                    <td>BDC (Baseline Deterioration Cost)</td>
                                    <td class="text-end">${{ number_format($bdcAnnual, 2) }}</td>
                                </tr>
                                <tr>
                                    <td>FRLC (Findings Remediation Labour Cost)</td>
                                    <td class="text-end">${{ number_format($frlcAnnual, 2) }}</td>
                                </tr>
                                <tr>
                                    <td>FMC (Findings Material Cost)</td>
                                    <td class="text-end">${{ number_format($fmcAnnual, 2) }}</td>
                                </tr>
                                <tr class="table-secondary">
                                    <td class="fw-semibold">TRC (BDC + FRLC + FMC)</td>
                                    <td class="text-end fw-semibold">${{ number_format($trcAnnual, 2) }}</td>
                                </tr>
                                <tr>
                                    <td>Other / Adjustment</td>
                                    <td class="text-end">${{ number_format($otherAdjustment, 2) }}</td>
                                </tr>
                            @endif
                            <tr class="table-primary">
                                <td class="fw-bold">Invoice Total</td>
                                <td class="text-end fw-bold">${{ number_format($invoiceTotal, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-light border-0 py-3">
                <h6 class="mb-0 fw-semibold">Invoice Info</h6>
            </div>
            <div class="card-body">
                <div class="mb-2"><strong>Property:</strong> {{ $invoice->project?->property?->property_name ?? 'N/A' }}</div>
                <div class="mb-2"><strong>Property Code:</strong> {{ $invoice->project?->property?->property_code ?? 'N/A' }}</div>
                @if($isInspectionFeeInvoice)
                    <div class="mb-2"><strong>Selected Inspection Date:</strong> {{ optional($inspection?->scheduled_date)->format('M d, Y h:i A') ?? 'N/A' }}</div>
                    <div class="mb-2"><strong>Paid By (Client):</strong> {{ $invoice->user?->name ?? ($invoice->project?->property?->user?->name ?? 'N/A') }}</div>
                @endif
                <div class="mb-2"><strong>Issue Date:</strong> {{ optional($invoice->issue_date)->format('M d, Y') ?? '-' }}</div>
                <div class="mb-2"><strong>Due Date:</strong> {{ optional($invoice->due_date)->format('M d, Y') ?? '-' }}</div>
                <div class="mb-2"><strong>Paid Amount:</strong> ${{ number_format((float) ($invoice->paid_amount ?? 0), 2) }}</div>
                <div class="mb-2"><strong>Balance:</strong> ${{ number_format((float) ($invoice->balance ?? 0), 2) }}</div>
                <div class="mb-2">
                    <strong>Status:</strong>
                    @if($invoice->status === 'paid')
                        <span class="badge bg-success">Paid</span>
                    @elseif($invoice->status === 'partial')
                        <span class="badge bg-primary">Partially Paid</span>
                    @elseif($invoice->status === 'overdue')
                        <span class="badge bg-danger">Overdue</span>
                    @else
                        <span class="badge bg-warning text-dark">Awaiting Payment</span>
                    @endif
                </div>
            </div>
        </div>

        @if(!$isInspectionFeeInvoice)
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-0 py-3">
                    <h6 class="mb-0 fw-semibold">Pricing References</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Scientific Final</span>
                        <span class="fw-semibold">${{ number_format($scientificFinal, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>ARP Equivalent Final</span>
                        <span class="fw-semibold">${{ number_format($arpEquivalentFinal, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Base Package Floor</span>
                        <span class="fw-semibold">${{ number_format($basePackageFloor, 2) }}</span>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

<div class="row mt-4">
    <div class="col-12">
        <a href="{{ route('client.invoices.index') }}" class="btn btn-outline-secondary">
            <i class="mdi mdi-arrow-left me-1"></i>Back to Invoices
        </a>
    </div>
</div>
@endsection
