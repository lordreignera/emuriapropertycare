@extends('admin.layout')

@section('title', 'Diagnosis Report')

@section('content')
<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="card-title mb-0">
                            <i class="mdi mdi-clipboard-check-outline me-2 text-success"></i>
                            Diagnosis Report
                        </h4>
                        <p class="text-muted small mb-0">Complete diagnosis findings, recommendations, evidence, and risk notes</p>
                    </div>
                    <div>
                        <a href="{{ route('inspections.index') }}" class="btn btn-light btn-sm no-print">
                            <i class="mdi mdi-arrow-left me-1"></i>Back to Diagnoses
                        </a>
                        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm no-print">
                            <i class="mdi mdi-printer me-1"></i>Print Report
                        </button>
                        @if($inspection->status === 'completed')
                        <a href="{{ route('inspections.download-invoice', $inspection->id) }}" class="btn btn-success btn-sm no-print">
                            <i class="mdi mdi-download me-1"></i>Download Full Report PDF
                        </a>
                        @endif
                    </div>
                </div>

                @if($inspection->bdc_annual === null || $inspection->bdc_annual == 0)
                <div class="alert alert-warning alert-dismissible fade show">
                    <i class="mdi mdi-alert-circle me-2"></i>
                    <strong>Calculations are missing!</strong> This diagnosis may have been created before the pricing calculation system was implemented.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                @endif

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="mdi mdi-check-circle me-2"></i>{{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

            <div class="card mb-4 border-dark">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="mdi mdi-file-sign me-2"></i>Agreement Workflow</h5>
                    @if(($inspection->status ?? null) === 'completed' && $inspection->approved_by_client && ($inspection->work_payment_status ?? 'pending') === 'paid' && !$inspection->ETOGO_signed_at)
                        @if(Auth::user()->hasRole(['Super Admin', 'Administrator', 'Project Manager']))
                        @php
                            $hasTools    = ($toolAssignments ?? collect())->where('returned_at', null)->where('quantity', '>', 0)->isNotEmpty();
                            $hasSched    = collect($inspection->work_schedule ?? [])->isNotEmpty();
                            $readyToSign = $hasTools && $hasSched;
                        @endphp
                        @if($readyToSign)
                        <a href="{{ route('inspections.preview-agreement', $inspection->id) }}?for_countersign=1"
                           class="btn btn-sm btn-warning no-print">
                            <i class="mdi mdi-pen me-1"></i>Review &amp; Countersign
                        </a>
                        @else
                        <span class="badge bg-secondary no-print" title="{{ !$hasTools ? 'Assign tools first.' : 'Set visit schedule first.' }}">
                            <i class="mdi mdi-lock me-1"></i>{{ !$hasTools ? 'Tools needed' : 'Schedule needed' }}
                        </span>
                        @endif
                        @endif
                    @endif
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-md-4">
                            Client Sign:
                            @if($inspection->approved_by_client)
                                <span class="badge bg-success">Done</span>
                            @else
                                <span class="badge bg-warning text-dark">Pending</span>
                            @endif
                        </div>
                        <div class="col-md-4">
                            Deposit:
                            @if(($inspection->work_payment_status ?? 'pending') === 'paid')
                                <span class="badge bg-success">Confirmed</span>
                            @else
                                <span class="badge bg-warning text-dark">Pending</span>
                            @endif
                        </div>
                        <div class="col-md-4">
                            ETOGO Sign:
                            @if($inspection->ETOGO_signed_at)
                                <span class="badge bg-success">Done</span>
                            @else
                                <span class="badge bg-secondary">Awaiting</span>
                            @endif
                        </div>
                    </div>
                    <div class="mt-2 small text-muted">
                        Planned Start: {{ optional($inspection->planned_start_date)->format('M d, Y') ?? 'Pending' }} |
                        Target Completion: {{ optional($inspection->target_completion_date)->format('M d, Y') ?? 'Pending' }} |
                        Estimated Duration: {{ $inspection->estimated_duration_days ? $inspection->estimated_duration_days . ' day(s)' : 'N/A' }}
                    </div>
                    @if(!empty($inspection->schedule_blocked_reason))
                        <div class="alert alert-warning mt-3 mb-0">
                            <strong>Scheduling Blocker:</strong> {{ $inspection->schedule_blocked_reason }}
                        </div>
                    @endif
                </div>
            </div>

            {{-- ====== WORK VISIT SCHEDULE ====== --}}
            @if($inspection->approved_by_client && ($inspection->work_payment_status ?? 'pending') === 'paid')
            @php
                $totalVisits   = max(1, (int)($inspection->bdc_visits_per_year ?? 1));
                $savedSchedule = collect($inspection->work_schedule ?? [])->sortBy('date')->values();

                // Pre-populate date inputs: use saved dates if available, else generate sequential weekday dates
                $suggestedDates = [];
                if ($savedSchedule->isNotEmpty()) {
                    $suggestedDates = $savedSchedule->pluck('date')->all();
                } else {
                    $cursor = \Illuminate\Support\Carbon::tomorrow();
                    for ($i = 0; $i < $totalVisits; $i++) {
                        // Mon–Sat working week; only skip Sunday
                        while ($cursor->dayOfWeek === \Illuminate\Support\Carbon::SUNDAY) {
                            $cursor->addDay();
                        }
                        $suggestedDates[] = $cursor->toDateString();
                        $cursor->addDay();
                    }
                }
            @endphp
            <div class="card mb-4 border-success">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="mdi mdi-calendar-check me-2"></i>Work Visit Schedule</h5>
                    <small>Mon – Sat &nbsp;|&nbsp; 7:00 AM – 6:00 PM &nbsp;|&nbsp; {{ $totalVisits }} visit(s) required</small>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if($savedSchedule->isNotEmpty())
                        <div class="table-responsive mb-3">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th><th>Date</th><th>Day</th><th>Hours</th><th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($savedSchedule as $vi => $visit)
                                    <tr class="{{ ($visit['status'] ?? 'scheduled') === 'completed' ? 'table-success' : '' }}">
                                        <td>{{ $vi + 1 }}</td>
                                        <td>{{ \Illuminate\Support\Carbon::parse($visit['date'])->format('M d, Y') }}</td>
                                        <td>{{ \Illuminate\Support\Carbon::parse($visit['date'])->format('l') }}</td>
                                        <td>7:00 AM – 6:00 PM</td>
                                        <td><span class="badge bg-{{ ($visit['status'] ?? 'scheduled') === 'completed' ? 'success' : 'secondary' }} text-capitalize">{{ $visit['status'] ?? 'scheduled' }}</span></td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    @if(!($scheduleLocked ?? false) && !$inspection->ETOGO_signed_at)
                        @if(Auth::user()->hasRole(['Super Admin', 'Administrator', 'Project Manager']))
                        <form method="POST" action="{{ route('inspections.work-schedule.store', $inspection->id) }}" id="showScheduleForm" onkeydown="if(event.key==='Enter'&&event.target.tagName==='INPUT'){event.preventDefault();return false;}">
                            @csrf
                            <p class="text-muted small mb-2">Set or update the {{ $totalVisits }} work visit date(s). Monday – Saturday are accepted (no Sundays).</p>

                            @php $existingSched = $savedSchedule ?? []; @endphp
                            @for($i = 0; $i < $totalVisits; $i++)
                            @php
                                $existingVisit       = $existingSched[$i] ?? null;
                                $existingDeliverables = $existingVisit['deliverables'] ?? [];
                            @endphp
                            <div class="card border mb-3" id="show-visit-card-{{ $i }}">
                                <div class="card-header bg-light py-2 fw-semibold small">
                                    <i class="mdi mdi-calendar-account me-1 text-primary"></i>Visit {{ $i + 1 }}
                                </div>
                                <div class="card-body pb-2">
                                    <div class="row mb-3">
                                        <div class="col-md-5">
                                            <label class="form-label small mb-1">Paid Visit Date <span class="text-danger">*</span></label>
                                            <input type="date"
                                                   name="visit_dates[]"
                                                   class="form-control form-control-sm show-visit-date"
                                                   value="{{ $suggestedDates[$i] ?? ($existingVisit['date'] ?? '') }}"
                                                   data-visit-idx="{{ $i }}"
                                                   required>
                                        </div>
                                    </div>
                                    <div class="rounded p-2 mb-2" style="background:#f0f4ff;border:1px solid #c5d2f6;">
                                        <p class="fw-semibold small mb-1">
                                            <i class="mdi mdi-clipboard-list-outline me-1 text-primary"></i>
                                            Day-by-Day Work Plan
                                            <span class="fw-normal text-muted">(optional — add as many days and tasks as needed)</span>
                                        </p>
                                        <p class="text-muted small mb-2">Day 1 = paid visit date. Per day, add multiple tasks. Add extra days for curing, drying, etc.</p>
                                        <div id="show-deliverables-{{ $i }}" class="mb-2">
                                            @foreach($existingDeliverables as $di => $dl)
                                            @php
                                                $existingTasks = $dl['tasks'] ?? ($dl['planned_work'] ? [$dl['planned_work']] : ['']);
                                            @endphp
                                            <div class="day-card border rounded p-2 mb-2" data-day-idx="{{ $di }}">
                                                <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                                                    <span class="badge bg-primary" style="min-width:58px;">Day {{ $dl['day'] ?? ($di + 1) }}</span>
                                                    <input type="date"
                                                           name="visit_deliverables[{{ $i }}][{{ $di }}][date]"
                                                           class="form-control form-control-sm show-day-date-input"
                                                           style="width:170px;"
                                                           value="{{ $dl['date'] ?? '' }}">
                                                    <button type="button" class="btn btn-outline-danger btn-sm ms-auto show-remove-day-btn"
                                                            onclick="showRemoveDayCard(this, {{ $i }})" title="Remove day">
                                                        <i class="mdi mdi-delete-outline me-1"></i>Remove Day
                                                    </button>
                                                </div>
                                                <div class="show-task-list ms-4 mb-1">
                                                    @foreach($existingTasks as $ti => $task)
                                                    <div class="row g-1 mb-1 align-items-center show-task-row">
                                                        <div class="col">
                                                            <input type="text"
                                                                   name="visit_deliverables[{{ $i }}][{{ $di }}][tasks][{{ $ti }}]"
                                                                   class="form-control form-control-sm show-task-input"
                                                                   placeholder="Describe this task…"
                                                                   value="{{ $task }}">
                                                        </div>
                                                        <div class="col-auto">
                                                            <button type="button" class="btn btn-outline-danger btn-sm show-remove-task-btn"
                                                                    onclick="showRemoveTask(this, {{ $i }})">
                                                                <i class="mdi mdi-close"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                    @endforeach
                                                </div>
                                                <div class="ms-4">
                                                    <button type="button" class="btn btn-sm btn-outline-secondary show-add-task-btn"
                                                            onclick="showAddTask(this, {{ $i }})">
                                                        <i class="mdi mdi-plus me-1"></i>Add Task
                                                    </button>
                                                </div>
                                            </div>
                                            @endforeach
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-primary"
                                                onclick="showAddDayCard({{ $i }})">
                                            <i class="mdi mdi-plus me-1"></i>Add Day
                                        </button>
                                    </div>
                                </div>
                            </div>
                            @endfor

                            <div class="mt-3">
                                <button type="submit" class="btn btn-success btn-sm">
                                    <i class="mdi mdi-content-save me-1"></i>Save Visit Schedule
                                </button>
                            </div>
                        </form>

                        <script>
                        function showAddDayCard(visitIdx) {
                            const container = document.getElementById('show-deliverables-' + visitIdx);
                            const daycards = container.querySelectorAll('.day-card');
                            let defaultDate = '';
                            if (daycards.length > 0) {
                                const lastDate = daycards[daycards.length - 1].querySelector('.show-day-date-input').value;
                                if (lastDate) {
                                    let d = new Date(lastDate + 'T12:00:00');
                                    d.setDate(d.getDate() + 1);
                                    while (d.getDay() === 0) d.setDate(d.getDate() + 1);
                                    defaultDate = d.toISOString().split('T')[0];
                                }
                            } else {
                                const vInput = document.querySelector('#show-visit-card-' + visitIdx + ' .show-visit-date');
                                defaultDate = vInput ? vInput.value : '';
                            }
                            const di = daycards.length;
                            const card = document.createElement('div');
                            card.className = 'day-card border rounded p-2 mb-2';
                            card.dataset.dayIdx = di;
                            card.innerHTML =
                                '<div class="d-flex align-items-center gap-2 mb-2 flex-wrap">' +
                                    '<span class="badge bg-primary" style="min-width:58px;">Day ' + (di + 1) + '</span>' +
                                    '<input type="date" name="visit_deliverables[' + visitIdx + '][' + di + '][date]"' +
                                           ' class="form-control form-control-sm show-day-date-input" style="width:170px;" value="' + defaultDate + '">' +
                                    '<button type="button" class="btn btn-outline-danger btn-sm ms-auto show-remove-day-btn"' +
                                            ' onclick="showRemoveDayCard(this,' + visitIdx + ')">' +
                                        '<i class="mdi mdi-delete-outline me-1"></i>Remove Day</button>' +
                                '</div>' +
                                '<div class="show-task-list ms-4 mb-1">' +
                                    '<div class="row g-1 mb-1 align-items-center show-task-row">' +
                                        '<div class="col"><input type="text" name="visit_deliverables[' + visitIdx + '][' + di + '][tasks][0]"' +
                                            ' class="form-control form-control-sm show-task-input" placeholder="Describe this task\u2026"></div>' +
                                        '<div class="col-auto"><button type="button" class="btn btn-outline-danger btn-sm show-remove-task-btn"' +
                                            ' onclick="showRemoveTask(this,' + visitIdx + ')"><i class="mdi mdi-close"></i></button></div>' +
                                    '</div>' +
                                '</div>' +
                                '<div class="ms-4"><button type="button" class="btn btn-sm btn-outline-secondary show-add-task-btn"' +
                                        ' onclick="showAddTask(this,' + visitIdx + ')"><i class="mdi mdi-plus me-1"></i>Add Task</button></div>';
                            container.appendChild(card);
                            showReindexDays(container, visitIdx);
                        }
                        function showRemoveDayCard(btn, visitIdx) {
                            btn.closest('.day-card').remove();
                            showReindexDays(document.getElementById('show-deliverables-' + visitIdx), visitIdx);
                        }
                        function showAddTask(addBtn, visitIdx) {
                            const dayCard = addBtn.closest('.day-card');
                            const di = parseInt(dayCard.dataset.dayIdx);
                            const taskList = dayCard.querySelector('.show-task-list');
                            const ti = taskList.querySelectorAll('.show-task-row').length;
                            const row = document.createElement('div');
                            row.className = 'row g-1 mb-1 align-items-center show-task-row';
                            row.innerHTML =
                                '<div class="col"><input type="text" name="visit_deliverables[' + visitIdx + '][' + di + '][tasks][' + ti + ']"' +
                                    ' class="form-control form-control-sm show-task-input" placeholder="Describe this task\u2026"></div>' +
                                '<div class="col-auto"><button type="button" class="btn btn-outline-danger btn-sm show-remove-task-btn"' +
                                    ' onclick="showRemoveTask(this,' + visitIdx + ')"><i class="mdi mdi-close"></i></button></div>';
                            taskList.appendChild(row);
                        }
                        function showRemoveTask(btn, visitIdx) {
                            const dayCard = btn.closest('.day-card');
                            const di = parseInt(dayCard.dataset.dayIdx);
                            btn.closest('.show-task-row').remove();
                            dayCard.querySelector('.show-task-list').querySelectorAll('.show-task-input').forEach(function(inp, ti) {
                                inp.name = 'visit_deliverables[' + visitIdx + '][' + di + '][tasks][' + ti + ']';
                            });
                        }
                        function showReindexDays(container, visitIdx) {
                            container.querySelectorAll('.day-card').forEach(function(card, di) {
                                card.dataset.dayIdx = di;
                                card.querySelector('.badge').textContent = 'Day ' + (di + 1);
                                const dateInput = card.querySelector('.show-day-date-input');
                                if (dateInput) dateInput.name = 'visit_deliverables[' + visitIdx + '][' + di + '][date]';
                                card.querySelectorAll('.show-task-input').forEach(function(inp, ti) {
                                    inp.name = 'visit_deliverables[' + visitIdx + '][' + di + '][tasks][' + ti + ']';
                                });
                                const rdBtn = card.querySelector('.show-remove-day-btn');
                                if (rdBtn) rdBtn.setAttribute('onclick', 'showRemoveDayCard(this,' + visitIdx + ')');
                                card.querySelectorAll('.show-add-task-btn').forEach(function(b) {
                                    b.setAttribute('onclick', 'showAddTask(this,' + visitIdx + ')');
                                });
                                card.querySelectorAll('.show-remove-task-btn').forEach(function(b) {
                                    b.setAttribute('onclick', 'showRemoveTask(this,' + visitIdx + ')');
                                });
                            });
                        }
                        </script>

                        @endif
                    @elseif($inspection->ETOGO_signed_at)
                        <div class="alert alert-info mb-0">
                            <i class="mdi mdi-lock me-1"></i>
                            Schedule is locked — this agreement has been countersigned. Contact a Super Admin to make changes.
                        </div>
                    @else
                        <div class="alert alert-warning mb-0">
                            Visit schedule is locked because maintenance work has already started.
                        </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- ====== ASSIGNED TOOLS ====== -->
            @if($toolAssignments->isNotEmpty())
            <div class="card mb-4 border-info">
                <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="mdi mdi-toolbox-outline me-2"></i>Assigned Tools &amp; Equipment</h5>
                    <span class="badge bg-white text-info">{{ $toolAssignments->count() }} item(s)</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Tool / Equipment</th>
                                    <th class="text-center">Qty Out</th>
                                    <th>Ownership</th>
                                    <th>Status</th>
                                    <th>Dispatch Notes</th>
                                    <th>Returned</th>
                                    <th>Return Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($toolAssignments as $ta)
                                <tr class="{{ $ta->returned_at ? 'table-success' : '' }}">
                                    <td class="fw-semibold">{{ $ta->tool_name }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $ta->returned_at ? 'success' : 'warning text-dark' }}">
                                            {{ $ta->quantity }}
                                        </span>
                                    </td>
                                    <td class="text-capitalize small">{{ $ta->ownership_status ?? '—' }}</td>
                                    <td>
                                        @if($ta->returned_at)
                                            <span class="badge bg-success"><i class="mdi mdi-check me-1"></i>Returned</span>
                                        @else
                                            <span class="badge bg-warning text-dark"><i class="mdi mdi-hammer-wrench me-1"></i>In Use</span>
                                        @endif
                                    </td>
                                    <td class="small text-muted">{{ $ta->assign_notes ?? '—' }}</td>
                                    <td class="small">
                                        @if($ta->returned_at)
                                            {{ $ta->returned_at->format('M d, Y') }}<br>
                                            <span class="text-muted">by {{ $ta->returnedBy->name ?? 'N/A' }}</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="small text-muted">{{ $ta->return_notes ?? '—' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @elseif($inspection->status === 'completed')
            <div class="card mb-4 border-secondary">
                <div class="card-body text-muted small">
                    <i class="mdi mdi-toolbox-outline me-1"></i>No tools have been assigned to this diagnosis yet.
                    @if(Auth::user()->hasRole(['Super Admin', 'Store Manager']))
                    <a href="{{ route('tool-assignments.index') }}" class="ms-2">Assign Tools</a>
                    @endif
                </div>
            </div>
            @endif

            <!-- Property & Diagnosis Summary -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="mdi mdi-home-outline me-2"></i>Property & Diagnosis Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <th width="40%">Property Name:</th>
                                    <td><strong>{{ $inspection->property?->property_name ?? 'N/A' }}</strong></td>
                                </tr>
                                <tr>
                                    <th>Property Code:</th>
                                    <td>{{ $inspection->property?->property_code ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Property Type:</th>
                                    <td class="text-capitalize">{{ $inspection->property?->type ? str_replace('_', ' ', $inspection->property->type) : 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Units:</th>
                                    <td>{{ $inspection->property?->residential_units ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Owner Name:</th>
                                    <td><strong>{{ $inspection->owner_name ?? $inspection->property?->user?->name ?? 'N/A' }}</strong></td>
                                </tr>
                                <tr>
                                    <th>Owner Phone:</th>
                                    <td>{{ $inspection->owner_phone ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Owner Email:</th>
                                    <td>{{ $inspection->owner_email ?? 'N/A' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <th width="40%">Inspector:</th>
                                    <td>{{ $inspection->inspector?->name ?? 'Not Assigned' }}</td>
                                </tr>
                                <tr>
                                    <th>Diagnosis Date:</th>
                                    <td>{{ $inspection->scheduled_date?->format('M d, Y') ?? 'Not Scheduled' }}</td>
                                </tr>
                                <tr>
                                    <th>Completed Date:</th>
                                    <td>{{ $inspection->completed_date?->format('M d, Y h:i A') ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Status:</th>
                                    <td>
                                        <span class="badge 
                                            @if($inspection->status === 'completed') bg-success
                                            @elseif($inspection->status === 'in_progress') bg-warning
                                            @else bg-secondary
                                            @endif">
                                            {{ ucfirst($inspection->status) }}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Findings Summary — grouped by severity -->
            @php
                $inlineFindingsRaw = is_array($inspection->findings)
                    ? $inspection->findings
                    : (json_decode($inspection->getRawOriginal('findings') ?? '[]', true) ?? []);

                $quotationSnapshot = collect($activeQuotation->findings_snapshot ?? [])->values();
                $quotationApprovedIds = collect($activeQuotation->approved_finding_ids ?? [])->map(fn($id) => (int) $id);
                $makeFindingKey = function ($issueOrTask, $category) {
                    $left = strtolower(trim((string) $issueOrTask));
                    $right = strtolower(trim((string) $category));
                    return $left . '|' . $right;
                };

                $showApprovedScopeOnly =
                    !empty($activeQuotation) &&
                    (($activeQuotation->status ?? null) === 'approved') &&
                    (
                        (($inspection->quotation_status ?? null) === 'approved') ||
                        (($inspection->quotation_status ?? null) === 'shared')
                    );

                if ($showApprovedScopeOnly) {
                    $allInline = collect($inlineFindingsRaw)->values();
                    $approvedIdMap = $quotationApprovedIds->flip();

                    $filteredById = $allInline
                        ->filter(fn ($f) => $approvedIdMap->has((int) ($f['id'] ?? 0)))
                        ->values();

                    if ($filteredById->isNotEmpty()) {
                        $inlineFindingsRaw = $filteredById->all();
                    } else {
                        $approvedScopeKeys = $quotationSnapshot
                            ->filter(fn($f) => $quotationApprovedIds->contains((int) ($f['id'] ?? 0)))
                            ->map(fn($f) => $makeFindingKey(
                                $f['task_question'] ?? ($f['issue'] ?? ''),
                                $f['category'] ?? ''
                            ))
                            ->filter(fn($k) => $k !== '|')
                            ->unique()
                            ->values();

                        $inlineFindingsRaw = $allInline
                            ->filter(function ($f) use ($approvedScopeKeys, $makeFindingKey) {
                                $key = $makeFindingKey(
                                    $f['task_question'] ?? ($f['issue'] ?? ''),
                                    $f['phar_category'] ?? ($f['category'] ?? '')
                                );
                                return $approvedScopeKeys->contains($key);
                            })
                            ->values()
                            ->all();
                    }
                }

                $severityOrder = ['critical','high','noi_protection','medium','low'];
                $severityMeta  = [
                    'critical'       => ['label' => 'Urgent — Safety Critical',  'color' => '#dc3545', 'icon' => '🔴'],
                    'high'           => ['label' => 'Health & Safety Risk',       'color' => '#fd7e14', 'icon' => '🟠'],
                    'noi_protection' => ['label' => 'NOI Protection',             'color' => '#6f42c1', 'icon' => '🟣'],
                    'medium'         => ['label' => 'Value Depreciation',         'color' => '#d4a017', 'icon' => '🟡'],
                    'low'            => ['label' => 'Non-Urgent',                 'color' => '#198754', 'icon' => '🟢'],
                ];
                // Backfill issue_description + recommendation_details from quotation snapshot
                $snapshotDescMap = [];
                foreach ($quotationSnapshot as $sf) {
                    $descKey = $makeFindingKey($sf['task_question'] ?? ($sf['issue'] ?? ''), $sf['category'] ?? '');
                    if (!empty($sf['issue_description']) || !empty($sf['recommendation_details'])) {
                        $snapshotDescMap[$descKey] = $sf;
                    }
                }
                $inlineFindingsRaw = array_map(function ($f) use ($snapshotDescMap, $makeFindingKey) {
                    $descKey = $makeFindingKey($f['task_question'] ?? ($f['issue'] ?? ''), $f['phar_category'] ?? ($f['category'] ?? ''));
                    $snapshotEntry = $snapshotDescMap[$descKey] ?? [];
                    $f['issue_description']      = $f['issue_description']      ?? $snapshotEntry['issue_description']      ?? '';
                    $f['recommendation_details'] = $f['recommendation_details'] ?? $snapshotEntry['recommendation_details'] ?? '';
                    return $f;
                }, $inlineFindingsRaw);

                $groupedFindings = collect($inlineFindingsRaw)->groupBy('severity');
            @endphp
            @if(count($inlineFindingsRaw) > 0)
            <div class="card mb-4 findings-card">
                <div class="card-header" style="background:#ff9800;color:white;">
                    <h5 class="mb-0">
                        <i class="mdi mdi-clipboard-text me-2"></i>Findings Summary ({{ count($inlineFindingsRaw) }} items)
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0" id="findingsTable">
                            <thead class="table-dark">
                                <tr>
                                    <th width="4%">#</th>
                                    <th width="96%">Finding Details</th>
                                </tr>
                            </thead>
                            <tbody>
                            @php $rowNum = 0; @endphp
                            @foreach($severityOrder as $sev)
                                @if($groupedFindings->has($sev))
                                @php $meta = $severityMeta[$sev]; @endphp
                                <tr>
                                    <td colspan="2" class="fw-bold text-white py-2 px-3" style="background:{{ $meta['color'] }};">
                                        {{ $meta['icon'] }} {{ $meta['label'] }}
                                        <span class="badge bg-white ms-2" style="color:{{ $meta['color'] }};">
                                            {{ $groupedFindings[$sev]->count() }}
                                        </span>
                                    </td>
                                </tr>
                                @foreach($groupedFindings[$sev] as $finding)
                                @php
                                    $rowNum++;
                                    $recommendations = $finding['recommendations'] ?? [];
                                    $componentName = trim((string) ($finding['component'] ?? ''));
                                    if ($componentName === '' && !empty($finding['building_component_id'])) {
                                        $componentName = (string) (optional(\App\Models\BuildingComponent::find((int) $finding['building_component_id']))->name ?? '');
                                    }
                                    $affectedAreas = collect($finding['affected_areas'] ?? [])->filter(function ($area) {
                                        return is_array($area) && (
                                            !empty($area['building_system_id'])
                                            || !empty($area['building_subsystem_id'])
                                            || !empty($area['building_component_id'])
                                            || trim((string) ($area['location'] ?? '')) !== ''
                                            || trim((string) ($area['impact_description'] ?? '')) !== ''
                                        );
                                    })->values();
                                @endphp
                                <tr>
                                    <td class="text-muted small align-top">{{ $rowNum }}</td>
                                    <td class="align-top">
                                        <strong>{{ $finding['issue'] ?? '-' }}</strong>
                                        @if(!empty($finding['system']))
                                            <br><small class="text-muted">{{ $finding['system'] }}{{ !empty($finding['subsystem']) ? ' › '.$finding['subsystem'] : '' }}</small>
                                        @endif
                                        @if($componentName !== '')
                                            <div class="mt-1" style="font-size:.8rem;color:#555;"><strong>Component:</strong> {{ $componentName }}</div>
                                        @endif
                                        @if(!empty($finding['location']) || !empty($finding['spot']))
                                            <div class="mt-1" style="font-size:.8rem;color:#555;">
                                                <i class="mdi mdi-map-marker-outline"></i>
                                                {{ implode(' — ', array_filter([$finding['location'] ?? null, $finding['spot'] ?? null])) }}
                                            </div>
                                        @endif
                                        @if(!empty($finding['issue_description']))
                                            <div class="mt-1" style="font-size:.82rem;color:#333;"><em>Issue detail:</em> {{ $finding['issue_description'] }}</div>
                                        @endif
                                        @if(!empty($finding['risk_impact']))
                                            <div class="mt-1" style="font-size:.82rem;color:#333;"><em>Risk / impact:</em> {{ $finding['risk_impact'] }}</div>
                                        @endif
                                        @if(!empty($recommendations))
                                            <ul class="mb-0 mt-1 ps-3" style="font-size:.78rem;color:#444;">
                                                @foreach($recommendations as $rec)
                                                    <li>{{ $rec }}</li>
                                                @endforeach
                                            </ul>
                                        @endif
                                        @if(!empty($finding['recommendation_details']))
                                            <div class="mt-1" style="font-size:.82rem;color:#333;"><em>Recommendation detail:</em> {{ $finding['recommendation_details'] }}</div>
                                        @endif
                                        @if($affectedAreas->isNotEmpty())
                                            <div class="mt-2 p-2 rounded" style="background:#fff8e8;border:1px solid #f1d9a6;font-size:.78rem;color:#444;">
                                                <strong>Cascading / affected areas:</strong>
                                                @foreach($affectedAreas as $area)
                                                    @php
                                                        $affectedSystem = !empty($area['building_system_id']) ? optional(\App\Models\BuildingSystem::find((int) $area['building_system_id']))->name : null;
                                                        $affectedSubsystem = !empty($area['building_subsystem_id']) ? optional(\App\Models\BuildingSubsystem::find((int) $area['building_subsystem_id']))->name : null;
                                                        $affectedComponent = !empty($area['building_component_id']) ? optional(\App\Models\BuildingComponent::find((int) $area['building_component_id']))->name : null;
                                                        $affectedPath = implode(' > ', array_filter([$affectedSystem, $affectedSubsystem, $affectedComponent]));
                                                    @endphp
                                                    <div class="mt-1">
                                                        {{ $affectedPath ?: 'Affected area' }}
                                                        @if(!empty($area['location']))
                                                            <span class="text-muted"> - {{ $area['location'] }}</span>
                                                        @endif
                                                        @if(!empty($area['impact_description']))
                                                            <div>{{ $area['impact_description'] }}</div>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                        @if(!empty($finding['finding_photos']))
                                            <div class="d-flex flex-wrap gap-1 mt-2">
                                                @foreach($finding['finding_photos'] as $fp)
                                                    @php
                                                        $mediaUrl = $inspection->getStorageUrl($fp);
                                                        $isVideoEvidence = (bool) preg_match('/\.(mp4|webm|mov|avi|mkv|m4v)$/', strtolower(parse_url($fp, PHP_URL_PATH) ?: $fp));
                                                    @endphp
                                                    @if($isVideoEvidence)
                                                        <video src="{{ $mediaUrl }}" controls preload="metadata" style="height:64px;width:104px;object-fit:cover;border-radius:4px;border:1px solid #dee2e6;background:#000;"></video>
                                                    @else
                                                        <a href="{{ $mediaUrl }}" target="_blank" title="View photo">
                                                            <img src="{{ $mediaUrl }}" style="height:52px;width:52px;object-fit:cover;border-radius:4px;border:1px solid #dee2e6;" alt="Finding photo">
                                                        </a>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                                @endif
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif



            @if($inspection->summary || $inspection->recommendations || $inspection->risk_summary)
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="mdi mdi-text-box-outline me-2 text-primary"></i>Inspector Diagnosis
                    </h5>
                </div>
                <div class="card-body">
                    @if($inspection->summary)
                    <div class="mb-3">
                        <h6 class="text-primary">Notes:</h6>
                        <p>{{ $inspection->summary }}</p>
                    </div>
                    @endif
                    
                    @if($recommendationItems->isNotEmpty())
                    <div class="mb-3">
                        <h6 class="text-primary">Recommendations:</h6>
                        <ul class="mb-0 ps-3">
                            @foreach($recommendationItems as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                    
                    @if($inspection->risk_summary)
                    <div class="mb-3">
                        <h6 class="text-danger">Risk Summary:</h6>
                        <p>{{ $inspection->risk_summary }}</p>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .no-print, .btn, .alert, nav, .navbar, .sidebar, #sidebar { display: none !important; }
    .card { page-break-inside: avoid; box-shadow: none !important; border: 1px solid #dee2e6 !important; }
    .card-header { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    body { background: white !important; font-size: 11px; }
    .col-md-4, .col-md-6, .col-md-8 { width: 33.33% !important; float: left; }
    #findingsTable td, #findingsTable th { font-size: 10px; padding: 4px 6px; }
    .badge { -webkit-print-color-adjust: exact; print-color-adjust: exact; border: 1px solid #999; }
    h4, h5, h6 { page-break-after: avoid; }
    .findings-card { page-break-before: auto; }
    @page { margin: 15mm; size: A4 landscape; }
}
</style>
@endsection

