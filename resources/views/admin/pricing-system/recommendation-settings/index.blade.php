@extends('admin.layout')

@section('content')
<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="card-title mb-0">Recommendation Settings</h4>
                    <div class="d-flex gap-2">
                        <form action="{{ route('admin.recommendation-settings.reload-defaults') }}" method="POST" onsubmit="return confirm('Reload default recommendation settings?');">
                            @csrf
                            <button type="submit" class="btn btn-outline-primary btn-sm">
                                <i class="mdi mdi-refresh"></i> Reload Defaults
                            </button>
                        </form>
                        <a href="{{ route('admin.recommendation-settings.create') }}" class="btn btn-primary btn-sm">
                            <i class="mdi mdi-plus"></i> Add Recommendation
                        </a>
                    </div>
                </div>

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <form method="GET" action="{{ route('admin.recommendation-settings.index') }}" class="row g-2 mb-4 align-items-end" id="filterForm">
                    <div class="col-12 col-md-3">
                        <label class="form-label mb-1 small">Search</label>
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Search recommendation..." value="{{ $search ?? '' }}">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label mb-1 small">System</label>
                        <select name="system_id" class="form-control form-control-sm" id="filterSystemSelect">
                            <option value="">All Systems</option>
                            @foreach($systems as $sys)
                                <option value="{{ $sys->id }}" {{ (string)($systemId ?? '') === (string)$sys->id ? 'selected' : '' }}>{{ $sys->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label mb-1 small">Subsystem</label>
                        <select name="subsystem_id" class="form-control form-control-sm" id="filterSubsystemSelect">
                            <option value="">All Subsystems</option>
                            @foreach($subsystems as $sub)
                                <option value="{{ $sub->id }}" {{ (string)($subsystemId ?? '') === (string)$sub->id ? 'selected' : '' }}>{{ $sub->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label mb-1 small">Status</label>
                        <select name="status" class="form-control form-control-sm">
                            <option value="">All</option>
                            <option value="active" {{ ($status ?? '') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ ($status ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-3 d-flex gap-1">
                        <button type="submit" class="btn btn-outline-primary btn-sm">
                            <i class="mdi mdi-filter"></i> Filter
                        </button>
                        @if(request()->hasAny(['search','system_id','subsystem_id','status']))
                            <a href="{{ route('admin.recommendation-settings.index') }}" class="btn btn-outline-secondary btn-sm" title="Clear filters">
                                <i class="mdi mdi-close"></i> Clear
                            </a>
                        @endif
                    </div>
                </form>

                <div class="text-muted small mb-2">
                    Showing {{ $recommendations->firstItem() ?? 0 }}-{{ $recommendations->lastItem() ?? 0 }} of {{ $recommendations->total() }} results
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>System</th>
                                <th>Subsystem</th>
                                <th>Recommendation</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recommendations as $item)
                                <tr>
                                    <td>{{ $recommendations->firstItem() + $loop->index }}</td>
                                    <td>{{ $item->system?->name ?: 'All Systems' }}</td>
                                    <td>{{ $item->subsystem?->name ?: 'All Subsystems' }}</td>
                                    <td>{{ $item->recommendation }}</td>
                                    <td>
                                        @if($item->is_active)
                                            <span class="badge badge-success">Active</span>
                                        @else
                                            <span class="badge badge-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.recommendation-settings.edit', $item) }}" class="btn btn-sm btn-warning">
                                            <i class="mdi mdi-pencil"></i> Edit
                                        </a>
                                        <form action="{{ route('admin.recommendation-settings.destroy', $item) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this recommendation?');">
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
                                    <td colspan="6" class="text-center py-4 text-muted">No recommendations found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($recommendations->hasPages())
                    <div class="mt-4 d-flex justify-content-center">
                        {{ $recommendations->onEachSide(1)->links('pagination::bootstrap-5') }}
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
            const subSelect = document.getElementById('filterSubsystemSelect');
            if (subSelect) subSelect.value = '';
            document.getElementById('filterForm').submit();
        });
    }
})();
</script>
@endpush
