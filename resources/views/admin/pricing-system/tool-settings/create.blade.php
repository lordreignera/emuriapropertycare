@extends('admin.layout')

@section('content')
<div class="row">
    <div class="col-lg-8 mx-auto grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Create Tool Setting</h4>

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

                    $findingsJson = ($findingTemplates ?? collect())->map(function ($finding) {
                        return [
                            'id' => $finding->id,
                            'task_question' => $finding->task_question,
                            'system_id' => $finding->system_id,
                            'subsystem_id' => $finding->subsystem_id,
                        ];
                    })->values()->all();
                @endphp

                <form action="{{ route('admin.tool-settings.store') }}" method="POST" class="forms-sample">
                    @csrf

                    <div class="form-group">
                        <label for="tool_name">Tool Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('tool_name') is-invalid @enderror" id="tool_name" name="tool_name" value="{{ old('tool_name') }}" required>
                        @error('tool_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="system_id">System</label>
                            <select class="form-control @error('system_id') is-invalid @enderror" id="system_id" name="system_id">
                                <option value="">All Systems</option>
                                @foreach(($systems ?? collect()) as $system)
                                    <option value="{{ $system->id }}" {{ (string) old('system_id') === (string) $system->id ? 'selected' : '' }}>{{ $system->name }}</option>
                                @endforeach
                            </select>
                            @error('system_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="subsystem_id">Subsystem</label>
                            <select class="form-control @error('subsystem_id') is-invalid @enderror" id="subsystem_id" name="subsystem_id">
                                <option value="">All Subsystems</option>
                            </select>
                            @error('subsystem_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="finding_template_setting_id">Finding Resolved</label>
                        <select class="form-control @error('finding_template_setting_id') is-invalid @enderror" id="finding_template_setting_id" name="finding_template_setting_id">
                            <option value="">-- Select Finding --</option>
                        </select>
                        @error('finding_template_setting_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="ownership_status">Ownership Status <span class="text-danger">*</span></label>
                            <select class="form-control @error('ownership_status') is-invalid @enderror" id="ownership_status" name="ownership_status" required>
                                <option value="owned" {{ old('ownership_status', 'owned') === 'owned' ? 'selected' : '' }}>Owned</option>
                                <option value="hired" {{ old('ownership_status') === 'hired' ? 'selected' : '' }}>Hired</option>
                            </select>
                            @error('ownership_status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="availability_status">Availability Status <span class="text-danger">*</span></label>
                            <select class="form-control @error('availability_status') is-invalid @enderror" id="availability_status" name="availability_status" required>
                                <option value="available" {{ old('availability_status', 'available') === 'available' ? 'selected' : '' }}>Available</option>
                                <option value="non_available" {{ old('availability_status') === 'non_available' ? 'selected' : '' }}>Non Available</option>
                            </select>
                            @error('availability_status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="sort_order">Sort Order</label>
                        <input type="number" min="0" class="form-control @error('sort_order') is-invalid @enderror" id="sort_order" name="sort_order" value="{{ old('sort_order', 0) }}">
                        @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label for="notes">Notes</label>
                        <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="3">{{ old('notes') }}</textarea>
                        @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-check">
                        <label class="form-check-label">
                            <input type="checkbox" class="form-check-input" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                            Active
                        </label>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary me-2"><i class="mdi mdi-content-save"></i> Create Tool</button>
                        <a href="{{ route('admin.tool-settings.index') }}" class="btn btn-light">Cancel</a>
                    </div>
                </form>

                <script>
                    (function () {
                        const systems = @json($systemsJson);
                        const findings = @json($findingsJson);
                        const systemSelect = document.getElementById('system_id');
                        const subsystemSelect = document.getElementById('subsystem_id');
                        const findingSelect = document.getElementById('finding_template_setting_id');

                        const selectedSubsystemId = "{{ old('subsystem_id') }}";
                        const selectedFindingId = "{{ old('finding_template_setting_id') }}";

                        function renderSubsystems(systemId) {
                            subsystemSelect.innerHTML = '<option value="">All Subsystems</option>';
                            const selectedSystem = systems.find((system) => String(system.id) === String(systemId));
                            if (!selectedSystem) return;

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

                        function renderFindings() {
                            const systemId = String(systemSelect.value || '');
                            const subsystemId = String(subsystemSelect.value || '');

                            findingSelect.innerHTML = '<option value="">-- Select Finding --</option>';

                            findings.forEach((finding) => {
                                const matchesSystem = !systemId || String(finding.system_id || '') === systemId;
                                const matchesSubsystem = !subsystemId || String(finding.subsystem_id || '') === subsystemId;

                                if (!matchesSystem || !matchesSubsystem) return;

                                const option = document.createElement('option');
                                option.value = finding.id;
                                option.textContent = finding.task_question;
                                if (String(finding.id) === String(selectedFindingId)) {
                                    option.selected = true;
                                }
                                findingSelect.appendChild(option);
                            });
                        }

                        systemSelect.addEventListener('change', function () {
                            renderSubsystems(this.value);
                            renderFindings();
                        });

                        subsystemSelect.addEventListener('change', renderFindings);

                        renderSubsystems(systemSelect.value);
                        renderFindings();
                    })();
                </script>
            </div>
        </div>
    </div>
</div>
@endsection
