@extends('client.layout')

@section('title', 'Client Agreement')

@section('content')
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

                @include('shared.inspection-job-approval-agreement', ['inspection' => $inspection])

                <hr class="my-4">

                @if($inspection->approved_by_client)
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
