@extends('admin.layout')

@section('title', 'Review Trade Application')
@section('header', 'Review Trade Application')

@section('content')
@php
    $docUrl = fn (?string $path) => $application->getStorageUrl($path);
    $statusOptions = [
        'ready_for_review' => 'Ready for Review',
        'needs_more_information' => 'Needs More Information',
        'conditionally_approved' => 'Conditionally Approved',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'suspended' => 'Suspended',
    ];
@endphp

<div class="row">
    <div class="col-lg-8 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-4">
                    <div>
                        <h4 class="card-title mb-1">{{ $application->company_name }}</h4>
                        <p class="text-muted mb-0">{{ $application->application_number }} | {{ $application->statusLabel() }}</p>
                    </div>
                    <a href="{{ route('admin.trade-applications.index') }}" class="btn btn-sm btn-outline-secondary">Back</a>
                </div>

                <h5>Company Information</h5>
                <div class="table-responsive mb-4">
                    <table class="table table-sm">
                        <tr><th>Contact</th><td>{{ $application->contact_person }}</td></tr>
                        <tr><th>Phone</th><td>{{ $application->phone }}</td></tr>
                        <tr><th>Email</th><td>{{ $application->email }}</td></tr>
                        <tr><th>Service Area</th><td>{{ $application->service_area }}</td></tr>
                        <tr><th>Years / Technicians</th><td>{{ $application->years_in_business ?? 'N/A' }} years | {{ $application->technicians_count ?? 'N/A' }} technicians</td></tr>
                        <tr><th>Description</th><td>{{ $application->company_description ?: 'N/A' }}</td></tr>
                    </table>
                </div>

                <h5>Selected Systems</h5>
                <div class="mb-4">
                    @forelse($systems as $system)
                        <span class="badge bg-primary me-1 mb-1">{{ $system->name }}</span>
                    @empty
                        <span class="text-muted">None selected</span>
                    @endforelse
                </div>

                <h5>Selected Subsystems</h5>
                <div class="mb-4">
                    @forelse($subsystems as $subsystem)
                        <span class="badge bg-secondary me-1 mb-1">{{ $subsystem->system?->name }} / {{ $subsystem->name }}</span>
                    @empty
                        <span class="text-muted">None selected</span>
                    @endforelse
                </div>

                <h5>System Pricing</h5>
                <div class="table-responsive mb-4">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>System</th>
                                <th>Units</th>
                                <th>Typical Trade Rate (CAD)</th>
                                <th>ETOGO Price at 35% Margin</th>
                                <th>Minimum (CAD)</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($systems as $system)
                                @php
                                    $pricing = $application->system_pricing[$system->id] ?? $application->system_pricing[(string) $system->id] ?? [];
                                    $typicalRate = isset($pricing['typical_rate']) ? (float) $pricing['typical_rate'] : null;
                                    $etogoTypicalRate = $typicalRate !== null ? $typicalRate / 0.65 : null;
                                @endphp
                                <tr>
                                    <td>{{ $system->name }}</td>
                                    <td>{{ collect($pricing['units'] ?? [])->map(fn($unit) => strtoupper($unit))->join(', ') ?: 'N/A' }}</td>
                                    <td>
                                        @if($typicalRate !== null)
                                            CAD ${{ number_format($typicalRate, 2) }}{{ !empty($pricing['rate_unit']) ? ' / ' . strtoupper($pricing['rate_unit']) : '' }}
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td>
                                        @if($etogoTypicalRate !== null)
                                            CAD ${{ number_format($etogoTypicalRate, 2) }}{{ !empty($pricing['rate_unit']) ? ' / ' . strtoupper($pricing['rate_unit']) : '' }}
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td>{{ isset($pricing['minimum_charge']) ? 'CAD $' . number_format((float) $pricing['minimum_charge'], 2) : 'N/A' }}</td>
                                    <td>{{ $pricing['notes'] ?? 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-muted">No system pricing submitted.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <h5>Compliance</h5>
                <div class="table-responsive mb-4">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Status</th>
                                <th>Details</th>
                                <th>Document</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Business Licence</td>
                                <td>{{ ucwords(str_replace('_', ' ', $application->business_licence_status)) }}</td>
                                <td>{{ $application->business_licence_number ?: 'N/A' }} @if($application->business_licence_expiry) | Exp {{ $application->business_licence_expiry->format('M d, Y') }} @endif</td>
                                <td>@if($docUrl($application->business_licence_document)) <a target="_blank" href="{{ $docUrl($application->business_licence_document) }}">Open</a> @else N/A @endif</td>
                            </tr>
                            <tr>
                                <td>Liability Insurance</td>
                                <td>{{ ucwords(str_replace('_', ' ', $application->liability_insurance_status)) }}</td>
                                <td>{{ $application->liability_insurance_provider ?: 'N/A' }} {{ $application->liability_insurance_policy_number ? '| ' . $application->liability_insurance_policy_number : '' }} @if($application->liability_insurance_expiry) | Exp {{ $application->liability_insurance_expiry->format('M d, Y') }} @endif</td>
                                <td>@if($docUrl($application->liability_insurance_document)) <a target="_blank" href="{{ $docUrl($application->liability_insurance_document) }}">Open</a> @else N/A @endif</td>
                            </tr>
                            <tr>
                                <td>WorkSafeBC</td>
                                <td>{{ ucwords(str_replace('_', ' ', $application->worksafebc_status)) }}</td>
                                <td>{{ $application->worksafebc_number ?: 'N/A' }} @if($application->worksafebc_expiry) | Exp {{ $application->worksafebc_expiry->format('M d, Y') }} @endif</td>
                                <td>@if($docUrl($application->worksafebc_document)) <a target="_blank" href="{{ $docUrl($application->worksafebc_document) }}">Open</a> @else N/A @endif</td>
                            </tr>
                            <tr>
                                <td>GST</td>
                                <td>{{ ucwords(str_replace('_', ' ', $application->gst_status)) }}</td>
                                <td>{{ $application->gst_number ?: 'N/A' }}</td>
                                <td>@if($docUrl($application->gst_document)) <a target="_blank" href="{{ $docUrl($application->gst_document) }}">Open</a> @else N/A @endif</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <h5>References</h5>
                <div class="table-responsive mb-4">
                    <table class="table table-sm">
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

                <h5>Company-wide Pricing Terms</h5>
                <div class="table-responsive mb-4">
                    <table class="table table-sm">
                        <tr><th>Minimum Charge</th><td>{{ $application->minimum_service_charge !== null ? 'CAD $' . number_format((float) $application->minimum_service_charge, 2) : 'N/A' }}</td></tr>
                        <tr><th>Emergency Premium</th><td>{{ $application->emergency_premium ?: 'N/A' }}</td></tr>
                        <tr><th>Travel</th><td>{{ $application->travel_charge_policy ?: 'N/A' }} @if($docUrl($application->travel_policy_document)) | <a target="_blank" href="{{ $docUrl($application->travel_policy_document) }}">Open policy</a> @endif</td></tr>
                        <tr><th>Materials</th><td>{{ $application->material_policy ?: 'N/A' }} @if($docUrl($application->material_policy_document)) | <a target="_blank" href="{{ $docUrl($application->material_policy_document) }}">Open policy</a> @endif</td></tr>
                        <tr><th>Equipment</th><td>{{ $application->equipment_policy ?: 'N/A' }} @if($docUrl($application->equipment_policy_document)) | <a target="_blank" href="{{ $docUrl($application->equipment_policy_document) }}">Open policy</a> @endif</td></tr>
                        <tr><th>Disposal</th><td>{{ $application->disposal_policy ?: 'N/A' }} @if($docUrl($application->disposal_policy_document)) | <a target="_blank" href="{{ $docUrl($application->disposal_policy_document) }}">Open policy</a> @endif</td></tr>
                        <tr><th>Warranty</th><td>{{ $application->standard_warranty ?: 'N/A' }} @if($docUrl($application->warranty_document)) | <a target="_blank" href="{{ $docUrl($application->warranty_document) }}">Open warranty</a> @endif</td></tr>
                        <tr><th>Notes</th><td>{{ $application->pricing_notes ?: 'N/A' }} @if($docUrl($application->pricing_policy_document)) | <a target="_blank" href="{{ $docUrl($application->pricing_policy_document) }}">Open pricing document</a> @endif</td></tr>
                    </table>
                </div>

                <h5>Additional Documents</h5>
                <div>
                    @forelse($application->additional_documents ?? [] as $path)
                        <a class="btn btn-sm btn-outline-secondary me-1 mb-1" target="_blank" href="{{ $application->getStorageUrl($path) }}">Open Document {{ $loop->iteration }}</a>
                    @empty
                        <span class="text-muted">No additional documents uploaded.</span>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Admin Review</h4>
                <form method="POST" action="{{ route('admin.trade-applications.update-status', $application) }}">
                    @csrf
                    @method('PATCH')
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control mb-3">
                        @foreach($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected($application->status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>

                    <label class="form-label">Admin notes</label>
                    <textarea name="admin_notes" rows="7" class="form-control mb-3">{{ old('admin_notes', $application->admin_notes) }}</textarea>

                    <button class="btn btn-primary w-100">Save Review</button>
                </form>

                @if($application->reviewer)
                    <p class="text-muted small mt-3 mb-0">Last reviewed by {{ $application->reviewer->name }} on {{ optional($application->reviewed_at)->format('M d, Y h:i A') }}</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
