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
                            Open <span class="badge bg-light text-dark ms-1">{{ $openCount }}</span>
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
                    <li class="nav-item">
                        <a class="nav-link {{ $status === 'all' ? 'active' : '' }}" href="{{ route('admin.trade-applications.index', ['status' => 'all']) }}">All</a>
                    </li>
                </ul>

                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th>Application #</th>
                                <th>Partner ID</th>
                                <th>Company</th>
                                <th>Contact</th>
                                <th>Service Area</th>
                                <th>Status</th>
                                <th>Submitted</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($applications as $application)
                                <tr>
                                    <td class="fw-semibold">{{ $application->application_number }}</td>
                                    <td>
                                        @if($application->tradePartner)
                                            <span class="badge bg-success">{{ $application->tradePartner->partner_number }}</span>
                                        @else
                                            <span class="text-muted">Pending approval</span>
                                        @endif
                                    </td>
                                    <td>{{ $application->company_name }}</td>
                                    <td>
                                        <div>{{ $application->contact_person }}</div>
                                        <small class="text-muted">{{ $application->email }}</small>
                                    </td>
                                    <td>{{ $application->service_area }}</td>
                                    <td><span class="badge bg-info text-dark">{{ $application->statusLabel() }}</span></td>
                                    <td>{{ optional($application->submitted_at ?? $application->created_at)->format('M d, Y') }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('admin.trade-applications.show', $application) }}" class="btn btn-sm btn-outline-primary">Review</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">No trade applications found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">{{ $applications->links() }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
