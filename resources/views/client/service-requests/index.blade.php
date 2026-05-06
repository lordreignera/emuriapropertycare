@extends('client.layout')

@section('title', 'Service Requests')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, #198754 0%, #20c997 100%);">
            <div class="card-body text-white p-4 d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="fw-bold mb-1">Service Requests</h3>
                    <p class="mb-0 opacity-75">Track your reported issues, repairs, and change requests</p>
                </div>
                <a href="{{ route('client.service-requests.create') }}" class="btn btn-light">
                    <i class="mdi mdi-plus-circle me-1"></i> Report Issue
                </a>
            </div>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-body">
        @if($serviceRequests->isEmpty())
            <div class="text-center py-5">
                <i class="mdi mdi-clipboard-text-outline text-muted" style="font-size: 3rem;"></i>
                <h5 class="mt-3">No service requests yet</h5>
                <p class="text-muted">Submit your first issue so our team can triage and assess it.</p>
                <a href="{{ route('client.service-requests.create') }}" class="btn btn-success">
                    <i class="mdi mdi-plus me-1"></i> Create Service Request
                </a>
            </div>
        @else
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="bg-light">
                        <tr>
                            <th>Request #</th>
                            <th>Property</th>
                            <th>Type</th>
                            <th>Urgency</th>
                            <th>Status</th>
                            <th>Submitted</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($serviceRequests as $request)
                            <tr>
                                <td class="fw-semibold">{{ $request->request_number }}</td>
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
                                <td>
                                    <span class="badge bg-info text-dark">{{ ucwords(str_replace('_', ' ', $request->status)) }}</span>
                                </td>
                                <td>{{ optional($request->submitted_at ?? $request->created_at)->format('M d, Y') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('client.service-requests.show', $request) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="mdi mdi-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $serviceRequests->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
