@extends('admin.layout')

@section('title', 'Trade Partner Details')
@section('header', 'Trade Partner Details')

@section('content')
@php
    $application = $application ?? $partner->application;
    $currency = 'CAD';
    $formatMoney = fn($value) => $value !== null && $value !== '' ? $currency . ' $' . number_format((float) $value, 2) : 'N/A';
    $formatUnit = fn($unit) => $unit ? strtoupper((string) $unit) : 'unit';
    $docUrl = function ($path) use ($application) {
        return $application && $path ? $application->getStorageUrl($path) : null;
    };
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-1">{{ $partner->company_name }}</h4>
        <div class="text-muted">Partner ID: <span class="fw-semibold">{{ $partner->partner_number }}</span></div>
    </div>
    <a href="{{ route('admin.trade-partners.index', ['status' => 'active']) }}" class="btn btn-sm btn-outline-secondary">Back to Partners</a>
</div>

<div class="row">
    <div class="col-lg-4 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3">Partner Profile</h5>
                <table class="table table-sm">
                    <tr><th>Status</th><td><span class="badge bg-{{ $partner->status === 'active' ? 'success' : ($partner->status === 'suspended' ? 'warning text-dark' : 'secondary') }}">{{ ucwords($partner->status) }}</span></td></tr>
                    <tr><th>Contact</th><td>{{ $partner->contact_person ?: 'N/A' }}</td></tr>
                    <tr><th>Phone</th><td>{{ $partner->phone ?: 'N/A' }}</td></tr>
                    <tr><th>Email</th><td>{{ $partner->email ?: 'N/A' }}</td></tr>
                    <tr><th>Service Area</th><td>{{ $partner->service_area ?: 'N/A' }}</td></tr>
                    <tr><th>Approved</th><td>{{ optional($partner->approved_at)->format('M d, Y') ?: 'N/A' }}</td></tr>
                    <tr><th>Approved By</th><td>{{ $partner->approver?->name ?: 'N/A' }}</td></tr>
                    <tr>
                        <th>Application</th>
                        <td>
                            @if($application)
                                <a href="{{ route('admin.trade-applications.show', $application) }}">{{ $application->application_number }}</a>
                            @else
                                N/A
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-8 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3">Approved Coverage</h5>
                <div class="mb-3">
                    <div class="fw-semibold mb-2">Systems</div>
                    @forelse($systems as $system)
                        <span class="badge bg-light text-dark border me-1 mb-1">{{ $system->name }}</span>
                    @empty
                        <span class="text-muted">No systems recorded.</span>
                    @endforelse
                </div>
                <div>
                    <div class="fw-semibold mb-2">Subsystems</div>
                    @forelse($subsystems as $subsystem)
                        <span class="badge bg-light text-dark border me-1 mb-1">{{ $subsystem->system?->name }} / {{ $subsystem->name }}</span>
                    @empty
                        <span class="text-muted">No subsystems recorded.</span>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <h5 class="card-title mb-3">Agreed Subsystem Pricing</h5>
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                    <tr>
                        <th>System / Subsystem</th>
                        <th>Agreed Rate</th>
                        <th>Maximum Charge</th>
                        <th>Duration</th>
                        <th>Submitted Rate</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($subsystems as $subsystem)
                        @php
                            $agreed = $partner->agreed_subsystem_pricing[$subsystem->id] ?? $partner->agreed_subsystem_pricing[(string) $subsystem->id] ?? [];
                            $submitted = $application
                                ? ($application->subsystem_pricing[$subsystem->id] ?? $application->subsystem_pricing[(string) $subsystem->id] ?? [])
                                : [];
                        @endphp
                        <tr>
                            <td>{{ $subsystem->system?->name }} / {{ $subsystem->name }}</td>
                            <td>
                                {{ $formatMoney($agreed['typical_rate'] ?? null) }}
                                @if(!empty($agreed['pricing_unit']))
                                    <span class="text-muted">/ {{ $formatUnit($agreed['pricing_unit']) }}</span>
                                @endif
                            </td>
                            <td>{{ $formatMoney($agreed['maximum_charge'] ?? null) }}</td>
                            <td>{{ $agreed['estimated_duration'] ?? 'N/A' }}</td>
                            <td>
                                {{ $formatMoney($submitted['typical_rate'] ?? null) }}
                                @if(!empty($submitted['pricing_unit']))
                                    <span class="text-muted">/ {{ $formatUnit($submitted['pricing_unit']) }}</span>
                                @endif
                            </td>
                            <td>{{ $agreed['notes'] ?? ($submitted['notes'] ?? 'N/A') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-muted text-center py-4">No subsystem pricing recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if(!empty($partner->agreed_custom_coverage))
<div class="card mb-4">
    <div class="card-body">
        <h5 class="card-title mb-3">Agreed Custom Coverage</h5>
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                    <tr>
                        <th>System / Subsystem</th>
                        <th>Agreed Rate</th>
                        <th>Maximum Charge</th>
                        <th>Duration</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($partner->agreed_custom_coverage as $coverage)
                        <tr>
                            <td>{{ $coverage['system_name'] ?? 'Other' }} / {{ $coverage['subsystem_name'] ?? 'N/A' }}</td>
                            <td>
                                {{ $formatMoney($coverage['typical_rate'] ?? null) }}
                                @if(!empty($coverage['pricing_unit']))
                                    <span class="text-muted">/ {{ $formatUnit($coverage['pricing_unit']) }}</span>
                                @endif
                            </td>
                            <td>{{ $formatMoney($coverage['maximum_charge'] ?? null) }}</td>
                            <td>{{ $coverage['estimated_duration'] ?? 'N/A' }}</td>
                            <td>{{ $coverage['notes'] ?? 'N/A' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

@if($application)
<div class="row">
    <div class="col-lg-6 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3">Company Pricing Terms</h5>
                <table class="table table-sm">
                    <tr><th>Minimum Charge</th><td>{{ $formatMoney($application->minimum_service_charge) }}</td></tr>
                    <tr><th>Emergency Premium</th><td>{{ $application->emergency_premium ?: 'N/A' }}</td></tr>
                    <tr><th>Travel</th><td>{{ $application->travel_charge_policy ?: 'N/A' }} @if($docUrl($application->travel_policy_document)) | <a target="_blank" href="{{ $docUrl($application->travel_policy_document) }}">Open</a> @endif</td></tr>
                    <tr><th>Materials</th><td>{{ $application->material_policy ?: 'N/A' }} @if($docUrl($application->material_policy_document)) | <a target="_blank" href="{{ $docUrl($application->material_policy_document) }}">Open</a> @endif</td></tr>
                    <tr><th>Equipment</th><td>{{ $application->equipment_policy ?: 'N/A' }} @if($docUrl($application->equipment_policy_document)) | <a target="_blank" href="{{ $docUrl($application->equipment_policy_document) }}">Open</a> @endif</td></tr>
                    <tr><th>Disposal</th><td>{{ $application->disposal_policy ?: 'N/A' }} @if($docUrl($application->disposal_policy_document)) | <a target="_blank" href="{{ $docUrl($application->disposal_policy_document) }}">Open</a> @endif</td></tr>
                    <tr><th>Warranty</th><td>{{ $application->standard_warranty ?: 'N/A' }} @if($docUrl($application->warranty_document)) | <a target="_blank" href="{{ $docUrl($application->warranty_document) }}">Open</a> @endif</td></tr>
                    <tr><th>Notes</th><td>{{ $application->pricing_notes ?: 'N/A' }}</td></tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-6 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3">Compliance Documents</h5>
                <table class="table table-sm">
                    <tr><th>Business Licence</th><td>{{ ucwords(str_replace('_', ' ', $application->business_licence_status)) }} @if($docUrl($application->business_licence_document)) | <a target="_blank" href="{{ $docUrl($application->business_licence_document) }}">Open</a> @endif</td></tr>
                    <tr><th>Liability Insurance</th><td>{{ ucwords(str_replace('_', ' ', $application->liability_insurance_status)) }} @if($docUrl($application->liability_insurance_document)) | <a target="_blank" href="{{ $docUrl($application->liability_insurance_document) }}">Open</a> @endif</td></tr>
                    <tr><th>WorkSafeBC</th><td>{{ ucwords(str_replace('_', ' ', $application->worksafebc_status)) }} @if($docUrl($application->worksafebc_document)) | <a target="_blank" href="{{ $docUrl($application->worksafebc_document) }}">Open</a> @endif</td></tr>
                    <tr><th>GST</th><td>{{ ucwords(str_replace('_', ' ', $application->gst_status)) }} @if($docUrl($application->gst_document)) | <a target="_blank" href="{{ $docUrl($application->gst_document) }}">Open</a> @endif</td></tr>
                    <tr>
                        <th>Additional</th>
                        <td>
                            @forelse($application->additional_documents ?? [] as $path)
                                <a class="btn btn-xs btn-outline-secondary me-1 mb-1" target="_blank" href="{{ $application->getStorageUrl($path) }}">Document {{ $loop->iteration }}</a>
                            @empty
                                N/A
                            @endforelse
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>
@endif
@endsection
