@extends('client.layout')

@section('title', 'Inspection Report & Breakdown')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="mdi mdi-file-document-outline me-2"></i>Inspection Report & Pricing Breakdown</h5>
                <a href="{{ route('client.inspections.index') }}" class="btn btn-light btn-sm">Back</a>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <p class="mb-1"><strong>Property:</strong> {{ $inspection->property?->property_name }}</p>
                        <p class="mb-1"><strong>Code:</strong> {{ $inspection->property?->property_code }}</p>
                        <p class="mb-0"><strong>Status:</strong> {{ ucfirst($inspection->status) }}</p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <p class="mb-1"><strong>Completed:</strong> {{ optional($inspection->completed_date)->format('M d, Y h:i A') ?? '-' }}</p>
                        <p class="mb-1"><strong>Package Floor:</strong> ${{ number_format((float)($inspection->base_package_price_snapshot ?? 0), 2) }}/month</p>
                        <p class="mb-0"><strong>Work Payment:</strong>
                            @if(($inspection->work_payment_status ?? 'pending') === 'paid')
                                <span class="badge bg-success">Paid ({{ ucfirst($inspection->work_payment_cadence ?? 'monthly') }})</span>
                            @else
                                <span class="badge bg-warning text-dark">Pending</span>
                            @endif
                        </p>
                    </div>
                </div>

                <div class="table-responsive mb-3">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Component</th>
                                <th class="text-end">Annual</th>
                                <th class="text-end">Monthly</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td>BDC</td><td class="text-end">${{ number_format((float)($inspection->bdc_annual ?? 0), 2) }}</td><td class="text-end">${{ number_format((float)($inspection->bdc_monthly ?? 0), 2) }}</td></tr>
                            <tr><td>FRLC</td><td class="text-end">${{ number_format((float)($inspection->frlc_annual ?? 0), 2) }}</td><td class="text-end">${{ number_format((float)($inspection->frlc_monthly ?? 0), 2) }}</td></tr>
                            <tr><td>FMC</td><td class="text-end">${{ number_format((float)($inspection->fmc_annual ?? 0), 2) }}</td><td class="text-end">${{ number_format((float)($inspection->fmc_monthly ?? 0), 2) }}</td></tr>
                            <tr class="table-primary"><td><strong>TRC</strong></td><td class="text-end"><strong>${{ number_format((float)($inspection->trc_annual ?? 0), 2) }}</strong></td><td class="text-end"><strong>${{ number_format((float)($inspection->trc_monthly ?? 0), 2) }}</strong></td></tr>
                            <tr><td>ARP</td><td class="text-end">—</td><td class="text-end">${{ number_format((float)($inspection->arp_monthly ?? 0), 2) }}</td></tr>
                            <tr><td>Multiplier</td><td class="text-end">—</td><td class="text-end">{{ number_format((float)($inspection->multiplier_final ?? 1), 2) }}x</td></tr>
                            <tr class="table-success"><td><strong>Final Monthly</strong></td><td class="text-end">—</td><td class="text-end"><strong>${{ number_format((float)($inspection->scientific_final_monthly ?? $inspection->arp_equivalent_final ?? 0), 2) }}</strong></td></tr>
                        </tbody>
                    </table>
                </div>

                @if(($inspection->work_payment_status ?? 'pending') !== 'paid')
                    <div class="d-flex gap-2">
                        <a href="{{ route('client.inspections.work-payment', ['inspection' => $inspection->id, 'cadence' => 'monthly']) }}" class="btn btn-success">
                            <i class="mdi mdi-credit-card me-1"></i>Pay Monthly
                        </a>
                        <a href="{{ route('client.inspections.work-payment', ['inspection' => $inspection->id, 'cadence' => 'annual']) }}" class="btn btn-outline-success">
                            <i class="mdi mdi-credit-card-settings me-1"></i>Pay Annual
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
