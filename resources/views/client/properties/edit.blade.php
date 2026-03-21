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

                    {{-- ===== VALIDATION ERROR SUMMARY ===== --}}
                    @if($errors->any())
                    <div class="alert alert-danger border-danger mb-4" role="alert"
                         style="border-left:4px solid #dc3545 !important; position:sticky; top:70px; z-index:100;">
                        <div class="d-flex align-items-center mb-2">
                            <i class="mdi mdi-alert-circle fs-5 me-2"></i>
                            <strong>Please fix the following errors before submitting:</strong>
                        </div>
                        <ul class="mb-0 ps-3">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
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
                            <label class="form-label">Home Style</label>
                            <select name="property_subtype" class="form-select @error('property_subtype') is-invalid @enderror">
                                <option value="">Select Home Style</option>
                                <option value="house" @selected(old('property_subtype', $property->property_subtype) === 'house')>House</option>
                                <option value="townhome" @selected(old('property_subtype', $property->property_subtype) === 'townhome')>Townhome</option>
                                <option value="condo" @selected(old('property_subtype', $property->property_subtype) === 'condo')>Condo</option>
                                <option value="duplex" @selected(old('property_subtype', $property->property_subtype) === 'duplex')>Duplex</option>
                                <option value="multi_unit" @selected(old('property_subtype', $property->property_subtype) === 'multi_unit')>Multi-Unit</option>
                            </select>
                            @error('property_subtype')
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

                    @php
                        $selectedHomeJourney = old('home_journey', $property->home_journey ?? []);
                        $selectedHomeFeel = old('home_feel', $property->home_feel ?? []);
                        $selectedCareGoals = old('care_goals', $property->care_goals ?? []);
                    @endphp

                    <h5 class="mb-3">Step 1 — Warm Welcome & Vision</h5>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Home Journey</label>
                            <div class="form-check"><input class="form-check-input" type="checkbox" name="home_journey[]" id="ehj_proactive" value="proactive_care" {{ in_array('proactive_care', (array) $selectedHomeJourney) ? 'checked' : '' }}><label class="form-check-label" for="ehj_proactive">Proactive care</label></div>
                            <div class="form-check"><input class="form-check-input" type="checkbox" name="home_journey[]" id="ehj_predictable" value="predictable_maintenance" {{ in_array('predictable_maintenance', (array) $selectedHomeJourney) ? 'checked' : '' }}><label class="form-check-label" for="ehj_predictable">Predictable maintenance</label></div>
                            <div class="form-check"><input class="form-check-input" type="checkbox" name="home_journey[]" id="ehj_quality" value="improve_quality_of_life" {{ in_array('improve_quality_of_life', (array) $selectedHomeJourney) ? 'checked' : '' }}><label class="form-check-label" for="ehj_quality">Improve quality of life</label></div>
                            <div class="form-check"><input class="form-check-input" type="checkbox" name="home_journey[]" id="ehj_value" value="maintain_property_value" {{ in_array('maintain_property_value', (array) $selectedHomeJourney) ? 'checked' : '' }}><label class="form-check-label" for="ehj_value">Maintain/increase property value</label></div>
                            <div class="form-check"><input class="form-check-input" type="checkbox" name="home_journey[]" id="ehj_repairs" value="support_repairs_and_renovations" {{ in_array('support_repairs_and_renovations', (array) $selectedHomeJourney) ? 'checked' : '' }}><label class="form-check-label" for="ehj_repairs">Support repairs/renovations</label></div>
                            <div class="form-check"><input class="form-check-input" type="checkbox" name="home_journey[]" id="ehj_team" value="trusted_team" {{ in_array('trusted_team', (array) $selectedHomeJourney) ? 'checked' : '' }}><label class="form-check-label" for="ehj_team">Trusted team</label></div>
                            <div class="form-check"><input class="form-check-input" type="checkbox" name="home_journey[]" id="ehj_ready" value="guest_ready" {{ in_array('guest_ready', (array) $selectedHomeJourney) ? 'checked' : '' }}><label class="form-check-label" for="ehj_ready">Guest-ready / inspection-ready</label></div>
                            <div class="mt-3">
                                <label for="edit_custom_home_journey_input" class="form-label">Add custom home journey item</label>
                                <div class="d-flex gap-2 mb-2">
                                    <input type="text" class="form-control" id="edit_custom_home_journey_input" placeholder="Type custom journey item and click Add">
                                    <button type="button" class="btn btn-outline-primary" id="edit_add_custom_home_journey_btn">
                                        <i class="mdi mdi-plus"></i> Add
                                    </button>
                                </div>
                                <div id="edit_custom_home_journey_list" class="list-input-container"></div>
                                <div id="edit_custom_home_journey_inputs"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Well-Cared-For Home Feels Like</label>
                            <div class="form-check"><input class="form-check-input" type="checkbox" name="home_feel[]" id="ehf_safe" value="safe_healthy" {{ in_array('safe_healthy', (array) $selectedHomeFeel) ? 'checked' : '' }}><label class="form-check-label" for="ehf_safe">Safe & healthy</label></div>
                            <div class="form-check"><input class="form-check-input" type="checkbox" name="home_feel[]" id="ehf_organized" value="organized_peaceful" {{ in_array('organized_peaceful', (array) $selectedHomeFeel) ? 'checked' : '' }}><label class="form-check-label" for="ehf_organized">Organized & peaceful</label></div>
                            <div class="form-check"><input class="form-check-input" type="checkbox" name="home_feel[]" id="ehf_inviting" value="inviting_beautiful" {{ in_array('inviting_beautiful', (array) $selectedHomeFeel) ? 'checked' : '' }}><label class="form-check-label" for="ehf_inviting">Inviting & beautiful</label></div>
                            <div class="form-check"><input class="form-check-input" type="checkbox" name="home_feel[]" id="ehf_efficient" value="efficient_modern" {{ in_array('efficient_modern', (array) $selectedHomeFeel) ? 'checked' : '' }}><label class="form-check-label" for="ehf_efficient">Efficient & modern</label></div>
                            <div class="form-check"><input class="form-check-input" type="checkbox" name="home_feel[]" id="ehf_lowstress" value="low_stress_effortless" {{ in_array('low_stress_effortless', (array) $selectedHomeFeel) ? 'checked' : '' }}><label class="form-check-label" for="ehf_lowstress">Low-stress & effortless</label></div>
                            <div class="form-check"><input class="form-check-input" type="checkbox" name="home_feel[]" id="ehf_life" value="ready_for_life_changes" {{ in_array('ready_for_life_changes', (array) $selectedHomeFeel) ? 'checked' : '' }}><label class="form-check-label" for="ehf_life">Ready for life changes</label></div>
                            <div class="mt-3">
                                <label for="edit_custom_home_feel_input" class="form-label">Add custom home feel item</label>
                                <div class="d-flex gap-2 mb-2">
                                    <input type="text" class="form-control" id="edit_custom_home_feel_input" placeholder="Type custom home-feel item and click Add">
                                    <button type="button" class="btn btn-outline-primary" id="edit_add_custom_home_feel_btn">
                                        <i class="mdi mdi-plus"></i> Add
                                    </button>
                                </div>
                                <div id="edit_custom_home_feel_list" class="list-input-container"></div>
                                <div id="edit_custom_home_feel_inputs"></div>
                            </div>
                        </div>
                    </div>

                    <h5 class="mb-3">Step 3 — Home Care Goals</h5>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Comfort & Beauty</label>
                            <div class="form-check"><input class="form-check-input" type="checkbox" name="care_goals[]" id="ecg_walls" value="walls_paint_care" {{ in_array('walls_paint_care', (array) $selectedCareGoals) ? 'checked' : '' }}><label class="form-check-label" for="ecg_walls">Walls & paint care</label></div>
                            <div class="form-check"><input class="form-check-input" type="checkbox" name="care_goals[]" id="ecg_trim" value="trim_woodwork_finishing" {{ in_array('trim_woodwork_finishing', (array) $selectedCareGoals) ? 'checked' : '' }}><label class="form-check-label" for="ecg_trim">Trim & woodwork finishing</label></div>
                            <div class="form-check"><input class="form-check-input" type="checkbox" name="care_goals[]" id="ecg_floor" value="flooring_care_patching" {{ in_array('flooring_care_patching', (array) $selectedCareGoals) ? 'checked' : '' }}><label class="form-check-label" for="ecg_floor">Flooring care & patching</label></div>
                            <div class="form-check"><input class="form-check-input" type="checkbox" name="care_goals[]" id="ecg_appliance" value="appliance_support" {{ in_array('appliance_support', (array) $selectedCareGoals) ? 'checked' : '' }}><label class="form-check-label" for="ecg_appliance">Appliance support</label></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Protection & Safety</label>
                            <div class="form-check"><input class="form-check-input" type="checkbox" name="care_goals[]" id="ecg_electrical" value="electrical_safety" {{ in_array('electrical_safety', (array) $selectedCareGoals) ? 'checked' : '' }}><label class="form-check-label" for="ecg_electrical">Electrical safety</label></div>
                            <div class="form-check"><input class="form-check-input" type="checkbox" name="care_goals[]" id="ecg_moisture" value="moisture_leak_prevention" {{ in_array('moisture_leak_prevention', (array) $selectedCareGoals) ? 'checked' : '' }}><label class="form-check-label" for="ecg_moisture">Moisture & leak prevention</label></div>
                            <div class="form-check"><input class="form-check-input" type="checkbox" name="care_goals[]" id="ecg_hvac" value="hvac_filters_program" {{ in_array('hvac_filters_program', (array) $selectedCareGoals) ? 'checked' : '' }}><label class="form-check-label" for="ecg_hvac">HVAC & filters program</label></div>
                            <div class="form-check"><input class="form-check-input" type="checkbox" name="care_goals[]" id="ecg_pest" value="pest_prevention_sealing" {{ in_array('pest_prevention_sealing', (array) $selectedCareGoals) ? 'checked' : '' }}><label class="form-check-label" for="ecg_pest">Pest prevention & sealing</label></div>
                        </div>
                        <div class="col-md-6 mt-3">
                            <label class="form-label fw-bold">Exterior & Grounds</label>
                            <div class="form-check"><input class="form-check-input" type="checkbox" name="care_goals[]" id="ecg_gutter" value="gutter_cleaning_drainage" {{ in_array('gutter_cleaning_drainage', (array) $selectedCareGoals) ? 'checked' : '' }}><label class="form-check-label" for="ecg_gutter">Gutter cleaning & drainage</label></div>
                            <div class="form-check"><input class="form-check-input" type="checkbox" name="care_goals[]" id="ecg_pressure" value="pressure_washing" {{ in_array('pressure_washing', (array) $selectedCareGoals) ? 'checked' : '' }}><label class="form-check-label" for="ecg_pressure">Pressure washing</label></div>
                            <div class="form-check"><input class="form-check-input" type="checkbox" name="care_goals[]" id="ecg_garden" value="garden_lawn_care" {{ in_array('garden_lawn_care', (array) $selectedCareGoals) ? 'checked' : '' }}><label class="form-check-label" for="ecg_garden">Garden / lawn care</label></div>
                            <div class="form-check"><input class="form-check-input" type="checkbox" name="care_goals[]" id="ecg_tree" value="tree_pruning_yard_health" {{ in_array('tree_pruning_yard_health', (array) $selectedCareGoals) ? 'checked' : '' }}><label class="form-check-label" for="ecg_tree">Tree pruning & yard health</label></div>
                        </div>
                        <div class="col-md-6 mt-3">
                            <label class="form-label fw-bold">Convenience & Lifestyle</label>
                            <div class="form-check"><input class="form-check-input" type="checkbox" name="care_goals[]" id="ecg_seasonal" value="seasonal_prep" {{ in_array('seasonal_prep', (array) $selectedCareGoals) ? 'checked' : '' }}><label class="form-check-label" for="ecg_seasonal">Seasonal prep (fall/spring)</label></div>
                            <div class="form-check"><input class="form-check-input" type="checkbox" name="care_goals[]" id="ecg_travel" value="travel_away_watch" {{ in_array('travel_away_watch', (array) $selectedCareGoals) ? 'checked' : '' }}><label class="form-check-label" for="ecg_travel">Travel-away home watch</label></div>
                            <div class="form-check"><input class="form-check-input" type="checkbox" name="care_goals[]" id="ecg_move" value="moving_in_out_service" {{ in_array('moving_in_out_service', (array) $selectedCareGoals) ? 'checked' : '' }}><label class="form-check-label" for="ecg_move">Moving in or out service</label></div>
                            <div class="form-check"><input class="form-check-input" type="checkbox" name="care_goals[]" id="ecg_presale" value="pre_sale_refresh" {{ in_array('pre_sale_refresh', (array) $selectedCareGoals) ? 'checked' : '' }}><label class="form-check-label" for="ecg_presale">Pre-sale property refresh</label></div>
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
                            <label class="form-label">Describe Personality Further <small class="text-muted">(optional)</small></label>
                            <textarea name="personality_notes" class="form-control @error('personality_notes') is-invalid @enderror" rows="3" placeholder="Add extra context for your property personality...">{{ old('personality_notes', $property->personality_notes) }}</textarea>
                            @error('personality_notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label d-block">Any sensitivities? <small class="text-muted">(check all)</small></label>
                            @php $selectedSensitivities = (array) old('sensitivities', $property->sensitivities ?? []); @endphp
                            <div class="form-check"><input class="form-check-input" type="checkbox" name="sensitivities[]" id="edit_sens_allergies" value="allergies_air_quality" {{ in_array('allergies_air_quality', $selectedSensitivities) ? 'checked' : '' }}><label class="form-check-label" for="edit_sens_allergies">Allergies / air quality</label></div>
                            <div class="form-check"><input class="form-check-input" type="checkbox" name="sensitivities[]" id="edit_sens_water" value="water_damage_risk" {{ in_array('water_damage_risk', $selectedSensitivities) ? 'checked' : '' }}><label class="form-check-label" for="edit_sens_water">Water damage risk</label></div>
                            <div class="form-check"><input class="form-check-input" type="checkbox" name="sensitivities[]" id="edit_sens_aging" value="aging_in_place_needs" {{ in_array('aging_in_place_needs', $selectedSensitivities) ? 'checked' : '' }}><label class="form-check-label" for="edit_sens_aging">Aging-in-place needs</label></div>
                            <div class="form-check"><input class="form-check-input" type="checkbox" name="sensitivities[]" id="edit_sens_eco" value="eco_friendly_products_only" {{ in_array('eco_friendly_products_only', $selectedSensitivities) ? 'checked' : '' }}><label class="form-check-label" for="edit_sens_eco">Eco-friendly products only</label></div>
                            <div class="form-check"><input class="form-check-input" type="checkbox" name="sensitivities[]" id="edit_sens_pet" value="pet_safe_cleaning_materials" {{ in_array('pet_safe_cleaning_materials', $selectedSensitivities) ? 'checked' : '' }}><label class="form-check-label" for="edit_sens_pet">Pet-safe cleaning materials</label></div>
                            <div class="form-check"><input class="form-check-input" type="checkbox" name="sensitivities[]" id="edit_sens_accessibility" value="accessibility_modifications" {{ in_array('accessibility_modifications', $selectedSensitivities) ? 'checked' : '' }}><label class="form-check-label" for="edit_sens_accessibility">Accessibility Modifications</label></div>

                            <div class="mt-3">
                                <label for="edit_custom_sensitivity_input" class="form-label">Add custom sensitivity</label>
                                <div class="d-flex gap-2 mb-2">
                                    <input type="text" class="form-control" id="edit_custom_sensitivity_input" placeholder="Type custom sensitivity and click Add">
                                    <button type="button" class="btn btn-outline-primary" id="edit_add_custom_sensitivity_btn">
                                        <i class="mdi mdi-plus"></i> Add
                                    </button>
                                </div>
                                <div id="edit_custom_sensitivities_list" class="list-input-container"></div>
                                <div id="edit_custom_sensitivities_inputs"></div>
                                <small class="form-text text-muted">You can add anything not listed above and remove it before submitting.</small>
                            </div>

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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const predefinedHomeJourneyValues = [
        'proactive_care',
        'predictable_maintenance',
        'improve_quality_of_life',
        'maintain_property_value',
        'support_repairs_and_renovations',
        'trusted_team',
        'guest_ready'
    ];

    const predefinedHomeFeelValues = [
        'safe_healthy',
        'organized_peaceful',
        'inviting_beautiful',
        'efficient_modern',
        'low_stress_effortless',
        'ready_for_life_changes'
    ];

    const predefinedSensitivityValues = [
        'allergies_air_quality',
        'water_damage_risk',
        'aging_in_place_needs',
        'eco_friendly_products_only',
        'pet_safe_cleaning_materials',
        'accessibility_modifications'
    ];

    function normalizeCustomSensitivity(value) {
        return String(value || '').trim();
    }

    function initCustomChecklistValues(config) {
        const inputEl = document.getElementById(config.inputId);
        const addBtn = document.getElementById(config.addBtnId);
        const listEl = document.getElementById(config.listId);
        const hiddenWrap = document.getElementById(config.hiddenContainerId);
        const values = (config.selectedRaw || []).filter(value => !config.predefinedValues.includes(value));

        function syncHiddenInputs() {
            hiddenWrap.innerHTML = '';
            values.forEach((value) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = config.hiddenName;
                input.value = value;
                hiddenWrap.appendChild(input);
            });
        }

        function renderValues() {
            listEl.innerHTML = '';

            if (!values.length) {
                const empty = document.createElement('div');
                empty.className = 'text-muted small';
                empty.textContent = config.emptyText;
                listEl.appendChild(empty);
                syncHiddenInputs();
                return;
            }

            values.forEach((item, index) => {
                const badge = document.createElement('div');
                badge.className = 'list-item-badge';
                badge.innerHTML = `
                    <span>${item}</span>
                    <button type="button" class="btn btn-sm btn-link text-danger p-0 ms-2" data-index="${index}" aria-label="${config.removeAriaLabel}">
                        <i class="mdi mdi-close-circle"></i>
                    </button>
                `;

                badge.querySelector('button').addEventListener('click', function() {
                    values.splice(index, 1);
                    renderValues();
                });

                listEl.appendChild(badge);
            });

            syncHiddenInputs();
        }

        function addValue() {
            const value = normalizeCustomSensitivity(inputEl.value);
            if (!value) {
                return;
            }

            const existsInPredefined = config.predefinedValues.includes(value);
            const existsInCustom = values.some(item => item.toLowerCase() === value.toLowerCase());

            if (!existsInPredefined && !existsInCustom) {
                values.push(value);
            }

            inputEl.value = '';
            renderValues();
        }

        addBtn?.addEventListener('click', addValue);
        inputEl?.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                addValue();
            }
        });

        renderValues();
    }

    initCustomChecklistValues({
        inputId: 'edit_custom_home_journey_input',
        addBtnId: 'edit_add_custom_home_journey_btn',
        listId: 'edit_custom_home_journey_list',
        hiddenContainerId: 'edit_custom_home_journey_inputs',
        hiddenName: 'home_journey[]',
        selectedRaw: @json($selectedHomeJourney ?? []),
        predefinedValues: predefinedHomeJourneyValues,
        emptyText: 'No custom home journey items added.',
        removeAriaLabel: 'Remove home journey item'
    });

    initCustomChecklistValues({
        inputId: 'edit_custom_home_feel_input',
        addBtnId: 'edit_add_custom_home_feel_btn',
        listId: 'edit_custom_home_feel_list',
        hiddenContainerId: 'edit_custom_home_feel_inputs',
        hiddenName: 'home_feel[]',
        selectedRaw: @json($selectedHomeFeel ?? []),
        predefinedValues: predefinedHomeFeelValues,
        emptyText: 'No custom home feel items added.',
        removeAriaLabel: 'Remove home feel item'
    });

    initCustomChecklistValues({
        inputId: 'edit_custom_sensitivity_input',
        addBtnId: 'edit_add_custom_sensitivity_btn',
        listId: 'edit_custom_sensitivities_list',
        hiddenContainerId: 'edit_custom_sensitivities_inputs',
        hiddenName: 'sensitivities[]',
        selectedRaw: @json($selectedSensitivities ?? []),
        predefinedValues: predefinedSensitivityValues,
        emptyText: 'No custom sensitivities added.',
        removeAriaLabel: 'Remove sensitivity'
    });
});
</script>
@endsection
