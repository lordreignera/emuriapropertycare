@extends('client.layout')

@section('title', 'My Quotations')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-primary text-white d-flex align-items-center justify-content-between">
                <h5 class="mb-0">
                    <i class="mdi mdi-file-check-outline me-2"></i>My Quotations
                </h5>
            </div>

            {{-- Filter Tabs --}}
            <div class="card-header bg-white border-bottom-0 pb-0">
                <ul class="nav nav-tabs card-header-tabs">
                    <li class="nav-item">
                        <a class="nav-link {{ $filter === 'all' ? 'active' : '' }}"
                           href="{{ route('client.inspections.quotations', ['filter' => 'all']) }}">
                            All Quotations
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $filter === 'pending' ? 'active' : '' }}"
                           href="{{ route('client.inspections.quotations', ['filter' => 'pending']) }}">
                            Pending Approval
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $filter === 'approved' ? 'active' : '' }}"
                           href="{{ route('client.inspections.quotations', ['filter' => 'approved']) }}">
                            Approved
                        </a>
                    </li>
                </ul>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Property</th>
                                <th>Quotation Status</th>
                                <th>Shared On</th>
                                <th>Inspection Status</th>
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
                                        @php $qs = $inspection->quotation_status ?? ''; @endphp
                                        @if($qs === 'approved')
                                            <span class="badge bg-success">Approved</span>
                                        @elseif($qs === 'client_reviewing')
                                            <span class="badge bg-info text-dark">Under Review</span>
                                        @elseif($qs === 'shared')
                                            <span class="badge bg-warning text-dark">Awaiting Your Approval</span>
                                        @else
                                            <span class="badge bg-secondary">{{ ucfirst(str_replace('_', ' ', $qs)) }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $inspection->quotation_shared_at
                                            ? \Carbon\Carbon::parse($inspection->quotation_shared_at)->format('M d, Y')
                                            : '—' }}
                                    </td>
                                    <td>
                                        @php $is = strtolower((string) ($inspection->status ?? 'pending')); @endphp
                                        @if($is === 'completed')
                                            <span class="badge bg-success">Completed</span>
                                        @elseif($is === 'in_progress')
                                            <span class="badge bg-info text-dark">In Progress</span>
                                        @elseif($is === 'scheduled')
                                            <span class="badge bg-warning text-dark">Scheduled</span>
                                        @else
                                            <span class="badge bg-secondary">{{ ucfirst(str_replace('_', ' ', $is)) }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if(($inspection->quotation_status ?? '') === 'approved')
                                            <a href="{{ route('client.inspections.quotation', $inspection->id) }}"
                                               class="btn btn-sm btn-success">
                                                <i class="mdi mdi-eye me-1"></i>View Approved Quotation
                                            </a>
                                        @else
                                            <a href="{{ route('client.inspections.quotation', $inspection->id) }}"
                                               class="btn btn-sm btn-primary">
                                                <i class="mdi mdi-file-check-outline me-1"></i>Review &amp; Approve Quotation
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        @if($filter === 'pending')
                                            No quotations awaiting approval.
                                        @elseif($filter === 'approved')
                                            No approved quotations yet.
                                        @else
                                            No quotations available yet.
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
@endsection
