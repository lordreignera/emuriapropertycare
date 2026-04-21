@extends('admin.layout')

@section('content')
<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">

                {{-- Header --}}
                <div class="d-flex align-items-start justify-content-between mb-3 flex-wrap gap-2">
                    <div>
                        <h4 class="card-title mb-0">
                            <i class="mdi mdi-toolbox-outline me-2 text-primary"></i>
                            Tool Assignment &amp; Return
                        </h4>
                        <p class="text-muted small mb-0 mt-1">
                            Assign tools to projects where both client and Etogo manager have signed.
                            Track quantities deployed and returned.
                        </p>
                    </div>
                    @if($unreturnedCount > 0)
                        <span class="badge bg-danger" style="font-size:0.9rem;padding:0.45em 0.85em;">
                            {{ $unreturnedCount }} Tool{{ $unreturnedCount !== 1 ? 's' : '' }} Still Out
                        </span>
                    @else
                        <span class="badge bg-success" style="font-size:0.9rem;padding:0.45em 0.85em;">
                            All Tools Returned
                        </span>
                    @endif
                </div>

                {{-- Flash messages --}}
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if($assignments->isEmpty())
                    <div class="text-center py-5 text-muted">
                        <i class="mdi mdi-toolbox-outline" style="font-size:3rem;opacity:.4;"></i>
                        <div class="mt-2">No tool assignments found.</div>
                        <div class="small mt-1">
                            Tools appear here once both the client and Etogo manager have signed the agreement
                            and the PHAR assessment is complete.
                        </div>
                    </div>
                @else

                @php
                    $grouped = $assignments->groupBy(function($a) {
                        return optional($a->inspection)->property_name ?? 'Unknown Property';
                    });
                @endphp

                @foreach($grouped as $propertyName => $propertyAssignments)
                    @php
                        $inspection    = $propertyAssignments->first()->inspection;
                        $property      = $inspection?->property;
                        $allReturned   = $propertyAssignments->every(fn($a) => $a->isReturned());
                        $outstanding   = $propertyAssignments->where('returned_at', null)->where('quantity', '>', 0)->count();
                        $anyAssigned   = $propertyAssignments->where('quantity', '>', 0)->count() > 0;
                    @endphp

                    <div class="card mb-4 border {{ $allReturned ? 'border-success' : ($anyAssigned ? 'border-warning' : 'border-secondary') }}" style="border-width:2px!important;">
                        <div class="card-header d-flex align-items-center justify-content-between py-2 flex-wrap gap-2"
                             style="background:{{ $allReturned ? '#f0fff4' : ($anyAssigned ? '#fffbf0' : '#f8f9fa') }};">
                            <div>
                                <strong class="me-2">{{ $propertyName }}</strong>
                                @if($property?->property_address)
                                    <span class="text-muted small">{{ $property->property_address }}</span>
                                @endif
                                @if($inspection)
                                    <div class="small text-muted mt-1">
                                        <i class="mdi mdi-file-sign me-1"></i>
                                        Signed: {{ $inspection->etogo_signed_at?->format('d M Y') ?? '&mdash;' }}
                                        @if($inspection->work_schedule && $inspection->work_schedule !== '[]')
                                            &nbsp;|&nbsp;<i class="mdi mdi-calendar-check me-1"></i>Scheduled
                                        @else
                                            &nbsp;|&nbsp;<span class="text-warning"><i class="mdi mdi-calendar-clock me-1"></i>Awaiting Schedule</span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                            <div>
                                @if($allReturned)
                                    <span class="badge bg-success">All Returned</span>
                                @elseif($outstanding > 0)
                                    <span class="badge bg-warning text-dark">{{ $outstanding }} Out</span>
                                @else
                                    <span class="badge bg-secondary">Not Yet Assigned</span>
                                @endif
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Tool</th>
                                        <th class="text-center">Stock Total</th>
                                        <th class="text-center">Remaining</th>
                                        <th class="text-center">Assigned Qty</th>
                                        <th class="text-center">Ownership</th>
                                        <th>Last Updated</th>
                                        <th>Status</th>
                                        <th>Returned By</th>
                                        <th>Return Notes</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($propertyAssignments as $assignment)
                                        @php
                                            $ts         = $assignment->toolSetting;
                                            $stockTotal = $ts ? (int)$ts->quantity : 0;
                                            $deployed   = $ts ? (int)($deployedByTool[$ts->id] ?? 0) : 0;
                                            $remaining  = max(0, $stockTotal - $deployed);
                                            $maxForRow  = $remaining + (int)$assignment->quantity;
                                        @endphp
                                        <tr class="{{ $assignment->isReturned() ? 'table-success' : ($assignment->quantity > 0 ? '' : 'table-light') }}">
                                            <td><strong>{{ $assignment->tool_name }}</strong></td>
                                            <td class="text-center fw-semibold">{{ $stockTotal ?: '&mdash;' }}</td>
                                            <td class="text-center">
                                                @if($ts)
                                                    <span class="badge {{ $remaining <= 0 ? 'bg-danger' : ($remaining < 3 ? 'bg-warning text-dark' : 'bg-success') }}">
                                                        {{ $remaining }}
                                                    </span>
                                                @else
                                                    <span class="text-muted">&mdash;</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if($assignment->quantity > 0)
                                                    <span class="badge bg-primary">{{ $assignment->quantity }}</span>
                                                @else
                                                    <span class="text-muted small">Not assigned</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <span class="badge {{ $assignment->ownership_status === 'owned' ? 'bg-primary' : 'bg-secondary' }}">
                                                    {{ ucfirst($assignment->ownership_status ?? '&mdash;') }}
                                                </span>
                                            </td>
                                            <td class="text-nowrap" style="font-size:0.85rem;">
                                                {{ $assignment->updated_at?->format('d M Y') }}
                                            </td>
                                            <td>
                                                @if($assignment->isReturned())
                                                    <span class="badge bg-success"><i class="mdi mdi-check-circle me-1"></i>Returned</span>
                                                    <div class="text-muted" style="font-size:0.78rem;">{{ $assignment->returned_at->format('d M Y, H:i') }}</div>
                                                @elseif($assignment->quantity > 0)
                                                    <span class="badge bg-danger"><i class="mdi mdi-clock-outline me-1"></i>Out</span>
                                                @else
                                                    <span class="badge bg-secondary">Pending</span>
                                                @endif
                                            </td>
                                            <td style="font-size:0.85rem;">{{ optional($assignment->returnedBy)->name ?? '&mdash;' }}</td>
                                            <td style="font-size:0.82rem;max-width:160px;">{{ $assignment->return_notes ?? '&mdash;' }}</td>
                                            <td class="text-center">
                                                <div class="d-flex gap-1 justify-content-center flex-nowrap">
                                                    @if(! $assignment->isReturned())
                                                        <button class="btn btn-sm btn-outline-primary"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#assignModal{{ $assignment->id }}"
                                                                title="Assign / Update Quantity">
                                                            <i class="mdi mdi-package-variant-closed"></i>
                                                        </button>
                                                        @if($assignment->quantity > 0)
                                                            <button class="btn btn-sm btn-outline-success"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#returnModal{{ $assignment->id }}"
                                                                    title="Mark Returned">
                                                                <i class="mdi mdi-keyboard-return"></i>
                                                            </button>
                                                        @endif
                                                    @else
                                                        <span class="text-success small"><i class="mdi mdi-check"></i> Done</span>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach

                @endif

            </div>
        </div>
    </div>
