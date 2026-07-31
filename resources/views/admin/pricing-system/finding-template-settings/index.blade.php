@extends('admin.layout')

@section('content')
<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="card-title mb-0">Findings Template Settings</h4>
                    <div class="d-flex gap-2">
                        <form action="{{ route('admin.finding-template-settings.reload-defaults') }}" method="POST" onsubmit="return confirm('Reload default finding templates?');">
                            @csrf
                            <button type="submit" class="btn btn-outline-primary btn-sm">
                                <i class="mdi mdi-refresh"></i> Reload Defaults
                            </button>
                        </form>
                        <a href="{{ route('admin.finding-template-settings.create') }}" class="btn btn-primary btn-sm">
                            <i class="mdi mdi-plus"></i> Add Finding Template
                        </a>
                    </div>
                </div>

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <form method="GET" action="{{ route('admin.finding-template-settings.index') }}" class="row g-2 mb-4 align-items-end" id="filterForm">
                    <div class="col-12 col-md-3">
                        <label class="form-label mb-1 small">Search</label>
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Search issue / finding..." value="{{ $search ?? '' }}">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label mb-1 small">System</label>
                        <select name="building_system_id" class="form-control form-control-sm" id="filterSystemSelect">
                            <option value="">All Systems</option>
                            @foreach($systems as $sys)
                                <option value="{{ $sys->id }}" {{ (string)($systemId ?? '') === (string)$sys->id ? 'selected' : '' }}>{{ $sys->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label mb-1 small">Subsystem</label>
                        <select name="building_subsystem_id" class="form-control form-control-sm" id="filterSubsystemSelect">
                            <option value="">All Subsystems</option>
                            @foreach($subsystems as $sub)
                                <option value="{{ $sub->id }}" {{ (string)($subsystemId ?? '') === (string)$sub->id ? 'selected' : '' }}>{{ $sub->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label mb-1 small">Component</label>
                        <select name="building_component_id" class="form-control form-control-sm" id="filterComponentSelect">
                            <option value="">All Components</option>
                            @foreach($components as $component)
                                <option value="{{ $component->id }}" {{ (string)($componentId ?? '') === (string)$component->id ? 'selected' : '' }}>{{ $component->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label mb-1 small">Category</label>
                        <select name="category" class="form-control form-control-sm">
                            <option value="">All Categories</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat }}" {{ ($category ?? '') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-1">
                        <label class="form-label mb-1 small">Status</label>
                        <select name="status" class="form-control form-control-sm">
                            <option value="">All</option>
                            <option value="active" {{ ($status ?? '') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ ($status ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-1 d-flex gap-1">
                        <button type="submit" class="btn btn-outline-primary btn-sm w-100">
                            <i class="mdi mdi-filter"></i> Filter
                        </button>
                        @if(request()->hasAny(['search','building_system_id','building_subsystem_id','building_component_id','category','status']))
                            <a href="{{ route('admin.finding-template-settings.index') }}" class="btn btn-outline-secondary btn-sm w-100" title="Clear filters">
                                <i class="mdi mdi-close"></i>
                            </a>
                        @endif
                    </div>
                </form>

                <div class="text-muted small mb-2">
                    Showing {{ $findings->firstItem() ?? 0 }}-{{ $findings->lastItem() ?? 0 }} of {{ $findings->total() }} results
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>System</th>
                                <th>Subsystem</th>
                                <th>Component</th>
                                <th>Issue / Finding</th>
                                <th>Category</th>
                                <th>Recommendations</th>
                                <th>Included</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($findings as $finding)
                                <tr>
                                    <td>{{ $findings->firstItem() + $loop->index }}</td>
                                    <td>{{ $finding->system?->name ?: '-' }}</td>
                                    <td>{{ $finding->subsystem?->name ?: '-' }}</td>
                                    <td>{{ $finding->component?->name ?: '-' }}</td>
                                    <td><strong>{{ $finding->task_question }}</strong></td>
                                    <td>{{ $finding->category ?: '-' }}</td>
                                    <td>
                                        @php $recs = $finding->default_recommendations ?? []; @endphp
                                        @if(count($recs) > 0)
                                            <span class="badge badge-info" title="{{ implode('\n', $recs) }}" style="cursor:help;">
                                                {{ count($recs) }} recommendation{{ count($recs) !== 1 ? 's' : '' }}
                                            </span>
                                            <div class="mt-1">
                                                @foreach($recs as $rec)
                                                    <div class="small text-muted" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:220px;" title="{{ $rec }}">- {{ $rec }}</div>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($finding->default_included)
                                            <span class="badge badge-success">Yes</span>
                                        @else
                                            <span class="badge badge-secondary">No</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($finding->is_active)
                                            <span class="badge badge-success">Active</span>
                                        @else
                                            <span class="badge badge-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.finding-template-settings.edit', $finding) }}" class="btn btn-sm btn-warning">
                                            <i class="mdi mdi-pencil"></i> Edit
                                        </a>
                                        <form action="{{ route('admin.finding-template-settings.destroy', $finding) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this finding template?');">
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
                                    <td colspan="10" class="text-center py-4 text-muted">No finding templates found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($findings->hasPages())
                    <div class="mt-4 d-flex justify-content-center">
                        {{ $findings->onEachSide(1)->links('pagination::bootstrap-5') }}
                    </div>
                @endif

            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const systemSelect = document.getElementById('filterSystemSelect');
    if (systemSelect) {
        systemSelect.addEventListener('change', function () {
            const form = document.getElementById('filterForm');
            const subSelect = document.getElementById('filterSubsystemSelect');
            const componentSelect = document.getElementById('filterComponentSelect');

            if (subSelect) subSelect.value = '';
            if (componentSelect) componentSelect.value = '';

            form.submit();
        });
    }

    const subsystemSelect = document.getElementById('filterSubsystemSelect');
    if (subsystemSelect) {
        subsystemSelect.addEventListener('change', function () {
            const componentSelect = document.getElementById('filterComponentSelect');

            if (componentSelect) componentSelect.value = '';

            document.getElementById('filterForm').submit();
        });
    }
})();
</script>
@endpush
