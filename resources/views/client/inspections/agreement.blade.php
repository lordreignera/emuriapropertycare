@extends($adminPreview ?? false ? 'admin.layout' : 'client.layout')

@section('title', 'Client Agreement')

@section('content')
@if(session('adminPreview') || isset($adminPreview))
<div class="alert alert-warning border-warning mb-3 no-print" role="alert" style="border-left:4px solid #f0ad4e;">
    <i class="mdi mdi-eye me-2"></i>
    <strong>ADMIN PREVIEW MODE — CONTRACT DRAFT</strong> — This is the contract as the client will see it. Staff signature is available only after client signature.
    <a href="javascript:window.close()" class="btn btn-sm btn-warning ms-3">Close Preview</a>
</div>
@endif
<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="mdi mdi-file-document-outline me-2"></i>Client Job Approval & Service Agreement</h5>
                <div class="d-flex gap-2">
                    <a href="{{ route('client.inspections.agreement.download', $inspection->id) }}" class="btn btn-outline-light btn-sm">
                        <i class="mdi mdi-download me-1"></i>Download PDF
                    </a>
                    <a href="{{ route('client.inspections.index') }}" class="btn btn-light btn-sm">
                        <i class="mdi mdi-arrow-left me-1"></i>Back to Inspections
                    </a>
                </div>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif

                <div class="alert alert-info">
                    <strong>Agreement Workflow</strong><br>
                    1. Client sign: {!! $inspection->approved_by_client ? '<span class="badge bg-success">Completed</span>' : '<span class="badge bg-warning text-dark">Pending</span>' !!}<br>
                    2. Deposit confirmation: {!! ($inspection->work_payment_status ?? 'pending') === 'paid' ? '<span class="badge bg-success">Completed</span>' : '<span class="badge bg-warning text-dark">Pending</span>' !!}<br>
                    3. Etogo countersign: {!! $inspection->etogo_signed_at ? '<span class="badge bg-success">Completed</span>' : '<span class="badge bg-secondary">Awaiting</span>' !!}
                    @if(!empty($inspection->schedule_blocked_reason))
                        <div class="mt-2 mb-0"><small>{{ $inspection->schedule_blocked_reason }}</small></div>
                    @endif
                </div>

                @include('shared.inspection-job-approval-agreement', ['inspection' => $inspection])

                <hr class="my-4">

                @if($adminPreview ?? false)
                    @if($inspection->etogo_signed_at)
                        <div class="alert alert-success mb-0">
                            <strong>Etogo Staff Signature Completed</strong><br>
                            Signed by staff user ID: {{ $inspection->etogo_signed_by ?? '-' }}<br>
                            Date: {{ optional($inspection->etogo_signed_at)->format('M d, Y h:i A') ?: '-' }}
                        </div>
                    @elseif(!$inspection->approved_by_client)
                        <div class="alert alert-info mb-0">
                            <strong>Awaiting Client Signature</strong><br>
                            Etogo staff can sign only after the client has signed this agreement.
                        </div>
                    @else
                        <form method="POST" action="{{ route('inspections.agreement.staff-sign', $inspection->id) }}">
                            @csrf
                            <button type="submit" class="btn btn-primary">
                                <i class="mdi mdi-pen me-1"></i>Sign as Etogo Staff
                            </button>
                            <div class="text-muted small mt-2">Business rule: staff can sign only after client signature.</div>
                        </form>
                    @endif
                @elseif($inspection->approved_by_client)
                    <div class="alert alert-success mb-0">
                        <strong>Agreement Signed</strong><br>
                        Signed by: {{ $inspection->client_full_name ?: 'Client' }}<br>
                        Date: {{ optional($inspection->client_approved_at)->format('M d, Y h:i A') ?: '-' }}
                    </div>
                @else
                    <form method="POST" action="{{ route('client.inspections.agreement.sign', $inspection->id) }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Full Name (Digital Signature)</label>
                            <input type="text" name="client_full_name" class="form-control @error('client_full_name') is-invalid @enderror" value="{{ old('client_full_name', auth()->user()->name ?? '') }}" required>
                            @error('client_full_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input @error('client_acknowledgment') is-invalid @enderror" type="checkbox" value="1" id="client_acknowledgment" name="client_acknowledgment" required>
                            <label class="form-check-label" for="client_acknowledgment">
                                I confirm that I have reviewed this report and agree to the Client Job Approval & Service Agreement terms.
                            </label>
                            @error('client_acknowledgment')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-success">
                            <i class="mdi mdi-check-decagram-outline me-1"></i>Sign Agreement Online
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
