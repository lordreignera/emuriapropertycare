@extends('admin.layout')

@section('title', 'Review Trade Application')
@section('header', 'Review Trade Application')

@section('content')
@php
    $docUrl = fn (?string $path) => $application->getStorageUrl($path);
    $pricingUnitOptions = [
        'sf' => 'SF',
        'lf' => 'LF',
        'ea' => 'EA',
        'pc' => 'PC',
        'hr' => 'HR',
        'day' => 'DAY',
        'ls' => 'LS',
        'ton' => 'TON',
    ];
    $displayStatus = match ($application->status) {
        \App\Models\TradeApplication::STATUS_APPROVED => 'Approved',
        \App\Models\TradeApplication::STATUS_REJECTED => 'Rejected',
        default => 'Awaiting Review',
    };
    $statusClass = match ($application->status) {
        \App\Models\TradeApplication::STATUS_APPROVED => 'trade-status-approved',
        \App\Models\TradeApplication::STATUS_REJECTED => 'trade-status-rejected',
        default => 'trade-status-open',
    };
    $documentRows = [
        [
            'label' => 'Business Licence',
            'status' => $application->business_licence_status,
            'details' => trim(($application->business_licence_number ?: 'N/A') . ($application->business_licence_expiry ? ' | Exp ' . $application->business_licence_expiry->format('M d, Y') : '')),
            'path' => $application->business_licence_document,
        ],
        [
            'label' => 'Liability Insurance',
            'status' => $application->liability_insurance_status,
            'details' => trim(($application->liability_insurance_provider ?: 'N/A') . ($application->liability_insurance_policy_number ? ' | ' . $application->liability_insurance_policy_number : '') . ($application->liability_insurance_expiry ? ' | Exp ' . $application->liability_insurance_expiry->format('M d, Y') : '')),
            'path' => $application->liability_insurance_document,
        ],
        [
            'label' => 'WorkSafeBC',
            'status' => $application->worksafebc_status,
            'details' => trim(($application->worksafebc_number ?: 'N/A') . ($application->worksafebc_expiry ? ' | Exp ' . $application->worksafebc_expiry->format('M d, Y') : '')),
            'path' => $application->worksafebc_document,
        ],
        [
            'label' => 'GST',
            'status' => $application->gst_status,
            'details' => $application->gst_number ?: 'N/A',
            'path' => $application->gst_document,
        ],
        [
            'label' => 'Travel Policy',
            'status' => null,
            'details' => $application->travel_charge_policy ?: 'N/A',
            'path' => $application->travel_policy_document,
        ],
        [
            'label' => 'Equipment Policy',
            'status' => null,
            'details' => $application->equipment_policy ?: 'N/A',
            'path' => $application->equipment_policy_document,
        ],
        [
            'label' => 'Disposal Policy',
            'status' => null,
            'details' => $application->disposal_policy ?: 'N/A',
            'path' => $application->disposal_policy_document,
        ],
        [
            'label' => 'Warranty',
            'status' => null,
            'details' => $application->standard_warranty ?: 'N/A',
            'path' => $application->warranty_document,
        ],
    ];
@endphp

