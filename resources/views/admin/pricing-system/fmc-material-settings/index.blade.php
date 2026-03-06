@extends('admin.layout')

@section('content')
<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="card-title mb-0">FMC Material Settings</h4>
                    <div class="d-flex gap-2">
                        <form action="{{ route('admin.fmc-material-settings.reload-defaults') }}" method="POST" onsubmit="return confirm('Reload default FMC material settings?');">
                            @csrf
                            <button type="submit" class="btn btn-outline-primary btn-sm">
                                <i class="mdi mdi-refresh"></i> Reload Defaults
                            </button>
                        </form>
                        <a href="{{ route('admin.fmc-material-settings.create') }}" class="btn btn-primary btn-sm">
                            <i class="mdi mdi-plus"></i> Add Material
                        </a>
                    </div>
                </div>

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Material / Part</th>
                                <th>Default Unit</th>
                                <th>Unit Cost ($)</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($materials as $material)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td><strong>{{ $material->material_name }}</strong></td>
                                    <td>{{ $material->default_unit }}</td>
                                    <td>${{ number_format((float) $material->default_unit_cost, 2) }}</td>
                                    <td>
                                        @if($material->is_active)
                                            <span class="badge badge-success">Active</span>
                                        @else
                                            <span class="badge badge-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.fmc-material-settings.edit', $material) }}" class="btn btn-sm btn-warning">
                                            <i class="mdi mdi-pencil"></i> Edit
                                        </a>
                                        <form action="{{ route('admin.fmc-material-settings.destroy', $material) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this FMC material setting?');">
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
                                    <td colspan="6" class="text-center py-4">
                                        <p class="text-muted">No FMC material settings found.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
