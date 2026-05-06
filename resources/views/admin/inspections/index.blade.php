@extends('admin.layout')

@section('title', 'Inspections')

@section('content')
<div class="content-wrapper">
    <style>
        .inspection-action-row {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .inspection-action-row form {
            margin: 0;
        }

        @media (max-width: 576px) {
            .inspection-action-row > .btn,
            .inspection-action-row > form {
                flex: 1 1 calc(50% - 0.5rem);
            }

            .inspection-action-row > form > .btn {
                width: 100%;
            }
        }
    </style>
    <div class="row">
        <div class="col-lg-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            @if(request('view') === 'pending-etogo')
                                <h4 class="card-title mb-0"><i class="mdi mdi-wrench me-2 text-danger"></i>Pre-Sign Setup</h4>
                                <p class="text-muted small mb-0">Client has signed and paid - assign tools and schedule visits before countersigning</p>
                            @elseif(request('view') === 'needs-schedule')
                                <h4 class="card-title mb-0"><i class="mdi mdi-pen me-2 text-warning"></i>Ready to Countersign</h4>
                                <p class="text-muted small mb-0">Tools assigned and visits scheduled - awaiting your countersignature</p>
                            @elseif(request('view') === 'awaiting-quotation')
                                <h4 class="card-title mb-0"><i class="mdi mdi-timer-sand me-2 text-info"></i>Pre-assessed Properties</h4>
                                <p class="text-muted small mb-0">Includes draft assessments in progress and quotations waiting for client response</p>
                            @else
                                <h4 class="card-title mb-0">Inspections Management</h4>
                                <p class="text-muted small mb-0">Scheduled and paid inspections</p>
                            @endif
                        </div>
                    </div>

                    @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    @endif

                    @if(!request('view'))
                    <ul class="nav nav-pills mb-3" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link {{ request('status') == 'scheduled' ? 'active' : '' }}"
                               href="{{ route('inspections.index', ['status' => 'scheduled']) }}">
                                Scheduled and Paid
                                <span class="badge bg-success ms-1">{{ $scheduledCount }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request('status') == 'in_progress' ? 'active' : '' }}"
                               href="{{ route('inspections.index', ['status' => 'in_progress']) }}">
                                In Progress
                                <span class="badge bg-primary ms-1">{{ $inProgressCount }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request('status') == 'completed' ? 'active' : '' }}"
                               href="{{ route('inspections.index', ['status' => 'completed']) }}">
                                Completed
                                <span class="badge bg-info ms-1">{{ $completedCount }}</span>
                            </a>
                        </li>
                    </ul>
                    @endif

                    <form method="GET" action="{{ route('inspections.index') }}" class="mb-3">
                        <input type="hidden" name="status" value="{{ request('status') }}">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control"
                                   placeholder="Search by property name, code, or city..."
                                   value="{{ request('search') }}">
                            <button type="submit" class="btn btn-primary">
                                <i class="mdi mdi-magnify"></i> Search
                            </button>
                            @if(request('search'))
                            <a href="{{ route('inspections.index', ['status' => request('status')]) }}" class="btn btn-secondary">
                                <i class="mdi mdi-close"></i> Clear
                            </a>
                            @endif
                        </div>
                    </form>

                    @if($inspections->isEmpty())
                        <div class="text-center py-5">
                            <i class="mdi mdi-clipboard-check-outline" style="font-size: 3rem; color: #ddd;"></i>
                            <p class="text-muted mt-2">
                                @if(request('view') === 'pending-etogo')
                                    No properties awaiting Etogo countersignature
                                @elseif(request('view') === 'needs-schedule')
                                    No properties awaiting project scheduling
                                @elseif(request('view') === 'awaiting-quotation')
                                    No pre-assessed properties found
                                @elseif(request('status') == 'scheduled')
                                    No scheduled inspections found
                                @elseif(request('status') == 'in_progress')
                                    No in-progress inspections found
                                @elseif(request('status') == 'completed')
                                    No completed inspections found
                                @else
                                    No inspections found
                                @endif
                            </p>
                        </div>
                    @else
                        <div class="row g-3">
                            @foreach($inspections as $inspection)
                            @php
                                $prop = $inspection->property;
                                $projectManager = null;
                                if ($inspection->project && $inspection->project->manager) {
                                    $projectManager = $inspection->project->manager;
                                } elseif ($prop && $prop->projectManager) {
                                    $projectManager = $prop->projectManager;
                                }

                                $resolvedPm = $inspection->project?->managed_by ?? $prop?->project_manager_id;
                                $teamFullyAssigned = $inspection->inspector_id && $resolvedPm;
                                $isInProgress = $inspection->status === 'in_progress';

                                if ($isInProgress && ($inspection->bdc_annual ?? 0) > 0) {
                                    $continueUrl = route('inspections.phar-data', $inspection->id);
                                } else {
                                    $continueUrl = route('inspections.create', ['property_id' => $inspection->property_id]);
                                }

                                $statusColor = match($inspection->status) {
                                    'scheduled' => 'bg-success',
                                    'in_progress' => 'bg-warning text-dark',
                                    'completed' => 'bg-info',
                                    default => 'bg-secondary',
                                };

                                $statusLabel = match($inspection->status) {
                                    'scheduled' => 'Scheduled',
                                    'in_progress' => 'In Progress',
                                    'completed' => 'Completed',
                                    default => ucfirst($inspection->status),
                                };

                                $photos = $prop?->property_photos ?? [];
                                $coverPhoto = count($photos) > 0 ? $prop->getStorageUrl($photos[0]) : null;
                                $hasAssignedTools = $inspection->toolAssignments
                                    ->whereNull('returned_at')
                                    ->where('quantity', '>', 0)
                                    ->isNotEmpty();

                                // Build compact findings reference for scheduling modal
                                // Only show findings the client approved on the quotation
                                $rawFindings = is_array($inspection->findings)
                                    ? $inspection->findings
                                    : (json_decode($inspection->getRawOriginal('findings') ?? '[]', true) ?? []);

                                $activeQ = $inspection->activeQuotation;
                                $approvedIds = collect($activeQ->approved_finding_ids ?? [])->map(fn($id) => (int)$id);
                                $snapshotFindings = collect($activeQ->findings_snapshot ?? []);

                                // Build snapshot key→data map for desc/rec backfill + key-based fallback
                                $snapshotMap = [];
                                foreach ($snapshotFindings as $sf) {
                                    $sk = strtolower(trim((string)($sf['task_question'] ?? ($sf['issue'] ?? '')))) . '|' . strtolower(trim((string)($sf['category'] ?? '')));
                                    $snapshotMap[$sk] = $sf;
                                }

                                // Pass 1: filter by approved IDs (exact match)
                                if ($approvedIds->isNotEmpty()) {
                                    $approvedIdFlip = $approvedIds->flip();
                                    $filteredFindings = collect($rawFindings)
                                        ->filter(fn($f) => $approvedIdFlip->has((int)($f['id'] ?? 0)))
                                        ->values();

                                    // Pass 2 fallback: if IDs didn't match (older data), match by key against approved snapshot
                                    if ($filteredFindings->isEmpty()) {
                                        $approvedKeys = $snapshotFindings
                                            ->filter(fn($sf) => $approvedIds->contains((int)($sf['id'] ?? 0)))
                                            ->map(fn($sf) => strtolower(trim((string)($sf['task_question'] ?? ($sf['issue'] ?? '')))) . '|' . strtolower(trim((string)($sf['category'] ?? ''))))
                                            ->filter(fn($k) => $k !== '|')
                                            ->unique()->values();

                                        $filteredFindings = collect($rawFindings)
                                            ->filter(function($f) use ($approvedKeys) {
                                                $fk = strtolower(trim((string)($f['task_question'] ?? ($f['issue'] ?? '')))) . '|' . strtolower(trim((string)($f['phar_category'] ?? ($f['category'] ?? ''))));
                                                return $approvedKeys->contains($fk);
                                            })->values();
                                    }
                                } else {
                                    // No quotation / no approved IDs — show nothing (don't guess)
                                    $filteredFindings = collect();
                                }

                                $findingsForModal = $filteredFindings->map(function($f) use ($snapshotMap) {
                                    $title = trim((string)($f['task_question'] ?? ($f['issue'] ?? '')));
                                    if ($title === '') return null;
                                    $cat = trim((string)($f['phar_category'] ?? ($f['category'] ?? '')));
                                    $sk = strtolower($title) . '|' . strtolower($cat);
                                    $snap = $snapshotMap[$sk] ?? [];
                                    return [
                                        'title' => $title,
                                        'cat'   => $cat,
                                        'sev'   => (string)($f['severity'] ?? 'medium'),
                                        'desc'  => (string)($f['issue_description'] ?? $snap['issue_description'] ?? $f['notes'] ?? ''),
                                        'rec'   => (string)($f['recommendation_details'] ?? $snap['recommendation_details'] ?? $snap['recommendation'] ?? ''),
                                    ];
                                })->filter()->values()->all();
                            @endphp
                            <div class="col-md-6 col-xl-4">
                                <div class="card border-0 shadow-sm h-100">
                                    @if($coverPhoto)
                                    <div style="height:140px;overflow:hidden;border-radius:.375rem .375rem 0 0;">
                                        <img src="{{ $coverPhoto }}" alt="{{ $prop?->property_name ?? 'Property' }}"
                                             style="width:100%;height:100%;object-fit:cover;">
                                    </div>
                                    @endif

                                    <div class="card-body pb-2">
                                        <div class="d-flex align-items-start justify-content-between mb-2">
                                            <div>
                                                <h6 class="fw-semibold mb-0">{{ $prop?->property_name ?? 'Property #'.$inspection->property_id }}</h6>
                                                <div class="text-muted small">
                                                    <code class="me-1">{{ $prop?->property_code ?? 'N/A' }}</code>
                                                    {{ $prop?->city ?? '-' }}, {{ $prop?->province ?? '-' }}
                                                </div>
                                            </div>
                                            <span class="badge {{ $statusColor }} ms-2">{{ $statusLabel }}</span>
                                        </div>

                                        <div class="small mb-2">
                                            <i class="mdi mdi-account-outline text-muted me-1"></i>
                                            <strong>{{ $prop?->user?->name ?? '-' }}</strong>
                                            <span class="text-muted ms-1">{{ $prop?->user?->email ?? '' }}</span>
                                        </div>

                                        <div class="row g-0 border rounded overflow-hidden mb-2">
                                            <div class="col border-end py-2 px-2 text-center">
                                                <i class="mdi mdi-account-check text-info d-block" style="font-size:1.1rem;"></i>
                                                <div class="text-muted" style="font-size:.65rem;">INSPECTOR</div>
                                                <div style="font-size:.72rem;" class="fw-semibold">{{ $inspection->inspector?->name ?? '-' }}</div>
                                            </div>
                                            <div class="col border-end py-2 px-2 text-center">
                                                <i class="mdi mdi-tools text-secondary d-block" style="font-size:1.1rem;"></i>
                                                <div class="text-muted" style="font-size:.65rem;">TECHNICIAN</div>
                                                <div style="font-size:.72rem;" class="fw-semibold">{{ $inspection->technician?->name ?? '-' }}</div>
                                            </div>
                                            <div class="col py-2 px-2 text-center">
                                                <i class="mdi mdi-account-hard-hat text-primary d-block" style="font-size:1.1rem;"></i>
                                                <div class="text-muted" style="font-size:.65rem;">MANAGER</div>
                                                <div style="font-size:.72rem;" class="fw-semibold">{{ $projectManager?->name ?? '-' }}</div>
                                            </div>
                                        </div>

                                        <div class="d-flex justify-content-between align-items-center small">
                                            <div>
                                                <i class="mdi mdi-calendar me-1 text-muted"></i>
                                                @if($inspection->scheduled_date)
                                                    {{ optional($inspection->scheduled_date)->format('M d, Y - h:i A') }}
                                                @else
                                                    <span class="text-warning">Not scheduled</span>
                                                @endif
                                            </div>
                                            <div>
                                                @if($inspection->inspection_fee_status === 'paid')
                                                    <span class="badge bg-success"><i class="mdi mdi-check-circle me-1"></i>Fee Paid</span>
                                                @else
                                                    <span class="badge bg-danger">{{ ucfirst($inspection->inspection_fee_status) }}</span>
                                                @endif
                                            </div>
                                        </div>

                                        @if($inspection->status === 'completed')
                                        <div class="mt-1 small">
                                            @if(($inspection->work_payment_status ?? 'pending') === 'paid')
                                                @php
                                                    $cadenceLabel = match($inspection->work_payment_cadence ?? '') {
                                                        'full' => 'In Full',
                                                        'per_visit' => 'Per Visit',
                                                        'annual' => 'Annual',
                                                        'monthly' => 'Monthly',
                                                        default => ucfirst($inspection->work_payment_cadence ?? '')
                                                    };
                                                @endphp
                                                <span class="badge bg-info"><i class="mdi mdi-credit-card-check-outline me-1"></i>Work Paid {{ $cadenceLabel }}</span>
                                            @else
                                                <span class="badge bg-warning text-dark"><i class="mdi mdi-credit-card-clock-outline me-1"></i>Work Payment Pending</span>
                                            @endif
                                        </div>
                                        @endif
                                    </div>

                                    <div class="card-footer bg-transparent border-top-0 pt-0 pb-3 px-3">
                                        <div class="inspection-action-row">
                                            <a href="{{ route('properties.show', $inspection->property_id) }}"
                                               class="btn btn-sm btn-primary fw-bold" title="View Property">
                                                <i class="mdi mdi-eye me-1"></i>View Property
                                            </a>

                                            @if($inspection->status === 'completed')
                                            <a href="{{ route('inspections.show', $inspection->id) }}"
                                               class="btn btn-sm btn-outline-info" title="View Inspection Report">
                                                <i class="mdi mdi-file-document-outline me-1"></i>Report
                                            </a>
                                            @endif

                                            @if($inspection->status === 'completed' && ($inspection->work_payment_status ?? 'pending') !== 'paid')
                                            <a href="{{ route('inspections.work-payment', $inspection->id) }}"
                                               class="btn btn-sm btn-warning" title="Pay to Start Work">
                                                <i class="mdi mdi-credit-card me-1"></i>Pay
                                            </a>
                                            @endif

                                            <button type="button"
                                                    class="btn btn-sm {{ $teamFullyAssigned ? 'btn-outline-primary' : 'btn-primary' }}"
                                                    onclick="assignInspector({{ $inspection->id }}, {{ $inspection->property_id }}, '{{ addslashes($prop?->property_name ?? 'Property') }}', {{ $resolvedPm ?? 'null' }}, {{ $inspection->inspector_id ?? 'null' }}, {{ $inspection->technician_id ?? 'null' }})"
                                                    title="{{ $teamFullyAssigned ? 'Edit Team' : 'Assign Team' }}">
                                                <i class="mdi mdi-account-{{ $teamFullyAssigned ? 'edit' : 'plus' }} me-1"></i>
                                                {{ $teamFullyAssigned ? 'Edit Team' : 'Assign Team' }}
                                            </button>

                                            @if($inspection->status !== 'completed')
                                            <a href="{{ $continueUrl }}"
                                               class="btn btn-sm {{ $isInProgress ? 'btn-warning' : 'btn-success' }} fw-bold"
                                               title="{{ $isInProgress ? 'Continue Inspection' : 'Start Inspection' }}">
                                                <i class="mdi {{ $isInProgress ? 'mdi-play-circle-outline' : 'mdi-clipboard-check' }} me-1"></i>
                                                {{ $isInProgress ? 'Continue' : 'Start Inspection' }}
                                            </a>
                                            @endif

                                            @if(request('view') === 'awaiting-quotation' && ($inspection->quotation_status ?? null) === 'approved')
                                            <a href="{{ route('inspections.phar-data', $inspection->id) }}"
                                               class="btn btn-sm btn-primary fw-bold"
                                               title="Open PHAR and complete assessment">
                                                <i class="mdi mdi-check-decagram me-1"></i>Open PHAR
                                            </a>
                                            @endif

                                            @if($inspection->status === 'scheduled' && !$inspection->scheduled_date)
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-secondary"
                                                    onclick="openAssessmentScheduleModal({{ $inspection->id }}, '{{ addslashes($prop?->property_name ?? '') }}', '{{ optional($inspection->scheduled_date)->format('Y-m-d\\TH:i') }}')"
                                                    title="Set assessment date">
                                                <i class="mdi mdi-calendar-edit me-1"></i>Set Date
                                            </button>
                                            @endif

                                            @if(request('view') === 'pending-etogo')
                                            @if(Auth::user()->hasRole(['Super Admin', 'Store Manager']))
                                            <a href="{{ route('tool-assignments.index', ['inspection_id' => $inspection->id]) }}"
                                               class="btn btn-sm btn-danger fw-bold"
                                               title="Assign tools for this job">
                                                <i class="mdi mdi-wrench me-1"></i>Assign Tools
                                            </a>
                                            @endif
                                            @if(Auth::user()->hasRole(['Super Admin', 'Administrator', 'Project Manager']))
                                            <button type="button"
                                                    class="btn btn-sm {{ $hasAssignedTools ? 'btn-success' : 'btn-outline-secondary' }} fw-bold"
                                                    onclick="openWorkScheduleModal({{ $inspection->id }}, '{{ addslashes($prop?->property_name ?? '') }}', {{ (int)($inspection->bdc_visits_per_year ?? 1) }}, {{ json_encode($inspection->work_schedule ?? []) }}, {{ json_encode($findingsForModal) }})"
                                                    title="{{ $hasAssignedTools ? 'Set visit schedule' : 'Assign tools first before scheduling visits' }}"
                                                    {{ $hasAssignedTools ? '' : 'disabled' }}>
                                                <i class="mdi mdi-calendar-check me-1"></i>Schedule Visits
                                            </button>
                                            @endif
                                            @endif

                                            @if(request('view') === 'needs-schedule' && !$inspection->etogo_signed_at)
                                            @if(Auth::user()->hasRole(['Super Admin', 'Administrator', 'Project Manager']))
                                            <button type="button"
                                                    class="btn btn-sm btn-info fw-bold text-white"
                                                    onclick="openWorkScheduleModal({{ $inspection->id }}, '{{ addslashes($prop?->property_name ?? '') }}', {{ (int)($inspection->bdc_visits_per_year ?? 1) }}, {{ json_encode($inspection->work_schedule ?? []) }}, {{ json_encode($findingsForModal) }})"
                                                    title="Edit visit schedule before countersigning">
                                                <i class="mdi mdi-calendar-edit me-1"></i>Reschedule
                                            </button>
                                            @endif
                                            <a href="{{ route('inspections.preview-agreement', $inspection->id) }}?for_countersign=1"
                                               class="btn btn-sm btn-warning fw-bold"
                                               title="Review contract and countersign">
                                                <i class="mdi mdi-file-document-edit-outline me-1"></i>Review &amp; Countersign
                                            </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @endif

                    @if($inspections->hasPages())
                    <div class="mt-3">
                        {{ $inspections->links() }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Schedule Inspection Modal -->
<div class="modal fade" id="scheduleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="background-color: #ffffff !important;">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="mdi mdi-calendar-clock me-2"></i>Schedule Inspection
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="scheduleForm" method="POST">
                @csrf
                <div class="modal-body" style="background-color: #ffffff !important; color: #000000 !important;">
                    <p class="small text-muted mb-2">Property: <strong id="schedulePropertyName">-</strong></p>
                    <div class="form-group">
                        <label for="scheduled_date" style="color: #000000 !important;">Inspection Date and Time <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="scheduled_date" id="scheduled_date"
                               class="form-control" required min="{{ date('Y-m-d\\TH:i') }}"
                               style="background-color: #ffffff !important; color: #000000 !important;">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="mdi mdi-check me-2"></i>Schedule
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Assign Team Modal -->
<div class="modal fade" id="assignTeamModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content bg-white text-dark">
            <div class="modal-header bg-white text-dark border-bottom">
                <h5 class="modal-title">
                    <i class="mdi mdi-account-multiple me-2"></i>Assign Project Team
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="assignTeamForm" method="POST" action="">
                @csrf
                <div class="modal-body bg-white text-dark">
                    <p class="mb-3 text-muted small">Inspection ID: <span id="assignInspectionId">-</span> &bull; Property: <span id="assignPropertyName">-</span></p>

                    <div class="mb-3">
                        <label for="project_manager_id" class="form-label">Project Manager <span class="text-muted small fw-normal">(leave blank to keep current)</span></label>
                        <select name="project_manager_id" id="project_manager_id" class="form-select">
                            <option value="">-- No change --</option>
                            @foreach($projectManagers ?? [] as $pm)
                                <option value="{{ $pm->id }}">{{ $pm->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="inspector_id" class="form-label">Inspector <span class="text-muted small fw-normal">(leave blank to keep current)</span></label>
                        <select name="inspector_id" id="inspector_id" class="form-select">
                            <option value="">-- No change --</option>
                            @foreach($inspectors ?? [] as $insp)
                                <option value="{{ $insp->id }}">{{ $insp->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="technician_id" class="form-label">Technician <span class="text-muted small fw-normal">(optional)</span></label>
                        <select name="technician_id" id="technician_id" class="form-select">
                            <option value="">-- None / Remove --</option>
                            @foreach($technicians ?? [] as $tech)
                                <option value="{{ $tech->id }}">{{ $tech->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-white border-top">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="mdi mdi-check me-1"></i> Save Assignment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Work Schedule Modal -->
<div class="modal fade" id="workScheduleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content bg-white text-dark">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="mdi mdi-calendar-check me-2"></i>Set Visit Schedule
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="workScheduleForm" method="POST" action="" onkeydown="if(event.key==='Enter'&&event.target.tagName==='INPUT'){event.preventDefault();return false;}">
                @csrf
                <div class="modal-body bg-white text-dark" style="max-height:65vh;overflow-y:auto;">
                    <div class="alert alert-info py-2 mb-3" id="scheduleInfo">
                        <strong id="schedulePropName"></strong> &mdash;
                        <span id="scheduleVisitCount"></span> paid visit(s) per year
                    </div>

                    {{-- Findings reference panel — populated by JS when modal opens --}}
                    <div id="scheduleFindingsRef" class="mb-3"></div>

                    <div class="alert alert-warning py-2 mb-3 small">
                        <i class="mdi mdi-information-outline me-1"></i>
                        <strong>Construction note:</strong> Set the paid visit date(s), then add a day-by-day work plan below each visit.
                        This lets you show the full work span (e.g. 14 days for curing, drying, etc.) even if only 2 visits were paid.
                    </div>

                    <div class="row align-items-end mb-3">
                        <div class="col-md-7">
                            <label class="form-label fw-semibold">First Visit Date <span class="text-danger">*</span></label>
                            <input type="date" id="firstVisitDate" class="form-control"
                                   min="{{ now()->toDateString() }}">
                            <div class="form-text">Pick a date then click <strong>Auto-fill</strong> to space remaining visits evenly. Sundays are skipped.</div>
                        </div>
                        <div class="col-md-5">
                            <button type="button" class="btn btn-outline-primary w-100" onclick="generateVisitDates()">
                                <i class="mdi mdi-refresh me-1"></i> Auto-fill Remaining Dates
                            </button>
                        </div>
                    </div>

                    <div id="visitDatesList"></div>

                    {{-- Inline save button always visible at bottom of scroll --}}
                    <div class="d-flex justify-content-end gap-2 mt-3 pt-3 border-top">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success fw-bold px-4">
                            <i class="mdi mdi-content-save me-1"></i> Save Schedule
                        </button>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top py-2">
                    <small class="text-muted me-auto"><i class="mdi mdi-information-outline me-1"></i>Scroll up to review all visits before saving.</small>
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-sm fw-bold">
                        <i class="mdi mdi-content-save me-1"></i> Save Schedule
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let _scheduleVisits = 1;
const _todayStr = '{{ now()->toDateString() }}';

function nextWeekday(date) {
    let d = new Date(date);
    // Mon-Sat are working days; only skip Sunday.
    while (d.getDay() === 0) {
        d.setDate(d.getDate() + 1);
    }
    return d;
}

function toDateInputVal(d) {
    return d.toISOString().split('T')[0];
}

function addDays(dateStr, n) {
    let d = new Date(dateStr + 'T12:00:00');
    d.setDate(d.getDate() + n);
    return toDateInputVal(nextWeekday(d));
}

function escapeAttr(str) {
    return (str || '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

// ─── Task management (multiple tasks per day) ─────────────────────────────

function buildTaskRowHtml(visitIdx, dayIdx, taskIdx, value) {
    return '<div class="row g-1 mb-1 align-items-center task-row">' +
        '<div class="col">' +
            '<input type="text"' +
                   ' name="visit_deliverables[' + visitIdx + '][' + dayIdx + '][tasks][' + taskIdx + ']"' +
                   ' class="form-control form-control-sm task-input"' +
                   ' placeholder="Describe this task\u2026"' +
                   ' value="' + escapeAttr(value) + '">' +
        '</div>' +
        '<div class="col-auto">' +
            '<button type="button" class="btn btn-outline-danger btn-sm remove-task-btn"' +
                    ' onclick="removeTask(this,' + visitIdx + ')">' +
                '<i class="mdi mdi-close"></i>' +
            '</button>' +
        '</div>' +
    '</div>';
}

function addTaskToDay(addBtn, visitIdx) {
    const dayCard = addBtn.closest('.day-card');
    const dayIdx  = parseInt(dayCard.dataset.dayIdx);
    const taskList = dayCard.querySelector('.task-list');
    const taskIdx  = taskList.querySelectorAll('.task-row').length;
    const div = document.createElement('div');
    div.innerHTML = buildTaskRowHtml(visitIdx, dayIdx, taskIdx, '');
    taskList.appendChild(div.firstChild);
}

function removeTask(btn, visitIdx) {
    const dayCard  = btn.closest('.day-card');
    const dayIdx   = parseInt(dayCard.dataset.dayIdx);
    btn.closest('.task-row').remove();
    dayCard.querySelector('.task-list').querySelectorAll('.task-input').forEach(function(inp, ti) {
        inp.name = 'visit_deliverables[' + visitIdx + '][' + dayIdx + '][tasks][' + ti + ']';
    });
}

// ─── Day card management ───────────────────────────────────────────────────

function buildDayCardElement(visitIdx, dayIdx, dateVal, tasks) {
    const card = document.createElement('div');
    card.className = 'day-card border rounded p-2 mb-2';
    card.dataset.dayIdx = dayIdx;
    const tasksToRender = (tasks && tasks.length > 0) ? tasks : [''];
    let taskHtml = '';
    tasksToRender.forEach(function(task, ti) {
        taskHtml += buildTaskRowHtml(visitIdx, dayIdx, ti, task);
    });
    card.innerHTML =
        '<div class="d-flex align-items-center gap-2 mb-2 flex-wrap">' +
            '<span class="badge bg-primary" style="min-width:58px;">Day ' + (dayIdx + 1) + '</span>' +
            '<input type="date"' +
                   ' name="visit_deliverables[' + visitIdx + '][' + dayIdx + '][date]"' +
                   ' class="form-control form-control-sm day-date-input" style="width:170px;"' +
                   ' value="' + (dateVal || '') + '">' +
            '<button type="button" class="btn btn-outline-danger btn-sm ms-auto remove-day-btn"' +
                    ' onclick="removeDeliverableRow(this,' + visitIdx + ')">' +
                '<i class="mdi mdi-delete-outline me-1"></i>Remove Day' +
            '</button>' +
        '</div>' +
        '<div class="task-list ms-4 mb-1">' + taskHtml + '</div>' +
        '<div class="ms-4 mt-1">' +
            '<button type="button" class="btn btn-sm btn-success fw-semibold add-task-btn"' +
                    ' onclick="addTaskToDay(this,' + visitIdx + ')">' +
                '<i class="mdi mdi-plus-circle me-1"></i>+ Add Another Task' +
            '</button>' +
        '</div>';
    return card;
}

function appendDeliverableRow(container, visitIdx, dateVal, tasks) {
    const dayIdx = container.querySelectorAll('.day-card').length;
    container.appendChild(buildDayCardElement(visitIdx, dayIdx, dateVal, tasks));
}

function addDeliverableRow(visitIdx) {
    const container = document.getElementById('deliverables-' + visitIdx);
    const daycards  = container.querySelectorAll('.day-card');
    let defaultDate = '';
    if (daycards.length > 0) {
        const lastDate = daycards[daycards.length - 1].querySelector('.day-date-input').value;
        defaultDate = lastDate ? addDays(lastDate, 1) : '';
    } else {
        const visitInput = document.querySelector('#visit-card-' + visitIdx + ' .visit-date-input');
        defaultDate = visitInput ? visitInput.value : '';
    }
    appendDeliverableRow(container, visitIdx, defaultDate, ['']);
}

function removeDeliverableRow(btn, visitIdx) {
    btn.closest('.day-card').remove();
    reindexDeliverableRows(document.getElementById('deliverables-' + visitIdx), visitIdx);
}

function reindexDeliverableRows(container, visitIdx) {
    container.querySelectorAll('.day-card').forEach(function(card, di) {
        card.dataset.dayIdx = di;
        card.querySelector('.badge').textContent = 'Day ' + (di + 1);
        const dateInput = card.querySelector('.day-date-input');
        if (dateInput) dateInput.name = 'visit_deliverables[' + visitIdx + '][' + di + '][date]';
        card.querySelectorAll('.task-input').forEach(function(inp, ti) {
            inp.name = 'visit_deliverables[' + visitIdx + '][' + di + '][tasks][' + ti + ']';
        });
        const rmDayBtn = card.querySelector('.remove-day-btn');
        if (rmDayBtn) rmDayBtn.setAttribute('onclick', 'removeDeliverableRow(this,' + visitIdx + ')');
        card.querySelectorAll('.add-task-btn').forEach(function(b) {
            b.setAttribute('onclick', 'addTaskToDay(this,' + visitIdx + ')');
        });
        card.querySelectorAll('.remove-task-btn').forEach(function(b) {
            b.setAttribute('onclick', 'removeTask(this,' + visitIdx + ')');
        });
    });
}

function syncDay1Date(visitIdx, newDate) {
    const container = document.getElementById('deliverables-' + visitIdx);
    if (!container) return;
    const firstCard = container.querySelector('.day-card');
    if (firstCard) {
        const dateInput = firstCard.querySelector('.day-date-input');
        if (dateInput) dateInput.value = newDate;
    }
}

// ─── Visit card builder ────────────────────────────────────────────────────

function buildVisitCard(visitIdx, dateVal, deliverables) {
    const wrapper = document.createElement('div');
    wrapper.className = 'mb-4';
    wrapper.id = 'visit-card-' + visitIdx;
    wrapper.innerHTML =
        '<div class="card border border-primary shadow-sm">' +
          '<div class="card-header bg-primary text-white py-2 d-flex align-items-center gap-2">' +
            '<i class="mdi mdi-calendar-account"></i>' +
            '<strong>Visit ' + (visitIdx + 1) + '</strong>' +
          '</div>' +
          '<div class="card-body">' +
            '<div class="row mb-3">' +
              '<div class="col-md-5">' +
                '<label class="form-label fw-semibold mb-1">Paid Visit Date <span class="text-danger">*</span></label>' +
                '<input type="date" name="visit_dates[]" class="form-control visit-date-input"' +
                       ' value="' + (dateVal || '') + '"' +
                       ' min="' + _todayStr + '" required' +
                       ' data-visit-idx="' + visitIdx + '">' +
                '<div class="form-text text-muted">Scheduled arrival / paid labour day.</div>' +
              '</div>' +
            '</div>' +
            '<div class="rounded p-3" style="background:#f0f4ff;border:1px solid #c5d2f6;">' +
              '<div class="d-flex justify-content-between align-items-center mb-1">' +
                '<span class="fw-semibold small">' +
                  '<i class="mdi mdi-clipboard-list-outline me-1 text-primary"></i>' +
                  'Day-by-Day Work Plan ' +
                  '<span class="fw-normal text-muted">(optional — for construction / repair work)</span>' +
                '</span>' +
              '</div>' +
              '<p class="text-muted small mb-2">' +
                'Add each day of activity. Day&nbsp;1 = paid visit date. ' +
                'Add extra days for curing, drying, stabilisation, etc. — the client will see the full plan.' +
              '</p>' +
              '<div id="deliverables-' + visitIdx + '" class="mb-2"></div>' +
              '<button type="button" class="btn btn-sm btn-outline-primary"' +
                      ' onclick="addDeliverableRow(' + visitIdx + ')">' +
                '<i class="mdi mdi-plus me-1"></i>Add Day' +
              '</button>' +
            '</div>' +
          '</div>' +
        '</div>';

    // Sync visit date → day 1 date when date changes
    const dateInput = wrapper.querySelector('.visit-date-input');
    dateInput.addEventListener('change', function() { syncDay1Date(visitIdx, this.value); });

    // Pre-fill existing deliverables (backward compat: tasks[] new, planned_work old)
    const deliContainer = wrapper.querySelector('#deliverables-' + visitIdx);
    if (deliverables && deliverables.length > 0) {
        deliverables.forEach(function(dl) {
            const tasks = (dl.tasks && dl.tasks.length > 0) ? dl.tasks
                        : (dl.planned_work ? [dl.planned_work] : ['']);
            appendDeliverableRow(deliContainer, visitIdx, dl.date || dateVal, tasks);
        });
    }

    return wrapper;
}

function renderVisitCards(visitData) {
    const container = document.getElementById('visitDatesList');
    container.innerHTML = '';
    visitData.forEach(function(v, i) {
        container.appendChild(buildVisitCard(i, v.date, v.deliverables || []));
    });
}

// backward-compat alias used by generateVisitDates()
function renderDateFields(dates) {
    renderVisitCards(dates.map(function(d) { return { date: d, deliverables: [] }; }));
}

// ─── Main schedule functions ───────────────────────────────────────────────

function generateVisitDates() {
    const firstVal = document.getElementById('firstVisitDate').value;
    if (!firstVal) { alert('Please pick a first visit date first.'); return; }

    const total = _scheduleVisits;
    const dates = [];
    let current = nextWeekday(new Date(firstVal + 'T12:00:00'));
    dates.push(toDateInputVal(current));
    for (let i = 1; i < total; i++) {
        let next = new Date(current);
        next.setDate(next.getDate() + 1);
        next = nextWeekday(next);
        dates.push(toDateInputVal(next));
        current = next;
    }
    renderDateFields(dates);
}

function openWorkScheduleModal(inspectionId, propertyName, totalVisits, existingSchedule, findings) {
    _scheduleVisits = totalVisits || 1;
    const form = document.getElementById('workScheduleForm');
    form.action = '/inspections/' + inspectionId + '/work-schedule';

    document.getElementById('schedulePropName').textContent = propertyName;
    document.getElementById('scheduleVisitCount').textContent = totalVisits;
    document.getElementById('firstVisitDate').value = '';

    // Render findings reference panel
    var refEl = document.getElementById('scheduleFindingsRef');
    if (findings && findings.length > 0) {
        var sevMeta = {
            critical:       { label: 'Urgent / Safety Critical', cls: 'danger' },
            high:           { label: 'Health & Safety Risk',      cls: 'warning' },
            noi_protection: { label: 'NOI Protection',            cls: 'purple' },
            medium:         { label: 'Value Depreciation',        cls: 'secondary' },
            low:            { label: 'Non-Urgent',                cls: 'success' }
        };
        var rows = findings.map(function(f, i) {
            var meta = sevMeta[f.sev] || { label: f.sev, cls: 'secondary' };
            var clsMap = { danger:'#dc3545', warning:'#d4a017', purple:'#6f42c1', secondary:'#6c757d', success:'#198754' };
            var color = clsMap[meta.cls] || '#6c757d';
            var desc = f.desc ? '<div class="small text-muted mt-1"><strong>Issue:</strong> ' + escapeHtml(f.desc) + '</div>' : '';
            var rec  = f.rec  ? '<div class="small mt-1" style="color:#0d6efd;"><strong>Recommendation:</strong> ' + escapeHtml(f.rec) + '</div>' : '';
            return '<tr>' +
                '<td class="py-1 text-center align-middle" style="width:2%">' +
                    '<span class="badge" style="background:' + color + ';font-size:.65rem;">' + (i+1) + '</span>' +
                '</td>' +
                '<td class="py-1 align-top" style="width:55%">' +
                    '<div class="fw-semibold" style="font-size:.82rem;">' + escapeHtml(f.title) + '</div>' +
                    '<span class="badge" style="background:' + color + ';font-size:.65rem;">' + escapeHtml(meta.label) + '</span>' +
                    (f.cat ? ' <span class="badge bg-light text-dark border" style="font-size:.65rem;">' + escapeHtml(f.cat) + '</span>' : '') +
                    desc + rec +
                '</td>' +
                '<td class="py-1 align-middle text-center" style="width:43%">' +
                    '<button type="button" class="btn btn-xs btn-outline-secondary btn-sm py-0 px-2" style="font-size:.7rem;" ' +
                        'onclick="copyFindingToTask(this)" data-title="' + escapeAttr(f.title) + '" data-rec="' + escapeAttr(f.rec || f.title) + '">' +
                        '<i class="mdi mdi-content-copy me-1"></i>Use as Task' +
                    '</button>' +
                '</td>' +
            '</tr>';
        }).join('');

        refEl.innerHTML =
            '<div class="card border-0 mb-0" style="background:#f8f4ff;border:1px solid #c9b8ff!important;">' +
                '<div class="d-flex align-items-center px-3 py-2" style="cursor:pointer;background:#ede6ff;border-radius:.375rem .375rem 0 0;" ' +
                     'data-bs-toggle="collapse" data-bs-target="#findingsRefBody" aria-expanded="true">' +
                    '<i class="mdi mdi-clipboard-list-outline me-2" style="color:#6f42c1;font-size:1.1rem;"></i>' +
                    '<strong style="color:#4a1a8e;font-size:.9rem;">Inspection Findings Reference</strong>' +
                    '<span class="badge ms-2" style="background:#6f42c1;">' + findings.length + ' findings</span>' +
                    '<span class="ms-auto small text-muted">Click to expand / collapse</span>' +
                '</div>' +
                '<div class="collapse show" id="findingsRefBody">' +
                    '<div class="px-2 py-1" style="font-size:.78rem;color:#5a3a8e;background:#f0eaff;border-bottom:1px solid #c9b8ff;">' +
                        '<i class="mdi mdi-lightbulb-outline me-1"></i>' +
                        'These are the approved findings for this property. Use them as a guide when planning tasks for each visit day.' +
                    '</div>' +
                    '<div class="table-responsive" style="max-height:260px;overflow-y:auto;">' +
                        '<table class="table table-sm table-borderless mb-0" style="font-size:.82rem;">' +
                            '<tbody>' + rows + '</tbody>' +
                        '</table>' +
                    '</div>' +
                '</div>' +
            '</div>';
    } else {
        refEl.innerHTML = '';
    }

    if (existingSchedule && existingSchedule.length > 0) {
        const visitData = existingSchedule.map(function(e) {
            if (typeof e === 'string') return { date: e, deliverables: [] };
            return { date: e.date || e, deliverables: e.deliverables || [] };
        });
        document.getElementById('firstVisitDate').value = visitData[0].date;
        renderVisitCards(visitData);
    } else {
        document.getElementById('visitDatesList').innerHTML =
            '<p class="text-muted small">Pick a first visit date above and click <strong>Auto-fill</strong>.</p>';
    }

    const firstInput = document.getElementById('firstVisitDate');
    firstInput.oninput = function() { if (this.value) generateVisitDates(); };

    new bootstrap.Modal(document.getElementById('workScheduleModal')).show();
}

function copyFindingToTask(btn) {
    // Finds the last visible task input in the currently open day card, or the first,
    // and prefills it with the finding recommendation text.
    var rec = btn.getAttribute('data-rec') || btn.getAttribute('data-title') || '';
    if (!rec) return;
    // Get all task inputs in the modal that are currently visible
    var inputs = document.querySelectorAll('#visitDatesList input[name*="[tasks]"]');
    if (inputs.length === 0) {
        alert('Add a day card and task row first, then use "Use as Task" to copy this finding into it.');
        return;
    }
    // Fill the last empty task input, or the last one if all filled
    var target = null;
    for (var i = 0; i < inputs.length; i++) {
        if (!inputs[i].value.trim()) { target = inputs[i]; break; }
    }
    if (!target) target = inputs[inputs.length - 1];
    target.value = rec;
    target.focus();
    target.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function escapeHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function assignInspector(inspectionId, propertyId, propertyName, projectManagerId, inspectorId, technicianId) {
    const form = document.getElementById('assignTeamForm');
    const inspectionIdNode = document.getElementById('assignInspectionId');
    const propertyNameNode = document.getElementById('assignPropertyName');
    const pmSelect = document.getElementById('project_manager_id');
    const inspectorSelect = document.getElementById('inspector_id');
    const techSelect = document.getElementById('technician_id');

    form.action = "{{ route('properties.assign', ['property' => '__PROPERTY_ID__']) }}".replace('__PROPERTY_ID__', propertyId);
    inspectionIdNode.textContent = inspectionId;
    propertyNameNode.textContent = propertyName || 'Property';
    pmSelect.value = (projectManagerId && projectManagerId !== 'null') ? String(projectManagerId) : '';
    inspectorSelect.value = (inspectorId && inspectorId !== 'null') ? String(inspectorId) : '';
    techSelect.value = (technicianId && technicianId !== 'null') ? String(technicianId) : '';

    const modal = new bootstrap.Modal(document.getElementById('assignTeamModal'));
    modal.show();
}

function openAssessmentScheduleModal(inspectionId, propertyName, currentDateTime) {
    const form = document.getElementById('scheduleForm');
    const propertyNameNode = document.getElementById('schedulePropertyName');
    const dateInput = document.getElementById('scheduled_date');

    form.action = '/inspections/' + inspectionId + '/assessment-schedule';
    propertyNameNode.textContent = propertyName || 'Property';
    dateInput.value = currentDateTime || '';

    new bootstrap.Modal(document.getElementById('scheduleModal')).show();
}
</script>
@endpush
