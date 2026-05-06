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
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-white text-primary fs-6 px-3 py-2">
                            {{ $projects->count() }} {{ Str::plural('Project', $projects->count()) }}
                        </span>
                        <a href="{{ route('client.service-requests.create') }}" class="btn btn-light btn-sm">
                            <i class="mdi mdi-alert-circle me-1"></i> Report Issue
                        </a>
                    </div>
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
                $insp = $project->inspections->sortByDesc('completed_date')->first();

                // Agreement signing state
                $clientSigned       = $insp && $insp->approved_by_client;
                $etogoCountersigned = $insp && $insp->etogo_signed_at;
                $fullySigned        = $clientSigned && $etogoCountersigned;

                // Dates ONLY from the fully-signed contract (work schedule first/last visit)
                // planned_start_date and target_completion_date are set from the work schedule
                // which is locked once the agreement is countersigned — these ARE the contract dates.
                $startDate = $fullySigned && $insp->planned_start_date
                    ? $insp->planned_start_date
                    : null;
                $endDate   = $fullySigned && $insp->target_completion_date
                    ? $insp->target_completion_date
                    : null;

                // Effective status — use inspection progress as source-of-truth
                // (project DB status may lag behind if completeProject was called on an older record)
                $progDoneCheck = !empty($insp?->completed_finding_ids);
                $effectiveStatus = match(true) {
                    $progDoneCheck                        => 'completed',
                    $project->status === 'completed'      => 'completed',
                    $project->status === 'on_hold'        => 'on_hold',
                    $project->status === 'cancelled'      => 'cancelled',
                    $project->status === 'active'         => 'active',
                    default                               => $project->status ?? 'pending',
                };
                $status = $effectiveStatus;
                $statusClass = match($status) {
                    'active'    => 'success',
                    'completed' => 'info',
                    'on_hold'   => 'warning',
                    'cancelled' => 'danger',
                    default     => 'secondary',
                };

                // Payment display — use payment_plan as primary source of truth
                $payPlan  = $insp?->payment_plan ?? null;
                $cadence  = $insp?->work_payment_cadence;
                $payLabel = match($payPlan ?? $cadence) {
                    'per_visit'   => 'Per Visit',
                    'installment' => 'Installment',
                    'full'        => 'Full Payment',
                    'monthly'     => 'Monthly',
                    'annual'      => 'Annual',
                    default       => 'Per Visit',
                };
                $payAmount = $insp?->work_payment_amount > 0
                    ? $insp->work_payment_amount
                    : (in_array($payPlan ?? $cadence, ['per_visit'])
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
                $paymentPlan      = $payPlan ?? 'full';
                $installAmt       = $insp?->installment_amount > 0 ? $insp->installment_amount : $payAmount;

                // Show Pay button whenever there is an outstanding balance (not when fully paid)
                $canPay = $clientSigned && $hasPayment && !$isFullyPaid;

                // Payment totals from controller-computed attributes
                $totalProjectCost = (float) ($insp?->payment_total_cost ?? 0);
                $paidSoFar        = (float) ($insp?->payment_paid_so_far ?? 0);
                $balance          = (float) ($insp?->payment_balance ?? 0);
                $hasBalance       = (bool)  ($insp?->payment_has_balance ?? false);
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
                                        {{ $startDate ? $startDate->format('M d, Y') : '—' }}
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="bg-light rounded p-2 text-center">
                                    <div class="small text-muted">End Date</div>
                                    <div class="fw-semibold small">
                                        {{ $endDate ? $endDate->format('M d, Y') : '—' }}
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
                            <div class="d-flex align-items-center mb-1">
                                <i class="mdi mdi-currency-usd text-success me-2"></i>
                                <small>
                                    <span class="text-muted">{{ $payLabel }}:</span>
                                    <strong class="text-success">${{ number_format($payAmount, 2) }}</strong>
                                </small>
                            </div>
                            @if($totalProjectCost > 0)
                            <div class="mb-2 ps-4">
                                <div class="d-flex justify-content-between align-items-center" style="font-size:.78rem;">
                                    <span class="text-muted">
                                        Paid:
                                        <strong class="text-success">${{ number_format($paidSoFar, 2) }}</strong>
                                        / ${{ number_format($totalProjectCost, 2) }}
                                    </span>
                                    @if($hasBalance)
                                        <span class="badge bg-warning text-dark">
                                            ${{ number_format($balance, 2) }} due
                                        </span>
                                    @else
                                        <span class="badge bg-success">Settled</span>
                                    @endif
                                </div>
                                @if($totalProjectCost > 0)
                                @php $paidPct = min(100, round($paidSoFar / $totalProjectCost * 100)); @endphp
                                <div class="progress mt-1" style="height:4px;">
                                    <div class="progress-bar bg-{{ $hasBalance ? 'warning' : 'success' }}"
                                         style="width:{{ $paidPct }}%"></div>
                                </div>
                                @endif
                            </div>
                            @endif
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

                        {{-- Project Progress --}}
                        @if($insp && $insp->pharFindings->isNotEmpty())
                            @php
                                $progPct      = $insp->progress_pct ?? 0;
                                $progResolved = $insp->progress_resolved ?? 0;
                                $progTotal    = $insp->progress_total ?? 0;
                                $progDone     = $insp->progress_done ?? false;
                                $progColor    = $progDone ? 'success' : ($progPct >= 50 ? 'primary' : ($progPct > 0 ? 'warning' : 'secondary'));
                            @endphp
                            <div class="mt-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <small class="fw-semibold text-muted">
                                        <i class="mdi mdi-progress-check me-1"></i>Project Progress
                                    </small>
                                    <small class="fw-bold text-{{ $progColor }}">{{ $progPct }}%</small>
                                </div>
                                <div class="progress" style="height:7px;">
                                    <div class="progress-bar bg-{{ $progColor }}" style="width:{{ $progPct }}%"></div>
                                </div>
                                <div class="d-flex justify-content-between mt-1">
                                    <small class="text-muted">
                                        {{ $progResolved }} / {{ $progTotal }} issues resolved
                                    </small>
                                    @if($progDone)
                                        <small class="text-success fw-semibold">
                                            <i class="mdi mdi-check-circle me-1"></i>Complete
                                        </small>
                                    @endif
                                </div>
                            </div>
                            {{-- Balance warning when project is complete but payment outstanding --}}
                            @if($progDone && $hasBalance)
                            <div class="alert alert-warning border-warning border py-2 px-3 mb-0 mt-2 small d-flex align-items-start gap-2">
                                <i class="mdi mdi-alert-circle text-warning mt-1 flex-shrink-0"></i>
                                <div>
                                    <strong>Outstanding Balance</strong> — Your project is complete but
                                    <strong>${{ number_format($balance, 2) }}</strong> remains unpaid.
                                    @if($canPay)
                                        <a href="{{ route('client.inspections.pay-installment', $insp->id) }}"
                                           class="d-block mt-1 fw-semibold text-warning">
                                            <i class="mdi mdi-credit-card me-1"></i>Pay now →
                                        </a>
                                    @endif
                                </div>
                            </div>
                            @endif
                        @endif

                        {{-- Agreement Status Banner --}}
                        @if($insp)
                            @if(!$progDoneCheck)
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
                            @if($insp && $progDoneCheck && !$clientSigned)
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
                                $totalLogCount = ($insp && $insp->relationLoaded('maintenanceVisitLogs'))
                                    ? $insp->maintenanceVisitLogs->count()
                                    : 0;
                            @endphp
                            @if($insp && $totalLogCount > 0)
                                <a href="{{ route('client.projects.log-sheet', [$project->id, $insp->id]) }}"
                                   class="btn btn-sm btn-outline-success w-100">
                                    <i class="mdi mdi-clipboard-check-outline me-1"></i>
                                    View Work Progress ({{ $totalLogCount }} {{ Str::plural('log', $totalLogCount) }})
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
