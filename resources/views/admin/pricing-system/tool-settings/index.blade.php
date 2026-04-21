@extends('admin.layout')

@section('content')
<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="card-title mb-0">Tool Settings</h4>
                    <a href="{{ route('admin.tool-settings.create') }}" class="btn btn-primary btn-sm">
                        <i class="mdi mdi-plus"></i> Add Tool
                    </a>
                </div>

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <form method="GET" action="{{ route('admin.tool-settings.index') }}" class="row g-2 mb-4 align-items-end" id="filterForm">
                    <div class="col-12 col-md-2">
                        <label class="form-label mb-1 small">Search</label>
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Tool name..." value="{{ $search ?? '' }}">
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
                        <label class="form-label mb-1 small">Ownership</label>
                        <select name="ownership_status" class="form-control form-control-sm">
                            <option value="">All</option>
                            <option value="owned" {{ ($ownership ?? '') === 'owned' ? 'selected' : '' }}>Owned</option>
                            <option value="hired" {{ ($ownership ?? '') === 'hired' ? 'selected' : '' }}>Hired</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label mb-1 small">Availability</label>
                        <select name="availability_status" class="form-control form-control-sm">
                            <option value="">All</option>
                            <option value="available" {{ ($availability ?? '') === 'available' ? 'selected' : '' }}>Available</option>
                            <option value="non_available" {{ ($availability ?? '') === 'non_available' ? 'selected' : '' }}>Non Available</option>
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
                        <button type="submit" class="btn btn-outline-primary btn-sm"><i class="mdi mdi-filter"></i></button>
                        @if(request()->hasAny(['search','system_id','subsystem_id','ownership_status','availability_status','status']))
                            <a href="{{ route('admin.tool-settings.index') }}" class="btn btn-outline-secondary btn-sm" title="Clear"><i class="mdi mdi-close"></i></a>
                        @endif
                    </div>
                </form>

                <div class="text-muted small mb-2">
                    Showing {{ $tools->firstItem() ?? 0 }}-{{ $tools->lastItem() ?? 0 }} of {{ $tools->total() }} results
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Tool</th>
                                <th class="text-center">Total Stock</th>
                                <th class="text-center">Stock Status<br><span class="fw-normal text-muted" style="font-size:0.72rem;">Deployed / Remaining</span></th>
                                <th>System</th>
                                <th>Subsystem</th>
                                <th>Finding Resolved</th>
                                <th>Ownership</th>
                                <th>Availability</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tools as $tool)
                                <tr>
                                    <td>{{ $tools->firstItem() + $loop->index }}</td>
                                    <td><strong>{{ $tool->tool_name }}</strong></td>
                                    <td class="text-center">
                                        <span class="fw-bold" style="font-size:1.05rem;">{{ $tool->quantity }}</span>
                                    </td>
                                    <td class="text-center">
                                        @php
                                            $dep = $tool->deployedQuantity();
                                            $rem = $tool->remainingQuantity();
                                        @endphp
                                        <span class="badge bg-warning text-dark me-1" title="Currently deployed to projects">{{ $dep }} out</span>
                                        <span class="badge {{ $rem <= 0 ? 'bg-danger' : ($rem < 3 ? 'bg-warning text-dark' : 'bg-success') }}" title="Available in store">{{ $rem }} in store</span>
                                    </td>
                                    <td>{{ $tool->system?->name ?: '--' }}</td>
                                    <td>{{ $tool->subsystem?->name ?: '--' }}</td>
                                    <td>
                                        <span class="small">{{ $tool->findingTemplateSetting?->task_question ?: '--' }}</span>
                                    </td>
                                    <td>
                                        <span class="badge {{ $tool->ownership_status === 'owned' ? 'bg-primary' : 'bg-warning text-dark' }}">
                                            {{ ucfirst($tool->ownership_status) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge {{ $tool->availability_status === 'available' ? 'bg-success' : 'bg-danger' }}">
                                            {{ $tool->availability_status === 'available' ? 'Available' : 'Non Available' }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($tool->is_active)
                                            <span class="badge badge-success">Active</span>
                                        @else
                                            <span class="badge badge-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.tool-settings.logs', $tool) }}"
                                           class="btn btn-sm btn-outline-info" title="View Deployment Logs">
                                            <i class="mdi mdi-history"></i> Logs
                                        </a>
                                        <a href="{{ route('admin.tool-settings.edit', $tool) }}" class="btn btn-sm btn-warning">
                                            <i class="mdi mdi-pencil"></i> Edit
                                        </a>
                                        <form action="{{ route('admin.tool-settings.destroy', $tool) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this tool setting?');">
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
                                    <td colspan="11" class="text-center py-4 text-muted">No tool settings found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($tools->hasPages())
                    <div class="mt-4 d-flex justify-content-center">
                        {{ $tools->onEachSide(1)->links('pagination::bootstrap-5') }}
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