<style>
    .trade-review-shell {
        display: grid;
        gap: 18px;
    }

    .trade-review-hero {
        display: flex;
        justify-content: space-between;
        gap: 18px;
        align-items: flex-start;
        padding: 22px;
        border: 1px solid #dfe5ef;
        border-radius: 8px;
        background: #ffffff;
    }

    .trade-review-hero h3 {
        margin: 0;
        color: #111827;
        font-size: 1.45rem;
        font-weight: 900;
    }

    .trade-review-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 10px;
    }

    .trade-pill {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 6px 11px;
        font-size: .78rem;
        font-weight: 900;
        background: #f1f5f9;
        color: #334155;
    }

    .trade-status-open {
        background: #e0f2fe;
        color: #075985;
    }

    .trade-status-approved {
        background: #dcfce7;
        color: #166534;
    }

    .trade-status-rejected {
        background: #fee2e2;
        color: #991b1b;
    }

    .trade-review-card {
        border: 1px solid #dfe5ef;
        border-radius: 8px;
        background: #ffffff;
        padding: 22px;
    }

    .trade-review-card h4 {
        color: #111827;
        font-size: 1.05rem;
        font-weight: 900;
        margin: 0 0 14px;
    }

    .trade-info-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
    }

    .trade-info-item {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 13px 14px;
        background: #f8fafc;
    }

    .trade-info-item span {
        display: block;
        color: #667085;
        font-size: .78rem;
        font-weight: 800;
        text-transform: uppercase;
    }

    .trade-info-item strong,
    .trade-info-item a {
        display: block;
        margin-top: 5px;
        color: #172033;
        font-weight: 900;
        overflow-wrap: anywhere;
    }

    .trade-chip {
        display: inline-flex;
        align-items: center;
        border: 1px solid #d7deea;
        background: #f8fafc;
        color: #344054;
        border-radius: 999px;
        padding: 5px 10px;
        margin: 0 6px 7px 0;
        font-size: .8rem;
        font-weight: 800;
    }

    .trade-pricing-table th,
    .trade-pricing-table td {
        white-space: normal !important;
        overflow-wrap: anywhere;
        max-width: none !important;
    }

    .trade-pricing-table {
        table-layout: auto;
        min-width: 760px;
    }

    .trade-pricing-table th:first-child,
    .trade-pricing-table td:first-child {
        width: 34%;
        min-width: 240px;
    }

    .trade-pricing-table th:nth-child(2),
    .trade-pricing-table td:nth-child(2),
    .trade-pricing-table th:nth-child(4),
    .trade-pricing-table td:nth-child(4) {
        width: 24%;
        min-width: 170px;
    }

    .trade-pricing-table th:nth-child(3),
    .trade-pricing-table td:nth-child(3) {
        width: 18%;
        min-width: 130px;
    }

    .trade-price {
        color: #172033;
        font-weight: 900;
        white-space: nowrap;
    }

    .trade-doc-list {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }

    .trade-doc-item {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 14px;
        background: #f8fafc;
    }

    .trade-doc-title {
        display: flex;
        justify-content: space-between;
        gap: 10px;
        align-items: flex-start;
        margin-bottom: 8px;
    }

    .trade-doc-title strong {
        color: #172033;
    }

    .trade-review-form {
        border-top: 4px solid #2458d6;
    }

    .trade-decision-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
        margin-bottom: 16px;
    }

    .trade-decision-option {
        border: 1px solid #d7deea;
        border-radius: 8px;
        padding: 14px;
        cursor: pointer;
        background: #ffffff;
    }

    .trade-decision-option input {
        margin-right: 8px;
    }

    .trade-decision-option strong {
        color: #111827;
    }

    .trade-agreed-card {
        border: 1px solid #dfe5ef;
        border-radius: 8px;
        padding: 14px;
        background: #f8fafc;
        margin-bottom: 12px;
    }

    @media (max-width: 991.98px) {
        .trade-info-grid,
        .trade-doc-list,
        .trade-decision-grid {
            grid-template-columns: 1fr;
        }

        .trade-review-hero {
            flex-direction: column;
        }
    }
</style>

