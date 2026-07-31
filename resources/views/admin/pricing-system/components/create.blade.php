@extends('admin.layout')

@section('content')
<div class="row">
    <div class="col-lg-8 mx-auto grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <h4 class="card-title mb-0">Create Building Component</h4>
                    <a href="{{ route('admin.subsystems.index', $selectedSystemId ? ['building_system_id' => $selectedSystemId] : []) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="mdi mdi-arrow-left"></i> Back to Subsystems
                    </a>
                </div>

                <form action="{{ route('admin.components.store') }}" method="POST" class="forms-sample">
                    @csrf

                    <div class="form-group">
                        <label for="building_system_id">Building System <span class="text-danger">*</span></label>
                        <select class="form-control @error('building_system_id') is-invalid @enderror" id="building_system_id" name="building_system_id" required>
                            <option value="">Select building system</option>
                            @foreach($systems as $system)
                                <option value="{{ $system->id }}" {{ (string) old('building_system_id', $selectedSystemId) === (string) $system->id ? 'selected' : '' }}>
                                    {{ $system->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('building_system_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label for="building_subsystem_id">Building Subsystem <span class="text-danger">*</span></label>
                        <select class="form-control @error('building_subsystem_id') is-invalid @enderror" id="building_subsystem_id" name="building_subsystem_id" required>
                            <option value="">Select building subsystem</option>
                            @foreach($subsystems as $subsystem)
                                <option value="{{ $subsystem->id }}" {{ (string) old('building_subsystem_id', $selectedSubsystemId) === (string) $subsystem->id ? 'selected' : '' }}>
                                    {{ $subsystem->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('building_subsystem_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label for="code">Code</label>
                        <input type="text" class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code') }}" placeholder="Auto-generated if empty">
                        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label for="name">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label for="slug">Slug</label>
                        <input type="text" class="form-control @error('slug') is-invalid @enderror" id="slug" name="slug" value="{{ old('slug') }}" placeholder="Auto-generated if empty">
                        @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label for="default_trade">Default Trade</label>
                        <input type="text" class="form-control @error('default_trade') is-invalid @enderror" id="default_trade" name="default_trade" value="{{ old('default_trade') }}">
                        @error('default_trade')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label for="aliases_text">Aliases</label>
                        <textarea class="form-control @error('aliases_text') is-invalid @enderror" id="aliases_text" name="aliases_text" rows="3" placeholder="One alias per line, or comma separated">{{ old('aliases_text') }}</textarea>
                        @error('aliases_text')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label for="sort_order">Sort Order</label>
                        <input type="number" min="0" class="form-control @error('sort_order') is-invalid @enderror" id="sort_order" name="sort_order" value="{{ old('sort_order', 0) }}">
                        @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description') }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-check">
                        <label class="form-check-label">
                            <input type="checkbox" class="form-check-input" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                            Active
                        </label>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary me-2"><i class="mdi mdi-content-save"></i> Create Component</button>
                        <a href="{{ route('admin.components.index', array_filter([
                            'building_system_id' => $selectedSystemId,
                            'building_subsystem_id' => $selectedSubsystemId,
                        ])) }}" class="btn btn-light">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const subsystems = @json($allSubsystems);
    const systemSelect = document.getElementById('building_system_id');
    const subsystemSelect = document.getElementById('building_subsystem_id');

    systemSelect?.addEventListener('change', function () {
        const systemId = String(this.value || '');
        subsystemSelect.innerHTML = '<option value="">Select building subsystem</option>';

        subsystems
            .filter((subsystem) => String(subsystem.building_system_id) === systemId)
            .forEach((subsystem) => {
                const option = document.createElement('option');
                option.value = subsystem.id;
                option.textContent = subsystem.name;
                subsystemSelect.appendChild(option);
            });
    });
});
</script>
@endpush
