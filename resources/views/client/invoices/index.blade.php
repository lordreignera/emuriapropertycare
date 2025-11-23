@extends('client.layout')

@section('title', 'My Invoices')

@section('header', 'My Invoices')

@section('breadcrumbs')
<li class="breadcrumb-item active" aria-current="page">Invoices</li>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="card-title mb-0">Invoice List</h4>
                        <p class="text-muted mb-0">View and manage your invoices</p>
                    </div>
                </div>

                @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="mdi mdi-check-circle me-2"></i>
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                @endif

                @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="mdi mdi-alert-circle me-2"></i>
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Property</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Due Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="mb-3">
                                        <i class="mdi mdi-file-document-outline" style="font-size: 4rem; color: #6c757d;"></i>
                                    </div>
                                    <h5 class="text-muted">No Invoices Yet</h5>
                                    <p class="text-muted mb-3">You don't have any invoices at the moment.</p>
                                    <p class="text-muted small">
                                        Invoices will appear here after you complete property inspections and accept service offers.
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Fix for dark input fields */
.form-control,
.form-control:focus {
    background-color: #ffffff !important;
    color: #000000 !important;
    border: 1px solid #ced4da !important;
}

.table {
    color: #212529 !important;
}

.card-body {
    color: #212529 !important;
}
</style>
@endsection
