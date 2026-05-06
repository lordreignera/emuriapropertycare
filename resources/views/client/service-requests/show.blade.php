@extends('client.layout')

@section('title', 'Service Request')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-1">{{ $serviceRequest->request_number }}</h4>
                    <p class="text-muted mb-0">{{ ucwords(str_replace('_', ' ', $serviceRequest->request_type)) }} for {{ $serviceRequest->property?->property_name }}</p>
                </div>
                <span class="badge bg-info text-dark fs-6">{{ ucwords(str_replace('_', ' ', $serviceRequest->status)) }}</span>
            </div>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="fw-bold">Request Details</h5>
                <hr>
                <p><strong>Title:</strong> {{ $serviceRequest->title }}</p>
                <p><strong>Description:</strong><br>{{ $serviceRequest->description }}</p>
                <p><strong>Location:</strong> {{ $serviceRequest->requested_location ?: 'Not specified' }}</p>
                <p><strong>Preferred Window:</strong> {{ $serviceRequest->preferred_visit_window ?: 'Not specified' }}</p>

                <h6 class="mt-4">Reported Items</h6>
                @if(!empty($serviceRequest->items_reported))
                    <ul class="mb-0">
                        @foreach($serviceRequest->items_reported as $item)
                            <li>{{ is_array($item) ? ($item['issue'] ?? '') : $item }}</li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-muted mb-0">No line items provided.</p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="fw-bold">Meta</h6>
                <hr>
                <p class="mb-2"><strong>Urgency:</strong> {{ ucfirst($serviceRequest->urgency) }}</p>
                <p class="mb-2"><strong>Submitted:</strong> {{ optional($serviceRequest->submitted_at ?? $serviceRequest->created_at)->format('M d, Y h:i A') }}</p>
                <p class="mb-2"><strong>Assigned To:</strong> {{ $serviceRequest->assignedTo?->name ?? 'Not assigned yet' }}</p>
            </div>
        </div>

        @if(!empty($serviceRequest->photos))
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-body">
                    <h6 class="fw-bold">Photos</h6>
                    <div class="row g-2 mt-1">
                        @foreach($serviceRequest->photos as $path)
                            <div class="col-6">
                                <a href="{{ Storage::disk(config('filesystems.default', 'public'))->url($path) }}" target="_blank" rel="noopener">
                                    <img src="{{ Storage::disk(config('filesystems.default', 'public'))->url($path) }}" class="img-fluid rounded border" alt="Service request photo">
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
