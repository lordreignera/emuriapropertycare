@extends('client.layout')

@section('title', 'My Invoices')

@section('header', 'My Invoices')

@section('breadcrumbs')
<li class="breadcrumb-item active" aria-current="page">Invoices</li>
@endsection

@section('content')
<!-- Page Header -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            <div class="card-body text-white p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="fw-bold mb-1">My Invoices</h3>
                        <p class="mb-0 opacity-75">View and manage your billing and payments</p>
                    </div>
                    <div class="text-end">
                        <div class="badge bg-light text-dark fs-5 px-3 py-2">
                            <i class="mdi mdi-file-document me-2"></i>0 Invoices
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                @if(session('success'))
                <div class="alert alert-success border-0 shadow-sm d-flex align-items-center" role="alert">
                    <div class="rounded-circle bg-success bg-opacity-25 p-2 me-3">
                        <i class="mdi mdi-check-circle text-success"></i>
                    </div>
                    <div>{{ session('success') }}</div>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                @endif

                @if(session('error'))
                <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center" role="alert">
                    <div class="rounded-circle bg-danger bg-opacity-25 p-2 me-3">
                        <i class="mdi mdi-alert-circle text-danger"></i>
                    </div>
                    <div>{{ session('error') }}</div>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th class="border-0 text-uppercase small fw-semibold py-3">Invoice #</th>
                                <th class="border-0 text-uppercase small fw-semibold py-3">Property</th>
                                <th class="border-0 text-uppercase small fw-semibold py-3">Date</th>
                                <th class="border-0 text-uppercase small fw-semibold py-3">Amount</th>
                                <th class="border-0 text-uppercase small fw-semibold py-3">Status</th>
                                <th class="border-0 text-uppercase small fw-semibold py-3">Due Date</th>
                                <th class="border-0 text-uppercase small fw-semibold py-3 text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="py-5">
                                        <div class="rounded-circle bg-light d-inline-flex p-5 mb-4">
                                            <i class="mdi mdi-file-document-outline text-muted" style="font-size: 5rem;"></i>
                                        </div>
                                        <h4 class="fw-semibold mb-2">No Invoices Yet</h4>
                                        <p class="text-muted mb-3">You don't have any invoices at the moment.</p>
                                        <div class="alert alert-info border-0 shadow-sm d-inline-block mx-auto">
                                            <i class="mdi mdi-information me-2"></i>
                                            Invoices will appear here after you complete property inspections and accept service offers.
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Info Cards -->
<div class="row mt-4 g-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-start">
                    <div class="rounded-3 p-3 me-3" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                        <i class="mdi mdi-check-circle text-white" style="font-size: 2rem;"></i>
                    </div>
                    <div>
                        <h6 class="fw-semibold mb-2">Secure Payments</h6>
                        <p class="text-muted small mb-0">All payments are processed securely through Stripe</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-start">
                    <div class="rounded-3 p-3 me-3" style="background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);">
                        <i class="mdi mdi-email text-white" style="font-size: 2rem;"></i>
                    </div>
                    <div>
                        <h6 class="fw-semibold mb-2">Email Notifications</h6>
                        <p class="text-muted small mb-0">Get notified when new invoices are available</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-start">
                    <div class="rounded-3 p-3 me-3" style="background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);">
                        <i class="mdi mdi-file-download text-white" style="font-size: 2rem;"></i>
                    </div>
                    <div>
                        <h6 class="fw-semibold mb-2">Download PDFs</h6>
                        <p class="text-muted small mb-0">Download invoice copies for your records</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
