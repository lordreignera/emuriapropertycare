@extends('admin.layout')

@section('content')
<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body subsystems-page">
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                    <h4 class="card-title mb-0">Subsystems</h4>
                    <a href="{{ route('admin.subsystems.create') }}" class="btn btn-primary btn-sm">
                        <i class="mdi mdi-plus"></i> Add Subsystem
                    </a>
                </div>

                <form method="GET" action="{{ route('admin.subsystems.index') }}" class="row g-2 mb-4 align-items-end">
                    <div class="col-md-4 col-lg-3">
                        <label class="form-label mb-1">Filter by System</label>
                        <select name="system_id" class="form-control">
                            <option value="">All Systems</option>
                            @foreach($systems as $system)
                                <option value="{{ $system->id }}" {{ (string) ($systemId ?? '') === (string) $system->id ? 'selected' : '' }}>
                                    {{ $system->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-8 col-lg-9">
                        <button type="submit" class="btn btn-outline-primary btn-sm me-2">
                            <i class="mdi mdi-filter"></i> Filter
                        </button>
                        @if(!empty($systemId))
                            <a href="{{ route('admin.subsystems.index') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
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
                                <th>Name</th>
                                <th>System</th>
                                <th>Slug</th>
                                <th>Sort</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($subsystems as $subsystem)
                                <tr>
                                    <td>{{ $subsystems->firstItem() + $loop->index }}</td>
                                    <td><strong>{{ $subsystem->name }}</strong></td>
                                    <td>{{ $subsystem->system?->name ?? 'N/A' }}</td>
                                    <td><code>{{ $subsystem->slug }}</code></td>
                                    <td>{{ $subsystem->sort_order }}</td>
                                    <td>
                                        @if($subsystem->is_active)
                                            <span class="badge badge-success">Active</span>
                                        @else
                                            <span class="badge badge-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.subsystems.edit', $subsystem) }}" class="btn btn-sm btn-warning">
                                            <i class="mdi mdi-pencil"></i> Edit
                                        </a>
                                        <form action="{{ route('admin.subsystems.destroy', $subsystem) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this subsystem?');">
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
                                    <td colspan="7" class="text-center py-4 text-muted">No subsystems found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($subsystems->hasPages())
                    <div class="mt-4 d-flex justify-content-center">
                        {{ $subsystems->onEachSide(1)->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .subsystems-page {
        padding: 1.5rem 1.5rem 1.25rem !important;
    }

    .subsystems-page .table thead th {
        white-space: nowrap;
        padding-top: 0.85rem !important;
        padding-bottom: 0.85rem !important;
    }

    .subsystems-page .table tbody td {
        vertical-align: middle;
        padding-top: 0.8rem !important;
        padding-bottom: 0.8rem !important;
    }

    .subsystems-page .btn {
        border-radius: 0.375rem;
    }

    .subsystems-page .pagination {
        margin-bottom: 0;
    }
</style>
@endpush
