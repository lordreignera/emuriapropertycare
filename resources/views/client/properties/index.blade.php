@extends('client.layout')

@section('title', 'My Properties')

@section('header', 'My Properties')

@section('breadcrumbs')
<li class="breadcrumb-item active" aria-current="page">Properties</li>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="card-title mb-0">Property List</h4>
                    <a href="{{ route('client.properties.create') }}" class="btn btn-primary btn-sm">
                        <i class="mdi mdi-plus"></i> Add New Property
                    </a>
                </div>
                
                @if($properties->count() > 0)
                <div class="table-responsive">
                    <table id="propertiesTable" class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th>Property Code</th>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th>Photos</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($properties as $property)
                            <tr>
                                <td>
                                    <span class="badge badge-info">{{ $property->property_code }}</span>
                                </td>
                                <td>
                                    <i class="mdi mdi-home text-primary me-2"></i>
                                    <strong>{{ $property->property_name }}</strong>
                                </td>
                                <td>{{ ucfirst(str_replace('_', ' ', $property->type)) }}</td>
                                <td>{{ $property->city }}, {{ $property->country }}</td>
                                <td>
                                    @if($property->status === 'pending_approval')
                                        <span class="badge badge-warning">Pending Approval</span>
                                    @elseif($property->status === 'approved')
                                        <span class="badge badge-success">Approved</span>
                                    @elseif($property->status === 'rejected')
                                        <span class="badge badge-danger">Rejected</span>
                                    @else
                                        <span class="badge badge-secondary">{{ ucfirst($property->status) }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($property->property_photos && count($property->property_photos) > 0)
                                        <span class="badge badge-primary">{{ count($property->property_photos) }} photos</span>
                                    @else
                                        <span class="text-muted">No photos</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('client.properties.show', $property->id) }}" 
                                           class="btn btn-sm btn-info" title="View">
                                            <i class="mdi mdi-eye"></i>
                                        </a>
                                        @if($property->status !== 'approved')
                                        <a href="{{ route('client.properties.edit', $property->id) }}" 
                                           class="btn btn-sm btn-warning" title="Edit">
                                            <i class="mdi mdi-pencil"></i>
                                        </a>
                                        <form action="{{ route('client.properties.destroy', $property->id) }}" 
                                              method="POST" 
                                              onsubmit="return confirm('Are you sure you want to delete this property?');"
                                              class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                <i class="mdi mdi-delete"></i>
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                @else
                <div class="text-center py-5">
                    <i class="mdi mdi-home-outline" style="font-size: 5rem; color: #ddd;"></i>
                    <h5 class="mt-3 text-muted">No properties found</h5>
                    <p class="text-muted">Start by adding your first property</p>
                    <a href="{{ route('client.properties.create') }}" class="btn btn-success mt-3">
                        <i class="mdi mdi-home-plus"></i> Add Property
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    @if($properties->count() > 0)
    $('#propertiesTable').DataTable({
        "pageLength": 10,
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
        "order": [[0, "desc"]],
        "language": {
            "search": "Search properties:",
            "lengthMenu": "Show _MENU_ properties per page",
            "info": "Showing _START_ to _END_ of _TOTAL_ properties",
            "infoEmpty": "No properties available",
            "infoFiltered": "(filtered from _MAX_ total properties)",
            "zeroRecords": "No matching properties found",
            "paginate": {
                "first": "First",
                "last": "Last",
                "next": "Next",
                "previous": "Previous"
            }
        },
        "columnDefs": [
            { "orderable": false, "targets": [5, 6] } // Disable sorting for Photos and Actions columns
        ]
    });
    @endif
});
</script>
@endpush
