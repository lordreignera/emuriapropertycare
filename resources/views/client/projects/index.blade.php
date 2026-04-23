@extends('client.layout')

@section('title', 'My Projects')

@section('header', 'My Projects')

@section('breadcrumbs')
<li class="breadcrumb-item active" aria-current="page">Projects</li>
@endsection

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            <div class="card-body text-white p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="fw-bold mb-1">My Projects</h3>
                        <p class="mb-0 opacity-75">Track all active and completed care projects</p>
                    </div>
                    <span class="badge bg-white text-primary fs-6 px-3 py-2">
                        {{ $projects->count() }} {{ Str::plural('Project', $projects->count()) }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

@if($projects->isEmpty())
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-5">
                    <i class="mdi mdi-folder-open-outline text-muted" style="font-size: 4rem;"></i>
                    <h5 class="mt-3 text-muted">No Projects Yet</h5>
                    <p class="text-muted">Projects are created after your property inspection is completed and your care agreement is signed.</p>
                    <a href="{{ route('client.inspections.index') }}" class="btn btn-primary mt-2">
                        <i class="mdi mdi-clipboard-check me-1"></i> View Inspections
                    </a>
                </div>
            </div>
        </div>
    </div>
@else
    <div class="row">
        @foreach($projects as $project)
            @php
                $status = $project->status ?? 'pending';
                $statusClass = match($status) {
                    'active'    => 'success',
                    'completed' => 'info',
                    'on_hold'   => 'warning',
                    'cancelled' => 'danger',
                    default     => 'secondary',
                };

                $insp = $project->inspections->sortByDesc('completed_date')->first();

                // Dates: prefer inspection schedule dates over project dates
                $startDate = optional($insp?->planned_start_date ?? $project->start_date);
                $endDate   = optional($insp?->target_completion_date ?? $project->end_date);

                // Agreement status
                $inspCompleted   = $insp && $insp->status === 'completed';
                $clientSigned    = $insp && $insp->approved_by_client;
                $etogoCountersigned = $insp && $insp->etogo_signed_at;

                // Payment display
                $cadence = $insp?->work_payment_cadence;
                $payLabel = match($cadence) {
                    'per_visit' => 'Per Visit',
                    'monthly'   => 'Monthly',
                    'annual'    => 'Annual',
                    'full'      => 'Full Payment',
                    default     => 'Per Visit',
                };
                $payAmount = $insp?->work_payment_amount > 0
                    ? $insp->work_payment_amount
                    : ($cadence === 'per_visit'
                        ? ($insp?->trc_per_visit ?? $insp?->scientific_final_monthly ?? 0)
                        : ($insp?->scientific_final_monthly ?? 0));
                $hasPayment = $payAmount > 0;

                // Payment status
                $workPayStatus    = $insp?->work_payment_status ?? 'pending';
                $isPaid           = $workPayStatus === 'paid';
                $isFullyPaid      = $insp?->arp_fully_paid_at !== null;
                $installmentsPaid = (int) ($insp?->installments_paid ?? 0);
                $installmentTotal = (int) ($insp?->installment_months ?? 0);
                $nextDue          = $insp?->next_installment_due_date;
                $paymentPlan      = $insp?->payment_plan ?? 'full';
                $installAmt       = $insp?->installment_amount > 0 ? $insp->installment_amount : $payAmount;
                $canPay           = $clientSigned && !$isPaid && $hasPayment;
            @endphp
            <div class="col-md-6 col-xl-4 mb-4">
                <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid var(--bs-{{ $statusClass }}) !important;">
                    {{-- Header --}}
                    <div class="card-header border-0 pb-0 pt-3 px-4">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="fw-bold mb-0">{{ $project->property?->property_name ?? 'N/A' }}</h6>
                                <small class="text-muted">{{ $project->project_number }}</small>
                            </div>
                            <span class="badge bg-{{ $statusClass }}">{{ ucfirst(str_replace('_', ' ', $status)) }}</span>
                        </div>
                    </div>

                    <div class="card-body px-4 pt-3 pb-2">
                        @if($project->title)
                            <p class="text-muted small mb-3">{{ $project->title }}</p>
                        @endif

                        {{-- Dates --}}
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <div class="bg-light rounded p-2 text-center">
                                    <div class="small text-muted">Start Date</div>
                                    <div class="fw-semibold small">
                                        {{ $startDate->format('M d, Y') ?? '—' }}
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="bg-light rounded p-2 text-center">
                                    <div class="small text-muted">End Date</div>
                                    <div class="fw-semibold small">
                                        {{ $endDate->format('M d, Y') ?? '—' }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Address --}}
                        @if($project->property)
                            <div class="d-flex align-items-center mb-2">
                                <i class="mdi mdi-map-marker text-muted me-2"></i>
                                <small class="text-muted">{{ $project->property->property_address ?? '' }}</small>
                            </div>
                        @endif

                        {{-- Payment --}}
                        @if($hasPayment)
                            <div class="d-flex align-items-center mb-2">
                                <i class="mdi mdi-currency-usd text-success me-2"></i>
                                <small>
                                    <span class="text-muted">{{ $payLabel }}:</span>
                                    <strong class="text-success">${{ number_format($payAmount, 2) }}</strong>
                                </small>
                            </div>
                        @else
                            <div class="d-flex align-items-center mb-2">
                                <i class="mdi mdi-currency-usd text-muted me-2"></i>
                                <small class="text-muted">Payment not yet set</small>
                            </div>
                        @endif

                        {{-- Payment Status Banner --}}
                        @if($insp && $clientSigned && $hasPayment)
                            @if($isFullyPaid)
                                <div class="alert alert-success border-0 py-2 px-3 mb-0 mt-2 small">
                                    <i class="mdi mdi-check-all me-1"></i>
                                    <strong>Fully Paid</strong> — {{ $insp->arp_fully_paid_at->format('M d, Y') }}
                                </div>
                            @elseif($paymentPlan === 'installment' && $installmentTotal > 0)
                                <div class="alert {{ $isPaid ? 'alert-success' : 'alert-warning' }} border-0 py-2 px-3 mb-0 mt-2 small">
                                    <i class="mdi mdi-calendar-check me-1"></i>
                                    <strong>Installments:</strong> {{ $installmentsPaid }} / {{ $installmentTotal }} paid
                                    @if(!$isPaid && $nextDue)
                                        <br><span class="text-danger"><i class="mdi mdi-alert-circle me-1"></i>Next due: {{ \Carbon\Carbon::parse($nextDue)->format('M d, Y') }}</span>
                                    @endif
                                </div>
                            @elseif($cadence === 'per_visit')
                                <div class="alert {{ $isPaid ? 'alert-success' : 'alert-danger' }} border-0 py-2 px-3 mb-0 mt-2 small">
                                    @if($isPaid)
                                        <i class="mdi mdi-check-circle me-1"></i>
                                        <strong>Visit payment received</strong>
                                    @else
                                        <i class="mdi mdi-alert-circle me-1"></i>
                                        <strong>Payment outstanding</strong> — ${{ number_format($payAmount, 2) }} per visit
                                    @endif
                                </div>
                            @else
                                <div class="alert {{ $isPaid ? 'alert-success' : 'alert-danger' }} border-0 py-2 px-3 mb-0 mt-2 small">
                                    @if($isPaid)
                                        <i class="mdi mdi-check-circle me-1"></i>
                                        <strong>Payment received</strong>
                                    @else
                                        <i class="mdi mdi-alert-circle me-1"></i>
                                        <strong>Payment outstanding</strong> — ${{ number_format($payAmount, 2) }}
                                    @endif
                                </div>
                            @endif
                        @elseif($insp && $clientSigned && !$hasPayment)
                            <div class="alert alert-light border py-2 px-3 mb-0 mt-2 small">
                                <i class="mdi mdi-information-outline me-1 text-muted"></i>
                                Payment amount not yet configured
                            </div>
                        @endif

                        {{-- Agreement Status Banner --}}
                        @if($insp)
                            @if(!$inspCompleted)
                                <div class="alert alert-light border py-2 px-3 mb-0 mt-2 small">
                                    <i class="mdi mdi-clock-outline me-1 text-warning"></i>
                                    Awaiting inspection completion
                                </div>
                            @elseif(!$clientSigned)
                                <div class="alert alert-warning border-0 py-2 px-3 mb-0 mt-2 small">
                                    <i class="mdi mdi-pen me-1"></i>
                                    <strong>Action required:</strong> Agreement not yet signed
                                </div>
                            @elseif(!$etogoCountersigned)
                                <div class="alert alert-info border-0 py-2 px-3 mb-0 mt-2 small">
                                    <i class="mdi mdi-clock-check-outline me-1"></i>
                                    Signed by you — awaiting Emuria countersignature
                                </div>
                            @else
                                <div class="alert alert-success border-0 py-2 px-3 mb-0 mt-2 small">
                                    <i class="mdi mdi-check-circle me-1"></i>
                                    Agreement fully signed on {{ $insp->etogo_signed_at->format('M d, Y') }}
                                </div>
                            @endif
                        @endif
                    </div>

                    {{-- Footer Actions --}}
                    <div class="card-footer border-0 bg-transparent px-4 pb-3 pt-2">
                        <div class="d-flex flex-column gap-2">
                            {{-- Pay Now button --}}
                            @if($insp && $canPay)
                                <a href="{{ route('client.inspections.work-payment', $insp->id) }}"
                                   class="btn btn-sm btn-danger w-100">
                                    <i class="mdi mdi-credit-card me-1"></i>
                                    @if($paymentPlan === 'installment')
                                        Pay Installment — ${{ number_format($installAmt, 2) }}
                                    @elseif($cadence === 'per_visit')
                                        Pay Visit — ${{ number_format($payAmount, 2) }}
                                    @else
                                        Pay Now — ${{ number_format($payAmount, 2) }}
                                    @endif
                                </a>
                            @endif
                            {{-- Agreement action --}}
                            @if($insp && $inspCompleted && !$clientSigned)
                                <a href="{{ route('client.inspections.agreement', $insp->id) }}"
                                   class="btn btn-sm btn-warning w-100">
                                    <i class="mdi mdi-pen me-1"></i> Sign Agreement
                                </a>
                            @elseif($insp && $clientSigned && !$etogoCountersigned)
                                <a href="{{ route('client.inspections.agreement', $insp->id) }}"
                                   class="btn btn-sm btn-outline-info w-100">
                                    <i class="mdi mdi-eye me-1"></i> View Agreement
                                </a>
                            @elseif($insp && $etogoCountersigned)
                                <a href="{{ route('client.inspections.agreement', $insp->id) }}"
                                   class="btn btn-sm btn-outline-success w-100">
                                    <i class="mdi mdi-file-check me-1"></i> View Signed Agreement
                                </a>
                            @endif

                            {{-- Quotation/Report + Property --}}
                            <div class="d-flex gap-2">
                                @if($insp && ($insp->status ?? null) === 'completed')
                                    <a href="{{ route('client.inspections.report', $insp->id) }}"
                                       class="btn btn-sm btn-outline-primary flex-grow-1">
                                        <i class="mdi mdi-eye me-1"></i> View Report
                                    </a>
                                @elseif($insp && !empty($insp->active_quotation_id) && in_array(($insp->quotation_status ?? ''), ['shared', 'client_reviewing', 'approved'], true))
                                    @if(($insp->quotation_status ?? null) === 'approved')
                                        <div class="d-flex flex-column gap-1 flex-grow-1">
                                            <span class="badge bg-warning text-dark px-2 py-2 text-wrap">
                                                <i class="mdi mdi-clock-outline me-1"></i>Awaiting Admin Finalization
                                            </span>
                                            <a href="{{ route('client.inspections.quotation', $insp->id) }}"
                                               class="btn btn-sm btn-link text-muted p-0">
                                                <small>View approved quotation</small>
                                            </a>
                                        </div>
                                    @else
                                        <a href="{{ route('client.inspections.quotation', $insp->id) }}"
                                           class="btn btn-sm btn-outline-primary flex-grow-1">
                                            <i class="mdi mdi-file-check-outline me-1"></i>
                                            Review Quotation
                                        </a>
                                    @endif
                                @endif
                                <a href="{{ route('client.properties.show', $project->property_id) }}"
                                   class="btn btn-sm btn-outline-secondary">
                                    <i class="mdi mdi-home"></i>
                                </a>
                            </div>

                            @php
                                $completedLogCount = ($insp && $insp->relationLoaded('maintenanceVisitLogs'))
                                    ? $insp->maintenanceVisitLogs->where('status', 'completed')->count()
                                    : 0;
                            @endphp
                            @if($insp && ($insp->status ?? null) === 'completed' && $completedLogCount > 0)
                                <a href="{{ route('client.projects.log-sheet', [$project->id, $insp->id]) }}"
                                   class="btn btn-sm btn-outline-success w-100">
                                    <i class="mdi mdi-clipboard-check-outline me-1"></i>
                                    View Completed Log Sheet ({{ $completedLogCount }})
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
@endsection
