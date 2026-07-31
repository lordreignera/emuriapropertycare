@extends('admin.layout')

@section('title', 'Service Requests')

@section('content')
<div class="content-wrapper">
    <style>
        .service-request-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(330px, 1fr));
            gap: 16px;
        }

        .service-request-card {
            border: 1px solid #dfe6ef;
            border-radius: 8px;
            background: #fff;
            padding: 16px;
            box-shadow: 0 8px 22px rgba(16, 24, 40, .055);
        }

        .service-request-meta {
            display: grid;
            gap: 8px;
            margin: 14px 0;
        }

        .service-request-meta-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            border-top: 1px solid #edf1f7;
            padding-top: 8px;
            font-size: .86rem;
        }

        .service-request-meta-row span:first-child {
            color: #667085;
            font-weight: 800;
        }
    </style>
    <div class="row">
        <div class="col-lg-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h4 class="card-title mb-0">{{ $type === 'addendum' ? 'Add-on Requests Queue' : 'Service Requests Queue' }}</h4>
                            <p class="text-muted small mb-0">
                                {{ $type === 'addendum' ? 'Additional work requests waiting for review, diagnosis, quotation, and client approval' : 'Client-reported issues waiting for triage and diagnosis intake' }}
                            </p>
                        </div>
                        <a href="{{ route('admin.service-requests.create', ['type' => 'addendum']) }}" class="btn btn-primary btn-sm">
                            <i class="mdi mdi-file-plus-outline me-1"></i>Log Add-on Request
                        </a>
                    </div>

                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <ul class="nav nav-pills mb-3">
                        <li class="nav-item">
                            <a class="nav-link {{ $status === 'open' ? 'active' : '' }}" href="{{ route('admin.service-requests.index', ['status' => 'open']) }}">
                                Open <span class="badge bg-light text-dark ms-1">{{ $openCount }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $type === 'addendum' ? 'active' : '' }}" href="{{ route('admin.service-requests.index', ['status' => 'open', 'type' => 'addendum']) }}">
                                Add-on Requests <span class="badge bg-light text-dark ms-1">{{ $addendumCount }}</span>
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

                    @if($serviceRequests->isEmpty())
                        <div class="text-center text-muted py-5">
                            <i class="mdi mdi-clipboard-text-search-outline" style="font-size:3rem;opacity:.35;"></i>
                            <div class="mt-2">No service requests found.</div>
                        </div>
                    @else
                        <div class="service-request-grid">
                            @foreach($serviceRequests as $request)
                                <article class="service-request-card">
                                    <div class="d-flex justify-content-between align-items-start gap-2">
                                        <div>
                                            <div class="fw-bold text-primary">{{ $request->request_number }}</div>
                                            <div class="text-muted small">Submitted {{ optional($request->submitted_at ?? $request->created_at)->format('M d, Y') }}</div>
                                        </div>
                                        <span class="badge bg-info text-dark">{{ ucwords(str_replace('_', ' ', $request->status)) }}</span>
                                    </div>

                                    <h5 class="fw-bold mt-3 mb-1">{{ $request->property?->property_name ?? 'N/A' }}</h5>
                                    <div class="text-muted small">{{ $request->property?->property_code ?? '' }}</div>

                                    <div class="d-flex flex-wrap gap-2 mt-3">
                                        @if($request->request_type === 'change_request')
                                            <span class="badge bg-primary">Add-on / Quotation</span>
                                        @else
                                            <span class="badge bg-light text-dark border">{{ ucwords(str_replace('_', ' ', $request->request_type)) }}</span>
                                        @endif
                                        <span class="badge bg-{{ $request->urgency === 'critical' ? 'danger' : ($request->urgency === 'high' ? 'warning text-dark' : 'secondary') }}">
                                            {{ ucfirst($request->urgency) }}
                                        </span>
                                    </div>

                                    <div class="service-request-meta">
                                        <div class="service-request-meta-row">
                                            <span>Client</span>
                                            <strong class="text-end">{{ $request->user?->name ?? 'N/A' }}</strong>
                                        </div>
                                        <div class="service-request-meta-row">
                                            <span>Assigned</span>
                                            <strong class="text-end">{{ $request->assignedTo?->name ?? 'Unassigned' }}</strong>
                                        </div>
                                    </div>

                                    <a href="{{ route('admin.service-requests.show', $request) }}" class="btn btn-sm btn-outline-primary w-100">
                                        <i class="mdi mdi-eye-outline me-1"></i>View Request
                                    </a>
                                </article>
                            @endforeach
                        </div>
                    @endif

                    <div class="mt-3">
                        {{ $serviceRequests->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
