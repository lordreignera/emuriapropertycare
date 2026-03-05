@extends('client.layout')

@section('title', 'Edit Property')

@section('header', 'Edit Property')

@section('breadcrumbs')
<li class="breadcrumb-item"><a href="{{ route('client.properties.index') }}">Properties</a></li>
<li class="breadcrumb-item"><a href="{{ route('client.properties.show', $property->id) }}">{{ $property->property_code }}</a></li>
<li class="breadcrumb-item active" aria-current="page">Edit</li>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('client.properties.update', $property->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Property Name</label>
                            <input type="text" name="property_name" class="form-control @error('property_name') is-invalid @enderror"
                                   value="{{ old('property_name', $property->property_name) }}" required>
                            @error('property_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Brand (optional)</label>
                            <input type="text" name="property_brand" class="form-control @error('property_brand') is-invalid @enderror"
                                   value="{{ old('property_brand', $property->property_brand) }}">
                            @error('property_brand')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Type</label>
                            <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                                @foreach(['residential','commercial','mixed_use'] as $type)
                                    <option value="{{ $type }}" @selected(old('type', $property->type) === $type)>
                                        {{ ucfirst(str_replace('_', ' ', $type)) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('type')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Year Built</label>
                            <input type="number" name="year_built" class="form-control @error('year_built') is-invalid @enderror"
                                   value="{{ old('year_built', $property->year_built) }}" min="1800" max="{{ date('Y') }}">
                            @error('year_built')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <hr>

                    <h5 class="mb-3">Address</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Address</label>
                            <input type="text" name="property_address" class="form-control @error('property_address') is-invalid @enderror"
                                   value="{{ old('property_address', $property->property_address) }}" required>
                            @error('property_address')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">City</label>
                            <input type="text" name="city" class="form-control @error('city') is-invalid @enderror"
                                   value="{{ old('city', $property->city) }}" required>
                            @error('city')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Province/State</label>
                            <input type="text" name="province" class="form-control @error('province') is-invalid @enderror"
                                   value="{{ old('province', $property->province) }}" required>
                            @error('province')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Postal Code</label>
                            <input type="text" name="postal_code" class="form-control @error('postal_code') is-invalid @enderror"
                                   value="{{ old('postal_code', $property->postal_code) }}" required>
                            @error('postal_code')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Country</label>
                            <input type="text" name="country" class="form-control @error('country') is-invalid @enderror"
                                   value="{{ old('country', $property->country) }}" required>
                            @error('country')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <hr>

                    <h5 class="mb-3">Owner</h5>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Owner Name</label>
                            <input type="text" name="owner_first_name" class="form-control @error('owner_first_name') is-invalid @enderror"
                                   value="{{ old('owner_first_name', $property->owner_first_name) }}" required>
                            @error('owner_first_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Owner Phone</label>
                            <input type="text" name="owner_phone" class="form-control @error('owner_phone') is-invalid @enderror"
                                   value="{{ old('owner_phone', $property->owner_phone) }}" required>
                            @error('owner_phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Owner Email</label>
                            <input type="email" name="owner_email" class="form-control @error('owner_email') is-invalid @enderror"
                                   value="{{ old('owner_email', $property->owner_email) }}" required>
                            @error('owner_email')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <hr>

                    <h5 class="mb-3">Additional Details</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Personality</label>
                            <select name="personality" class="form-select @error('personality') is-invalid @enderror">
                                <option value="">Select</option>
                                @foreach(['calm','busy','luxury','high-use'] as $personality)
                                    <option value="{{ $personality }}" @selected(old('personality', $property->personality) === $personality)>
                                        {{ ucfirst($personality) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('personality')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Sensitivities (comma-separated)</label>
                            <input type="text" name="sensitivities" class="form-control @error('sensitivities') is-invalid @enderror"
                                   value="{{ old('sensitivities', is_array($property->sensitivities) ? implode(', ', $property->sensitivities) : '') }}">
                            @error('sensitivities')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Known Problems</label>
                            <textarea name="known_problems" class="form-control @error('known_problems') is-invalid @enderror" rows="3">{{ old('known_problems', $property->known_problems) }}</textarea>
                            @error('known_problems')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <hr>

                    <h5 class="mb-3">Uploads</h5>
                    @if($property->property_photos && count($property->property_photos) > 0)
                        <div class="row mb-3">
                            @foreach($property->property_photos as $photo)
                                <div class="col-md-3 mb-2">
                                    <img src="{{ $property->getStorageUrl($photo) }}" class="img-fluid rounded" alt="Property Photo">
                                </div>
                            @endforeach
                        </div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label">Replace Property Photos</label>
                        <input type="file" name="property_photos[]" class="form-control @error('property_photos.*') is-invalid @enderror" multiple accept="image/*">
                        @error('property_photos.*')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Replace Blueprint (optional)</label>
                        <input type="file" name="blueprint_file" class="form-control @error('blueprint_file') is-invalid @enderror" accept="image/jpeg,image/png,image/jpg,application/pdf,.dwg,.dxf">
                        <small class="form-text text-muted">Supported formats: PDF, JPG/JPEG, PNG, DWG, DXF. For images, use at least 1000px shortest side.</small>
                        @error('blueprint_file')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('client.properties.show', $property->id) }}" class="btn btn-light">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update Property</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
