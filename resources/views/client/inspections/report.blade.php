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

                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="border rounded p-2 bg-light">
                            <small class="text-muted d-block">CPI Total Score</small>
                            <strong>{{ (int)($inspection->cpi_total_score ?? 0) }}</strong>
                        </div>
                    </div>
                    <div class="col-md-4 mt-2 mt-md-0">
                        <div class="border rounded p-2 bg-light">
                            <small class="text-muted d-block">CPI Band</small>
                            <strong>{{ $inspection->cpi_band ?? '-' }}</strong>
                        </div>
                    </div>
                    <div class="col-md-4 mt-2 mt-md-0">
                        <div class="border rounded p-2 bg-light">
                            <small class="text-muted d-block">CPI Multiplier</small>
                            <strong>{{ number_format((float)($inspection->cpi_multiplier ?? $inspection->multiplier_final ?? 1), 2) }}x</strong>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <h6 class="mb-2 text-primary"><i class="mdi mdi-format-list-numbered me-1"></i>CPI Domain Results (1-6)</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Domain</th>
                                    <th class="text-end">Score</th>
                                    <th>Result Details</th>
                                    <th>Inspector Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($domains as $domain)
                                    @php
                                        $domainNo = (int)($domain->domain_number ?? 0);
                                        $scoreField = 'domain_' . $domainNo . '_score';
                                        $notesField = 'domain_' . $domainNo . '_notes';
                                        $domainScore = (int)($inspection->{$scoreField} ?? 0);
                                        $domainMax = (int)($domain->max_possible_points ?? 0);
                                    @endphp
                                    <tr>
                                        <td>
                                            <strong>D{{ $domainNo }}:</strong> {{ $domain->domain_name ?? ('Domain ' . $domainNo) }}
                                        </td>
                                        <td class="text-end">
                                            <strong>{{ $domainScore }}</strong>@if($domainMax > 0) / {{ $domainMax }} @endif
                                        </td>
                                        <td>
                                            @switch($domainNo)
                                                @case(1)
                                                    Unit Shutoffs: <strong>{{ ucfirst((string)($inspection->cpi_unit_shutoffs ?? '-')) }}</strong>,
                                                    Shared Risers: <strong>{{ ucfirst((string)($inspection->cpi_shared_risers ?? '-')) }}</strong>,
                                                    Static Pressure: <strong>{{ $inspection->cpi_static_pressure ?? '-' }}</strong>,
                                                    Isolation Zones: <strong>{{ ucfirst((string)($inspection->cpi_isolation_zones ?? '-')) }}</strong>
                                                    @break
                                                @case(2)
                                                    Supply Material: <strong>{{ $inspection->cpi_supply_material_name ?? '-' }}</strong>,
                                                    Drain Unknown: <strong>{{ ucfirst((string)($inspection->cpi_drain_material_unknown ?? '-')) }}</strong>
                                                    @break
                                                @case(3)
                                                    Building Age: <strong>{{ $inspection->building_age_calculated ?? '-' }}</strong>,
                                                    Fixture Age: <strong>{{ $inspection->cpi_fixture_age ?? '-' }}</strong>,
                                                    Systems Documented: <strong>{{ ucfirst((string)($inspection->cpi_systems_documented ?? '-')) }}</strong>
                                                    @break
                                                @case(4)
                                                    Containment Category: <strong>{{ $inspection->cpi_containment_category_name ?? '-' }}</strong>
                                                    @break
                                                @case(5)
                                                    Crawl Access: <strong>{{ $inspection->cpi_crawl_access_name ?? '-' }}</strong>,
                                                    Roof Access: <strong>{{ $inspection->cpi_roof_access_name ?? '-' }}</strong>,
                                                    Equipment: <strong>{{ $inspection->cpi_equipment_requirement_name ?? '-' }}</strong>,
                                                    Time to Access: <strong>{{ $inspection->cpi_time_to_access ?? '-' }}</strong>
                                                    @break
                                                @case(6)
                                                    Complexity Category: <strong>{{ $inspection->cpi_complexity_category_name ?? '-' }}</strong>
                                                    @break
                                                @default
                                                    -
                                            @endswitch
                                        </td>
                                        <td>{{ $inspection->{$notesField} ?: '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">No CPI domain definitions found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
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

                <div class="mb-4">
                    <h6 class="mb-2 text-primary"><i class="mdi mdi-hammer-wrench me-1"></i>FRLC Detailed Breakdown</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Task / Finding</th>
                                    <th>Category</th>
                                    <th class="text-center">Priority</th>
                                    <th class="text-center">Included</th>
                                    <th class="text-end">Labour Hours</th>
                                    <th class="text-end">Hourly Rate</th>
                                    <th class="text-end">Labour Cost</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($findings as $finding)
                                    @php
                                        $labourHours = (float)($finding->labour_hours ?? 0);
                                        $hourlyRate = (float)($inspection->labour_hourly_rate ?? 165);
                                        $labourCost = $labourHours * $hourlyRate;
                                    @endphp
                                    <tr>
                                        <td>
                                            <div>{{ $finding->task_question ?: '-' }}</div>
                                            @if(!empty($finding->notes))
                                                <small class="text-muted">{{ $finding->notes }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $finding->category ?: '-' }}</td>
                                        <td class="text-center">{{ $finding->priority ?: '-' }}</td>
                                        <td class="text-center">{{ (int)($finding->included_yn ?? 0) === 1 ? 'Yes' : 'No' }}</td>
                                        <td class="text-end">{{ number_format($labourHours, 2) }}</td>
                                        <td class="text-end">${{ number_format($hourlyRate, 2) }}</td>
                                        <td class="text-end">${{ number_format($labourCost, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">No FRLC findings available for this inspection.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mb-4">
                    <h6 class="mb-2 text-primary"><i class="mdi mdi-package-variant-closed me-1"></i>FMC Detailed Breakdown</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Material</th>
                                    <th>Category</th>
                                    <th class="text-end">Quantity</th>
                                    <th>Unit</th>
                                    <th class="text-end">Unit Cost</th>
                                    <th class="text-end">Line Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($materials as $material)
                                    <tr>
                                        <td>
                                            <div>{{ $material->material_name ?: '-' }}</div>
                                            @if(!empty($material->notes))
                                                <small class="text-muted">{{ $material->notes }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $material->category ?: '-' }}</td>
                                        <td class="text-end">{{ number_format((float)($material->quantity ?? 0), 2) }}</td>
                                        <td>{{ $material->unit ?: '-' }}</td>
                                        <td class="text-end">${{ number_format((float)($material->unit_cost ?? 0), 2) }}</td>
                                        <td class="text-end">${{ number_format((float)($material->line_total ?? (($material->quantity ?? 0) * ($material->unit_cost ?? 0))), 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">No FMC materials available for this inspection.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
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
