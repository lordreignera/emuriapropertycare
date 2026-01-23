@extends('admin.layout')

@section('content')
<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="card-title mb-0">Pricing Packages</h4>
                    <a href="{{ route('admin.pricing-packages.create') }}" class="btn btn-primary btn-sm">
                        <i class="mdi mdi-plus"></i> Add New Package
                    </a>
                </div>

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead>
                            <tr>
                                <th>Package Name</th>
                                @foreach($propertyTypes as $type)
                                    <th class="text-end">{{ $type->type_name }}</th>
                                @endforeach
                                <th>Status</th>
                                <th>Sort Order</th>
                                <th width="120">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($packages as $package)
                                <tr>
                                    <td>
                                        <strong>{{ $package->package_name }}</strong>
                                        @if($package->description)
                                            <br><small class="text-muted">{{ Str::limit($package->description, 60) }}</small>
                                        @endif
                                    </td>
                                    @foreach($propertyTypes as $type)
                                        <td class="text-end">
                                            @php
                                                $pricing = $package->packagePricing->firstWhere('property_type_id', $type->id);
                                            @endphp
                                            @if($pricing)
                                                <span class="badge badge-success">${{ number_format($pricing->base_monthly_price, 2) }}</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                    @endforeach
                                    <td>
                                        @if($package->is_active)
                                            <span class="badge badge-success">Active</span>
                                        @else
                                            <span class="badge badge-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ $package->sort_order }}</td>
                                    <td>
                                        <a href="{{ route('admin.pricing-packages.edit', $package) }}" class="btn btn-sm btn-warning">
                                            <i class="mdi mdi-pencil"></i>
                                        </a>
                                        <form action="{{ route('admin.pricing-packages.destroy', $package) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this package and all its pricing?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="mdi mdi-delete"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ 4 + $propertyTypes->count() }}" class="text-center py-4">
                                        <p class="text-muted">No pricing packages found.</p>
                                        <a href="{{ route('admin.pricing-packages.create') }}" class="btn btn-primary btn-sm">
                                            <i class="mdi mdi-plus"></i> Create First Package
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
