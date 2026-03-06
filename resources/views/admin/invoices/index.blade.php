@extends('admin.layout')

@section('title', 'Invoices')
@section('header', 'Invoices')

@section('breadcrumbs')
<li class="breadcrumb-item active" aria-current="page">Invoices</li>
@endsection

@section('content')
<div class="row">
    <div class="col-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <h4 class="card-title mb-0">All Invoices</h4>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="{{ route('invoices.index') }}" class="btn btn-sm {{ request()->has('status') ? 'btn-outline-primary' : 'btn-primary' }}">All <span class="badge bg-light text-dark ms-1">{{ $summary['total'] ?? 0 }}</span></a>
                        <a href="{{ route('invoices.index', ['status' => 'pending']) }}" class="btn btn-sm {{ request('status') === 'pending' ? 'btn-warning' : 'btn-outline-warning' }}">Pending <span class="badge bg-light text-dark ms-1">{{ $summary['pending'] ?? 0 }}</span></a>
                        <a href="{{ route('invoices.index', ['status' => 'paid']) }}" class="btn btn-sm {{ request('status') === 'paid' ? 'btn-success' : 'btn-outline-success' }}">Paid <span class="badge bg-light text-dark ms-1">{{ $summary['paid'] ?? 0 }}</span></a>
                        <a href="{{ route('invoices.index', ['status' => 'overdue']) }}" class="btn btn-sm {{ request('status') === 'overdue' ? 'btn-danger' : 'btn-outline-danger' }}">Overdue <span class="badge bg-light text-dark ms-1">{{ $summary['overdue'] ?? 0 }}</span></a>
                    </div>
                </div>

                <form method="GET" action="{{ route('invoices.index') }}" class="row g-2 mb-3">
                    <div class="col-md-10">
                        <input type="text" class="form-control" name="search" value="{{ request('search') }}" placeholder="Search invoice number, client, property, or notes...">
                    </div>
                    <div class="col-md-2 d-grid">
                        <button type="submit" class="btn btn-primary"><i class="mdi mdi-magnify me-1"></i>Search</button>
                    </div>
                    @if(request()->filled('status'))
                        <input type="hidden" name="status" value="{{ request('status') }}">
                    @endif
                </form>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Client</th>
                                <th>Property</th>
                                <th>Type</th>
                                <th>Total</th>
                                <th>Issue Date</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($invoices as $invoice)
                                <tr>
                                    <td><code>{{ $invoice->invoice_number ?? ('INV-' . $invoice->id) }}</code></td>
                                    <td>
                                        <div>{{ $invoice->user->name ?? 'N/A' }}</div>
                                        <small class="text-muted">{{ $invoice->user->email ?? '' }}</small>
                                    </td>
                                    <td>
                                        <div>{{ $invoice->project?->property?->property_name ?? 'N/A' }}</div>
                                        <small class="text-muted">{{ $invoice->project?->property?->property_code ?? '' }}</small>
                                    </td>
                                    <td>{{ ucfirst((string) ($invoice->type ?? 'general')) }}</td>
                                    <td><strong>${{ number_format((float) ($invoice->total ?? 0), 2) }}</strong></td>
                                    <td>{{ optional($invoice->issue_date)->format('M d, Y') ?? '-' }}</td>
                                    <td>{{ optional($invoice->due_date)->format('M d, Y') ?? '-' }}</td>
                                    <td>
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
                                    </td>
                                    <td>
                                        <a href="{{ route('invoices.show', $invoice->id) }}" class="btn btn-sm btn-info">
                                            <i class="mdi mdi-eye me-1"></i>View
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">No invoices found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($invoices->hasPages())
                    <div class="mt-3">{{ $invoices->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
