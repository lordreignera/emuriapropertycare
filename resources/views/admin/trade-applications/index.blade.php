@extends('admin.layout')

@section('title', 'Trade Applications')
@section('header', 'Trade Applications')

@section('content')
<style>
    .trade-review-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        margin-bottom: 18px;
    }

    .trade-review-title h4 {
        font-size: 1.25rem;
        font-weight: 900;
        margin-bottom: 4px;
        color: #172033;
    }

    .trade-review-title p {
        margin: 0;
        color: #667085;
    }

    .trade-filter-bar {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 18px;
        padding-bottom: 18px;
        border-bottom: 1px solid #dfe5ef;
    }

    .trade-filter-bar .nav-link {
        min-width: 118px;
        text-align: center;
        border: 1px solid #d7deea;
        border-radius: 8px;
        font-weight: 800;
    }

    .trade-application-number {
        color: #0f3f8f;
        font-weight: 900;
        white-space: nowrap;
    }

    .trade-muted {
        color: #667085;
        font-size: .82rem;
    }

    .trade-status-pill {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 5px 10px;
        font-size: .78rem;
        font-weight: 900;
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

    .trade-application-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 16px;
    }

    .trade-application-card {
        border: 1px solid #dfe5ef;
        border-radius: 8px;
        background: #fff;
        padding: 16px;
        box-shadow: 0 8px 22px rgba(16, 24, 40, .055);
    }

    .trade-application-meta {
        display: grid;
        gap: 8px;
        margin: 14px 0;
    }

    .trade-application-meta-item {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        border-top: 1px solid #edf1f7;
        padding-top: 8px;
        font-size: .86rem;
    }

    .trade-application-meta-item span:first-child {
        color: #667085;
        font-weight: 800;
    }
</style>
<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="trade-review-header">
                    <div class="trade-review-title">
                        <h4>Trade Applications</h4>
                        <p>Review self-submitted trade partner onboarding forms and compliance documents.</p>
                    </div>
                    <a href="{{ route('trade-applications.create') }}" target="_blank" class="btn btn-sm btn-outline-primary">Open Public Form</a>
                </div>

                <ul class="nav nav-pills trade-filter-bar">
                    <li class="nav-item">
                        <a class="nav-link {{ $status === 'open' ? 'active' : '' }}" href="{{ route('admin.trade-applications.index', ['status' => 'open']) }}">
                            Awaiting Review <span class="badge bg-light text-dark ms-1">{{ $openCount }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $status === 'approved' ? 'active' : '' }}" href="{{ route('admin.trade-applications.index', ['status' => 'approved']) }}">
                            Approved <span class="badge bg-light text-dark ms-1">{{ $approvedCount }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $status === 'rejected' ? 'active' : '' }}" href="{{ route('admin.trade-applications.index', ['status' => 'rejected']) }}">
                            Rejected <span class="badge bg-light text-dark ms-1">{{ $rejectedCount }}</span>
                        </a>
                    </li>
                </ul>

                @if($applications->isEmpty())
                    <div class="text-center text-muted py-5">
                        <i class="mdi mdi-briefcase-search-outline" style="font-size:3rem;opacity:.35;"></i>
                        <div class="mt-2">No trade applications found.</div>
                    </div>
                @else
                    <div class="trade-application-grid">
                        @foreach($applications as $application)
                            @php
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
                            @endphp
                            <article class="trade-application-card">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div>
                                        <div class="trade-application-number">{{ $application->application_number }}</div>
                                        <div class="trade-muted">Submitted {{ optional($application->submitted_at ?? $application->created_at)->format('M d, Y') }}</div>
                                    </div>
                                    <span class="trade-status-pill {{ $statusClass }}">{{ $displayStatus }}</span>
                                </div>

                                <h5 class="fw-bold mt-3 mb-1">{{ $application->company_name }}</h5>
                                <div class="trade-muted">{{ $application->service_area ?: 'Service area not provided' }}</div>

                                <div class="trade-application-meta">
                                    <div class="trade-application-meta-item">
                                        <span>Partner ID</span>
                                        <strong>
                                            @if($application->tradePartner)
                                                {{ $application->tradePartner->partner_number }}
                                            @else
                                                Created after approval
                                            @endif
                                        </strong>
                                    </div>
                                    <div class="trade-application-meta-item">
                                        <span>Contact</span>
                                        <strong class="text-end">{{ $application->contact_person }}<br><small class="trade-muted">{{ $application->email }}</small></strong>
                                    </div>
                                </div>

                                <a href="{{ route('admin.trade-applications.show', $application) }}" class="btn btn-sm btn-outline-primary w-100">
                                    <i class="mdi mdi-file-eye-outline me-1"></i>Review Application
                                </a>
                            </article>
                        @endforeach
                    </div>
                @endif

                <div class="mt-3">{{ $applications->links() }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
