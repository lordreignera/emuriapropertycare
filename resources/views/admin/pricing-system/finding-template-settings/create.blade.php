@extends('admin.layout')

@section('content')
<div class="row">
    <div class="col-lg-8 mx-auto grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Create Finding Template</h4>

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

                <form action="{{ route('admin.finding-template-settings.store') }}" method="POST" class="forms-sample">
                    @csrf

                    <div class="form-group">
                        <label for="task_question">Issue / Finding <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('task_question') is-invalid @enderror" id="task_question" name="task_question" value="{{ old('task_question') }}" required>
                        @error('task_question')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="system_id">System</label>
                            <select class="form-control @error('system_id') is-invalid @enderror" id="system_id" name="system_id">
                                <option value="">-- Select System --</option>
                                @foreach(($systems ?? collect()) as $system)
                                    <option value="{{ $system->id }}" {{ (string) old('system_id') === (string) $system->id ? 'selected' : '' }}>{{ $system->name }}</option>
                                @endforeach
                            </select>
                            @error('system_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="subsystem_id">Subsystem</label>
                            <select class="form-control @error('subsystem_id') is-invalid @enderror" id="subsystem_id" name="subsystem_id">
                                <option value="">-- Select Subsystem --</option>
                            </select>
                            @error('subsystem_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="category">Category</label>
                            <input type="text" class="form-control @error('category') is-invalid @enderror" id="category" name="category" value="{{ old('category') }}">
                            @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="sort_order">Sort Order</label>
                            <input type="number" min="0" class="form-control @error('sort_order') is-invalid @enderror" id="sort_order" name="sort_order" value="{{ old('sort_order', 0) }}">
                            @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="default_notes">Default Notes</label>
                        <textarea class="form-control @error('default_notes') is-invalid @enderror" id="default_notes" name="default_notes" rows="3">{{ old('default_notes') }}</textarea>
                        @error('default_notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    {{-- Recommendations tag editor --}}
                    <div class="form-group mb-3">
                        <label class="fw-semibold">Recommendations</label>
                        <div id="rec-tags" class="border rounded p-2 mb-2" style="min-height:44px;display:flex;flex-wrap:wrap;gap:6px;align-items:flex-start;">
                            @foreach(old('default_recommendations', []) as $rec)
                                <span class="badge bg-primary d-flex align-items-center gap-1 rec-tag" style="font-size:.82rem;font-weight:500;padding:.35em .65em;">
                                    {{ $rec }}
                                    <input type="hidden" name="default_recommendations[]" value="{{ $rec }}">
                                    <button type="button" class="btn-close btn-close-white remove-rec" style="font-size:.65rem;" aria-label="Remove"></button>
                                </span>
                            @endforeach
                        </div>
                        <div class="input-group input-group-sm">
                            <input type="text" id="rec-input" class="form-control" placeholder="Type a recommendation and press Enter or click Add…" maxlength="500">
                            <button type="button" id="rec-add-btn" class="btn btn-outline-primary"><i class="mdi mdi-plus"></i> Add</button>
                        </div>
                        <small class="text-muted">Add one recommendation per line. These will be available in the inspection form recommendation builder.</small>
                    </div>
                        <label class="form-check-label me-3">
                            <input type="checkbox" class="form-check-input" name="default_included" value="1" {{ old('default_included', true) ? 'checked' : '' }}>
                            Included by default
                        </label>
                        <label class="form-check-label">
                            <input type="checkbox" class="form-check-input" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                            Active
                        </label>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary me-2"><i class="mdi mdi-content-save"></i> Create Template</button>
                        <a href="{{ route('admin.finding-template-settings.index') }}" class="btn btn-light">Cancel</a>
                    </div>
                </form>

                <script>
                    (function () {
                        const systems = @json($systemsJson);
                        const systemSelect = document.getElementById('system_id');
                        const subsystemSelect = document.getElementById('subsystem_id');
                        const selectedSubsystemId = "{{ old('subsystem_id') }}";

                        function renderSubsystems(systemId) {
                            subsystemSelect.innerHTML = '<option value="">-- Select Subsystem --</option>';
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

                    // Recommendation tag editor
                    (function () {
                        const tagsContainer = document.getElementById('rec-tags');
                        const input = document.getElementById('rec-input');
                        const addBtn = document.getElementById('rec-add-btn');

                        function addTag(text) {
                            text = text.trim();
                            if (!text) return;
                            const existing = Array.from(tagsContainer.querySelectorAll('input[name="default_recommendations[]"]')).map(i => i.value);
                            if (existing.includes(text)) { input.value = ''; return; }
                            const span = document.createElement('span');
                            span.className = 'badge bg-primary d-flex align-items-center gap-1 rec-tag';
                            span.style.cssText = 'font-size:.82rem;font-weight:500;padding:.35em .65em;';
                            span.innerHTML = `${text.replace(/</g,'&lt;')}<input type="hidden" name="default_recommendations[]" value="${text.replace(/"/g,'&quot;')}"><button type="button" class="btn-close btn-close-white remove-rec" style="font-size:.65rem;" aria-label="Remove"></button>`;
                            span.querySelector('.remove-rec').addEventListener('click', function() { span.remove(); });
                            tagsContainer.appendChild(span);
                            input.value = '';
                        }

                        tagsContainer.addEventListener('click', function(e) {
                            if (e.target.classList.contains('remove-rec')) e.target.closest('.rec-tag').remove();
                        });
                        addBtn.addEventListener('click', () => addTag(input.value));
                        input.addEventListener('keydown', function(e) { if (e.key === 'Enter') { e.preventDefault(); addTag(this.value); } });
                    })();
                </script>
            </div>
        </div>
    </div>
</div>
@endsection
