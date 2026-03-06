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

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Task / Question</th>
                                <th>Category</th>
                                <th>Priority</th>
                                <th>Included</th>
                                <th>Labour Hours</th>
                                <th>Photo Ref</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($findings as $finding)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td><strong>{{ $finding->task_question }}</strong></td>
                                    <td>{{ $finding->category ?: '—' }}</td>
                                    <td>{{ $finding->default_priority }}</td>
                                    <td>
                                        @if($finding->default_included)
                                            <span class="badge badge-success">Yes</span>
                                        @else
                                            <span class="badge badge-secondary">No</span>
                                        @endif
                                    </td>
                                    <td>{{ number_format((float) $finding->default_labour_hours, 2) }}</td>
                                    <td>{{ $finding->photo_reference ?: '—' }}</td>
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
                                    <td colspan="8" class="text-center py-4">
                                        <p class="text-muted">No finding templates found.</p>
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