<div class="trade-review-shell">
    <div class="trade-review-hero">
        <div>
            <h3>{{ $application->company_name }}</h3>
            <div class="trade-review-meta">
                <span class="trade-pill">{{ $application->application_number }}</span>
                <span class="trade-pill {{ $statusClass }}">{{ $displayStatus }}</span>
                <span class="trade-pill">Submitted {{ optional($application->submitted_at ?? $application->created_at)->format('M d, Y') }}</span>
                @if($application->tradePartner)
                    <span class="trade-pill trade-status-approved">Partner {{ $application->tradePartner->partner_number }}</span>
                @endif
            </div>
        </div>
        <a href="{{ route('admin.trade-applications.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="mdi mdi-arrow-left me-1"></i>Back to Applications
        </a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <strong>Please correct the review form.</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="trade-review-card">
        <h4>Company Information</h4>
        <div class="trade-info-grid">
            <div class="trade-info-item">
                <span>Contact</span>
                <strong>{{ $application->contact_person }}</strong>
            </div>
            <div class="trade-info-item">
                <span>Email</span>
                <a href="mailto:{{ $application->email }}">{{ $application->email }}</a>
            </div>
            <div class="trade-info-item">
                <span>Phone</span>
                <strong>{{ $application->phone }}</strong>
            </div>
            <div class="trade-info-item">
                <span>Service Area</span>
                <strong>{{ $application->service_area }}</strong>
            </div>
            <div class="trade-info-item">
                <span>Years</span>
                <strong>{{ $application->years_in_business ?? 'N/A' }}</strong>
            </div>
            <div class="trade-info-item">
                <span>Technicians</span>
                <strong>{{ $application->technicians_count ?? 'N/A' }}</strong>
            </div>
            <div class="trade-info-item">
                <span>Minimum Charge</span>
                <strong>{{ $application->minimum_service_charge !== null ? 'CAD $' . number_format((float) $application->minimum_service_charge, 2) : 'N/A' }}</strong>
            </div>
            <div class="trade-info-item">
                <span>Emergency Premium</span>
                <strong>{{ $application->emergency_premium ?: 'N/A' }}</strong>
            </div>
        </div>
        @if($application->company_description)
            <p class="text-muted mt-3 mb-0">{{ $application->company_description }}</p>
        @endif
    </div>

    <div class="trade-review-card">
        <h4>Coverage Submitted</h4>
        <div class="mb-2">
            @forelse($systems as $system)
                <span class="trade-chip">{{ $system->name }}</span>
            @empty
                @if(empty($application->custom_coverage))
                    <span class="text-muted">No building systems selected.</span>
                @endif
            @endforelse
        </div>
        <div>
            @forelse($subsystems as $subsystem)
                <span class="trade-chip">{{ $subsystem->system?->name }} / {{ $subsystem->name }}</span>
            @empty
                @if(empty($application->custom_coverage))
                    <span class="text-muted">No building subsystems selected.</span>
                @endif
            @endforelse
            @foreach($application->custom_coverage ?? [] as $coverage)
                <span class="trade-chip">Other: {{ $coverage['system_name'] ?? 'N/A' }} / {{ $coverage['subsystem_name'] ?? 'N/A' }}</span>
            @endforeach
        </div>
    </div>

    <div class="trade-review-card">
        <h4>Submitted Pricing</h4>
        <div class="table-responsive" data-admin-table-plain="1">
            <table class="table table-striped align-middle trade-pricing-table">
                <thead>
                    <tr>
                        <th>System / Subsystem</th>
                        <th>Submitted Price</th>
                        <th>Estimated Hours</th>
                        <th>Agreed Price</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($subsystems as $subsystem)
                        @php
                            $pricing = $application->subsystem_pricing[$subsystem->id] ?? $application->subsystem_pricing[(string) $subsystem->id] ?? [];
                            $agreedPricing = $application->agreed_subsystem_pricing[$subsystem->id] ?? $application->agreed_subsystem_pricing[(string) $subsystem->id] ?? [];
                            $typicalRate = isset($pricing['typical_rate']) ? (float) $pricing['typical_rate'] : null;
                            $unit = $pricing['pricing_unit'] ?? null;
                            $agreedRate = isset($agreedPricing['typical_rate']) ? (float) $agreedPricing['typical_rate'] : null;
                            $agreedUnit = $agreedPricing['pricing_unit'] ?? null;
                        @endphp
                        <tr>
                            <td><strong>{{ $subsystem->system?->name }}</strong><br><span class="text-muted">{{ $subsystem->name }}</span></td>
                            <td>
                                @if($typicalRate !== null)
                                    <span class="trade-price">CAD ${{ number_format($typicalRate, 2) }}{{ $unit ? ' / ' . strtoupper($unit) : '' }}</span>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>{{ $pricing['estimated_hours'] ?? 'N/A' }}</td>
                            <td>
                                @if($agreedRate !== null)
                                    <span class="trade-price">CAD ${{ number_format($agreedRate, 2) }}{{ $agreedUnit ? ' / ' . strtoupper($agreedUnit) : '' }}</span>
                                @else
                                    <span class="text-muted">Not agreed yet</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    @foreach($application->custom_coverage ?? [] as $coverageIndex => $coverage)
                        @php
                            $agreedCoverage = $application->agreed_custom_coverage[$coverageIndex] ?? [];
                            $typicalRate = isset($coverage['typical_rate']) ? (float) $coverage['typical_rate'] : null;
                            $unit = $coverage['pricing_unit'] ?? null;
                            $agreedRate = isset($agreedCoverage['typical_rate']) ? (float) $agreedCoverage['typical_rate'] : null;
                            $agreedUnit = $agreedCoverage['pricing_unit'] ?? null;
                        @endphp
                        <tr>
                            <td><strong>Other: {{ $coverage['system_name'] ?? 'N/A' }}</strong><br><span class="text-muted">{{ $coverage['subsystem_name'] ?? 'N/A' }}</span></td>
                            <td>
                                @if($typicalRate !== null)
                                    <span class="trade-price">CAD ${{ number_format($typicalRate, 2) }}{{ $unit ? ' / ' . strtoupper($unit) : '' }}</span>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>{{ $coverage['estimated_hours'] ?? 'N/A' }}</td>
                            <td>
                                @if($agreedRate !== null)
                                    <span class="trade-price">CAD ${{ number_format($agreedRate, 2) }}{{ $agreedUnit ? ' / ' . strtoupper($agreedUnit) : '' }}</span>
                                @else
                                    <span class="text-muted">Not agreed yet</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    @if($subsystems->isEmpty() && empty($application->custom_coverage))
                        <tr><td colspan="4" class="text-muted">No subsystem pricing submitted.</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <div class="trade-review-card">
        <h4>Documents and Compliance</h4>
        <div class="trade-doc-list">
            @foreach($documentRows as $document)
                @php $url = $docUrl($document['path'] ?? null); @endphp
                <div class="trade-doc-item">
                    <div class="trade-doc-title">
                        <strong>{{ $document['label'] }}</strong>
                        @if(!empty($document['status']))
                            <span class="trade-pill">{{ ucwords(str_replace('_', ' ', $document['status'])) }}</span>
                        @endif
                    </div>
                    <p class="text-muted small mb-3">{{ $document['details'] ?: 'N/A' }}</p>
                    @if($url)
                        <a class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener" href="{{ $url }}">
                            <i class="mdi mdi-eye me-1"></i>View Document
                        </a>
                    @else
                        <span class="text-muted small">No document uploaded.</span>
                    @endif
                </div>
            @endforeach
            @foreach($application->additional_documents ?? [] as $path)
                @php $url = $docUrl($path); @endphp
                <div class="trade-doc-item">
                    <div class="trade-doc-title">
                        <strong>Additional Document {{ $loop->iteration }}</strong>
                    </div>
                    @if($url)
                        <a class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener" href="{{ $url }}">
                            <i class="mdi mdi-eye me-1"></i>View Document
                        </a>
                    @else
                        <span class="text-muted small">Document path could not be resolved.</span>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <div class="trade-review-card">
        <h4>References</h4>
        <div class="table-responsive" data-admin-table-plain="1">
            <table class="table table-sm align-middle">
                <thead><tr><th>Name</th><th>Phone</th><th>Email</th></tr></thead>
                <tbody>
                    @forelse($application->references ?? [] as $reference)
                        <tr>
                            <td>{{ $reference['name'] ?? 'N/A' }}</td>
                            <td>{{ $reference['phone'] ?? 'N/A' }}</td>
                            <td>{{ $reference['email'] ?? 'N/A' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-muted">No references submitted.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.trade-applications.update-status', $application) }}" class="trade-review-card trade-review-form">
        @csrf
        @method('PATCH')

        <h4>Agreed Pricing and Final Decision</h4>
        <p class="text-muted mb-3">Confirm the agreed trade pricing first, then approve or reject the application with a clear reason.</p>

        @foreach($subsystems as $subsystem)
            @php
                $submitted = $application->subsystem_pricing[$subsystem->id] ?? $application->subsystem_pricing[(string) $subsystem->id] ?? [];
                $agreed = $application->agreed_subsystem_pricing[$subsystem->id] ?? $application->agreed_subsystem_pricing[(string) $subsystem->id] ?? $submitted;
            @endphp
            <div class="trade-agreed-card">
                <div class="fw-semibold mb-2">{{ $subsystem->system?->name }} / {{ $subsystem->name }}</div>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label small mb-1">Pricing Unit</label>
                        <select name="agreed_subsystem_pricing[{{ $subsystem->id }}][pricing_unit]" class="form-control form-control-sm">
                            <option value="">Select</option>
                            @foreach($pricingUnitOptions as $value => $label)
                                <option value="{{ $value }}" @selected(($agreed['pricing_unit'] ?? '') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1">Agreed Rate CAD</label>
                        <input type="number" min="0" step="0.01" name="agreed_subsystem_pricing[{{ $subsystem->id }}][typical_rate]" class="form-control form-control-sm" value="{{ $agreed['typical_rate'] ?? '' }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1">Estimated Hours</label>
                        <input type="number" min="0" step="0.25" name="agreed_subsystem_pricing[{{ $subsystem->id }}][estimated_hours]" class="form-control form-control-sm" value="{{ $agreed['estimated_hours'] ?? '' }}">
                    </div>
                </div>
            </div>
        @endforeach

        @foreach($application->custom_coverage ?? [] as $coverageIndex => $coverage)
            @php $agreed = $application->agreed_custom_coverage[$coverageIndex] ?? $coverage; @endphp
            <div class="trade-agreed-card">
                <div class="fw-semibold mb-2">Other: {{ $coverage['system_name'] ?? 'N/A' }} / {{ $coverage['subsystem_name'] ?? 'N/A' }}</div>
                <input type="hidden" name="agreed_custom_coverage[{{ $coverageIndex }}][system_name]" value="{{ $coverage['system_name'] ?? '' }}">
                <input type="hidden" name="agreed_custom_coverage[{{ $coverageIndex }}][subsystem_name]" value="{{ $coverage['subsystem_name'] ?? '' }}">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label small mb-1">Pricing Unit</label>
                        <select name="agreed_custom_coverage[{{ $coverageIndex }}][pricing_unit]" class="form-control form-control-sm">
                            <option value="">Select</option>
                            @foreach($pricingUnitOptions as $value => $label)
                                <option value="{{ $value }}" @selected(($agreed['pricing_unit'] ?? '') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1">Agreed Rate CAD</label>
                        <input type="number" min="0" step="0.01" name="agreed_custom_coverage[{{ $coverageIndex }}][typical_rate]" class="form-control form-control-sm" value="{{ $agreed['typical_rate'] ?? '' }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1">Estimated Hours</label>
                        <input type="number" min="0" step="0.25" name="agreed_custom_coverage[{{ $coverageIndex }}][estimated_hours]" class="form-control form-control-sm" value="{{ $agreed['estimated_hours'] ?? '' }}">
                    </div>
                </div>
            </div>
        @endforeach

        @if($subsystems->isEmpty() && empty($application->custom_coverage))
            <p class="text-muted">No submitted pricing rows to agree.</p>
        @endif

        @if($application->pricing_agreed_at)
            <p class="text-muted small">Last pricing agreement saved {{ $application->pricing_agreed_at->format('M d, Y h:i A') }}.</p>
        @endif

        <div class="mt-4">
            <label class="form-label fw-semibold">Decision</label>
            <div class="trade-decision-grid">
                <label class="trade-decision-option">
                    <input type="radio" name="status" value="approved" @checked(old('status', $application->status) === \App\Models\TradeApplication::STATUS_APPROVED)>
                    <strong>Approve</strong>
                    <div class="text-muted small mt-1">Creates or updates the approved trade partner record.</div>
                </label>
                <label class="trade-decision-option">
                    <input type="radio" name="status" value="rejected" @checked(old('status', $application->status) === \App\Models\TradeApplication::STATUS_REJECTED)>
                    <strong>Reject</strong>
                    <div class="text-muted small mt-1">Keeps the application for record purposes without activating a partner.</div>
                </label>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label fw-semibold">Approval / Rejection Reason</label>
            <textarea name="admin_notes" rows="5" class="form-control" required placeholder="Enter the reason for this approval or rejection.">{{ old('admin_notes', $application->admin_notes) }}</textarea>
        </div>

        <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
            @if($application->reviewer)
                <p class="text-muted small mb-0">Last reviewed by {{ $application->reviewer->name }} on {{ optional($application->reviewed_at)->format('M d, Y h:i A') }}</p>
            @else
                <p class="text-muted small mb-0">This application has not been reviewed yet.</p>
            @endif
            <button class="btn btn-primary px-4">
                <i class="mdi mdi-content-save-check me-1"></i>Save Decision
            </button>
        </div>
    </form>
</div>
@endsection
