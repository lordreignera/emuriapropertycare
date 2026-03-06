@extends('admin.layout')

@section('title', 'Invoice Details')
@section('header', 'Invoice Details')

@section('breadcrumbs')
<li class="breadcrumb-item"><a href="{{ route('invoices.index') }}">Invoices</a></li>
<li class="breadcrumb-item active" aria-current="page">{{ $invoice->invoice_number ?? ('INV-' . $invoice->id) }}</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="card-title mb-0">Invoice {{ $invoice->invoice_number ?? ('INV-' . $invoice->id) }}</h4>
                    @php $status = strtolower((string) ($invoice->status ?? 'pending')); @endphp
                    @if($status === 'paid')
                        <span class="badge bg-success">Paid</span>
                    @elseif($status === 'overdue')
                        <span class="badge bg-danger">Overdue</span>
                    @elseif(in_array($status, ['draft', 'sent', 'partial', 'pending'], true))
                        <span class="badge bg-warning text-dark">Pending</span>
                    @else
                        <span class="badge bg-secondary">{{ ucfirst($status) }}</span>
                    @endif
                </div>

                <div class="table-responsive">
                    <table class="table table-sm">
                        <tbody>
                            <tr>
                                <th style="width: 220px;">Client</th>
                                <td>{{ $invoice->user->name ?? 'N/A' }} ({{ $invoice->user->email ?? 'N/A' }})</td>
                            </tr>
                            <tr>
                                <th>Property</th>
                                <td>{{ $invoice->project?->property?->property_name ?? 'N/A' }} {{ $invoice->project?->property?->property_code ? '(' . $invoice->project->property->property_code . ')' : '' }}</td>
                            </tr>
                            <tr>
                                <th>Type</th>
                                <td>{{ ucfirst((string) ($invoice->type ?? 'general')) }}</td>
                            </tr>
                            <tr>
                                <th>Issue Date</th>
                                <td>{{ optional($invoice->issue_date)->format('M d, Y') ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Due Date</th>
                                <td>{{ optional($invoice->due_date)->format('M d, Y') ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Subtotal</th>
                                <td>${{ number_format((float) ($invoice->subtotal ?? 0), 2) }}</td>
                            </tr>
                            <tr>
                                <th>Tax</th>
                                <td>${{ number_format((float) ($invoice->tax ?? 0), 2) }}</td>
                            </tr>
                            <tr>
                                <th>Total</th>
                                <td><strong>${{ number_format((float) ($invoice->total ?? 0), 2) }}</strong></td>
                            </tr>
                            <tr>
                                <th>Paid Amount</th>
                                <td>${{ number_format((float) ($invoice->paid_amount ?? 0), 2) }}</td>
                            </tr>
                            <tr>
                                <th>Balance</th>
                                <td>${{ number_format((float) ($invoice->balance ?? 0), 2) }}</td>
                            </tr>
                            @if(!empty($invoice->notes))
                            <tr>
                                <th>Notes</th>
                                <td>{{ $invoice->notes }}</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h5 class="mb-3">Line Items</h5>
                @if(is_array($invoice->line_items) && count($invoice->line_items) > 0)
                    <ul class="list-group">
                        @foreach($invoice->line_items as $item)
                            <li class="list-group-item">
                                <div class="fw-semibold">{{ $item['description'] ?? 'Item' }}</div>
                                <small class="text-muted">Qty: {{ $item['quantity'] ?? 1 }} • Unit: ${{ number_format((float) ($item['unit_price'] ?? 0), 2) }}</small>
                                <div><strong>Total: ${{ number_format((float) ($item['total'] ?? 0), 2) }}</strong></div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-muted mb-0">No line items available.</p>
                @endif

                <div class="mt-3">
                    <a href="{{ route('invoices.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="mdi mdi-arrow-left me-1"></i>Back to Invoices
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
