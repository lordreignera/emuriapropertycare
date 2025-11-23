@extends('admin.layout')

@section('title', 'Finance Dashboard')

@section('header', 'Finance Dashboard')

@section('breadcrumbs')
<li class="breadcrumb-item active" aria-current="page">Dashboard</li>
@endsection

@section('content')
<div class="row">
    {{-- Revenue Stats --}}
    <div class="col-md-3 stretch-card grid-margin">
        <div class="card bg-gradient-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="font-weight-normal mb-2">Total Revenue</h6>
                        <h2 class="mb-0">${{ number_format($totalRevenue, 2) }}</h2>
                        <p class="text-white-50 mb-0 mt-2 small">All Time</p>
                    </div>
                    <div>
                        <i class="mdi mdi-cash-multiple mdi-48px opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 stretch-card grid-margin">
        <div class="card bg-gradient-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="font-weight-normal mb-2">Monthly Revenue</h6>
                        <h2 class="mb-0">${{ number_format($monthlyRevenue, 2) }}</h2>
                        <p class="text-white-50 mb-0 mt-2 small">{{ now()->format('F Y') }}</p>
                    </div>
                    <div>
                        <i class="mdi mdi-calendar-month mdi-48px opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 stretch-card grid-margin">
        <div class="card bg-gradient-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="font-weight-normal mb-2">Pending Revenue</h6>
                        <h2 class="mb-0">${{ number_format($pendingRevenue, 2) }}</h2>
                        <p class="text-white-50 mb-0 mt-2 small">{{ $pendingInvoices }} Invoices</p>
                    </div>
                    <div>
                        <i class="mdi mdi-clock-alert mdi-48px opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 stretch-card grid-margin">
        <div class="card bg-gradient-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="font-weight-normal mb-2">Subscription Revenue</h6>
                        <h2 class="mb-0">${{ number_format($subscriptionRevenue, 2) }}</h2>
                        <p class="text-white-50 mb-0 mt-2 small">{{ $activeSubscriptions }} Active</p>
                    </div>
                    <div>
                        <i class="mdi mdi-cash-refund mdi-48px opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    {{-- Invoice Statistics --}}
    <div class="col-md-6 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-3">Invoice Statistics</h4>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="d-flex align-items-center">
                            <div class="icon-box bg-success text-white rounded-circle p-3 me-3">
                                <i class="mdi mdi-check-circle mdi-24px"></i>
                            </div>
                            <div>
                                <h3 class="mb-0">{{ $paidInvoices }}</h3>
                                <p class="text-muted mb-0">Paid Invoices</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="d-flex align-items-center">
                            <div class="icon-box bg-warning text-white rounded-circle p-3 me-3">
                                <i class="mdi mdi-clock-alert mdi-24px"></i>
                            </div>
                            <div>
                                <h3 class="mb-0">{{ $pendingInvoices }}</h3>
                                <p class="text-muted mb-0">Pending Invoices</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="d-flex align-items-center">
                            <div class="icon-box bg-danger text-white rounded-circle p-3 me-3">
                                <i class="mdi mdi-alert-circle mdi-24px"></i>
                            </div>
                            <div>
                                <h3 class="mb-0">{{ $overdueInvoices }}</h3>
                                <p class="text-muted mb-0">Overdue Invoices</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="d-flex align-items-center">
                            <div class="icon-box bg-info text-white rounded-circle p-3 me-3">
                                <i class="mdi mdi-file-document mdi-24px"></i>
                            </div>
                            <div>
                                <h3 class="mb-0">{{ $totalInvoices }}</h3>
                                <p class="text-muted mb-0">Total Invoices</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4">
                    <canvas id="invoiceChart" height="120"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="col-md-6 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-3">Quick Actions</h4>
                <div class="d-grid gap-2 mb-4">
                    <a href="{{ route('invoices.index') }}" class="btn btn-outline-primary btn-icon-text">
                        <i class="mdi mdi-file-document btn-icon-prepend"></i> View All Invoices
                    </a>
                    <a href="{{ route('invoices.create') }}" class="btn btn-outline-success btn-icon-text">
                        <i class="mdi mdi-plus-circle btn-icon-prepend"></i> Create Invoice
                    </a>
                    <a href="{{ route('invoices.index', ['status' => 'pending']) }}" class="btn btn-outline-warning btn-icon-text">
                        <i class="mdi mdi-clock-alert btn-icon-prepend"></i> View Pending
                    </a>
                    <a href="{{ route('invoices.index', ['status' => 'overdue']) }}" class="btn btn-outline-danger btn-icon-text">
                        <i class="mdi mdi-alert-circle btn-icon-prepend"></i> View Overdue
                    </a>
                </div>

                <h5 class="mb-3">Financial Summary</h5>
                <div class="financial-summary">
                    <div class="d-flex justify-content-between mb-3 pb-3 border-bottom">
                        <span>Collection Rate</span>
                        <strong class="text-success">{{ $totalInvoices > 0 ? number_format(($paidInvoices / $totalInvoices) * 100, 1) : 0 }}%</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-3 pb-3 border-bottom">
                        <span>Avg Invoice Value</span>
                        <strong>${{ $totalInvoices > 0 ? number_format($totalRevenue / $paidInvoices, 2) : 0 }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-3 pb-3 border-bottom">
                        <span>Active Subscriptions</span>
                        <strong class="text-primary">{{ $activeSubscriptions }}</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Monthly Recurring</span>
                        <strong class="text-info">${{ number_format($subscriptionRevenue, 2) }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Overdue Invoices Alert --}}
