@extends('admin.layout')

@section('title', 'Trade Partners')
@section('header', 'Trade Partners')

@section('content')
<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="card-title mb-1">Approved Trade Partners</h4>
                <p class="text-muted mb-0">Active partners created after trade application approval.</p>
            </div>
            <a href="{{ route('admin.trade-applications.index') }}" class="btn btn-sm btn-outline-primary">Review Applications</a>
        </div>

        <ul class="nav nav-tabs mb-3">
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

        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                    <tr>
                        <th>Partner ID</th>
                        <th>Company</th>
                        <th>Contact</th>
                        <th>Service Area</th>
                        <th>Status</th>
                        <th>Approved</th>
                        <th>Application</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($partners as $partner)
                        <tr>
                            <td class="fw-semibold">{{ $partner->partner_number }}</td>
                            <td>{{ $partner->company_name }}</td>
                            <td>
                                {{ $partner->contact_person }}<br>
                                <span class="text-muted small">{{ $partner->phone }} | {{ $partner->email }}</span>
                            </td>
                            <td>{{ $partner->service_area }}</td>
                            <td><span class="badge bg-{{ $partner->status === 'active' ? 'success' : ($partner->status === 'suspended' ? 'warning text-dark' : 'secondary') }}">{{ ucwords($partner->status) }}</span></td>
                            <td>{{ optional($partner->approved_at)->format('M d, Y') ?: 'N/A' }}</td>
                            <td>
                                @if($partner->application)
                                    <a href="{{ route('admin.trade-applications.show', $partner->application) }}">{{ $partner->application->application_number }}</a>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.trade-partners.show', $partner) }}" class="btn btn-sm btn-outline-primary">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-muted text-center py-4">No trade partners found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $partners->links() }}
    </div>
</div>
@endsection
