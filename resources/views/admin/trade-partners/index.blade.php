@extends('admin.layout')

@section('title', 'Trade Partners')
@section('header', 'Trade Partners')

@section('content')
<style>
    .partner-list-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
        margin-bottom: 18px;
    }

    .partner-title h4 {
        font-size: 1.25rem;
        font-weight: 900;
        margin-bottom: 4px;
        color: #172033;
    }

    .partner-title p {
        margin: 0;
        color: #667085;
    }

    .partner-filter-bar {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 18px;
        padding-bottom: 18px;
        border-bottom: 1px solid #dfe5ef;
    }

    .partner-filter-bar .nav-link {
        min-width: 118px;
        text-align: center;
        border: 1px solid #d7deea;
        border-radius: 8px;
        font-weight: 800;
    }

    .partner-summary-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
        margin-bottom: 18px;
    }

    .partner-summary-item {
        border: 1px solid #dfe5ef;
        border-radius: 8px;
        padding: 14px 16px;
        background: #f8fafc;
    }

    .partner-summary-item span {
        display: block;
        color: #667085;
        font-size: .78rem;
        font-weight: 800;
        text-transform: uppercase;
    }

    .partner-summary-item strong {
        display: block;
        margin-top: 4px;
        color: #172033;
        font-size: 1.5rem;
        font-weight: 900;
    }

    .partner-company {
        display: flex;
        gap: 12px;
        align-items: center;
    }

    .partner-avatar {
        width: 42px;
        height: 42px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #e8f0ff;
        color: #2458d6;
        font-weight: 900;
        flex: 0 0 auto;
    }

    .coverage-chip {
        display: inline-flex;
        align-items: center;
        border: 1px solid #d7deea;
        background: #f8fafc;
        color: #344054;
        border-radius: 999px;
        padding: 4px 9px;
        margin: 0 5px 5px 0;
        font-size: .78rem;
        font-weight: 800;
    }

    .partner-muted {
        color: #667085;
        font-size: .82rem;
    }

    .partner-number {
        color: #0f3f8f;
        font-weight: 900;
    }

    .partner-pricing {
        color: #172033;
        font-weight: 900;
        white-space: nowrap;
    }

    .partner-card-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
        gap: 16px;
    }

    .partner-card {
        border: 1px solid #dfe5ef;
        border-radius: 8px;
        background: #fff;
        padding: 16px;
        box-shadow: 0 8px 22px rgba(16, 24, 40, .055);
    }

    .partner-card-meta {
        display: grid;
        gap: 8px;
        margin: 14px 0;
    }

    .partner-card-meta-item {
        border-top: 1px solid #edf1f7;
        padding-top: 8px;
    }

    .partner-card-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    @media (max-width: 991.98px) {
        .partner-summary-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 575.98px) {
        .partner-list-header {
            flex-direction: column;
        }

        .partner-summary-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
<div class="card">
    <div class="card-body">
        <div class="partner-list-header">
            <div class="partner-title">
                <h4 class="card-title mb-1">Approved Trade Partners</h4>
                <p class="text-muted mb-0">Active partners created after trade application approval.</p>
            </div>
            <a href="{{ route('admin.trade-applications.index') }}" class="btn btn-sm btn-outline-primary">Review Applications</a>
        </div>

        <div class="partner-summary-grid">
            <div class="partner-summary-item">
                <span>Active partners</span>
                <strong>{{ $activeCount }}</strong>
            </div>
            <div class="partner-summary-item">
                <span>Inactive</span>
                <strong>{{ $inactiveCount }}</strong>
            </div>
            <div class="partner-summary-item">
                <span>Suspended</span>
                <strong>{{ $suspendedCount }}</strong>
            </div>
            <div class="partner-summary-item">
                <span>Current view</span>
                <strong>{{ $partners->total() }}</strong>
            </div>
        </div>

        <ul class="nav nav-pills partner-filter-bar">
            <li class="nav-item">
                <a class="nav-link {{ $status === 'active' ? 'active' : '' }}" href="{{ route('admin.trade-partners.index', ['status' => 'active']) }}">
                    Active <span class="badge bg-success ms-1">{{ $activeCount }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $status === 'inactive' ? 'active' : '' }}" href="{{ route('admin.trade-partners.index', ['status' => 'inactive']) }}">
                    Inactive <span class="badge bg-secondary ms-1">{{ $inactiveCount }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $status === 'suspended' ? 'active' : '' }}" href="{{ route('admin.trade-partners.index', ['status' => 'suspended']) }}">
                    Suspended <span class="badge bg-warning text-dark ms-1">{{ $suspendedCount }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $status === 'all' ? 'active' : '' }}" href="{{ route('admin.trade-partners.index', ['status' => 'all']) }}">All</a>
            </li>
        </ul>

        @if($partners->isEmpty())
            <div class="text-muted text-center py-5">
                <i class="mdi mdi-account-hard-hat-outline" style="font-size:3rem;opacity:.35;"></i>
                <div class="mt-2">No trade partners found.</div>
            </div>
        @else
            <div class="partner-card-grid">
                @foreach($partners as $partner)
                    @php
                        $systemNames = collect($partner->system_ids ?? [])
                            ->map(fn ($id) => $systemsById[(int) $id] ?? null)
                            ->filter()
                            ->values();
                        $subsystemNames = collect($partner->subsystem_ids ?? [])
                            ->map(fn ($id) => $subsystemsById[(int) $id] ?? null)
                            ->filter()
                            ->values();
                        $pricingRows = collect($partner->agreed_subsystem_pricing ?? []);
                        $firstPricing = $pricingRows->first();
                        $initials = collect(explode(' ', (string) $partner->company_name))
                            ->filter()
                            ->take(2)
                            ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
                            ->join('');
                    @endphp
                    <article class="partner-card">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div class="partner-company">
                                <span class="partner-avatar">{{ $initials ?: 'TP' }}</span>
                                <div>
                                    <div class="partner-number">{{ $partner->partner_number }}</div>
                                    <h5 class="fw-bold mb-0">{{ $partner->company_name }}</h5>
                                    <div class="partner-muted">{{ $partner->service_area }}</div>
                                </div>
                            </div>
                            <span class="badge bg-{{ $partner->status === 'active' ? 'success' : ($partner->status === 'suspended' ? 'warning text-dark' : 'secondary') }}">
                                {{ ucwords($partner->status) }}
                            </span>
                        </div>

                        <div class="partner-card-meta">
                            <div class="partner-card-meta-item">
                                <div class="small text-muted fw-bold">Contact</div>
                                <div>{{ $partner->contact_person }}</div>
                                <div class="partner-muted">{{ $partner->phone }}{{ $partner->phone && $partner->email ? ' | ' : '' }}{{ $partner->email }}</div>
                            </div>

                            <div class="partner-card-meta-item">
                                <div class="small text-muted fw-bold">Coverage</div>
                                @forelse($systemNames->take(2) as $systemName)
                                    <span class="coverage-chip">{{ $systemName }}</span>
                                @empty
                                    <span class="text-muted">No system</span>
                                @endforelse
                                <div>
                                    @foreach($subsystemNames->take(3) as $subsystemName)
                                        <span class="coverage-chip">{{ $subsystemName }}</span>
                                    @endforeach
                                    @if($subsystemNames->count() > 3)
                                        <span class="coverage-chip">+{{ $subsystemNames->count() - 3 }}</span>
                                    @endif
                                </div>
                            </div>

                            <div class="partner-card-meta-item">
                                <div class="small text-muted fw-bold">Pricing</div>
                                @if($firstPricing)
                                    <div class="partner-pricing">CAD ${{ number_format((float) ($firstPricing['typical_rate'] ?? 0), 2) }} / {{ strtoupper($firstPricing['pricing_unit'] ?? 'unit') }}</div>
                                    <span class="partner-muted">{{ $firstPricing['estimated_hours'] ?? 'N/A' }} estimated hrs</span>
                                @else
                                    <span class="text-muted">No pricing</span>
                                @endif
                            </div>

                            <div class="partner-card-meta-item">
                                <div class="small text-muted fw-bold">Approved</div>
                                <div>{{ optional($partner->approved_at)->format('M d, Y') ?: 'N/A' }}</div>
                                @if($partner->application)
                                    <div class="partner-muted">{{ $partner->application->application_number }}</div>
                                @endif
                            </div>
                        </div>

                        <div class="partner-card-actions">
                            <a href="{{ route('admin.trade-partners.show', $partner) }}" class="btn btn-sm btn-outline-primary">
                                <i class="mdi mdi-account-eye-outline me-1"></i>View Partner
                            </a>
                            @if($partner->application)
                                <a href="{{ route('admin.trade-applications.show', $partner->application) }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="mdi mdi-file-eye-outline me-1"></i>Review Application
                                </a>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        @endif

        {{ $partners->links() }}
    </div>
</div>
@endsection
