@extends('admin.layout')

@section('content')
<div class="row">
    <div class="col-lg-8 mx-auto grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Edit Recommendation</h4>

                @php
                    $systemsJson = ($systems ?? collect())->map(function ($system) {
                        return [
                            'id' => $system->id,
                            'name' => $system->name,
                            'subsystems' => $system->subsystems->map(function ($subsystem) {
                                return [
                                    'id' => $subsystem->id,
                                    'name' => $subsystem->name,
                                    'components' => $subsystem->components->map(function ($component) {
                                        return [
                                            'id' => $component->id,
                                            'name' => $component->name,
                                        ];
                                    })->values()->all(),
                                ];
                            })->values()->all(),
                        ];
                    })->values()->all();
                @endphp

                <form action="{{ route('admin.recommendation-settings.update', $recommendationSetting) }}" method="POST" class="forms-sample">
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label for="recommendation">Recommendation <span class="text-danger">*</span></label>
                        <textarea class="form-control @error('recommendation') is-invalid @enderror" id="recommendation" name="recommendation" rows="4" maxlength="500" required>{{ old('recommendation', $recommendationSetting->recommendation) }}</textarea>
                        @error('recommendation')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label for="building_system_id">System Scope</label>
                            <select class="form-control @error('building_system_id') is-invalid @enderror" id="building_system_id" name="building_system_id">
                                <option value="">All Systems</option>
                                @foreach(($systems ?? collect()) as $system)
                                    <option value="{{ $system->id }}" {{ (string) old('building_system_id', $recommendationSetting->building_system_id) === (string) $system->id ? 'selected' : '' }}>{{ $system->name }}</option>
                                @endforeach
                            </select>
                            @error('building_system_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4 form-group">
                            <label for="building_subsystem_id">Subsystem Scope</label>
                            <select class="form-control @error('building_subsystem_id') is-invalid @enderror" id="building_subsystem_id" name="building_subsystem_id">
                                <option value="">All Subsystems</option>
                            </select>
                            @error('building_subsystem_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4 form-group">
                            <label for="building_component_id">Component Scope</label>
                            <select class="form-control @error('building_component_id') is-invalid @enderror" id="building_component_id" name="building_component_id">
                                <option value="">All Components</option>
                            </select>
                            @error('building_component_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="sort_order">Sort Order</label>
                        <input type="number" min="0" class="form-control @error('sort_order') is-invalid @enderror" id="sort_order" name="sort_order" value="{{ old('sort_order', $recommendationSetting->sort_order) }}">
                        @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-check">
                        <label class="form-check-label">
                            <input type="checkbox" class="form-check-input" name="is_active" value="1" {{ old('is_active', $recommendationSetting->is_active) ? 'checked' : '' }}>
                            Active
                        </label>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary me-2"><i class="mdi mdi-content-save"></i> Update Recommendation</button>
                        <a href="{{ route('admin.recommendation-settings.index') }}" class="btn btn-light">Cancel</a>
                    </div>
                </form>

                <script>
                    (function () {
                        const systems = @json($systemsJson);
                        const systemSelect = document.getElementById('building_system_id');
                        const subsystemSelect = document.getElementById('building_subsystem_id');
                        const componentSelect = document.getElementById('building_component_id');
                        const selectedSubsystemId = "{{ old('building_subsystem_id', $recommendationSetting->building_subsystem_id) }}";
                        const selectedComponentId = "{{ old('building_component_id', $recommendationSetting->building_component_id) }}";

                        function renderSubsystems(systemId) {
                            subsystemSelect.innerHTML = '<option value="">All Subsystems</option>';
                            componentSelect.innerHTML = '<option value="">All Components</option>';
                            const selectedSystem = systems.find((system) => String(system.id) === String(systemId));

                            if (!selectedSystem) {
                                return;
                            }

                            selectedSystem.subsystems.forEach((subsystem) => {
                                const option = document.createElement('option');
                                option.value = subsystem.id;
                                option.textContent = subsystem.name;
                                if (String(subsystem.id) === String(selectedSubsystemId)) {
                                    option.selected = true;
                                }
                                subsystemSelect.appendChild(option);
                            });
                        }

                        function renderComponents(subsystemId) {
                            componentSelect.innerHTML = '<option value="">All Components</option>';
                            const selectedSystem = systems.find((system) => String(system.id) === String(systemSelect.value));
                            const selectedSubsystem = selectedSystem?.subsystems.find((subsystem) => String(subsystem.id) === String(subsystemId));

                            selectedSubsystem?.components.forEach((component) => {
                                const option = document.createElement('option');
                                option.value = component.id;
                                option.textContent = component.name;
                                if (String(component.id) === String(selectedComponentId)) {
                                    option.selected = true;
                                }
                                componentSelect.appendChild(option);
                            });
                        }

                        systemSelect.addEventListener('change', function () {
                            renderSubsystems(this.value);
                        });
                        subsystemSelect.addEventListener('change', function () {
                            renderComponents(this.value);
                        });

                        renderSubsystems(systemSelect.value);
                        renderComponents(subsystemSelect.value);
                    })();
                </script>
            </div>
        </div>
    </div>
</div>
@endsection
