@extends('admin.layout')

@section('content')
<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="card-title mb-0">CPI Parameters</h4>
                    <div class="d-flex gap-2">
                        <form action="{{ route('admin.parameters.reload-defaults') }}" method="POST" onsubmit="return confirm('Reload default parameters? This will overwrite default keys to their original values.');">
                            @csrf
                            <button type="submit" class="btn btn-outline-primary btn-sm">
                                <i class="mdi mdi-refresh"></i> Reload Defaults
                            </button>
                        </form>
                        <a href="{{ route('admin.parameters.create') }}" class="btn btn-primary btn-sm">
                            <i class="mdi mdi-plus"></i> Add Parameter
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
                                <th>Key</th>
                                <th>Value</th>
                                <th>Group</th>
                                <th>Status</th>
                                <th>Description</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($parameters as $parameter)
                                <tr>
                                    <td><strong><code>{{ $parameter->parameter_key }}</code></strong></td>
                                    <td>{{ number_format((float) $parameter->parameter_value, 6, '.', '') }}</td>
                                    <td><span class="badge badge-info">{{ $parameter->group_name }}</span></td>
                                    <td>
                                        @if($parameter->is_active)
                                            <span class="badge badge-success">Active</span>
                                        @else
                                            <span class="badge badge-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td>{{ $parameter->description ?: '—' }}</td>
                                    <td>
                                        <a href="{{ route('admin.parameters.edit', $parameter) }}" class="btn btn-sm btn-warning">
                                            <i class="mdi mdi-pencil"></i> Edit
                                        </a>
                                        <form action="{{ route('admin.parameters.destroy', $parameter) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this parameter?');">
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
                                        <p class="text-muted mb-2">No parameters found.</p>
                                        <a href="{{ route('admin.parameters.create') }}" class="btn btn-primary btn-sm">
                                            <i class="mdi mdi-plus"></i> Create First Parameter
                                        </a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($parameters->hasPages())
                    <div class="mt-3">
                        {{ $parameters->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