</div>

{{-- ===== MODALS ===== --}}
@foreach($assignments as $assignment)
    @php
        $ts         = $assignment->toolSetting;
        $stockTotal = $ts ? (int)$ts->quantity : 0;
        $deployed   = $ts ? (int)($deployedByTool[$ts->id] ?? 0) : 0;
        $remaining  = max(0, $stockTotal - $deployed);
        $maxForRow  = $remaining + (int)$assignment->quantity;
    @endphp

    @if(! $assignment->isReturned())
    <div class="modal fade" id="assignModal{{ $assignment->id }}" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content bg-white text-dark">
                <form action="{{ route('tool-assignments.assign', $assignment) }}" method="POST">
                    @csrf
                    <div class="modal-header bg-white text-dark border-bottom">
                        <h5 class="modal-title text-dark">
                            <i class="mdi mdi-package-variant-closed me-2 text-primary"></i>
                            Assign Tool to Project
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body bg-white text-dark">
                        <div class="alert alert-light border mb-3">
                            <div class="fw-semibold">{{ $assignment->tool_name }}</div>
                            <div class="text-muted small">Project: <strong>{{ optional($assignment->inspection)->property_name ?? '&mdash;' }}</strong></div>
                        </div>
                        <div class="row g-2 mb-3 text-center">
                            <div class="col-4">
                                <div class="border rounded p-2 bg-white">
                                    <div class="fw-bold fs-5 text-dark">{{ $stockTotal }}</div>
                                    <div class="text-muted" style="font-size:0.75rem;">Total Stock</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="border rounded p-2" style="background:#fff8e1;">
                                    <div class="fw-bold fs-5 text-warning">{{ $deployed }}</div>
                                    <div class="text-muted" style="font-size:0.75rem;">Deployed</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="border rounded p-2" style="{{ $remaining <= 0 ? 'background:#fdecea;' : 'background:#e8f5e9;' }}">
                                    <div class="fw-bold fs-5 {{ $remaining <= 0 ? 'text-danger' : 'text-success' }}">{{ $remaining }}</div>
                                    <div class="text-muted" style="font-size:0.75rem;">Available</div>
                                </div>
                            </div>
                        </div>
                        @if($remaining <= 0 && $assignment->quantity == 0)
                            <div class="alert alert-danger py-2 small mb-3">
                                <i class="mdi mdi-alert me-1"></i>
                                No stock available — all units are currently deployed elsewhere.
                            </div>
                        @endif
                        <div class="mb-2">
                            <label class="form-label fw-semibold">
                                Quantity to Assign
                                <span class="text-muted fw-normal small">(0&ndash;{{ $maxForRow }})</span>
                            </label>
                            <input type="number" name="quantity" class="form-control"
                                   min="0" max="{{ $maxForRow }}"
                                   value="{{ $assignment->quantity }}"
                                   onwheel="this.blur()"
                                   {{ $maxForRow <= 0 ? 'disabled' : '' }}>
                            <div class="form-text">Set to 0 to un-assign this tool from the project.</div>
                        </div>
                    </div>
                    <div class="modal-footer bg-white border-top">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm" {{ $maxForRow <= 0 ? 'disabled' : '' }}>
                            <i class="mdi mdi-check me-1"></i>Save Assignment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    @if(! $assignment->isReturned() && $assignment->quantity > 0)
    <div class="modal fade" id="returnModal{{ $assignment->id }}" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content bg-white text-dark">
                <form action="{{ route('tool-assignments.return', $assignment) }}" method="POST">
                    @csrf
                    <div class="modal-header bg-white text-dark border-bottom">
                        <h5 class="modal-title text-dark">
                            <i class="mdi mdi-keyboard-return me-2 text-success"></i>
                            Mark Tool Returned
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body bg-white text-dark">
                        <div class="alert alert-light border mb-3">
                            <strong>{{ $assignment->tool_name }}</strong>
                            <div class="text-muted small">
                                {{ optional($assignment->inspection)->property_name ?? '&mdash;' }}
                                &mdash; Qty assigned: <strong>{{ $assignment->quantity }}</strong>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Return Notes <span class="text-muted fw-normal">(optional)</span></label>
                            <textarea name="return_notes" class="form-control" rows="3"
                                      placeholder="e.g. Returned in good condition, minor wear noted..."></textarea>
                        </div>
                        <div class="alert alert-info py-2 small">
                            <i class="mdi mdi-information-outline me-1"></i>
                            Recorded by <strong>{{ Auth::user()->name }}</strong> at <strong>{{ now()->format('d M Y, H:i') }}</strong>.
                            The {{ $assignment->quantity }} unit(s) will return to available stock.
                        </div>
                    </div>
                    <div class="modal-footer bg-white border-top">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success btn-sm">
                            <i class="mdi mdi-check me-1"></i>Confirm Return
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
@endforeach
@endsection
