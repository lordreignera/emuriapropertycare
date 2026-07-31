@extends('admin.layout')

@section('content')
<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body components-page">
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                    <h4 class="card-title mb-0">Building Components</h4>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="{{ route('admin.subsystems.index', $systemId ? ['building_system_id' => $systemId] : []) }}" class="btn btn-outline-secondary btn-sm">
                            <i class="mdi mdi-arrow-left"></i> Back to Subsystems
                        </a>
                        <a href="{{ route('admin.components.create', request()->only(['building_system_id', 'building_subsystem_id'])) }}" class="btn btn-primary btn-sm">
                            <i class="mdi mdi-plus"></i> Add Component
                        </a>
                    </div>
                </div>

                <form method="GET" action="{{ route('admin.components.index') }}" class="row g-2 mb-4 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label mb-1">Building System</label>
                        <select name="building_system_id" class="form-control" onchange="this.form.submit()">
                            <option value="">All Systems</option>
                            @foreach($systems as $system)
                                <option value="{{ $system->id }}" {{ (string) ($systemId ?? '') === (string) $system->id ? 'selected' : '' }}>
                                    {{ $system->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label mb-1">Building Subsystem</label>
                        <select name="building_subsystem_id" class="form-control">
                            <option value="">All Subsystems</option>
                            @foreach($subsystems as $subsystem)
                                <option value="{{ $subsystem->id }}" {{ (string) ($subsystemId ?? '') === (string) $subsystem->id ? 'selected' : '' }}>
                                    {{ $subsystem->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-outline-primary btn-sm me-2">
                            <i class="mdi mdi-filter"></i> Filter
                        </button>
                        @if(!empty($systemId) || !empty($subsystemId))
                            <a href="{{ route('admin.components.index') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
                        @endif
                    </div>
                </form>

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Component</th>
                                <th>Code</th>
                                <th>System</th>
                                <th>Subsystem</th>
                                <th>Trade</th>
                                <th>Sort</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($components as $component)
                                <tr>
                                    <td>{{ $components->firstItem() + $loop->index }}</td>
                                    <td>
                                        <strong>{{ $component->name }}</strong>
                                        @if(!empty($component->aliases))
                                            <div class="text-muted small">Aliases: {{ implode(', ', $component->aliases) }}</div>
                                        @endif
                                    </td>
                                    <td><code>{{ $component->code }}</code></td>
                                    <td>{{ $component->subsystem?->system?->name ?? 'N/A' }}</td>
                                    <td>{{ $component->subsystem?->name ?? 'N/A' }}</td>
                                    <td>{{ $component->default_trade ?: 'N/A' }}</td>
                                    <td>{{ $component->sort_order }}</td>
                                    <td>
                                        @if($component->is_active)
                                            <span class="badge badge-success">Active</span>
                                        @else
                                            <span class="badge badge-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.components.edit', $component) }}" class="btn btn-sm btn-warning">
                                            <i class="mdi mdi-pencil"></i> Edit
                                        </a>
                                        <form action="{{ route('admin.components.destroy', $component) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this building component?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="mdi mdi-delete"></i> Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-4 text-muted">No building components found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($components->hasPages())
                    <div class="mt-4 d-flex justify-content-center">
                        {{ $components->onEachSide(1)->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
