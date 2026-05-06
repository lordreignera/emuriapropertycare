@extends('admin.layout')

@section('title', 'Service Requests')

@section('content')
<div class="content-wrapper">
    <div class="row">
        <div class="col-lg-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h4 class="card-title mb-0">Service Requests Queue</h4>
                            <p class="text-muted small mb-0">Client-reported issues waiting for triage and assessment intake</p>
                        </div>
                    </div>

                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    <ul class="nav nav-pills mb-3">
                        <li class="nav-item">
                            <a class="nav-link {{ $status === 'open' ? 'active' : '' }}" href="{{ route('admin.service-requests.index', ['status' => 'open']) }}">
                                Open <span class="badge bg-light text-dark ms-1">{{ $openCount }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $status === 'resolved' ? 'active' : '' }}" href="{{ route('admin.service-requests.index', ['status' => 'resolved']) }}">
                                Resolved <span class="badge bg-light text-dark ms-1">{{ $resolvedCount }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $status === 'all' ? 'active' : '' }}" href="{{ route('admin.service-requests.index', ['status' => 'all']) }}">All</a>
                        </li>
                    </ul>

                    <div class="table-responsive">
                        <table class="table table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>Request #</th>
                                    <th>Client</th>
                                    <th>Property</th>
                                    <th>Type</th>
                                    <th>Urgency</th>
                                    <th>Status</th>
                                    <th>Assigned</th>
                                    <th>Submitted</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($serviceRequests as $request)
                                    <tr>
                                        <td class="fw-semibold">{{ $request->request_number }}</td>
                                        <td>{{ $request->user?->name ?? 'N/A' }}</td>
                                        <td>
                                            <div>{{ $request->property?->property_name ?? 'N/A' }}</div>
                                            <small class="text-muted">{{ $request->property?->property_code ?? '' }}</small>
                                        </td>
                                        <td>{{ ucwords(str_replace('_', ' ', $request->request_type)) }}</td>
                                        <td>
                                            <span class="badge bg-{{ $request->urgency === 'critical' ? 'danger' : ($request->urgency === 'high' ? 'warning text-dark' : 'secondary') }}">
                                                {{ ucfirst($request->urgency) }}
                                            </span>
                                        </td>
                                        <td><span class="badge bg-info text-dark">{{ ucwords(str_replace('_', ' ', $request->status)) }}</span></td>
                                        <td>{{ $request->assignedTo?->name ?? 'Unassigned' }}</td>
                                        <td>{{ optional($request->submitted_at ?? $request->created_at)->format('M d, Y') }}</td>
                                        <td class="text-end">
                                            <a href="{{ route('admin.service-requests.show', $request) }}" class="btn btn-sm btn-outline-primary">View</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-4">No service requests found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $serviceRequests->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
