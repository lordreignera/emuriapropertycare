@extends('client.layout')

@section('title', 'My Inspections')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="mdi mdi-clipboard-check me-2"></i>My Inspections</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Property</th>
                                <th>Status</th>
                                <th>Inspection Fee</th>
                                <th>Date</th>
                                <th>Final Monthly</th>
                                <th>Work Payment</th>
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
                                        @php $status = strtolower((string) ($inspection->status ?? 'pending')); @endphp
                                        @if($status === 'completed')
                                            <span class="badge bg-success">Completed</span>
                                        @elseif($status === 'in_progress')
                                            <span class="badge bg-info text-dark">In Progress</span>
                                        @elseif($status === 'scheduled')
                                            <span class="badge bg-warning text-dark">Scheduled</span>
                                        @else
                                            <span class="badge bg-secondary">{{ ucfirst(str_replace('_', ' ', $status)) }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if(($inspection->inspection_fee_status ?? 'pending') === 'paid')
                                            <span class="badge bg-success">Paid</span>
                                        @else
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if(($inspection->status ?? null) === 'completed')
                                            {{ optional($inspection->completed_date)->format('M d, Y') ?? '-' }}
                                        @else
                                            {{ optional($inspection->scheduled_date)->format('M d, Y') ?? '-' }}
                                        @endif
                                    </td>
                                        @php
                                            $displayPrice = ($inspection->work_payment_cadence === 'monthly')
                                                ? (float) ($inspection->arp_monthly ?? $inspection->trc_monthly ?? 0)
                                                : (float) ($inspection->trc_annual ?? 0);
                                        @endphp
                                        <td>${{ number_format($displayPrice, 2) }}</td>
                                    <td>
                                        @if(($inspection->status ?? null) !== 'completed')
                                            <span class="badge bg-secondary">N/A</span>
                                        @elseif(($inspection->work_payment_status ?? 'pending') === 'paid')
                                            <span class="badge bg-success">Paid ({{ ucfirst($inspection->work_payment_cadence ?? 'monthly') }})</span>
                                        @else
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if(($inspection->status ?? null) === 'completed')
                                            <div class="d-flex flex-wrap gap-1">
                                                <a href="{{ route('client.inspections.report', $inspection->id) }}" class="btn btn-sm btn-info">
                                                    <i class="mdi mdi-eye"></i> Report
                                                </a>
                                                <a href="{{ route('client.inspections.agreement', $inspection->id) }}" class="btn btn-sm {{ $inspection->approved_by_client ? 'btn-success' : 'btn-outline-success' }}">
                                                    <i class="mdi mdi-file-sign"></i>
                                                    {{ $inspection->etogo_signed_at ? 'Agreement Finalized' : ($inspection->approved_by_client ? 'Awaiting Etogo Sign' : 'Agreement') }}
                                                </a>
                                            </div>
                                        @else
                                            <button class="btn btn-sm btn-secondary text-white border-0" style="opacity: 1; cursor: not-allowed;" disabled>
                                                Awaiting report
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No inspections yet.</td>
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
