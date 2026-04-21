@extends('admin.layout')

@section('content')
<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">

                {{-- Header --}}
                <div class="d-flex align-items-start justify-content-between mb-3 flex-wrap gap-2">
                    <div>
                        <a href="{{ route('admin.tool-settings.index') }}" class="text-muted small text-decoration-none">
                            <i class="mdi mdi-arrow-left me-1"></i>Tool Settings
                        </a>
                        <h4 class="card-title mb-0 mt-1">
                            <i class="mdi mdi-toolbox-outline me-2 text-primary"></i>
                            {{ $toolSetting->tool_name }} — Deployment Logs
                        </h4>
                        <div class="text-muted small mt-1">
                            <span class="badge {{ $toolSetting->ownership_status === 'owned' ? 'bg-primary' : 'bg-warning text-dark' }} me-1">
                                {{ ucfirst($toolSetting->ownership_status) }}
                            </span>
                            <span class="badge {{ $toolSetting->availability_status === 'available' ? 'bg-success' : 'bg-danger' }}">
                                {{ $toolSetting->availability_status === 'available' ? 'Available' : 'Out / Non-Available' }}
                            </span>
                            @if($toolSetting->quantity)
                                <span class="ms-2">Qty: {{ $toolSetting->quantity }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="d-flex gap-3 text-center">
                        <div>
                            <div class="fw-bold text-danger" style="font-size:1.4rem;">{{ $activeCount }}</div>
                            <div class="text-muted small">OUT / ACTIVE</div>
                        </div>
                        <div>
                            <div class="fw-bold text-success" style="font-size:1.4rem;">{{ $returnedCount }}</div>
                            <div class="text-muted small">RETURNED</div>
                        </div>
                    </div>
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

                {{-- Filter tabs --}}
                <div class="mb-3">
                    <div class="btn-group btn-group-sm" role="group">
                        <a href="{{ route('admin.tool-settings.logs', [$toolSetting, 'status' => 'all']) }}"
                           class="btn {{ $filterStatus === 'all' ? 'btn-primary' : 'btn-outline-primary' }}">
                            All <span class="badge bg-light text-dark ms-1">{{ $activeCount + $returnedCount }}</span>
                        </a>
                        <a href="{{ route('admin.tool-settings.logs', [$toolSetting, 'status' => 'active']) }}"
                           class="btn {{ $filterStatus === 'active' ? 'btn-danger' : 'btn-outline-danger' }}">
                            Active / Out <span class="badge bg-light text-dark ms-1">{{ $activeCount }}</span>
                        </a>
                        <a href="{{ route('admin.tool-settings.logs', [$toolSetting, 'status' => 'returned']) }}"
                           class="btn {{ $filterStatus === 'returned' ? 'btn-success' : 'btn-outline-success' }}">
                            Returned <span class="badge bg-light text-dark ms-1">{{ $returnedCount }}</span>
                        </a>
                    </div>
                </div>

                @if($assignments->isEmpty())
                    <div class="text-center py-5 text-muted">
                        <i class="mdi mdi-clipboard-text-off-outline" style="font-size:3rem;"></i>
                        <p class="mt-2">No deployment records found for this tool.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Project / Property</th>
                                    <th>Address</th>
                                    <th>Qty Used</th>
                                    <th>Inspection Status</th>
                                    <th>Deployed On</th>
                                    <th>Status</th>
                                    <th>Returned By</th>
                                    <th>Return Notes</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($assignments as $i => $assignment)
                                    @php
                                        $property    = $assignment->inspection?->property;
                                        $isReturned  = $assignment->returned_at !== null;
                                    @endphp
                                    <tr class="{{ $isReturned ? '' : 'table-warning' }}">
                                        <td>{{ $i + 1 }}</td>
                                        <td>
                                            <div class="fw-semibold">
                                                {{ $property->property_name ?? '— No Property —' }}
                                            </div>
                                            @if($assignment->inspection)
                                                <div class="text-muted small">
                                                    Inspection #{{ $assignment->inspection->id }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="text-muted small">
                                            {{ $property->property_address ?? '—' }}
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border">{{ $assignment->quantity ?? 1 }}</span>
                                        </td>
                                        <td>
                                            @if($assignment->inspection)
                                                @php
                                                    $iStatus = $assignment->inspection->status ?? 'unknown';
                                                    $iBadge  = match($iStatus) {
                                                        'completed'   => 'bg-success',
                                                        'in_progress' => 'bg-primary',
                                                        'scheduled'   => 'bg-info',
                                                        default       => 'bg-secondary',
                                                    };
                                                @endphp
                                                <span class="badge {{ $iBadge }}">{{ ucfirst(str_replace('_',' ', $iStatus)) }}</span>
                                                @if($assignment->inspection->etogo_signed_at)
                                                    <span class="badge bg-success ms-1" title="Work authorised">
                                                        <i class="mdi mdi-pen-check"></i> Signed
                                                    </span>
                                                @endif
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="small text-muted">
                                            {{ $assignment->created_at?->format('d M Y') ?? '—' }}
                                        </td>
                                        <td>
                                            @if($isReturned)
                                                <span class="badge bg-success">
                                                    <i class="mdi mdi-check-circle me-1"></i>Returned
                                                </span>
                                                <div class="text-muted small mt-1">
                                                    {{ $assignment->returned_at->format('d M Y, H:i') }}
                                                </div>
                                            @else
                                                <span class="badge bg-danger">
                                                    <i class="mdi mdi-alert-circle me-1"></i>Out
                                                </span>
                                            @endif
                                        </td>
                                        <td class="small">
                                            {{ $assignment->returnedBy?->name ?? ($isReturned ? 'Unknown' : '—') }}
                                        </td>
                                        <td class="small text-muted">
                                            {{ $assignment->return_notes ?? '—' }}
                                        </td>
                                        <td>
                                            @if(!$isReturned)
                                                <button type="button"
                                                        class="btn btn-sm btn-success"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#returnModal-{{ $assignment->id }}">
                                                    <i class="mdi mdi-keyboard-return me-1"></i>Mark Returned
                                                </button>

                                                {{-- Return Modal --}}
                                                <div class="modal fade" id="returnModal-{{ $assignment->id }}" tabindex="-1">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">
                                                                    <i class="mdi mdi-keyboard-return me-2 text-success"></i>
                                                                    Mark Tool Returned
                                                                </h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <form action="{{ route('admin.admin-tool-assignments.return', $assignment) }}"
                                                                  method="POST">
                                                                @csrf
                                                                <div class="modal-body">
                                                                    <p class="text-muted small mb-3">
                                                                        Confirm that <strong>{{ $toolSetting->tool_name }}</strong>
                                                                        has been returned from
                                                                        <strong>{{ $property->property_name ?? 'this project' }}</strong>.
                                                                    </p>
                                                                    <div>
                                                                        <label class="form-label fw-semibold">Return Notes <span class="text-muted fw-normal">(optional)</span></label>
                                                                        <textarea name="return_notes" class="form-control" rows="3"
                                                                                  placeholder="Condition on return, any damage, notes…"></textarea>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                                                    <button type="submit" class="btn btn-success btn-sm">
                                                                        <i class="mdi mdi-check me-1"></i>Confirm Return
                                                                    </button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

            </div>
        </div>
    </div>
</div>
@endsection
