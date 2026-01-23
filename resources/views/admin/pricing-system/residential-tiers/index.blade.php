@extends('admin.layout')

@section('content')
<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="card-title mb-0">Residential Size Tiers</h4>
                    <a href="{{ route('admin.residential-tiers.create') }}" class="btn btn-primary btn-sm">
                        <i class="mdi mdi-plus"></i> Add New
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
                                <th>Tier Name</th>
                                <th>Unit Range</th>
                                <th>Size Factor</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tiers as $residentialTier)
                                <tr>
                                    <td><strong>{{ $residentialTier->tier_name }}</strong></td>
                                    <td>{{ $residentialTier->min_units }} - {{ $residentialTier->max_units ?? '51+' }} units</td>
                                    <td><span class="badge badge-info">{{ $residentialTier->size_factor }}x</span></td>
                                    <td>
                                        @if($residentialTier->is_active)
                                            <span class="badge badge-success">Active</span>
                                        @else
                                            <span class="badge badge-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.residential-tiers.edit', $residentialTier) }}" class="btn btn-sm btn-warning">
                                            <i class="mdi mdi-pencil"></i> Edit
                                        </a>
                                        <form action="{{ route('admin.residential-tiers.destroy', $residentialTier) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?');">
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
                                        <p class="text-muted">No records found.</p>
                                        <a href="{{ route('admin.residential-tiers.create') }}" class="btn btn-primary btn-sm">
                                            <i class="mdi mdi-plus"></i> Create First Record
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
