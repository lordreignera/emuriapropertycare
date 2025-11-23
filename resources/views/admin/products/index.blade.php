@extends('admin.layout')

@section('title', 'Product Management')

@section('content')
<div class="content-wrapper">
    <div class="row">
        <div class="col-lg-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h4 class="card-title mb-0">Product Management</h4>
                            <p class="text-muted small mb-0">Manage base products and templates for custom client offers</p>
                        </div>
                        <a href="{{ route('admin.products.create') }}" class="btn btn-primary btn-sm">
                            <i class="mdi mdi-plus"></i> Create New Product
                        </a>
                    </div>

                    @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    @endif

                    @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    @endif

                    <!-- Filter Tabs -->
                    <ul class="nav nav-pills mb-3" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="pill" href="#all-products">
                                All Products <span class="badge bg-secondary ms-1">{{ $products->total() }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="pill" href="#active-products">
                                Active <span class="badge bg-success ms-1">{{ $products->where('is_active', true)->count() }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="pill" href="#customizable-products">
                                Customizable <span class="badge bg-info ms-1">{{ $products->where('is_customizable', true)->count() }}</span>
                            </a>
                        </li>
                    </ul>

                    <div class="table-responsive">
                        <table id="productsTable" class="table table-hover table-striped">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Product Name</th>
                                    <th>Category</th>
                                    <th>Pricing Type</th>
                                    <th>Base Price</th>
                                    <th>Components</th>
                                    <th>Status</th>
                                    <th>Customizable</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($products as $product)
                                <tr>
                                    <td><code>{{ $product->product_code }}</code></td>
                                    <td>
                                        <strong>{{ $product->product_name }}</strong>
                                        @if($product->description)
                                        <br><small class="text-muted">{{ Str::limit($product->description, 60) }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ 
                                            $product->category === 'subscription_package' ? 'primary' :
                                            ($product->category === 'emergency' ? 'danger' :
                                            ($product->category === 'preventive' ? 'success' :
                                            ($product->category === 'custom' ? 'warning' : 'secondary')))
                                        }}">
                                            {{ ucfirst(str_replace('_', ' ', $product->category)) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-outline-info">
                                            {{ ucfirst(str_replace('_', ' ', $product->pricing_type)) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($product->pricing_type === 'component_based')
                                            <span class="text-muted">${{ number_format($product->calculateTotalPrice(), 2) }}</span>
                                            <small class="d-block text-muted">(calculated)</small>
                                        @else
                                            <strong>${{ number_format($product->base_price, 2) }}</strong>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-pill badge-info">
                                            {{ $product->components->count() }} components
                                        </span>
                                    </td>
                                    <td>
                                        @if($product->is_active)
                                        <span class="badge badge-success">
                                            <i class="mdi mdi-check-circle"></i> Active
                                        </span>
                                        @else
                                        <span class="badge badge-secondary">
                                            <i class="mdi mdi-pause-circle"></i> Inactive
                                        </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($product->is_customizable)
                                        <i class="mdi mdi-check text-success" title="Customizable"></i>
                                        @else
                                        <i class="mdi mdi-close text-danger" title="Not Customizable"></i>
                                        @endif
                                    </td>
                                    <td>
                                        <small>{{ $product->created_at->format('M d, Y') }}</small>
                                        @if($product->creator)
                                        <br><small class="text-muted">by {{ $product->creator->name }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('admin.products.show', $product) }}" 
                                               class="btn btn-sm btn-info" 
                                               title="View Details">
                                                <i class="mdi mdi-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.products.edit', $product) }}" 
                                               class="btn btn-sm btn-warning" 
                                               title="Edit Product">
                                                <i class="mdi mdi-pencil"></i>
                                            </a>
                                            @if(!$product->customProducts->count())
                                            <form action="{{ route('admin.products.destroy', $product) }}" 
                                                  method="POST" 
                                                  class="d-inline"
                                                  onsubmit="return confirm('Are you sure you want to delete this product?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" 
                                                        class="btn btn-sm btn-danger" 
                                                        title="Delete Product">
                                                    <i class="mdi mdi-delete"></i>
                                                </button>
                                            </form>
                                            @else
                                            <button class="btn btn-sm btn-secondary" 
                                                    title="Cannot delete - has custom products" 
                                                    disabled>
                                                <i class="mdi mdi-delete"></i>
                                            </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="10" class="text-center py-5">
                                        <i class="mdi mdi-package-variant mdi-48px text-muted"></i>
                                        <p class="text-muted mt-3">No products found. Create your first product to get started!</p>
                                        <a href="{{ route('admin.products.create') }}" class="btn btn-primary btn-sm mt-2">
                                            <i class="mdi mdi-plus"></i> Create Product
                                        </a>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($products->hasPages())
                    <div class="mt-3">
                        {{ $products->links() }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Product Statistics Cards -->
    <div class="row mt-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="icon-box bg-primary text-white rounded-circle p-3 me-3">
                            <i class="mdi mdi-package-variant mdi-24px"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">Total Products</h6>
                            <h3 class="mb-0">{{ $totalProducts ?? $products->total() }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="icon-box bg-success text-white rounded-circle p-3 me-3">
                            <i class="mdi mdi-check-circle mdi-24px"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">Active Products</h6>
                            <h3 class="mb-0">{{ $activeProducts ?? $products->where('is_active', true)->count() }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="icon-box bg-info text-white rounded-circle p-3 me-3">
                            <i class="mdi mdi-cog mdi-24px"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">Customizable</h6>
                            <h3 class="mb-0">{{ $customizableProducts ?? $products->where('is_customizable', true)->count() }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="icon-box bg-warning text-white rounded-circle p-3 me-3">
                            <i class="mdi mdi-account-multiple mdi-24px"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">Client Custom</h6>
                            <h3 class="mb-0">{{ $clientCustomProducts ?? 0 }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Only initialize DataTables if table has data rows
        if ($('#productsTable tbody tr').length > 0 && !$('#productsTable tbody tr td[colspan]').length) {
            $('#productsTable').DataTable({
                "pageLength": 25,
                "order": [[8, "desc"]], // Sort by created date
                "columnDefs": [
                    { "orderable": false, "targets": [9] } // Actions column
                ],
                "language": {
                    "emptyTable": "No products available"
                }
            });
        }
    });
</script>
@endpush
