@extends('client.layout')

@section('title', 'Report Issue')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);">
            <div class="card-body text-white p-4">
                <h3 class="fw-bold mb-1">Report Issue / Request Change</h3>
                <p class="mb-0 opacity-75">Submit details so admin can triage and move this into assessment</p>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ route('client.service-requests.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Property</label>
                    <select name="property_id" class="form-select @error('property_id') is-invalid @enderror" required>
                        <option value="">Select property</option>
                        @foreach($properties as $property)
                            <option value="{{ $property->id }}" @selected(old('property_id') == $property->id)>
                                {{ $property->property_name }} ({{ $property->property_code }})
                            </option>
                        @endforeach
                    </select>
                    @error('property_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label">Request Type</label>
                    <select name="request_type" class="form-select @error('request_type') is-invalid @enderror" required>
                        <option value="emergency" @selected(old('request_type') === 'emergency')>Emergency</option>
                        <option value="repair" @selected(old('request_type', 'repair') === 'repair')>Repair</option>
                        <option value="change_request" @selected(old('request_type') === 'change_request')>Change Request</option>
                    </select>
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
                    <input type="text" name="title" value="{{ old('title') }}" class="form-control @error('title') is-invalid @enderror" maxlength="180" required>
                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" rows="4" class="form-control @error('description') is-invalid @enderror" required>{{ old('description') }}</textarea>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Location (optional)</label>
                    <input type="text" name="requested_location" value="{{ old('requested_location') }}" class="form-control @error('requested_location') is-invalid @enderror" maxlength="180" placeholder="Kitchen, rooftop AC room, Unit 302, etc.">
                    @error('requested_location')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Preferred Visit Window (optional)</label>
                    <input type="text" name="preferred_visit_window" value="{{ old('preferred_visit_window') }}" class="form-control @error('preferred_visit_window') is-invalid @enderror" maxlength="180" placeholder="Mon-Fri 9AM-12PM">
                    @error('preferred_visit_window')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label class="form-label">Items Reported (one per line)</label>
                    <textarea name="items_reported_text" rows="4" class="form-control @error('items_reported_text') is-invalid @enderror" placeholder="Leaking sink in unit 12B&#10;Cracked lobby tile near entrance">{{ old('items_reported_text') }}</textarea>
                    <div class="form-text">These entries seed the initial findings list for assessment.</div>
                    @error('items_reported_text')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label class="form-label">Photos (optional)</label>
                    <input type="file" name="photos[]" multiple accept="image/*" class="form-control @error('photos.*') is-invalid @enderror">
                    @error('photos.*')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="{{ route('client.service-requests.index') }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="mdi mdi-send me-1"></i> Submit Request
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
