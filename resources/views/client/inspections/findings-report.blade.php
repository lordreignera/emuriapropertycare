@extends('client.layout')

@section('title', 'PHAR Findings Report')

@section('content')
@php
    $property = $inspection->property;
    $hasDigitalTwin = $inspection->activeSpatialModels->isNotEmpty() || $inspection->activeMatterportModel;
    $reportDate = $inspection->assessment_finalised_at
        ?? $inspection->completed_date
        ?? $inspection->scheduled_date
        ?? $inspection->created_at;

    $severityBadge = [
        'critical' => 'badge bg-danger',
        'high'     => 'badge bg-warning text-dark',
        'moderate' => 'badge bg-info text-dark',
        'medium'   => 'badge bg-info text-dark',
        'low'      => 'badge bg-secondary',
    ];

    $decisionLabels = [
        'immediate_remediation' => 'Do now',
        'stewardship_monitoring' => 'Defer',
        'declined' => 'Decline',
    ];

    $normalizeDecision = function ($decision) {
        return match ((string) $decision) {
            'commit', 'immediate_remediation' => 'immediate_remediation',
            'defer', 'stewardship_monitoring' => 'stewardship_monitoring',
            'decline', 'declined' => 'declined',
            default => (string) $decision,
        };
    };

    $findingTitle = function ($finding) {
        return $finding->task_question
            ?: $finding->plain_language_definition
            ?: $finding->observed_condition
            ?: 'Property finding';
    };

    $findingRecommendation = function ($finding) {
        $raw = trim((string) (
            $finding->remediation_strategy
            ?: $finding->stewardship_strategy
            ?: $finding->management_strategy
            ?: $finding->plain_language_meaning
            ?: $finding->notes
            ?: ''
        ));

        $badFragments = [
            'an error occurred while processing your diagnosis request',
            'please try again',
            'payment verification failed',
        ];

        foreach ($badFragments as $fragment) {
            if (str_contains(strtolower($raw), $fragment)) {
                $raw = '';
                break;
            }
        }

        if ($raw !== '') {
            return $raw;
        }

        $haystack = strtolower(implode(' ', array_filter([
            $finding->task_question,
            $finding->category,
            $finding->system?->name,
            $finding->subsystem?->name,
            $finding->observed_condition,
        ])));

        if (str_contains($haystack, 'gutter') || str_contains($haystack, 'downspout') || str_contains($haystack, 'drain')) {
            return 'Repair or replace damaged gutter sections, clear the drainage path, verify slope, and confirm downspouts discharge water away from the building.';
        }

        if (str_contains($haystack, 'roof') || str_contains($haystack, 'flashing')) {
            return 'Repair the affected roof or flashing area and verify the weather barrier before the next heavy rain cycle.';
        }

        if (str_contains($haystack, 'electrical') || str_contains($haystack, 'gfci') || str_contains($haystack, 'outlet')) {
            return 'Have a qualified electrical trade partner diagnose the issue, correct the unsafe condition, and verify the circuit is operating safely.';
        }

        if (str_contains($haystack, 'plumbing') || str_contains($haystack, 'leak') || str_contains($haystack, 'moisture')) {
            return 'Trace the moisture source, stop the leak, dry affected materials, and verify no hidden water damage remains.';
        }

        return 'Have ETOGO review this finding, correct the affected property system, and verify the result during stewardship follow-up.';
    };

