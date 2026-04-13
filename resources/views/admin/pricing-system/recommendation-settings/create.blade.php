@extends('admin.layout')

@section('content')
<div class="row">
    <div class="col-lg-8 mx-auto grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Create Recommendation</h4>

                @php
                    $systemsJson = ($systems ?? collect())->map(function ($system) {
                        return [
                            'id' => $system->id,
                            'name' => $system->name,
                            'subsystems' => $system->subsystems->map(function ($subsystem) {
                                return [
                                    'id' => $subsystem->id,
                                    'name' => $subsystem->name,
                                ];
                            })->values()->all(),
                        ];
                    })->values()->all();
                @endphp

                <form action="{{ route('admin.recommendation-settings.store') }}" method="POST" class="forms-sample">
                    @csrf

                    <div class="form-group">
                        <label for="recommendation">Recommendation <span class="text-danger">*</span></label>
                        <textarea class="form-control @error('recommendation') is-invalid @enderror" id="recommendation" name="recommendation" rows="4" maxlength="500" required>{{ old('recommendation') }}</textarea>
                        @error('recommendation')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="system_id">System Scope</label>
                            <select class="form-control @error('system_id') is-invalid @enderror" id="system_id" name="system_id">
                                <option value="">All Systems</option>
                                @foreach(($systems ?? collect()) as $system)
                                    <option value="{{ $system->id }}" {{ (string) old('system_id') === (string) $system->id ? 'selected' : '' }}>{{ $system->name }}</option>
                                @endforeach
                            </select>
                            @error('system_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="subsystem_id">Subsystem Scope</label>
                            <select class="form-control @error('subsystem_id') is-invalid @enderror" id="subsystem_id" name="subsystem_id">
                                <option value="">All Subsystems</option>
                            </select>
                            @error('subsystem_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="sort_order">Sort Order</label>
                        <input type="number" min="0" class="form-control @error('sort_order') is-invalid @enderror" id="sort_order" name="sort_order" value="{{ old('sort_order', 0) }}">
                        @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-check">
                        <label class="form-check-label">
                            <input type="checkbox" class="form-check-input" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                            Active
                        </label>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary me-2"><i class="mdi mdi-content-save"></i> Create Recommendation</button>
                        <a href="{{ route('admin.recommendation-settings.index') }}" class="btn btn-light">Cancel</a>
                    </div>
                </form>

                <script>
                    (function () {
                        const systems = @json($systemsJson);
                        const systemSelect = document.getElementById('system_id');
                        const subsystemSelect = document.getElementById('subsystem_id');
                        const selectedSubsystemId = "{{ old('subsystem_id') }}";

                        function renderSubsystems(systemId) {
                            subsystemSelect.innerHTML = '<option value="">All Subsystems</option>';
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

                        systemSelect.addEventListener('change', function () {
                            renderSubsystems(this.value);
                        });

                        renderSubsystems(systemSelect.value);
                    })();
                </script>
            </div>
        </div>
    </div>
</div>
@endsection
