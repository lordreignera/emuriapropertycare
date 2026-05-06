@extends('admin.layout')

@section('title', 'Service Request Details')

@section('content')
<div class="content-wrapper">
    <div class="row">
        <div class="col-lg-8 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h4 class="card-title mb-0">{{ $serviceRequest->request_number }}</h4>
                            <p class="text-muted small mb-0">{{ ucwords(str_replace('_', ' ', $serviceRequest->request_type)) }} request from {{ $serviceRequest->user?->name }}</p>
                        </div>
                        <span class="badge bg-info text-dark">{{ ucwords(str_replace('_', ' ', $serviceRequest->status)) }}</span>
                    </div>

                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    <p><strong>Property:</strong> {{ $serviceRequest->property?->property_name }} ({{ $serviceRequest->property?->property_code }})</p>
                    <p><strong>Urgency:</strong> {{ ucfirst($serviceRequest->urgency) }}</p>
                    <p><strong>Title:</strong> {{ $serviceRequest->title }}</p>
                    <p><strong>Description:</strong><br>{{ $serviceRequest->description }}</p>
                    <p><strong>Requested Location:</strong> {{ $serviceRequest->requested_location ?: 'Not specified' }}</p>
                    <p><strong>Preferred Window:</strong> {{ $serviceRequest->preferred_visit_window ?: 'Not specified' }}</p>

                    <h6 class="mt-4">Client Reported Items (seed for findings)</h6>
                    @if(!empty($serviceRequest->items_reported))
                        <ul>
                            @foreach($serviceRequest->items_reported as $item)
                                <li>{{ is_array($item) ? ($item['issue'] ?? '') : $item }}</li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted">No explicit line items provided.</p>
                    @endif

                    @if(!empty($serviceRequest->triage_notes))
                        <div class="alert alert-light border mt-3 mb-0">
                            <strong>Triage Notes:</strong><br>
                            {{ $serviceRequest->triage_notes }}
                        </div>
                    @endif

                    @if(!empty($serviceRequest->photos))
                        <h6 class="mt-4">Photos</h6>
                        <div class="row g-2">
                            @foreach($serviceRequest->photos as $path)
                                <div class="col-md-3 col-6">
                                    <a href="{{ Storage::disk(config('filesystems.default', 'public'))->url($path) }}" target="_blank" rel="noopener">
                                        <img src="{{ Storage::disk(config('filesystems.default', 'public'))->url($path) }}" class="img-fluid rounded border" alt="Service request photo">
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <h5 class="mb-3">Triage</h5>

                    <form method="POST" action="{{ route('admin.service-requests.triage', $serviceRequest) }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                                <option value="triaged" @selected(old('status', $serviceRequest->status) === 'triaged')>Triaged</option>
                                <option value="awaiting_assessment" @selected(old('status', $serviceRequest->status) === 'awaiting_assessment')>Awaiting Assessment</option>
                                <option value="cancelled" @selected(old('status', $serviceRequest->status) === 'cancelled')>Cancelled</option>
                            </select>
                            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Assign To</label>
                            <select name="assigned_to" class="form-select @error('assigned_to') is-invalid @enderror">
                                <option value="">Unassigned</option>
                                @foreach($assignableStaff as $staff)
                                    <option value="{{ $staff->id }}" @selected(old('assigned_to', $serviceRequest->assigned_to) == $staff->id)>
                                        {{ $staff->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('assigned_to')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Triage Notes</label>
                            <textarea name="triage_notes" rows="4" class="form-control @error('triage_notes') is-invalid @enderror">{{ old('triage_notes', $serviceRequest->triage_notes) }}</textarea>
                            @error('triage_notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Save Triage</button>
                    </form>

                    <form method="POST" action="{{ route('admin.service-requests.assess', $serviceRequest) }}" class="mt-3">
                        @csrf
                        <button type="submit" class="btn btn-success w-100">
                            <i class="mdi mdi-clipboard-check me-1"></i> Convert To Assessment
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