@endphp

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
            <div>
                <h3 class="mb-1 fw-bold">PHAR Findings Report</h3>
                <div class="text-muted">
                    {{ $property?->property_name ?? 'Property' }}
                    @if($property?->property_code)
                        <span class="mx-1">.</span>{{ $property->property_code }}
                    @endif
                    <span class="mx-1">.</span>
                    {{ optional($reportDate)->format('M d, Y') ?? 'Date pending' }}
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                @if($hasDigitalTwin)
                    <a href="{{ route('inspections.digital-twin', $inspection->id) }}" class="btn btn-outline-primary btn-sm">
                        <i class="mdi mdi-cube-scan me-1"></i>Open Digital Twin
                    </a>
                @endif
                <a href="{{ route('client.inspections.index') }}" class="btn btn-light btn-sm">
                    <i class="mdi mdi-arrow-left me-1"></i>Back to PHAR Assessments
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('warning'))
            <div class="alert alert-warning">{{ session('warning') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if($alreadyCommitted)
            <div class="alert alert-info border-info" style="border-left:4px solid #0dcaf0;">
                <i class="mdi mdi-check-decagram me-1"></i>
                You already submitted your decisions on
                <strong>{{ optional($inspection->client_committed_at)->format('M d, Y H:i') }}</strong>.
                Our team is preparing pricing and a formal remediation proposal.
            </div>
        @endif

        @if($findings->isEmpty())
            <div class="alert alert-warning">No findings have been recorded yet.</div>
        @else
            <section class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
                        <div>
                            <h4 class="fw-bold mb-1">Assessment Report</h4>
                            <p class="text-muted mb-0">
                                This report shows what was found, the supporting photos, why it matters, and what ETOGO recommends.
                            </p>
                        </div>
                        <span class="badge bg-primary px-3 py-2">{{ $findings->count() }} finding{{ $findings->count() === 1 ? '' : 's' }}</span>
                    </div>

                    @include('inspections.partials.phar-executive-dashboard', [
                        'inspection' => $inspection,
                        'property' => $property,
                        'findings' => $findings,
                        'useEvidenceOverview' => true,
                    ])

                    @include('inspections.partials.digital-twin-evidence-summary', ['inspection' => $inspection])

                    <div class="client-report-findings">
                        @foreach($findings as $i => $f)
                            @php
                                $sev = strtolower((string) ($f->severity ?? 'moderate'));
                                $sevClass = $severityBadge[$sev] ?? 'badge bg-secondary';
                                $recommendation = $findingRecommendation($f);
                            @endphp

                            <article class="client-report-finding" id="report-finding-{{ $f->id }}">
                                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                                    <div>
                                        <span class="{{ $sevClass }} me-2">{{ strtoupper($sev) }}</span>
                                        <strong>#{{ $i + 1 }} - {{ $findingTitle($f) }}</strong>
                                        <div class="text-muted small mt-1">
                                            {{ $f->system?->name ?? $f->category ?? 'General property system' }}
                                            @if($f->subsystem?->name)
                                                / {{ $f->subsystem->name }}
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                @include('inspections.partials.finding-understanding', ['f' => $f])

                                @if($recommendation)
                                    <div class="client-recommendation-box">
                                        <div class="fw-bold small text-uppercase mb-1">Recommended action</div>
                                        <div>{{ $recommendation }}</div>
                                    </div>
                                @endif

                                @include('inspections.partials.finding-evidence-photos', [
                                    'f' => $f,
                                    'inspection' => $inspection,
                                    'findingIndex' => $i,
                                ])
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="card border-0 shadow-sm client-decision-card">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
                        <div>
                            <h4 class="fw-bold mb-1">Client Decisions</h4>
                            <p class="text-muted mb-0">
                                This section is separate from the report. Choose what should be priced now, what should be deferred, and what should not be included.
                            </p>
                        </div>
                        @if(!$alreadyCommitted)
                            <span class="badge bg-warning text-dark px-3 py-2">Action needed</span>
                        @endif
                    </div>

                    <div class="alert alert-light border" style="background:#f8fbff;border-left:4px solid #0d6efd;">
                        <div class="small">
                            <strong>Decision guide:</strong>
                            <span class="ms-1"><strong>Do now</strong> means include it in the pricing proposal.</span>
                            <span class="ms-1"><strong>Defer</strong> means acknowledge and monitor or plan later.</span>
                            <span class="ms-1"><strong>Decline</strong> means do not include it.</span>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('client.inspections.commit-findings', $inspection->id) }}" @if($alreadyCommitted) style="opacity:.78;pointer-events:none;" @endif>
                        @csrf

                        <div class="client-decision-list">
                            @foreach($findings as $i => $f)
                                @php
                                    $existing = $existingDecisions[$f->id] ?? null;
                                    $currentDecision = $normalizeDecision(old("decisions.$i.decision", optional($existing)->decision));
                                    $existingDecisionLabel = $decisionLabels[$currentDecision] ?? ucwords(str_replace('_', ' ', $currentDecision));
                                    $sev = strtolower((string) ($f->severity ?? 'moderate'));
                                    $sevClass = $severityBadge[$sev] ?? 'badge bg-secondary';
                                    $recommendation = $findingRecommendation($f);
                                @endphp

                                <div class="client-decision-item">
                                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                                        <div>
                                            <span class="{{ $sevClass }} me-2">{{ strtoupper($sev) }}</span>
                                            <strong>#{{ $i + 1 }} - {{ $findingTitle($f) }}</strong>
                                            @if($recommendation)
                                                <div class="text-muted small mt-1">{{ \Illuminate\Support\Str::limit($recommendation, 160) }}</div>
                                            @endif
                                        </div>
                                        @if($existing)
                                            <span class="badge bg-light text-dark border">
                                                Saved: <strong class="ms-1">{{ strtoupper($existingDecisionLabel) }}</strong>
                                            </span>
                                        @endif
                                    </div>

                                    <input type="hidden" name="decisions[{{ $i }}][finding_id]" value="{{ $f->id }}">

                                    <div class="row g-3 align-items-end">
                                        <div class="col-lg-4">
                                            <label class="form-label small fw-semibold mb-1">Decision</label>
                                            <select name="decisions[{{ $i }}][decision]" class="form-select" required>
                                                <option value="">Choose decision</option>
                                                <option value="commit" @selected($currentDecision === 'immediate_remediation')>Do now - include in proposal</option>
                                                <option value="defer" @selected($currentDecision === 'stewardship_monitoring')>Defer - monitor or plan later</option>
                                                <option value="decline" @selected($currentDecision === 'declined')>Decline - do not include</option>
                                            </select>
                                        </div>
                                        <div class="col-lg-8">
                                            <label class="form-label small fw-semibold mb-1">Notes <span class="text-muted">(optional)</span></label>
                                            <input type="text"
                                                   name="decisions[{{ $i }}][notes]"
                                                   class="form-control"
                                                   value="{{ old("decisions.$i.notes", optional($existing)->client_notes) }}"
                                                   maxlength="1000"
                                                   placeholder="Add context for the ETOGO team">
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        @if(!$alreadyCommitted)
                            <div class="d-flex justify-content-end gap-2 mt-4">
                                <a href="{{ route('client.inspections.index') }}" class="btn btn-light">Cancel</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="mdi mdi-check-circle-outline me-1"></i>
                                    Submit My Decisions
                                </button>
                            </div>
                        @endif
                    </form>
                </div>
            </section>
        @endif
    </div>
</div>

<style>
.client-report-findings,
.client-decision-list {
    display: grid;
    gap: 14px;
}

.client-report-finding,
.client-decision-item {
    border: 1px solid #dfe6ef;
    border-radius: 8px;
    background: #ffffff;
    padding: 16px;
}

.client-report-finding {
    border-left: 4px solid #1769e8;
}

.client-decision-card {
    border-left: 4px solid #198754 !important;
}

.client-decision-item {
    background: #fbfcfe;
}

.client-recommendation-box {
    margin-top: 12px;
    padding: 12px 14px;
    border-left: 4px solid #198754;
    border-radius: 8px;
    background: #f1fbf5;
    color: #163c28;
}

</style>
@endsection
