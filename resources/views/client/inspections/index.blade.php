@extends('client.layout')

@section('title', 'Completed Inspections')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="mdi mdi-clipboard-check me-2"></i>Completed Inspection Reports</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Property</th>
                                <th>Completed</th>
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
                                    <td>{{ optional($inspection->completed_date)->format('M d, Y') ?? '-' }}</td>
                                        @php
                                            $displayMonthly = max(
                                                (float) ($inspection->scientific_final_monthly ?? 0),
                                                (float) ($inspection->arp_equivalent_final ?? 0),
                                                (float) ($inspection->base_package_price_snapshot ?? 0),
                                                (float) ($inspection->trc_monthly ?? 0)
                                            );
                                        @endphp
                                        <td>${{ number_format($displayMonthly, 2) }}</td>
                                    <td>
                                        @if(($inspection->work_payment_status ?? 'pending') === 'paid')
                                            <span class="badge bg-success">Paid ({{ ucfirst($inspection->work_payment_cadence ?? 'monthly') }})</span>
                                        @else
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('client.inspections.report', $inspection->id) }}" class="btn btn-sm btn-info">
                                            <i class="mdi mdi-eye"></i> Report
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">No completed inspections yet.</td>
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
