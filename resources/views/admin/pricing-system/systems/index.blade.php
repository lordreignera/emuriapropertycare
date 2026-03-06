@extends('admin.layout')

@section('content')
<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body systems-page">
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                    <h4 class="card-title mb-0">Systems</h4>
                    <a href="{{ route('admin.systems.create') }}" class="btn btn-primary btn-sm">
                        <i class="mdi mdi-plus"></i> Add System
                    </a>
                </div>

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
                                <th>Slug</th>
                                <th>Sort</th>
                                <th>Status</th>
                                <th>Subsystems</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($systems as $system)
                                <tr>
                                    <td>{{ $systems->firstItem() + $loop->index }}</td>
                                    <td><strong>{{ $system->name }}</strong></td>
                                    <td><code>{{ $system->slug }}</code></td>
                                    <td>{{ $system->sort_order }}</td>
                                    <td>
                                        @if($system->is_active)
                                            <span class="badge badge-success">Active</span>
                                        @else
                                            <span class="badge badge-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td>{{ $system->subsystems()->count() }}</td>
                                    <td>
                                        <a href="{{ route('admin.subsystems.index', ['system_id' => $system->id]) }}" class="btn btn-sm btn-info">
                                            <i class="mdi mdi-format-list-bulleted"></i> Manage Subsystems
                                        </a>
                                        <a href="{{ route('admin.systems.edit', $system) }}" class="btn btn-sm btn-warning">
                                            <i class="mdi mdi-pencil"></i> Edit
                                        </a>
                                        <form action="{{ route('admin.systems.destroy', $system) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this system and all its subsystems?');">
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
                                    <td colspan="7" class="text-center py-4 text-muted">No systems found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($systems->hasPages())
                    <div class="mt-4 d-flex justify-content-center">
                        {{ $systems->onEachSide(1)->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .systems-page {
        padding: 1.5rem 1.5rem 1.25rem !important;
    }

    .systems-page .table thead th {
        white-space: nowrap;
        padding-top: 0.85rem !important;
        padding-bottom: 0.85rem !important;
    }

    .systems-page .table tbody td {
        vertical-align: middle;
        padding-top: 0.8rem !important;
        padding-bottom: 0.8rem !important;
    }

    .systems-page .btn {
        border-radius: 0.375rem;
    }

    .systems-page .pagination {
        margin-bottom: 0;
    }
</style>
@endpush
