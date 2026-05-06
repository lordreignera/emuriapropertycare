@php
    $isClient = Auth::user()->hasRole('Client');
    $layout   = $isClient ? 'client.layout' : 'admin.layout';
@endphp

@extends($layout)

@section('title', 'My Profile & Settings')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h4 class="fw-bold mb-1">
            <i class="mdi mdi-account-cog me-2 text-primary"></i>Profile &amp; Settings
        </h4>
        <p class="text-muted small mb-0">Manage your personal information, password, profile photo and signature.</p>
    </div>
</div>

{{-- Flash Messages --}}
@foreach(['profile_updated' => 'success', 'password_updated' => 'success', 'photo_updated' => 'success', 'signature_updated' => 'success', 'signature_removed' => 'info'] as $key => $type)
    @if(session($key))
    <div class="alert alert-{{ $type }} alert-dismissible fade show mb-3" role="alert">
        <i class="mdi mdi-check-circle me-2"></i>{{ session($key) }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif
@endforeach

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
    <i class="mdi mdi-alert-circle me-2"></i>
    <ul class="mb-0 ps-3">
        @foreach($errors->all() as $e)
            <li>{{ $e }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row g-4">

    {{-- LEFT: Avatar + Signature --}}
    <div class="col-lg-4">

        {{-- Profile Photo Card --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body text-center py-4">
                <div class="position-relative d-inline-block mb-3" id="avatarWrap">
                    <img id="avatarPreview"
                         src="{{ Auth::user()->profile_photo_url }}"
                         alt="{{ Auth::user()->name }}"
                         class="rounded-circle border border-3 border-primary"
                         style="width:110px;height:110px;object-fit:cover;">
                    <label for="photoInput"
                           class="position-absolute bottom-0 end-0 bg-primary text-white rounded-circle d-flex align-items-center justify-content-center"
                           style="width:30px;height:30px;cursor:pointer;border:2px solid #fff;"
                           title="Change photo">
                        <i class="mdi mdi-camera" style="font-size:.9rem;"></i>
                    </label>
                </div>
                <h6 class="fw-bold mb-0">{{ Auth::user()->name }}</h6>
                <small class="text-muted">{{ Auth::user()->email }}</small>

                <form method="POST"
                      action="{{ route('profile.photo') }}"
                      enctype="multipart/form-data"
                      id="photoForm"
                      class="mt-3">
                    @csrf
                    <input type="file"
                           id="photoInput"
                           name="photo"
                           accept="image/jpeg,image/png,image/webp"
                           class="d-none"
                           onchange="previewAndSubmit(this, 'avatarPreview', 'photoForm')">
                    <small class="d-block text-muted" style="font-size:.75rem;">
                        JPG, PNG or WEBP · max 2 MB
                    </small>
                </form>
            </div>
        </div>

        {{-- Signature Card --}}
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-bottom py-3">
                <h6 class="mb-0 fw-semibold">
                    <i class="mdi mdi-draw me-2 text-info"></i>My Signature
                </h6>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    This signature will appear on agreements and contracts.
                    Upload a clear image of your handwritten signature on a white background.
                </p>

                {{-- Current signature --}}
                @if(Auth::user()->signature_path)
                <div class="mb-3 text-center">
                    <div class="border rounded p-2 bg-white mb-2">
                        <img src="{{ Auth::user()->signature_url }}"
                             alt="My Signature"
                             id="sigPreview"
                             style="max-height:90px;max-width:100%;object-fit:contain;">
                    </div>
                    <form method="POST" action="{{ route('profile.signature.remove') }}" class="d-inline">
                        @csrf @method('DELETE')
                        <button type="submit"
                                class="btn btn-sm btn-outline-danger"
                                onclick="return confirm('Remove your signature?')">
                            <i class="mdi mdi-delete me-1"></i>Remove
                        </button>
                    </form>
                </div>
                @else
                <div class="border rounded p-3 bg-light text-center mb-3" id="sigPlaceholder">
                    <img id="sigPreview" src="" alt="" style="max-height:90px;max-width:100%;object-fit:contain;display:none;">
                    <i class="mdi mdi-draw text-muted" style="font-size:2.5rem;"></i>
                    <p class="text-muted small mb-0 mt-1">No signature uploaded yet</p>
                </div>
                @endif

                <form method="POST"
                      action="{{ route('profile.signature') }}"
                      enctype="multipart/form-data"
                      id="sigForm">
                    @csrf
                    <div class="mb-2">
                        <label for="sigInput" class="form-label fw-semibold small">Upload Signature Image</label>
                        <input type="file"
                               id="sigInput"
                               name="signature"
                               accept="image/jpeg,image/png,image/webp"
                               class="form-control form-control-sm @error('signature') is-invalid @enderror"
                               onchange="previewAndSubmit(this, 'sigPreview', 'sigForm', 'sigPlaceholder')">
                        @error('signature')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">JPG, PNG or WEBP · max 2 MB · Uploads automatically on selection</small>
                    </div>
                    {{-- Fallback submit button for browsers without JS --}}
                    <noscript>
                        <button type="submit" class="btn btn-info btn-sm w-100 mt-2">
                            <i class="mdi mdi-upload me-1"></i>Upload Signature
                        </button>
                    </noscript>
                </form>
            </div>
        </div>

    </div>{{-- /LEFT --}}

    {{-- RIGHT: Profile info + Password --}}
    <div class="col-lg-8">

        {{-- Profile Information --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent border-bottom py-3">
                <h6 class="mb-0 fw-semibold">
                    <i class="mdi mdi-account me-2 text-primary"></i>Profile Information
                </h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('profile.update') }}">
                    @csrf @method('PUT')

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label fw-semibold small">Full Name <span class="text-danger">*</span></label>
                            <input type="text"
                                   id="name"
                                   name="name"
                                   class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', Auth::user()->name) }}"
                                   required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label fw-semibold small">Email Address <span class="text-danger">*</span></label>
                            <input type="email"
                                   id="email"
                                   name="email"
                                   class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email', Auth::user()->email) }}"
                                   required>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mt-3 d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="mdi mdi-content-save me-1"></i>Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Change Password --}}
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-bottom py-3">
                <h6 class="mb-0 fw-semibold">
                    <i class="mdi mdi-lock me-2 text-warning"></i>Change Password
                </h6>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    Choose a strong password with at least 8 characters, including uppercase, lowercase and numbers.
                </p>
                <form method="POST" action="{{ route('profile.password') }}">
                    @csrf @method('PUT')

                    <div class="row g-3">
                        <div class="col-12">
                            <label for="current_password" class="form-label fw-semibold small">Current Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password"
                                       id="current_password"
                                       name="current_password"
                                       class="form-control @error('current_password') is-invalid @enderror"
                                       autocomplete="current-password">
                                <button class="btn btn-outline-secondary" type="button"
                                        onclick="togglePwd('current_password', this)"
                                        title="Show/Hide">
                                    <i class="mdi mdi-eye"></i>
                                </button>
                                @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="password" class="form-label fw-semibold small">New Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password"
                                       id="password"
                                       name="password"
                                       class="form-control @error('password') is-invalid @enderror"
                                       autocomplete="new-password">
                                <button class="btn btn-outline-secondary" type="button"
                                        onclick="togglePwd('password', this)"
                                        title="Show/Hide">
                                    <i class="mdi mdi-eye"></i>
                                </button>
                                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="password_confirmation" class="form-label fw-semibold small">Confirm New Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password"
                                       id="password_confirmation"
                                       name="password_confirmation"
                                       class="form-control"
                                       autocomplete="new-password">
                                <button class="btn btn-outline-secondary" type="button"
                                        onclick="togglePwd('password_confirmation', this)"
                                        title="Show/Hide">
                                    <i class="mdi mdi-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Strength meter --}}
                    <div class="mt-2" id="strengthWrap" style="display:none;">
                        <div class="progress" style="height:4px;">
                            <div id="strengthBar" class="progress-bar" style="width:0%;transition:width .3s;"></div>
                        </div>
                        <small id="strengthLabel" class="text-muted"></small>
                    </div>

                    <div class="mt-3 d-flex justify-content-end">
                        <button type="submit" class="btn btn-warning">
                            <i class="mdi mdi-lock-reset me-1"></i>Update Password
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>{{-- /RIGHT --}}

</div>{{-- /row --}}

@push('scripts')
<script>
/**
 * Preview an image in an <img> tag and optionally submit the form immediately.
 * If formId is null, only the preview is shown (form has its own submit button).
 * placeholderId: div to hide when a preview becomes available.
 */
function previewAndSubmit(input, imgId, formId, placeholderId) {
    const file = input.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function (e) {
        const img = document.getElementById(imgId);
        if (img) {
            img.src    = e.target.result;
            img.style.display = 'block';
        }
        if (placeholderId) {
            const ph = document.getElementById(placeholderId);
            if (ph) ph.style.display = 'none';
        }
        if (formId) {
            document.getElementById(formId).submit();
        }
    };
    reader.readAsDataURL(file);
}

function togglePwd(fieldId, btn) {
    const field = document.getElementById(fieldId);
    if (!field) return;
    const isText = field.type === 'text';
    field.type = isText ? 'password' : 'text';
    btn.querySelector('i').className = isText ? 'mdi mdi-eye' : 'mdi mdi-eye-off';
}

// Password strength meter
(function () {
    const pwdInput = document.getElementById('password');
    if (!pwdInput) return;

    pwdInput.addEventListener('input', function () {
        const val  = this.value;
        const wrap = document.getElementById('strengthWrap');
        const bar  = document.getElementById('strengthBar');
        const lbl  = document.getElementById('strengthLabel');

        if (!val) { wrap.style.display = 'none'; return; }
        wrap.style.display = 'block';

        let score = 0;
        if (val.length >= 8)  score++;
        if (/[A-Z]/.test(val)) score++;
        if (/[a-z]/.test(val)) score++;
        if (/[0-9]/.test(val)) score++;
        if (/[^A-Za-z0-9]/.test(val)) score++;

        const levels = [
            { pct: 20,  cls: 'bg-danger',  text: 'Very weak' },
            { pct: 40,  cls: 'bg-danger',  text: 'Weak' },
            { pct: 60,  cls: 'bg-warning', text: 'Fair' },
            { pct: 80,  cls: 'bg-info',    text: 'Strong' },
            { pct: 100, cls: 'bg-success', text: 'Very strong' },
        ];
        const level = levels[score - 1] || levels[0];
        bar.style.width = level.pct + '%';
        bar.className   = 'progress-bar ' + level.cls;
        lbl.textContent = level.text;
    });
})();
</script>
@endpush
@endsection