@if($overdueInvoices > 0)
<div class="row">
    <div class="col-md-12 grid-margin stretch-card">
        <div class="card border-danger">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="card-title mb-0 text-danger">
                        <i class="mdi mdi-alert-circle me-2"></i>Overdue Invoices Requiring Attention
                    </h4>
                    <span class="badge badge-danger">{{ $overdueInvoices }} Overdue</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Client</th>
                                <th>Amount</th>
                                <th>Due Date</th>
                                <th>Days Overdue</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($overdueInvoicesList as $invoice)
                            <tr>
                                <td><code>{{ $invoice->invoice_number ?? 'INV-' . $invoice->id }}</code></td>
                                <td>
                                    {{ $invoice->user->name ?? 'N/A' }}<br>
                                    <small class="text-muted">{{ $invoice->user->email ?? '' }}</small>
                                </td>
                                <td><strong>${{ number_format($invoice->amount, 2) }}</strong></td>
                                <td>{{ $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('M d, Y') : 'N/A' }}</td>
                                <td>
                                    <span class="badge badge-danger">
                                        {{ $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->diffInDays(now()) : 0 }} days
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('invoices.show', $invoice->id) }}" 
                                           class="btn btn-sm btn-info" title="View Invoice">
                                            <i class="mdi mdi-eye"></i>
                                        </a>
                                        <a href="{{ route('invoices.edit', $invoice->id) }}" 
                                           class="btn btn-sm btn-warning" title="Edit Invoice">
                                            <i class="mdi mdi-pencil"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

{{-- Recent Invoices --}}
<div class="row">
    <div class="col-md-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="card-title mb-0">Recent Invoices</h4>
                    <a href="{{ route('invoices.index') }}" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover" id="recentInvoicesTable">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Client</th>
                                <th>Amount</th>
                                <th>Issue Date</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentInvoices as $invoice)
                            <tr>
                                <td><code>{{ $invoice->invoice_number ?? 'INV-' . $invoice->id }}</code></td>
                                <td>
                                    {{ $invoice->user->name ?? 'N/A' }}<br>
                                    <small class="text-muted">{{ $invoice->user->email ?? '' }}</small>
                                </td>
                                <td><strong>${{ number_format($invoice->amount, 2) }}</strong></td>
                                <td>{{ $invoice->created_at->format('M d, Y') }}</td>
                                <td>{{ $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('M d, Y') : 'N/A' }}</td>
                                <td>
                                    @if($invoice->status == 'paid')
                                    <span class="badge badge-success">Paid</span>
                                    @elseif($invoice->status == 'pending')
                                    <span class="badge badge-warning">Pending</span>
                                    @elseif($invoice->status == 'overdue')
                                    <span class="badge badge-danger">Overdue</span>
                                    @elseif($invoice->status == 'cancelled')
                                    <span class="badge badge-secondary">Cancelled</span>
                                    @else
                                    <span class="badge badge-info">{{ ucfirst($invoice->status) }}</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('invoices.show', $invoice->id) }}" 
                                           class="btn btn-sm btn-info" title="View Invoice">
                                            <i class="mdi mdi-eye"></i>
                                        </a>
                                        <a href="{{ route('invoices.edit', $invoice->id) }}" 
                                           class="btn btn-sm btn-warning" title="Edit Invoice">
                                            <i class="mdi mdi-pencil"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Ensure finance dashboard content is visible */
body.light-theme .content-wrapper {
    background-color: #f4f5f7 !important;
}

body.light-theme .card {
    background-color: #ffffff !important;
    color: #212529 !important;
    border: 1px solid #e3e6f0;
}

body.light-theme .card-title {
    color: #212529 !important;
}

body.light-theme .card-body {
    color: #212529 !important;
}

body.light-theme .table {
    color: #212529 !important;
}

body.light-theme .table thead th {
    color: #212529 !important;
    border-color: #dee2e6 !important;
}

body.light-theme .text-muted {
    color: #6c757d !important;
}

/* Gradient cards */
.bg-gradient-success {
    background: linear-gradient(135deg, #28a745 0%, #218838 100%) !important;
}

.bg-gradient-info {
    background: linear-gradient(135deg, #17a2b8 0%, #138496 100%) !important;
}

.bg-gradient-warning {
    background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%) !important;
}

.bg-gradient-primary {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%) !important;
}

.icon-box {
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function() {
    // Initialize DataTable
    @if($recentInvoices->count() > 0)
    $('#recentInvoicesTable').DataTable({
        "pageLength": 10,
        "order": [[3, "desc"]],
        "columnDefs": [
            { "orderable": false, "targets": [6] }
        ]
    });
    @endif

    // Invoice Status Chart
    const ctx = document.getElementById('invoiceChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Paid', 'Pending', 'Overdue'],
                datasets: [{
                    data: [{{ $paidInvoices }}, {{ $pendingInvoices }}, {{ $overdueInvoices }}],
                    backgroundColor: [
                        'rgba(40, 167, 69, 0.8)',
                        'rgba(255, 193, 7, 0.8)',
                        'rgba(220, 53, 69, 0.8)'
                    ],
                    borderColor: [
                        'rgba(40, 167, 69, 1)',
                        'rgba(255, 193, 7, 1)',
                        'rgba(220, 53, 69, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                }
            }
        });
    }
});
</script>
@endpush
