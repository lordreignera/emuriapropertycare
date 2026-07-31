@extends('admin.layout')

@section('title', 'Findings Preview')

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
    $alreadyShared = in_array($inspection->status, ['findings_shared', 'client_committed', 'estimation_in_progress', 'estimation_completed', 'quotation_shared', 'quotation_approved', 'completed', 'approved'], true);
@endphp
<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
                    <div>
                        <h4 class="card-title mb-0">
                            <i class="mdi mdi-clipboard-text-search-outline me-2 text-primary"></i>
                            Findings Preview — {{ $property?->property_name ?? 'Property' }}
                        </h4>
                        <p class="text-muted small mb-0">
                            This is exactly how the client will see the findings report. Check that each finding reads clearly
                            in plain language &mdash; what it is, what was found, why it matters, and what you recommend &mdash; then share it.
                        </p>
                    </div>
                    <div class="d-flex gap-2">
                        @if($hasDigitalTwin)
                            <a href="{{ route('inspections.digital-twin', $inspection->id) }}" class="btn btn-outline-primary btn-sm">
                                <i class="mdi mdi-cube-scan me-1"></i>Open Digital Twin
                            </a>
                        @endif
                        <a href="{{ route('inspections.create', ['property_id' => $inspection->property_id]) }}" class="btn btn-light btn-sm">
                            <i class="mdi mdi-pencil-outline me-1"></i>Edit Findings
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
                        <i class="mdi mdi-check-decagram me-1"></i>
                        This findings report has already been shared with the client
                        @if($inspection->findings_report_shared_at)
                            on <strong>{{ optional($inspection->findings_report_shared_at)->format('M d, Y H:i') }}</strong>.
                        @else
                            .
                        @endif
                    </div>
                @else
                    <div class="alert alert-light border" style="background:#f8fbff;border-left:4px solid #0d6efd;">
                        <div class="d-flex">
                            <i class="mdi mdi-information-outline me-2 text-primary fs-5"></i>
                            <div class="small">
                                <strong>Before you share:</strong> the client sees no pricing here — only what was found and
                                what it means for them. Pricing is prepared later, after the client commits to the items they
                                want addressed.
                            </div>
                        </div>
                    </div>
                @endif

                @include('inspections.partials.digital-twin-evidence-summary', ['inspection' => $inspection])

                @if($findings->isEmpty())
                    <div class="alert alert-warning">
                        No findings have been captured yet.
                        <a href="{{ route('inspections.create', ['property_id' => $inspection->property_id]) }}">Go back and add findings</a>.
                    </div>
                @else
                    @include('inspections.partials.phar-executive-dashboard', [
                        'inspection' => $inspection,
                        'property' => $property,
                        'findings' => $findings,
                    ])

                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                        <div>
                            <h5 class="mb-0 fw-bold">Finding Evidence Appendix</h5>
                            <p class="text-muted small mb-0">Detailed plain-language notes and photos that support the executive dashboard.</p>
                        </div>
                    </div>

                    @foreach($findings as $i => $f)
                        @php
                            $sev = strtolower((string)($f->severity ?? 'moderate'));
                            $sevClass = $severityBadge[$sev] ?? 'badge bg-secondary';
                        @endphp
                        <div class="card mb-3 border">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <span class="{{ $sevClass }} me-2">{{ strtoupper($sev) }}</span>
                                        <strong>#{{ $i + 1 }} — {{ $f->task_question ?: ($f->plain_language_definition ?? 'Finding') }}</strong>
                                        @if($f->system?->name ?? null)
                                            <span class="text-muted small ms-2">
                                                <i class="mdi mdi-home-variant-outline"></i>
                                                {{ $f->system?->name }}@if($f->subsystem?->name) / {{ $f->subsystem?->name }}@endif
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                @include('inspections.partials.finding-understanding', ['f' => $f])
                                @include('inspections.partials.finding-affected-areas', ['f' => $f])
                                @include('inspections.partials.finding-evidence-photos', ['f' => $f, 'inspection' => $inspection, 'findingIndex' => $i])
                            </div>
                        </div>
                    @endforeach

                    @if($alreadyShared)
                        <div class="d-flex justify-content-end gap-2 mt-3">
                            <a href="{{ route('inspections.assessment-report', $inspection->id) }}" class="btn btn-outline-primary">
                                <i class="mdi mdi-file-document-check-outline me-1"></i>View PHAR Report
                            </a>
                            <a href="{{ route('inspections.show', $inspection->id) }}" class="btn btn-primary">
                                <i class="mdi mdi-eye-outline me-1"></i>View Diagnosis
                            </a>
                        </div>
                    @elseif($inspection->assessment_finalised_at)
                        <div class="d-flex justify-content-end gap-2 mt-3">
                            <a href="{{ route('inspections.assessment-report', $inspection->id) }}" class="btn btn-primary">
                                <i class="mdi mdi-file-document-check-outline me-1"></i>Open PHAR Diagnosis Report
                            </a>
                        </div>
                    @else
                        <div class="d-flex justify-content-end gap-2 mt-3">
                            <a href="{{ route('inspections.create', ['property_id' => $inspection->property_id]) }}" class="btn btn-light">
                                <i class="mdi mdi-pencil-outline me-1"></i>Keep editing
                            </a>
                            <form method="POST" action="{{ route('inspections.finalise-assessment', $inspection->id) }}"
                                  onsubmit="return confirm('Finalise this diagnosis? The findings will be locked and the official PHAR diagnosis report will be generated. You can reopen it for edits until it is shared with the client.');">
                                @csrf
                                <button type="submit" class="btn btn-primary">
                                    <i class="mdi mdi-check-decagram me-1"></i>Finalise Diagnosis &amp; Generate Report
                                </button>
                            </form>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
