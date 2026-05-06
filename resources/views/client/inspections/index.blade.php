@extends('client.layout')

@section('title', ($viewMode ?? 'inspections') === 'quotations' ? 'My Quotations' : 'My Inspections')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    @if(($viewMode ?? 'inspections') === 'quotations')
                        <i class="mdi mdi-file-check-outline me-2"></i>My Quotations
                    @else
                        <i class="mdi mdi-clipboard-check me-2"></i>My Inspections
                    @endif
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Property</th>
                                <th>Status</th>
                                <th>Inspection Fee</th>
                                <th>Date</th>
                                <th>Final Monthly</th>
                                <th>Work Payment</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($inspections as $inspection)
                                <tr>
                                    <td>
                                        <strong>{{ $inspection->property?->property_name ?? 'N/A' }}</strong><br>
                                        <small class="text-muted">{{ $inspection->property?->property_code ?? '' }}</small>
                                    </td>
                                    <td>
                                        @php $status = strtolower((string) ($inspection->status ?? 'pending')); @endphp
                                        @if($status === 'completed')
                                            <span class="badge bg-success">Completed</span>
                                        @elseif($status === 'in_progress')
                                            <span class="badge bg-info text-dark">In Progress</span>
                                        @elseif($status === 'scheduled')
                                            <span class="badge bg-warning text-dark">Scheduled</span>
                                        @else
                                            <span class="badge bg-secondary">{{ ucfirst(str_replace('_', ' ', $status)) }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if(($inspection->inspection_fee_status ?? 'pending') === 'paid')
                                            <span class="badge bg-success">Paid</span>
                                        @else
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if(($inspection->status ?? null) === 'completed')
                                            {{ optional($inspection->completed_date)->format('M d, Y') ?? '-' }}
                                        @else
                                            {{ optional($inspection->scheduled_date)->format('M d, Y') ?? '-' }}
                                        @endif
                                    </td>
                                        @php
                                            $cadenceLabel = match($inspection->work_payment_cadence) {
                                                'per_visit' => 'Visit',
                                                'annual'    => 'Annual',
                                                'monthly'   => 'Monthly',
                                                'full'      => 'Full',
                                                default     => 'Monthly',
                                            };
                                            $displayPrice = $inspection->work_payment_amount > 0
                                                ? $inspection->work_payment_amount
                                                : ($inspection->scientific_final_monthly ?? 0);
                                        @endphp
                                        <td>
                                            @if($displayPrice > 0)
                                                ${{ number_format($displayPrice, 2) }}<br>
                                                <small class="text-muted">{{ $cadenceLabel }}</small>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                    @php
                                        $wps       = $inspection->work_payment_status ?? 'pending';
                                        $cadence   = $inspection->work_payment_cadence;
                                        $payPlan   = $inspection->payment_plan ?? 'full';
                                        $isPaid    = $wps === 'paid';
                                        $isFullyPaid = $inspection->arp_fully_paid_at !== null;
                                        $payAmt    = $inspection->work_payment_amount > 0
                                                        ? $inspection->work_payment_amount
                                                        : ($inspection->scientific_final_monthly ?? 0);
                                        $instAmt   = $inspection->installment_amount > 0
                                                        ? $inspection->installment_amount
                                                        : $payAmt;
                                        $instPaid  = (int) ($inspection->installments_paid ?? 0);
                                        $instTotal = (int) ($inspection->installment_months ?? 0);
                                        $canPay    = ($inspection->status ?? null) === 'completed'
                                                        && $inspection->approved_by_client
                                                        && !$isPaid
                                                        && $payAmt > 0;
                                    @endphp
                                    <td>
                                        @if(($inspection->status ?? null) !== 'completed')
                                            <span class="badge bg-secondary">N/A</span>
                                        @elseif($isFullyPaid)
                                            <span class="badge bg-success">Fully Paid</span>
                                        @elseif($isPaid && $payPlan === 'installment')
                                            <span class="badge bg-info text-dark">{{ $instPaid }}/{{ $instTotal }} Installments</span>
                                        @elseif($isPaid)
                                            <span class="badge bg-success">Paid</span>
                                        @elseif($payAmt > 0)
                                            <span class="badge bg-danger">
                                                Outstanding — ${{ number_format($cadence === 'per_visit' || $payPlan === 'installment' ? $instAmt : $payAmt, 2) }}
                                            </span>
                                        @else
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if(($inspection->status ?? null) === 'completed')
                                            <div class="d-flex flex-wrap gap-1">
                                                <a href="{{ route('client.inspections.report', $inspection->id) }}" class="btn btn-sm btn-info">
                                                    <i class="mdi mdi-eye"></i> Report
                                                </a>
                                                <a href="{{ route('client.inspections.agreement', $inspection->id) }}" class="btn btn-sm {{ $inspection->approved_by_client ? 'btn-success' : 'btn-outline-success' }}">
                                                    <i class="mdi mdi-file-sign"></i>
                                                    {{ $inspection->etogo_signed_at ? 'Finalized' : ($inspection->approved_by_client ? 'Awaiting Countersign' : 'Sign Agreement') }}
                                                </a>
                                                @if($canPay)
                                                    <a href="{{ route('client.inspections.work-payment', $inspection->id) }}" class="btn btn-sm btn-danger">
                                                        <i class="mdi mdi-credit-card"></i>
                                                        @if($cadence === 'per_visit') Pay Visit
                                                        @elseif($payPlan === 'installment') Pay Installment
                                                        @else Pay Now
                                                        @endif
                                                    </a>
                                                @endif
                                            </div>
                                        @elseif(!empty($inspection->active_quotation_id) && in_array(($inspection->quotation_status ?? ''), ['shared', 'client_reviewing', 'approved'], true))
                                            <div class="d-flex flex-wrap gap-1">
                                                @if(($inspection->quotation_status ?? null) === 'approved')
                                                    <div class="d-flex flex-column gap-1">
                                                        <span class="badge bg-warning text-dark px-2 py-2">
                                                            <i class="mdi mdi-clock-outline me-1"></i>Awaiting Admin Finalization
                                                        </span>
                                                        <a href="{{ route('client.inspections.quotation', $inspection->id) }}" class="btn btn-sm btn-link text-muted p-0">
                                                            <small>View approved quotation</small>
                                                        </a>
                                                    </div>
                                                @else
                                                    <a href="{{ route('client.inspections.quotation', $inspection->id) }}" class="btn btn-sm btn-primary">
                                                        <i class="mdi mdi-file-check-outline"></i>
                                                        Review Quotation
                                                    </a>
                                                @endif
                                            </div>
                                        @else
                                            <button class="btn btn-sm btn-secondary text-white border-0" style="opacity: 1; cursor: not-allowed;" disabled>
                                                Awaiting quotation/report
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        @if(($viewMode ?? 'inspections') === 'quotations')
                                            No quotations available yet.
                                        @else
                                            No inspections yet.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($inspections->hasPages())
                    <div class="mt-3">{{ $inspections->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
