@extends('admin.layout')

@section('title', 'Inspection Report & Pricing Breakdown')

@section('content')
<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="card-title mb-0">
                            <i class="mdi mdi-clipboard-check-outline me-2 text-success"></i>
                            Inspection Report & Pricing Breakdown
                        </h4>
                        <p class="text-muted small mb-0">Complete breakdown of inspection findings and pricing calculation</p>
                    </div>
                    <div>
                        <a href="{{ route('inspections.index') }}" class="btn btn-light btn-sm">
                            <i class="mdi mdi-arrow-left me-1"></i>Back to Inspections
                        </a>
                        @if($inspection->status === 'completed')
                        <a href="{{ route('inspections.download-invoice', $inspection->id) }}" class="btn btn-success btn-sm">
                            <i class="mdi mdi-download me-1"></i>Download Invoice
                        </a>
                        @endif
                    </div>
                </div>

                @if($inspection->bdc_annual === null || $inspection->bdc_annual == 0)
                <div class="alert alert-warning alert-dismissible fade show">
                    <i class="mdi mdi-alert-circle me-2"></i>
                    <strong>Calculations are missing!</strong> This inspection may have been created before the pricing calculation system was implemented.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                @endif

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="mdi mdi-check-circle me-2"></i>{{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

            <!-- Property & Inspection Summary -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="mdi mdi-home-outline me-2"></i>Property & Inspection Information
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
                                    <th>Phase 1 Package:</th>
                                    <td>{{ $inspection->service_package_name ?? 'Not Snapshotted' }}</td>
                                </tr>
                                <tr>
                                    <th>Phase 1 Base Price:</th>
                                    <td>${{ number_format((float)($inspection->base_price_snapshot ?? 0), 2) }}/month</td>
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
                                    <th>Inspection Date:</th>
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

            <!-- Findings Summary -->
            @if($findings->count() > 0)
            <div class="card mb-4">
                <div class="card-header" style="background: #ff9800; color: white;">
                    <h5 class="mb-0">
                        <i class="mdi mdi-clipboard-text me-2"></i>Findings Summary ({{ $findings->count() }} items)
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="30%">Task / Issue</th>
                                    <th width="15%">Category</th>
                                    <th width="8%">Priority</th>
                                    <th width="10%">Included?</th>
                                    <th width="12%">Labour Hours</th>
                                    <th width="12%">Material Cost</th>
                                    <th width="8%">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($findings as $finding)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $finding->task_question ?? '-' }}</td>
                                    <td>
                                        <span class="badge bg-secondary">{{ $finding->category ?? 'General' }}</span>
                                    </td>
                                    <td class="text-center">
                                        @if($finding->priority == '1')
                                            <span class="badge bg-danger">High</span>
                                        @elseif($finding->priority == '2')
                                            <span class="badge bg-warning">Medium</span>
                                        @else
                                            <span class="badge bg-info">Low</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($finding->included_yn)
                                            <i class="mdi mdi-check-circle text-success fs-5"></i>
                                        @else
                                            <i class="mdi mdi-close-circle text-danger fs-5"></i>
                                        @endif
                                    </td>
                                    <td class="text-end">{{ number_format($finding->labour_hours, 1) }} hrs</td>
                                    <td class="text-end">${{ number_format($finding->material_cost, 2) }}</td>
                                    <td class="text-end">
                                        <strong>${{ number_format($finding->labour_cost + $finding->material_cost, 2) }}</strong>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-secondary">
                                <tr>
                                    <th colspan="5" class="text-end">TOTALS:</th>
                                    <th class="text-end">{{ number_format($findings->sum('labour_hours'), 1) }} hrs</th>
                                    <th class="text-end">${{ number_format($findings->sum('material_cost'), 2) }}</th>
                                    <th class="text-end">${{ number_format($findings->sum('labour_cost') + $findings->sum('material_cost'), 2) }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            <!-- Pricing Breakdown -->
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="mdi mdi-calculator me-2"></i>Pricing Calculation Breakdown
                    </h5>
                </div>
                <div class="card-body">
                    
                    <!-- Step 1: Cost Components -->
                    <div class="mb-4">
                        <h6 class="text-primary border-bottom pb-2">
                            <i class="mdi mdi-numeric-1-circle me-2"></i>Cost Components (Annual)
                        </h6>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="text-muted mb-1">Base Deployment Cost (BDC)</h6>
                                        <div class="fs-4">${{ number_format($inspection->bdc_annual ?? 0, 2) }}</div>
                                        <small class="text-muted">Operational baseline for property care</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="text-muted mb-1">Findings Remediation Labour (FRLC)</h6>
                                        <div class="fs-4">${{ number_format($inspection->frlc_annual ?? 0, 2) }}</div>
                                        <small class="text-muted">{{ number_format($findings->sum('labour_hours'), 1) }} hrs @ ${{ number_format($inspection->labour_hourly_rate ?? 165, 2) }}/hr</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="text-muted mb-1">Findings Material Cost (FMC)</h6>
                                        <div class="fs-4">${{ number_format($inspection->fmc_annual ?? 0, 2) }}</div>
                                        <small class="text-muted">Materials for remediation work</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Total Remediation Cost -->
                    <div class="mb-4">
                        <h6 class="text-primary border-bottom pb-2">
                            <i class="mdi mdi-numeric-2-circle me-2"></i>Total Remediation Cost (TRC)
                        </h6>
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <h5 class="mb-1">TRC = BDC + FRLC + FMC</h5>
                                        <small>Annual: ${{ number_format($inspection->trc_annual ?? 0, 2) }}</small>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <div class="fs-3">
                                            <strong>${{ number_format($inspection->trc_monthly ?? 0, 2) }}</strong>
                                            <div class="fs-6">per month</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: ARP & Condition -->
                    <div class="mb-4">
                        <h6 class="text-primary border-bottom pb-2">
                            <i class="mdi mdi-numeric-3-circle me-2"></i>Annual Recurring Price (ARP) & Condition Assessment
                        </h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card border-primary">
                                    <div class="card-body">
                                        <h6 class="text-muted mb-1">ARP (Monthly TRC)</h6>
                                        <div class="fs-4 text-primary">${{ number_format($inspection->arp_monthly ?? 0, 2) }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border-primary">
                                    <div class="card-body">
                                        <h6 class="text-muted mb-1">Condition Score (from CPI)</h6>
                                        <div class="fs-4 text-primary">{{ $inspection->condition_score ?? 0 }}/100</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 4: Dual-Gate Tier Assignment -->
                    <div class="mb-4">
                        <h6 class="text-primary border-bottom pb-2">
                            <i class="mdi mdi-numeric-4-circle me-2"></i>Dual-Gate Tier Assignment
                        </h6>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <strong>Gate 1: Condition-Based</strong>
                                    </div>
                                    <div class="card-body text-center">
                                        <span class="badge bg-primary fs-5">{{ $inspection->tier_score ?? 'N/A' }}</span>
                                        <p class="text-muted small mt-2">Based on property condition score</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <strong>Gate 2: ARP Cost Pressure</strong>
                                    </div>
                                    <div class="card-body text-center">
                                        <span class="badge bg-warning fs-5">{{ $inspection->tier_arp ?? 'N/A' }}</span>
                                        <p class="text-muted small mt-2">Based on remediation cost pressure</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-success">
                                    <div class="card-header bg-success text-white">
                                        <strong>Final Tier (Max of Both)</strong>
                                    </div>
                                    <div class="card-body text-center">
                                        <span class="badge bg-success fs-4">{{ $inspection->tier_final ?? 'N/A' }}</span>
                                        <p class="text-muted small mt-2">Highest tier wins (conservative approach)</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 5: Final Pricing -->
                    <div class="mb-4">
                        <h6 class="text-primary border-bottom pb-2">
                            <i class="mdi mdi-numeric-5-circle me-2"></i>Final Pricing with Multiplier
                        </h6>
                        <div class="alert alert-primary">
                            <strong>Locked Phase 1 Snapshot:</strong>
                            {{ $inspection->service_package_name ?? 'N/A' }} @
                            ${{ number_format((float)($inspection->base_price_snapshot ?? 0), 2) }}/month
                        </div>
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <h5>ARP × Tier Multiplier ({{ number_format($inspection->multiplier_final ?? 1, 2) }})</h5>
                                        <p class="mb-0">Floor: ${{ number_format($inspection->base_package_price_snapshot ?? 0, 2) }}/month</p>
                                    </div>
                                    <div class="col-md-6 text-end">
                                        <div class="fs-1">
                                            <strong>${{ number_format($inspection->arp_equivalent_final ?? 0, 2) }}</strong>
                                        </div>
                                        <div class="fs-5">per month</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 6: Per-Unit Breakdown (Multi-Unit Properties) -->
                    @if($inspection->units_for_calculation > 1)
                    <div class="mb-4">
                        <h6 class="text-primary border-bottom pb-2">
                            <i class="mdi mdi-numeric-6-circle me-2"></i>Per-Unit Cost Breakdown ({{ $inspection->units_for_calculation }} Units)
                        </h6>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Cost Component</th>
                                        <th class="text-end">Total Annual</th>
                                        <th class="text-end">Per Unit Annual</th>
                                        <th class="text-end">Per Unit Monthly</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><strong>BDC</strong></td>
                                        <td class="text-end">${{ number_format($inspection->bdc_annual ?? 0, 2) }}</td>
                                        <td class="text-end">${{ number_format($inspection->bdc_per_unit_annual ?? 0, 2) }}</td>
                                        <td class="text-end">${{ number_format(($inspection->bdc_per_unit_annual ?? 0) / 12, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>FRLC</strong></td>
                                        <td class="text-end">${{ number_format($inspection->frlc_annual ?? 0, 2) }}</td>
                                        <td class="text-end">${{ number_format($inspection->frlc_per_unit_annual ?? 0, 2) }}</td>
                                        <td class="text-end">${{ number_format(($inspection->frlc_per_unit_annual ?? 0) / 12, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>FMC</strong></td>
                                        <td class="text-end">${{ number_format($inspection->fmc_annual ?? 0, 2) }}</td>
                                        <td class="text-end">${{ number_format($inspection->fmc_per_unit_annual ?? 0, 2) }}</td>
                                        <td class="text-end">${{ number_format(($inspection->fmc_per_unit_annual ?? 0) / 12, 2) }}</td>
                                    </tr>
                                    <tr class="table-secondary">
                                        <td><strong>TRC</strong></td>
                                        <td class="text-end"><strong>${{ number_format($inspection->trc_annual ?? 0, 2) }}</strong></td>
                                        <td class="text-end"><strong>${{ number_format($inspection->trc_per_unit_annual ?? 0, 2) }}</strong></td>
                                        <td class="text-end"><strong>${{ number_format(($inspection->trc_per_unit_annual ?? 0) / 12, 2) }}</strong></td>
                                    </tr>
                                    <tr class="table-success">
                                        <td><strong>Final Price (with multiplier)</strong></td>
                                        <td class="text-end"><strong>${{ number_format(($inspection->arp_equivalent_final ?? 0) * 12, 2) }}</strong></td>
                                        <td class="text-end"><strong>${{ number_format((($inspection->arp_equivalent_final ?? 0) * 12) / $inspection->units_for_calculation, 2) }}</strong></td>
                                        <td class="text-end"><strong>${{ number_format($inspection->final_monthly_per_unit ?? 0, 2) }}</strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif

                </div>
            </div>

            <!-- Assessment Notes -->
            @if($inspection->summary || $inspection->recommendations || $inspection->risk_summary)
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="mdi mdi-text-box-outline me-2 text-primary"></i>Inspector Assessment
                    </h5>
                </div>
                <div class="card-body">
                    @if($inspection->summary)
                    <div class="mb-3">
                        <h6 class="text-primary">Notes:</h6>
                        <p>{{ $inspection->summary }}</p>
                    </div>
                    @endif
                    
                    @if($inspection->recommendations)
                    <div class="mb-3">
                        <h6 class="text-primary">Recommendations:</h6>
                        <p>{{ $inspection->recommendations }}</p>
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
    .btn, .alert { display: none !important; }
    .card { page-break-inside: avoid; }
}
</style>
@endsection
