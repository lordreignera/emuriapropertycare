@extends('admin.layout')

@section('title', 'PHAR Diagnosis Report')

@section('content')
@php
    $hasDigitalTwin = $inspection->activeSpatialModels->isNotEmpty() || $inspection->activeMatterportModel;
    $severityBadge = [
        'critical' => 'badge bg-danger',
        'high'     => 'badge bg-warning text-dark',
        'moderate' => 'badge bg-info text-dark',
        'medium'   => 'badge bg-info text-dark',
        'low'      => 'badge bg-secondary',
    ];

    $alreadyShared = $inspection->findings_report_shared_at !== null;

    $counts = [
        'critical' => 0, 'high' => 0, 'moderate' => 0, 'low' => 0,
    ];
    foreach ($findings as $f) {
        $sev = strtolower((string) ($f->severity ?? 'moderate'));
        if ($sev === 'medium') { $sev = 'moderate'; }
        if (isset($counts[$sev])) { $counts[$sev]++; }
    }
@endphp
<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
                    <div>
                        <h4 class="card-title mb-1">
                            <i class="mdi mdi-file-document-check-outline me-2 text-primary"></i>
                            PHAR Diagnosis Report &mdash; {{ $property?->property_name ?? 'Property' }}
                        </h4>
                        <p class="text-muted small mb-0">
                            Official diagnosis of the property's systems and subsystems.
                            @if($inspection->assessment_finalised_at)
                                Finalised on <strong>{{ optional($inspection->assessment_finalised_at)->format('M d, Y H:i') }}</strong>
                                @if($inspection->finalisedBy) by <strong>{{ $inspection->finalisedBy->name }}</strong>@endif.
                            @endif
                        </p>
                    </div>
                    <div class="d-flex gap-2">
                        @if($hasDigitalTwin)
                            <a href="{{ route('inspections.digital-twin', $inspection->id) }}" class="btn btn-outline-primary btn-sm">
                                <i class="mdi mdi-cube-scan me-1"></i>Open Digital Twin
                            </a>
                        @endif
                        <a href="{{ route('client.inspections.findings-report', $inspection->id) }}" target="_blank" class="btn btn-light btn-sm">
                            <i class="mdi mdi-account-eye-outline me-1"></i>View client version
                        </a>
                        <a href="{{ route('inspections.index') }}" class="btn btn-light btn-sm">
                            <i class="mdi mdi-arrow-left me-1"></i>Back to list
                        </a>
                    </div>
                </div>

                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif
                @if(session('info'))
                    <div class="alert alert-info">{{ session('info') }}</div>
                @endif

                @if($alreadyShared)
                    <div class="alert alert-info border-info" style="border-left:4px solid #0dcaf0;">
                        <i class="mdi mdi-send-check-outline me-1"></i>
                        This report was shared with the client on
                        <strong>{{ optional($inspection->findings_report_shared_at)->format('M d, Y H:i') }}</strong>.
                        It is now locked.
                    </div>
                @else
                    <div class="alert alert-light border" style="background:#f6fff8;border-left:4px solid #198754;">
                        <i class="mdi mdi-lock-check-outline me-1 text-success"></i>
                        <span class="small">
                            The diagnosis is <strong>finalised and locked</strong>. Review it below, then share the
                            client-facing report. Need to change something? Reopen it first.
                        </span>
                    </div>
                @endif

                @include('inspections.partials.digital-twin-evidence-summary', ['inspection' => $inspection])

                @if($findings->isEmpty())
                    <div class="alert alert-warning">No findings were captured for this diagnosis.</div>
                @else
                    @include('inspections.partials.phar-executive-dashboard', [
                        'inspection' => $inspection,
                        'property' => $property,
                        'findings' => $findings,
                    ])

                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                        <div>
                            <h5 class="mb-0 fw-bold">Diagnosis Detail Appendix</h5>
                            <p class="text-muted small mb-0">Inspector notes, client education, and evidence photos for each PHAR finding.</p>
                        </div>
                    </div>

                    @foreach($findings as $i => $f)
                        @php
                            $sev = strtolower((string)($f->severity ?? 'moderate'));
                            $sevClass = $severityBadge[$sev] ?? 'badge bg-secondary';
                            $internalNotes = trim((string) ($f->notes ?? ''));
                        @endphp
                        <div class="card mb-3 border">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2 flex-wrap gap-1">
                                    <div>
                                        <span class="{{ $sevClass }} me-2">{{ strtoupper($sev) }}</span>
                                        <strong>#{{ $i + 1 }} — {{ $f->task_question ?: ($f->plain_language_definition ?? 'Finding') }}</strong>
                                    </div>
                                    @if($f->system?->name ?? null)
                                        <span class="text-muted small">
                                            <i class="mdi mdi-home-variant-outline"></i>
                                            {{ $f->system?->name }}@if($f->subsystem?->name) / {{ $f->subsystem?->name }}@endif
                                        </span>
                                    @endif
                                </div>

                                @include('inspections.partials.finding-understanding', ['f' => $f])
                                @include('inspections.partials.finding-affected-areas', ['f' => $f])
                                @include('inspections.partials.finding-evidence-photos', ['f' => $f, 'inspection' => $inspection, 'findingIndex' => $i])

                                @if($internalNotes !== '')
                                    <div class="mt-2 p-2 px-3 rounded" style="background:#fffdf3;border-left:4px solid #d4a017;">
                                        <div class="fw-bold small text-uppercase mb-1" style="color:#a07d10;letter-spacing:.04em;">
                                            <i class="mdi mdi-note-text-outline me-1"></i>Internal notes (not shown to client)
                                        </div>
                                        <div class="small">{{ $internalNotes }}</div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach

                    <div class="d-flex justify-content-between align-items-center gap-2 mt-4 flex-wrap">
                        <div>
                            @unless($alreadyShared)
                                @if(Auth::user()->hasAnyRole(['Super Admin', 'Administrator', 'Project Manager']))
                                <form method="POST" action="{{ route('inspections.reopen-assessment', $inspection->id) }}"
                                      onsubmit="return confirm('Reopen this diagnosis for editing? It will return to the in-progress state.');">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-secondary">
                                        <i class="mdi mdi-lock-open-variant-outline me-1"></i>Reopen to Edit
                                    </button>
                                </form>
                                @endif
                            @endunless
                        </div>
                        <div class="d-flex gap-2">
                            @if($alreadyShared)
                                <a href="{{ route('client.inspections.findings-report', $inspection->id) }}" target="_blank" class="btn btn-outline-primary">
                                    <i class="mdi mdi-eye-outline me-1"></i>View Shared Report
                                </a>
                                @if(in_array($inspection->status, ['client_committed', 'estimation_in_progress']))
                                    <a href="{{ route('inspections.estimation', $inspection->id) }}" class="btn btn-success fw-bold">
                                        <i class="mdi mdi-cash-plus me-1"></i>Assign &amp; Cost Work
                                    </a>
                                @endif
                            @else
                                <form method="POST" action="{{ route('inspections.share-findings-report', $inspection->id) }}"
                                      onsubmit="return confirm('Share this diagnosis report with the client? They will be able to review the findings and commit to items for remediation.');">
                                    @csrf
                                    <button type="submit" class="btn btn-primary">
                                        <i class="mdi mdi-send-check-outline me-1"></i>Share Report with Client
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
