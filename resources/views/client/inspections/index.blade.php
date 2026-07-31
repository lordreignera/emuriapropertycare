@extends('client.layout')

@section('title', ($viewMode ?? 'inspections') === 'quotations' ? 'Remediation Proposals' : 'PHAR Diagnoses')

@section('content')
@if(($viewMode ?? 'inspections') !== 'quotations' && !empty($findingsReadyInspections) && $findingsReadyInspections->isNotEmpty())
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm client-report-ready-card">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
                    <div class="d-flex align-items-start gap-3">
                        <div class="client-report-ready-icon">
                            <i class="mdi mdi-file-document-check-outline"></i>
                        </div>
                        <div>
                            <h4 class="mb-1 fw-bold">Your PHAR report is ready</h4>
                            <p class="text-muted mb-0">
                                Open the report to review the diagnosis findings, photos, and plain-language explanation before choosing what should move forward.
                            </p>
                        </div>
                    </div>
                    @if($findingsReadyInspections->count() === 1)
                        @php $readyInspection = $findingsReadyInspections->first(); @endphp
                        <a href="{{ route('client.inspections.findings-report', $readyInspection->id) }}" class="btn btn-primary">
                            <i class="mdi mdi-open-in-new me-1"></i>
                            Open PHAR Report
                        </a>
                    @endif
                </div>

                <div class="row g-3">
                    @foreach($findingsReadyInspections as $inspection)
                        <div class="col-xl-4 col-md-6">
                            <div class="client-report-ready-item">
                                <div class="fw-semibold text-dark">{{ $inspection->property?->property_name ?? 'Property' }}</div>
                                <div class="small text-muted mb-3">
                                    {{ $inspection->property?->property_code ?? 'Diagnosis #' . $inspection->id }}
                                    <span class="mx-1">.</span>
                                    {{ (int) ($inspection->phar_findings_count ?? 0) }} finding{{ (int) ($inspection->phar_findings_count ?? 0) === 1 ? '' : 's' }}
                                </div>
                                <a href="{{ route('client.inspections.findings-report', $inspection->id) }}" class="btn btn-primary btn-sm w-100">
                                    <i class="mdi mdi-file-eye-outline me-1"></i>
                                    Open PHAR Report
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    @if(($viewMode ?? 'inspections') === 'quotations')
                        <i class="mdi mdi-file-check-outline me-2"></i>Remediation Proposals
                    @else
                        <i class="mdi mdi-clipboard-check me-2"></i>PHAR Diagnoses
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
                                <th>PHAR Fee</th>
                                <th>Date</th>
                                <th>Approved Amount</th>
                                <th>Remediation Payment</th>
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
                                        @elseif(!empty($inspection->findings_report_shared_at) && empty($inspection->client_committed_at))
                                            <span class="badge bg-primary">Findings Ready for Review</span>
                                        @elseif(!empty($inspection->client_committed_at) && empty($inspection->active_quotation_id))
                                            <span class="badge bg-info text-dark">Proposal Being Prepared</span>
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
                                        @if($inspection->activeSpatialModels->isNotEmpty() || $inspection->activeMatterportModel)
                                            <div class="d-flex flex-wrap gap-1 mb-1">
                                                <a href="{{ route('inspections.digital-twin', $inspection->id) }}" class="btn btn-sm btn-outline-primary">
                                                    <i class="mdi mdi-cube-scan"></i>
                                                    Open Digital Twin
                                                </a>
                                            </div>
                                        @endif

                                        @if(($inspection->status ?? null) === 'completed')
                                            <div class="d-flex flex-wrap gap-1">
                                                <a href="{{ route('client.inspections.report', $inspection->id) }}" class="btn btn-sm btn-info">
                                                    <i class="mdi mdi-eye"></i> Report
                                                </a>
                                                <a href="{{ route('client.inspections.agreement', $inspection->id) }}" class="btn btn-sm {{ $inspection->approved_by_client ? 'btn-success' : 'btn-outline-success' }}">
                                                    <i class="mdi mdi-file-sign"></i>
                                                    {{ $inspection->etogo_signed_at ? 'Finalized' : ($inspection->approved_by_client ? 'Awaiting ETOGO Signoff' : 'Sign Agreement') }}
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
                                                            <small>View approved proposal</small>
                                                        </a>
                                                    </div>
                                                @else
                                                    <a href="{{ route('client.inspections.quotation', $inspection->id) }}" class="btn btn-sm btn-primary">
                                                        <i class="mdi mdi-file-check-outline"></i>
                                                        Review Proposal
                                                    </a>
                                                @endif
                                            </div>
                                        @elseif(!empty($inspection->findings_report_shared_at))
                                            <div class="d-flex flex-wrap gap-1">
                                                @if(empty($inspection->client_committed_at))
                                                    <a href="{{ route('client.inspections.findings-report', $inspection->id) }}" class="btn btn-sm btn-primary">
                                                        <i class="mdi mdi-file-eye-outline"></i>
                                                        Open PHAR Report
                                                    </a>
                                                @else
                                                    <div class="d-flex flex-column gap-1">
                                                        <span class="badge bg-info text-dark px-2 py-2">
                                                            <i class="mdi mdi-clock-outline me-1"></i>Proposal Being Prepared
                                                        </span>
                                                        <a href="{{ route('client.inspections.findings-report', $inspection->id) }}" class="btn btn-sm btn-link text-muted p-0">
                                                            <small>View my findings decisions</small>
                                                        </a>
                                                    </div>
                                                @endif
                                            </div>
                                        @else
                                            <button class="btn btn-sm btn-secondary text-white border-0" style="opacity: 1; cursor: not-allowed;" disabled>
                                                Awaiting proposal/report
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        @if(($viewMode ?? 'inspections') === 'quotations')
                                            No remediation proposals available yet.
                                        @else
                                            No PHAR assessments yet.
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
<style>
.client-report-ready-card {
    border-left: 4px solid #1769e8 !important;
    background: #f8fbff !important;
}

.client-report-ready-icon {
    width: 48px !important;
    height: 48px !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    flex: 0 0 auto !important;
    border-radius: 8px !important;
    background: #e8f1ff !important;
    color: #1769e8 !important;
}

.client-report-ready-icon i {
    font-size: 1.5rem !important;
}

.client-report-ready-item {
    height: 100% !important;
    padding: 14px !important;
    border: 1px solid #dfe6ef !important;
    border-radius: 8px !important;
    background: #ffffff !important;
}
</style>
@endsection
