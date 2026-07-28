@extends('admin.layout')

@section('title', 'Log Service Request')

@section('content')
@php
    $addendumMode = $isAddendum ?? false;
@endphp
<div class="content-wrapper">
    <div class="row">
        <div class="col-lg-10 grid-margin stretch-card mx-auto">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
                        <div>
                            <h4 class="card-title mb-0">
                                <i class="mdi mdi-file-plus-outline me-2 text-primary"></i>
                                {{ $addendumMode ? 'Log Add-on Request' : 'Log Service Request' }}
                            </h4>
                            <p class="text-muted small mb-0">
                                {{ $addendumMode ? 'Record extra work requested by a client so it can be assessed and quoted.' : 'Record a client request and route it into triage.' }}
                            </p>
                        </div>
                        <a href="{{ route('admin.service-requests.index', $addendumMode ? ['type' => 'addendum'] : []) }}" class="btn btn-light btn-sm">
                            <i class="mdi mdi-arrow-left me-1"></i>Back to queue
                        </a>
                    </div>

                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <form method="POST" action="{{ route('admin.service-requests.store') }}">
                        @csrf

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Client Property</label>
                                <select name="property_id" class="form-select @error('property_id') is-invalid @enderror" required>
                                    <option value="">Select property</option>
                                    @foreach($properties as $property)
                                        <option value="{{ $property->id }}" @selected(old('property_id') == $property->id)>
                                            {{ $property->property_name }} ({{ $property->property_code }}) - {{ $property->user?->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('property_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Request Type</label>
                                @if($addendumMode)
                                    <input type="hidden" name="request_type" value="change_request">
                                    <input type="text" class="form-control" value="Add-on / Quotation" readonly>
                                @else
                                    <select name="request_type" class="form-select @error('request_type') is-invalid @enderror" required>
                                        <option value="emergency" @selected(old('request_type') === 'emergency')>Emergency</option>
                                        <option value="repair" @selected(old('request_type', 'repair') === 'repair')>Repair</option>
                                        <option value="change_request" @selected(old('request_type') === 'change_request')>Change Request</option>
                                    </select>
                                @endif
                                @error('request_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Urgency</label>
                                <select name="urgency" class="form-select @error('urgency') is-invalid @enderror" required>
                                    <option value="low" @selected(old('urgency') === 'low')>Low</option>
                                    <option value="medium" @selected(old('urgency', 'medium') === 'medium')>Medium</option>
                                    <option value="high" @selected(old('urgency') === 'high')>High</option>
                                    <option value="critical" @selected(old('urgency') === 'critical')>Critical</option>
                                </select>
                                @error('urgency')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">Title</label>
                                <input type="text" name="title" value="{{ old('title') }}" class="form-control @error('title') is-invalid @enderror" maxlength="180" placeholder="{{ $addendumMode ? 'Example: Add hallway repainting to current works' : '' }}" required>
                                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" rows="4" class="form-control @error('description') is-invalid @enderror" placeholder="{{ $addendumMode ? 'Summarise what the client asked for, why it is needed, and any commercial context.' : '' }}" required>{{ old('description') }}</textarea>
                                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Location (optional)</label>
                                <input type="text" name="requested_location" value="{{ old('requested_location') }}" class="form-control @error('requested_location') is-invalid @enderror" maxlength="180" placeholder="Lobby, roof, Unit 12B, exterior wall, etc.">
                                @error('requested_location')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Preferred Visit Window (optional)</label>
                                <input type="text" name="preferred_visit_window" value="{{ old('preferred_visit_window') }}" class="form-control @error('preferred_visit_window') is-invalid @enderror" maxlength="180" placeholder="Mon-Fri 9AM-12PM">
                                @error('preferred_visit_window')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">{{ $addendumMode ? 'Requested Add-ons (one per line)' : 'Items Reported (one per line)' }}</label>
                                <textarea name="items_reported_text" rows="4" class="form-control @error('items_reported_text') is-invalid @enderror" placeholder="{{ $addendumMode ? 'Add door closer replacement&#10;Quote repainting stairwell walls' : 'Leaking sink in unit 12B&#10;Cracked lobby tile near entrance' }}">{{ old('items_reported_text') }}</textarea>
                                <div class="form-text">Each line can seed the findings list when this request is converted to assessment.</div>
                                @error('items_reported_text')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('admin.service-requests.index', $addendumMode ? ['type' => 'addendum'] : []) }}" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="mdi mdi-content-save-outline me-1"></i>{{ $addendumMode ? 'Log Add-on Request' : 'Log Request' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection