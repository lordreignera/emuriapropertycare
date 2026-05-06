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
                    <a href="{{ ($adminPreview ?? false) ? route('inspections.agreement.download', $inspection->id) : route('client.inspections.agreement.download', $inspection->id) }}" class="btn btn-outline-light btn-sm">
                        <i class="mdi mdi-download me-1"></i>Download PDF
                    </a>
                    <a href="{{ ($adminPreview ?? false) ? (($forCountersign ?? false) ? route('inspections.index', ['view' => 'pending-etogo']) : route('inspections.show', $inspection->id)) : route('client.inspections.index') }}" class="btn btn-light btn-sm">
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
                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0 ps-3">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="alert alert-info">
                    <strong>Agreement Workflow</strong><br>
                    1. Client sign: {!! $inspection->approved_by_client ? '<span class="badge bg-success">Completed</span>' : '<span class="badge bg-warning text-dark">Pending</span>' !!}<br>
                    2. Deposit confirmation:
                    @if(($inspection->payment_plan ?? 'full') === 'installment' && ($inspection->work_payment_status ?? 'pending') === 'paid')
                        <span class="badge bg-success">Completed - 50% Paid</span>
                    @elseif(($inspection->work_payment_status ?? 'pending') === 'paid')
                        <span class="badge bg-success">Completed - Payment Confirmed</span>
                    @else
                        <span class="badge bg-warning text-dark">Pending</span>
                    @endif
                    <br>
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
                            @if($inspection->etogo_signature_image_path)
                                <div class="mt-2 border rounded bg-white p-2 d-inline-block">
                                    <img src="{{ \Illuminate\Support\Facades\Storage::disk(config('filesystems.default','public'))->url($inspection->etogo_signature_image_path) }}"
                                         alt="Etogo Signature"
                                         style="max-height:60px;max-width:220px;object-fit:contain;">
                                </div>
                            @endif
                        </div>
                    @elseif(!$inspection->approved_by_client)
                        <div class="alert alert-info mb-0">
                            <strong>Awaiting Client Signature</strong><br>
                            Etogo staff can sign only after the client has signed this agreement.
                        </div>
                    @elseif(($forCountersign ?? false) && (($inspection->work_payment_status ?? 'pending') !== 'paid'))
                        <div class="alert alert-warning mb-0">
                            <strong>Work Payment Pending</strong><br>
                            Deposit/work payment must be confirmed before Etogo countersign.
                        </div>
                    @elseif($forCountersign ?? false)
                        <div class="card border-warning mb-0">
                            <div class="card-header bg-warning text-dark fw-semibold">
                                <i class="mdi mdi-draw me-1"></i>Etogo Countersign Agreement
                            </div>
                            <div class="card-body">
                                <p class="small text-muted mb-3">
                                    Review this contract carefully, then countersign below to authorize work start.
                                </p>
                                @php $adminSignatureUrl = auth()->user()->signature_url; @endphp
                                @if($adminSignatureUrl)
                                <div class="mb-3">
                                    <label class="form-label fw-semibold small">Your Saved Signature</label>
                                    <div class="border rounded p-2 bg-white text-center d-inline-block w-100">
                                        <img src="{{ $adminSignatureUrl }}"
                                             alt="Staff Signature"
                                             style="max-height:70px;max-width:280px;object-fit:contain;">
                                    </div>
                                    <small class="text-muted d-block mt-1">
                                        <i class="mdi mdi-check-circle text-success me-1"></i>
                                        This signature image will be attached as the Etogo countersignature.
                                        <a href="{{ route('profile.settings') }}" target="_blank" class="ms-1">Change</a>
                                    </small>
                                </div>
                                @else
                                <div class="alert alert-light border small mb-3">
                                    <i class="mdi mdi-information-outline me-1"></i>
                                    No signature uploaded to your profile.
                                    <a href="{{ route('profile.settings') }}" target="_blank" class="fw-semibold">Upload your signature</a>
                                    to attach it here.
                                </div>
                                @endif
                                <form method="POST" action="{{ route('inspections.agreement.countersign', $inspection->id) }}">
                                    @csrf
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Full Name (Digital Signature)</label>
                                        <input type="text" name="staff_full_name" class="form-control @error('staff_full_name') is-invalid @enderror" value="{{ old('staff_full_name', auth()->user()->name ?? '') }}" required>
                                        @error('staff_full_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-check mb-3">
                                        <input class="form-check-input @error('staff_acknowledgment') is-invalid @enderror" type="checkbox" value="1" id="staff_acknowledgment" name="staff_acknowledgment" required>
                                        <label class="form-check-label" for="staff_acknowledgment">
                                            I confirm that I have reviewed this client-signed agreement and I countersign on behalf of Etogo.
                                        </label>
                                        @error('staff_acknowledgment')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <button type="submit" class="btn btn-warning fw-semibold"
                                            onclick="return confirm('Countersign this agreement and authorize work start?');">
                                        <i class="mdi mdi-check-decagram-outline me-1"></i>Review Complete &amp; Countersign
                                    </button>
                                </form>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-secondary mb-0">
                            <strong>Preview Only</strong><br>
                            To countersign from operations queue, open Pending Etogo and click <em>Review &amp; Countersign</em>.
                        </div>
                    @endif
                @elseif($inspection->approved_by_client)
                    <div class="alert alert-success mb-0">
                        <strong>Agreement Signed</strong><br>
                        Signed by: {{ $inspection->client_full_name ?: 'Client' }}<br>
                        Date: {{ optional($inspection->client_approved_at)->format('M d, Y h:i A') ?: '-' }}
                        @if($inspection->client_signature_image_path)
                            <div class="mt-2 border rounded bg-white p-2 d-inline-block">
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk(config('filesystems.default','public'))->url($inspection->client_signature_image_path) }}"
                                     alt="Client Signature"
                                     style="max-height:60px;max-width:220px;object-fit:contain;">
                            </div>
                        @endif
                    </div>
                @else
                    @php $mySignatureUrl = auth()->user()->signature_url; @endphp
                    <form method="POST" action="{{ route('client.inspections.agreement.sign', $inspection->id) }}">
                        @csrf
                        @if($mySignatureUrl)
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Your Saved Signature</label>
                            <div class="border rounded p-2 bg-white text-center d-inline-block w-100">
                                <img src="{{ $mySignatureUrl }}"
                                     alt="My Signature"
                                     style="max-height:70px;max-width:280px;object-fit:contain;">
                            </div>
                            <small class="text-muted d-block mt-1">
                                <i class="mdi mdi-check-circle text-success me-1"></i>
                                This signature image will be attached to the agreement.
                                <a href="{{ route('profile.settings') }}" target="_blank" class="ms-1">Change</a>
                            </small>
                        </div>
                        @else
                        <div class="alert alert-light border small mb-3">
                            <i class="mdi mdi-information-outline me-1"></i>
                            No signature image on file.
                            <a href="{{ route('profile.settings') }}" target="_blank" class="fw-semibold">Upload your signature</a>
                            to attach it here. Your typed name below will still serve as your digital signature.
                        </div>
                        @endif
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
