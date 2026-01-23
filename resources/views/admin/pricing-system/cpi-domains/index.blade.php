@extends('admin.layout')

@section('content')
<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="card-title mb-0">CPI Domains</h4>
                    <a href="{{ route('admin.cpi-domains.create') }}" class="btn btn-primary btn-sm">
                        <i class="mdi mdi-plus"></i> Add New Domain
                    </a>
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
                                <th>Name</th>
                                <th>Slug</th>
                                <th>Max Points</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($domains as $domain)
                                <tr>
                                    <td>
                                        <strong>{{ $domain->name }}</strong>
                                        @if($domain->description)
                                            <br><small class="text-muted">{{ Str::limit($domain->description, 60) }}</small>
                                        @endif
                                    </td>
                                    <td><code>{{ $domain->slug }}</code></td>
                                    <td><span class="badge badge-primary">{{ $domain->max_points }} points</span></td>
                                    <td>
                                        @if($domain->is_active)
                                            <span class="badge badge-success">Active</span>
                                        @else
                                            <span class="badge badge-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.cpi-domains.edit', $domain) }}" class="btn btn-sm btn-warning">
                                            <i class="mdi mdi-pencil"></i> Edit
                                        </a>
                                        <form action="{{ route('admin.cpi-domains.destroy', $domain) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?');">
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
                                    <td colspan="5" class="text-center py-4">
                                        <p class="text-muted">No CPI domains found.</p>
                                        <a href="{{ route('admin.cpi-domains.create') }}" class="btn btn-primary btn-sm">
                                            <i class="mdi mdi-plus"></i> Create First Domain
                                        </a>
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
