<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Trade Partner Onboarding | {{ config('app.name', 'EMURIA') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --brand: #4452b4;
            --brand-dark: #26337f;
            --gold: #ffc33a;
            --ink: #172033;
            --muted: #667085;
            --line: #d9dee8;
        }

        body {
            background:
                linear-gradient(120deg, rgba(68, 82, 180, 0.94), rgba(38, 51, 127, 0.9)),
                url('/home/images/work-10.jpg') center/cover fixed;
            color: var(--ink);
        }

        .page-shell {
            min-height: 100vh;
            padding: 32px 16px;
        }

        .wizard-wrap {
            max-width: 1120px;
            margin: 0 auto;
        }

        .hero-panel {
            color: #fff;
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 24px;
            align-items: end;
            margin-bottom: 24px;
        }

        .back-link {
            color: #fff;
            font-weight: 700;
            text-decoration: none;
        }

        .hero-panel h1 {
            margin: 14px 0 8px;
            font-size: clamp(2rem, 4vw, 3.5rem);
            line-height: 1;
            font-weight: 900;
            letter-spacing: 0;
        }

        .hero-panel p {
            max-width: 680px;
            color: rgba(255, 255, 255, 0.86);
            font-size: 1.05rem;
        }

        .wizard-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 24px 70px rgba(10, 20, 60, 0.24);
            overflow: hidden;
        }

        .wizard-top {
            display: grid;
            grid-template-columns: 280px minmax(0, 1fr);
            min-height: 720px;
        }

        .step-rail {
            background: #f6f7fb;
            border-right: 1px solid var(--line);
            padding: 28px 22px;
        }

        .rail-title {
            font-weight: 900;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--muted);
            margin-bottom: 18px;
        }

        .step-indicator {
            width: 100%;
            display: flex;
            gap: 12px;
            align-items: center;
            border: 0;
            background: transparent;
            padding: 13px 8px;
            text-align: left;
            border-radius: 8px;
            color: #344054;
            cursor: pointer;
        }

        .step-indicator.is-active {
            background: #fff;
            color: var(--brand-dark);
            box-shadow: 0 8px 22px rgba(20, 30, 80, 0.08);
        }

        .step-number {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #e9ecf5;
            color: #344054;
            font-weight: 800;
            flex: 0 0 auto;
        }

        .step-indicator.is-active .step-number {
            background: var(--gold);
            color: #111827;
        }

        .step-label strong {
            display: block;
            font-size: 0.95rem;
        }

        .step-label span {
            display: block;
            font-size: 0.78rem;
            color: var(--muted);
            margin-top: 2px;
        }

        .form-panel {
            padding: 34px;
        }

        .form-step {
            display: none;
        }

        .form-step.is-active {
            display: block;
        }

        .step-kicker {
            color: var(--brand);
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            font-weight: 900;
        }

        .step-title {
            font-size: 1.8rem;
            font-weight: 900;
            margin: 6px 0 8px;
            letter-spacing: 0;
        }

        .step-copy {
            color: var(--muted);
            margin-bottom: 24px;
        }

        .field-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .field-full {
            grid-column: 1 / -1;
        }

        .field label,
        .field-label {
            display: block;
            font-size: 0.85rem;
            font-weight: 800;
            color: #344054;
            margin-bottom: 7px;
        }

        .field input,
        .field select,
        .field textarea {
            width: 100%;
            border: 1px solid #cfd6e4;
            border-radius: 7px;
            padding: 12px 13px;
            color: var(--ink);
            background: #fff;
        }

        .field input:focus,
        .field select:focus,
        .field textarea:focus {
            outline: 2px solid rgba(68, 82, 180, 0.2);
            border-color: var(--brand);
        }

        .system-card,
        .soft-card {
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 17px;
            background: #fff;
        }

        .system-card {
            margin-bottom: 14px;
        }

        .coverage-picker {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 12px;
            align-items: end;
            margin-bottom: 18px;
        }

        .coverage-empty {
            border: 1px dashed #cfd6e4;
            border-radius: 8px;
            background: #f8fafc;
            color: var(--muted);
            padding: 18px;
            margin-top: 12px;
        }

        .system-card.is-hidden {
            display: none;
        }

        .system-card.is-selected {
            border-color: rgba(68, 82, 180, 0.35);
            box-shadow: 0 10px 26px rgba(16, 24, 40, 0.07);
        }

        .system-head {
            display: flex;
            gap: 10px;
            align-items: center;
            font-weight: 900;
            color: #26337f;
            justify-content: space-between;
        }

        .system-title {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .coverage-remove {
            border: 1px solid #cfd6e4;
            border-radius: 7px;
            background: #fff;
            color: #475467;
            font-weight: 800;
            padding: 7px 10px;
            cursor: pointer;
            font-size: 0.82rem;
        }

        .coverage-remove:hover {
            border-color: #ef4444;
            color: #b42318;
        }

        .system-pricing-panel {
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid var(--line);
        }

        .system-pricing-title {
            font-weight: 900;
            color: #344054;
            margin-bottom: 10px;
        }

        .subsystem-pricing-list {
            display: grid;
            gap: 12px;
            margin-top: 16px;
        }

        .subsystem-pricing-row {
            border: 1px solid #e5e9f2;
            border-radius: 8px;
            background: #fbfcff;
            padding: 14px;
        }

        .subsystem-pricing-row[hidden] {
            display: none;
        }

        .custom-coverage-card {
            margin-top: 18px;
            border: 1px dashed #c6d0e3;
            border-radius: 8px;
            background: #f8fafc;
            padding: 16px;
        }

        .subsystem-pricing-head {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: center;
            margin-bottom: 10px;
        }

        .subsystem-pricing-head strong {
            color: #26337f;
        }

        .currency-note {
            color: var(--muted);
            font-size: 0.78rem;
            font-weight: 800;
        }

        .field-error,
        .client-error {
            color: #b42318;
            font-size: 0.78rem;
            font-weight: 800;
            margin-top: 6px;
        }

        .field input.is-invalid,
        .field select.is-invalid,
        .field textarea.is-invalid {
            border-color: #ef4444;
            background: #fff7f7;
        }

        .mini-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
            margin-top: 12px;
        }

        .check-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
            margin-top: 14px;
        }

        .check-tile {
            display: flex;
            gap: 8px;
            align-items: center;
            min-height: 42px;
            padding: 9px 10px;
            border: 1px solid #e5e9f2;
            border-radius: 7px;
            background: #fafbff;
            font-size: 0.9rem;
        }

        .doc-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .doc-card h3 {
            margin: 0 0 12px;
            font-weight: 900;
            color: #26337f;
        }

        .doc-card input,
        .doc-card select {
            margin-top: 9px;
        }

        .terms-box {
            border: 1px solid #cfd6e4;
            background: #f8fafc;
            border-radius: 8px;
            padding: 18px;
        }

        .terms-box ul {
            margin: 12px 0 0;
            padding-left: 20px;
            color: #475467;
        }

        .wizard-actions {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding-top: 26px;
            margin-top: 28px;
            border-top: 1px solid var(--line);
        }

        .btn-wizard {
            min-height: 46px;
            border-radius: 7px;
            padding: 0 18px;
            font-weight: 900;
            border: 1px solid transparent;
            cursor: pointer;
        }

        .btn-secondary {
            background: #fff;
            border-color: #cfd6e4;
            color: #344054;
        }

        .btn-primary {
            background: var(--brand);
            color: #fff;
        }

        .btn-primary:hover {
            background: var(--brand-dark);
        }

        .error-panel {
            border: 1px solid #fecaca;
            background: #fff1f2;
            color: #991b1b;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 18px;
        }

        @media (max-width: 900px) {
            .hero-panel,
            .wizard-top,
            .field-grid,
            .doc-grid,
            .coverage-picker,
            .mini-grid,
            .check-grid {
                grid-template-columns: 1fr;
            }

            .step-rail {
                border-right: 0;
                border-bottom: 1px solid var(--line);
                padding: 18px;
            }

            .form-panel {
                padding: 22px;
            }
        }
    </style>
</head>
<body>
    <main class="page-shell">
        <div class="wizard-wrap">
            <div class="hero-panel">
                <div>
                    <a href="/home/index.html" class="back-link">Back to EMURIA</a>
                    <h1>Become an EMURIA Trade Partner</h1>
                    <p>Tell us who you are, the systems you service, and upload your compliance documents. Approved partners can be matched to clear, documented work scopes.</p>
                </div>
            </div>

            @if($errors->any())
                <div class="error-panel">
                    <p class="font-semibold">Please fix the following:</p>
                    <ul class="mt-2 list-disc pl-5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('trade-applications.store') }}" enctype="multipart/form-data" class="wizard-card" id="tradeWizard">
                @csrf
                @php
                    $statusOptions = ['yes' => 'Yes', 'pending' => 'Pending', 'no' => 'No', 'not_applicable' => 'Not applicable'];
                    $pricingUnitOptions = [
                        'sf' => '(SF) Square Foot',
                        'lf' => '(LF) Linear Foot',
                        'ea' => '(EA) Each',
                        'hr' => '(HR) Hour',
                        'day' => '(DAY) Day',
                        'ls' => '(LS) Lump Sum',
                        'ton' => '(TON) Ton',
                    ];
                    $errorKeys = collect(array_keys($errors->getMessages()));
                    $initialStep = 0;
                    if ($errors->any()) {
                        if ($errorKeys->contains(fn($key) => in_array($key, ['system_ids', 'subsystem_ids'], true) || str_starts_with($key, 'system_pricing.') || str_starts_with($key, 'subsystem_pricing.') || str_starts_with($key, 'custom_coverage.'))) {
                            $initialStep = 1;
                        } elseif ($errorKeys->contains(fn($key) => str_contains($key, 'licence') || str_contains($key, 'insurance') || str_contains($key, 'worksafebc') || str_contains($key, 'gst'))) {
                            $initialStep = 2;
                        } elseif ($errorKeys->contains(fn($key) => str_contains($key, 'policy') || str_contains($key, 'pricing') || str_contains($key, 'references') || str_contains($key, 'availability') || str_contains($key, 'minimum_service_charge') || str_contains($key, 'additional_documents'))) {
                            $initialStep = 3;
                        } elseif ($errorKeys->contains(fn($key) => str_contains($key, 'terms_accepted'))) {
                            $initialStep = 4;
                        }
                    }
                @endphp

                <div class="wizard-top">
                    <aside class="step-rail">
                        <div class="rail-title">Application Steps</div>
                        @foreach([
                            ['Company', 'Business profile'],
                            ['Coverage', 'Systems & subsystems'],
                            ['Compliance', 'Documents & licences'],
                            ['Pricing', 'Rates & availability'],
                            ['Review', 'Terms & submission'],
                        ] as $index => $step)
                            <button type="button" class="step-indicator {{ $index === 0 ? 'is-active' : '' }}" data-step-target="{{ $index }}">
                                <span class="step-number">{{ $index + 1 }}</span>
                                <span class="step-label"><strong>{{ $step[0] }}</strong><span>{{ $step[1] }}</span></span>
                            </button>
                        @endforeach
                    </aside>

                    <div class="form-panel">
                        <section class="form-step is-active" data-step="0">
                            <div class="step-kicker">Step 1 of 5</div>
                            <h2 class="step-title">Company information</h2>
                            <p class="step-copy">Start with the details admin needs to identify and contact your company.</p>

                            <div class="field-grid">
                                <div class="field"><label>Company name</label><input name="company_name" value="{{ old('company_name') }}" required></div>
                                <div class="field"><label>Contact person</label><input name="contact_person" value="{{ old('contact_person') }}" required></div>
                                <div class="field"><label>Phone</label><input name="phone" value="{{ old('phone') }}" required></div>
                                <div class="field"><label>Email</label><input type="email" name="email" value="{{ old('email') }}" required></div>
                                <div class="field"><label>Service area</label><input name="service_area" value="{{ old('service_area') }}" required placeholder="Metro Vancouver, Fraser Valley..."></div>
                                <div class="field-grid" style="gap: 12px;">
                                    <div class="field"><label>Years in business</label><input type="number" min="0" name="years_in_business" value="{{ old('years_in_business') }}"></div>
                                    <div class="field"><label>Technicians</label><input type="number" min="0" name="technicians_count" value="{{ old('technicians_count') }}"></div>
                                </div>
                                <div class="field field-full"><label>Company description</label><textarea name="company_description" rows="4" placeholder="Briefly describe your services, specialties, and team capacity.">{{ old('company_description') }}</textarea></div>
                            </div>
                        </section>

                        <section class="form-step" data-step="1">
                            <div class="step-kicker">Step 2 of 5</div>
                            <h2 class="step-title">Systems and subsystems</h2>
                            <p class="step-copy">Add one system at a time, then choose the subsystems your company can safely handle.</p>

                            @php
                                $selectedSystemIds = collect(old('system_ids', []))->map(fn($id) => (string) $id)->all();
                                $selectedSubsystemIds = collect(old('subsystem_ids', []))->map(fn($id) => (string) $id)->all();
                                $oldSubsystemPricing = old('subsystem_pricing', []);
                                $oldCustomCoverage = old('custom_coverage', [[]]);
                            @endphp

                            <div class="coverage-picker">
                                <div class="field">
                                    <label for="coverageSystemPicker">Choose a system to add</label>
                                    <select id="coverageSystemPicker">
                                        <option value="">Select a system...</option>
                                        @foreach($systems as $system)
                                            <option value="{{ $system->id }}" {{ in_array((string) $system->id, $selectedSystemIds, true) ? 'disabled' : '' }}>{{ $system->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="button" class="btn-wizard btn-secondary" id="addCoverageSystem">Add System</button>
                            </div>

                            <div class="coverage-empty" id="coverageEmpty" {{ count($selectedSystemIds) > 0 ? 'hidden' : '' }}>
                                No systems added yet. Choose a system above to reveal its subsystems.
                            </div>

                            <div id="coverageSelectedSystems">
                                @foreach($systems as $system)
                                    @php $systemSelected = in_array((string) $system->id, $selectedSystemIds, true); @endphp
                                    <div class="system-card {{ $systemSelected ? 'is-selected' : 'is-hidden' }}" data-coverage-system="{{ $system->id }}">
                                        <div class="system-head">
                                            <label class="system-title">
                                                <input type="checkbox" name="system_ids[]" value="{{ $system->id }}" {{ $systemSelected ? 'checked' : '' }}>
                                                <span>{{ $system->name }}</span>
                                            </label>
                                            <button type="button" class="coverage-remove" data-remove-system="{{ $system->id }}">Remove</button>
                                        </div>

                                    @if($system->subsystems->isNotEmpty())
                                        <div class="check-grid">
                                            @foreach($system->subsystems as $subsystem)
                                                <label class="check-tile">
                                                    <input type="checkbox" name="subsystem_ids[]" value="{{ $subsystem->id }}" data-subsystem-checkbox="{{ $subsystem->id }}" {{ in_array((string) $subsystem->id, $selectedSubsystemIds, true) ? 'checked' : '' }}>
                                                    <span>{{ $subsystem->name }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                        <div class="subsystem-pricing-list">
                                            @foreach($system->subsystems as $subsystem)
                                                @php
                                                    $subsystemPricing = $oldSubsystemPricing[$subsystem->id] ?? $oldSubsystemPricing[(string) $subsystem->id] ?? [];
                                                    $subsystemSelected = in_array((string) $subsystem->id, $selectedSubsystemIds, true);
                                                @endphp
                                                <div class="subsystem-pricing-row" data-subsystem-pricing="{{ $subsystem->id }}" {{ $subsystemSelected ? '' : 'hidden' }}>
                                                    <div class="subsystem-pricing-head">
                                                        <strong>{{ $system->name }} / {{ $subsystem->name }}</strong>
                                                        <span class="currency-note">All rates in CAD</span>
                                                    </div>
                                                    <div class="mini-grid">
                                                        <div class="field">
                                                            <label>Pricing unit</label>
                                                            <select name="subsystem_pricing[{{ $subsystem->id }}][pricing_unit]" data-subsystem-required="{{ $subsystem->id }}">
                                                                <option value="">Select</option>
                                                                @foreach($pricingUnitOptions as $value => $label)
                                                                    <option value="{{ $value }}" @selected(($subsystemPricing['pricing_unit'] ?? '') === $value)>{{ $label }}</option>
                                                                @endforeach
                                                            </select>
                                                            @error("subsystem_pricing.$subsystem->id.pricing_unit")
                                                                <div class="field-error">{{ $message }}</div>
                                                            @enderror
                                                        </div>
                                                        <div class="field">
                                                            <label>Typical trade rate (CAD)</label>
                                                            <input type="number" min="0" step="0.01" name="subsystem_pricing[{{ $subsystem->id }}][typical_rate]" value="{{ $subsystemPricing['typical_rate'] ?? '' }}" placeholder="Example: 100.00" data-subsystem-required="{{ $subsystem->id }}">
                                                            @error("subsystem_pricing.$subsystem->id.typical_rate")
                                                                <div class="field-error">{{ $message }}</div>
                                                            @enderror
                                                        </div>
                                                        <div class="field">
                                                            <label>Maximum charge (CAD)</label>
                                                            <input type="number" min="0" step="0.01" name="subsystem_pricing[{{ $subsystem->id }}][maximum_charge]" value="{{ $subsystemPricing['maximum_charge'] ?? '' }}" placeholder="Optional">
                                                            @error("subsystem_pricing.$subsystem->id.maximum_charge")
                                                                <div class="field-error">{{ $message }}</div>
                                                            @enderror
                                                        </div>
                                                        <div class="field">
                                                            <label>Estimated duration</label>
                                                            <input name="subsystem_pricing[{{ $subsystem->id }}][estimated_duration]" value="{{ $subsystemPricing['estimated_duration'] ?? '' }}" placeholder="Example: 3 days, 4 hours">
                                                        </div>
                                                        <div class="field" style="grid-column: span 2;">
                                                            <label>Pricing notes</label>
                                                            <input name="subsystem_pricing[{{ $subsystem->id }}][notes]" value="{{ $subsystemPricing['notes'] ?? '' }}" placeholder="Materials, exclusions, access rules, warranty limits">
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <p style="color: #667085; margin: 12px 0 0;">No active subsystems are listed for this system.</p>
                                    @endif
                                    </div>
                                @endforeach
                            </div>

                            <div class="custom-coverage-card">
                                <div class="subsystem-pricing-head">
                                    <strong>Other system or subsystem not listed</strong>
                                    <span class="currency-note">Optional, all rates in CAD</span>
                                </div>
                                <p class="step-copy" style="margin-bottom: 14px;">Use this when your trade covers work that is not yet in our system list.</p>

                                <div id="customCoverageRows">
                                    @foreach(array_values($oldCustomCoverage ?: [[]]) as $customIndex => $customCoverage)
                                        <div class="subsystem-pricing-row" data-custom-coverage-row>
                                            <div class="mini-grid">
                                                <div class="field">
                                                    <label>System name</label>
                                                    <input name="custom_coverage[{{ $customIndex }}][system_name]" value="{{ $customCoverage['system_name'] ?? '' }}" placeholder="Example: Masonry">
                                                    @error("custom_coverage.$customIndex.system_name")
                                                        <div class="field-error">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                                <div class="field">
                                                    <label>Subsystem / work type</label>
                                                    <input name="custom_coverage[{{ $customIndex }}][subsystem_name]" value="{{ $customCoverage['subsystem_name'] ?? '' }}" placeholder="Example: Chimney repair">
                                                    @error("custom_coverage.$customIndex.subsystem_name")
                                                        <div class="field-error">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                                <div class="field">
                                                    <label>Pricing unit</label>
                                                    <select name="custom_coverage[{{ $customIndex }}][pricing_unit]">
                                                        <option value="">Select</option>
                                                        @foreach($pricingUnitOptions as $value => $label)
                                                            <option value="{{ $value }}" @selected(($customCoverage['pricing_unit'] ?? '') === $value)>{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                    @error("custom_coverage.$customIndex.pricing_unit")
                                                        <div class="field-error">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                                <div class="field">
                                                    <label>Typical trade rate (CAD)</label>
                                                    <input type="number" min="0" step="0.01" name="custom_coverage[{{ $customIndex }}][typical_rate]" value="{{ $customCoverage['typical_rate'] ?? '' }}" placeholder="Example: 100.00">
                                                    @error("custom_coverage.$customIndex.typical_rate")
                                                        <div class="field-error">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                                <div class="field">
                                                    <label>Maximum charge (CAD)</label>
                                                    <input type="number" min="0" step="0.01" name="custom_coverage[{{ $customIndex }}][maximum_charge]" value="{{ $customCoverage['maximum_charge'] ?? '' }}" placeholder="Optional">
                                                    @error("custom_coverage.$customIndex.maximum_charge")
                                                        <div class="field-error">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                                <div class="field">
                                                    <label>Estimated duration</label>
                                                    <input name="custom_coverage[{{ $customIndex }}][estimated_duration]" value="{{ $customCoverage['estimated_duration'] ?? '' }}" placeholder="Example: 2 days">
                                                </div>
                                                <div class="field" style="grid-column: span 2;">
                                                    <label>Pricing notes</label>
                                                    <input name="custom_coverage[{{ $customIndex }}][notes]" value="{{ $customCoverage['notes'] ?? '' }}" placeholder="Materials, exclusions, access rules">
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <button type="button" class="btn-wizard btn-secondary" id="addCustomCoverage" style="margin-top: 12px;">Add Another Other Coverage</button>
                            </div>
                        </section>

                        <section class="form-step" data-step="2">
                            <div class="step-kicker">Step 3 of 5</div>
                            <h2 class="step-title">Compliance documents</h2>
                            <p class="step-copy">Upload what you have now. Pending items can be requested or reviewed by admin.</p>

                            <div class="doc-grid">
                                <div class="soft-card doc-card">
                                    <h3>Business licence</h3>
                                    <div class="field"><label>Status</label><select name="business_licence_status">@foreach($statusOptions as $value => $label)<option value="{{ $value }}" @selected(old('business_licence_status') === $value)>{{ $label }}</option>@endforeach</select></div>
                                    <div class="field"><input name="business_licence_number" value="{{ old('business_licence_number') }}" placeholder="Licence number"></div>
                                    <div class="field"><input type="date" name="business_licence_expiry" value="{{ old('business_licence_expiry') }}"></div>
                                    <div class="field"><input type="file" name="business_licence_document"></div>
                                </div>

                                <div class="soft-card doc-card">
                                    <h3>Liability insurance</h3>
                                    <div class="field"><label>Status</label><select name="liability_insurance_status">@foreach($statusOptions as $value => $label)<option value="{{ $value }}" @selected(old('liability_insurance_status') === $value)>{{ $label }}</option>@endforeach</select></div>
                                    <div class="field"><input name="liability_insurance_provider" value="{{ old('liability_insurance_provider') }}" placeholder="Provider"></div>
                                    <div class="field"><input name="liability_insurance_policy_number" value="{{ old('liability_insurance_policy_number') }}" placeholder="Policy number"></div>
                                    <div class="field"><input type="date" name="liability_insurance_expiry" value="{{ old('liability_insurance_expiry') }}"></div>
                                    <div class="field"><input type="file" name="liability_insurance_document"></div>
                                </div>

                                <div class="soft-card doc-card">
                                    <h3>WorkSafeBC</h3>
                                    <div class="field"><label>Status</label><select name="worksafebc_status">@foreach($statusOptions as $value => $label)<option value="{{ $value }}" @selected(old('worksafebc_status') === $value)>{{ $label }}</option>@endforeach</select></div>
                                    <div class="field"><input name="worksafebc_number" value="{{ old('worksafebc_number') }}" placeholder="WorkSafeBC number"></div>
                                    <div class="field"><input type="date" name="worksafebc_expiry" value="{{ old('worksafebc_expiry') }}"></div>
                                    <div class="field"><input type="file" name="worksafebc_document"></div>
                                </div>

                                <div class="soft-card doc-card">
                                    <h3>GST</h3>
                                    <div class="field"><label>Status</label><select name="gst_status">@foreach($statusOptions as $value => $label)<option value="{{ $value }}" @selected(old('gst_status') === $value)>{{ $label }}</option>@endforeach</select></div>
                                    <div class="field"><input name="gst_number" value="{{ old('gst_number') }}" placeholder="GST number"></div>
                                    <div class="field"><input type="file" name="gst_document"></div>
                                </div>
                            </div>
                        </section>

                        <section class="form-step" data-step="3">
                            <div class="step-kicker">Step 4 of 5</div>
                            <h2 class="step-title">Terms, references and availability</h2>
                            <p class="step-copy">Add company-wide rules that apply across systems, plus references and availability.</p>

                            <div class="field-grid">
                                <div class="soft-card field-full">
                                    <h3 style="font-weight: 900; margin-bottom: 12px;">Company-wide pricing terms</h3>
                                    <div class="field-grid">
                                        <div class="field"><label>Minimum service charge (CAD)</label><input type="number" min="0" step="0.01" name="minimum_service_charge" value="{{ old('minimum_service_charge') }}" placeholder="Example: CAD 350.00"></div>
                                        <div class="field"><label>Emergency premium</label><input name="emergency_premium" value="{{ old('emergency_premium') }}" placeholder="Example: 1.5x after hours"></div>
                                        <div class="field"><label>Travel charge policy</label><input name="travel_charge_policy" value="{{ old('travel_charge_policy') }}" placeholder="Included, per trip, per zone, per km"><input type="file" name="travel_policy_document" aria-label="Upload travel charge policy document"></div>
                                        <div class="field"><label>Material policy</label><input name="material_policy" value="{{ old('material_policy') }}" placeholder="Included, excluded, case by case"><input type="file" name="material_policy_document" aria-label="Upload material policy document"></div>
                                        <div class="field"><label>Equipment policy</label><input name="equipment_policy" value="{{ old('equipment_policy') }}" placeholder="Included or separately disclosed"><input type="file" name="equipment_policy_document" aria-label="Upload equipment policy document"></div>
                                        <div class="field"><label>Disposal policy</label><input name="disposal_policy" value="{{ old('disposal_policy') }}" placeholder="Onsite bin, trade removal, dump fees"><input type="file" name="disposal_policy_document" aria-label="Upload disposal policy document"></div>
                                        <div class="field"><label>Standard warranty</label><input name="standard_warranty" value="{{ old('standard_warranty') }}" placeholder="Example: 1 year labour"><input type="file" name="warranty_document" aria-label="Upload warranty document"></div>
                                        <div class="field"><label>Pricing notes</label><input name="pricing_notes" value="{{ old('pricing_notes') }}" placeholder="Anything admin should know about your pricing"><input type="file" name="pricing_policy_document" aria-label="Upload pricing policy document"></div>
                                    </div>
                                </div>

                                @for($i = 0; $i < 2; $i++)
                                    <div class="soft-card">
                                        <h3 style="font-weight: 900; margin-bottom: 12px;">Reference {{ $i + 1 }}</h3>
                                        <div class="field"><input name="references[{{ $i }}][name]" value="{{ old("references.$i.name") }}" placeholder="Name"></div>
                                        <div class="field"><input name="references[{{ $i }}][phone]" value="{{ old("references.$i.phone") }}" placeholder="Phone"></div>
                                        <div class="field"><input type="email" name="references[{{ $i }}][email]" value="{{ old("references.$i.email") }}" placeholder="Email"></div>
                                    </div>
                                @endfor

                                <div class="soft-card field-full">
                                    <div class="field-label">Availability</div>
                                    <div class="check-grid">
                                        @foreach(['regular_hours' => 'Regular hours', 'after_hours' => 'After hours', 'weekends' => 'Weekends', 'emergency' => 'Emergency calls'] as $value => $label)
                                            <label class="check-tile">
                                                <input type="checkbox" name="availability[]" value="{{ $value }}" {{ in_array($value, old('availability', [])) ? 'checked' : '' }}>
                                                <span>{{ $label }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="field field-full">
                                    <label>Additional documents</label>
                                    <input type="file" name="additional_documents[]" multiple>
                                </div>
                            </div>
                        </section>

                        <section class="form-step" data-step="4">
                            <div class="step-kicker">Step 5 of 5</div>
                            <h2 class="step-title">Review and agreement</h2>
                            <p class="step-copy">Before submitting, confirm that your information is accurate and that you agree to EMURIA's partner review terms.</p>

                            <div class="terms-box">
                                <strong>Trade partner submission terms</strong>
                                <ul>
                                    <li>The information and documents submitted are accurate to the best of your knowledge.</li>
                                    <li>EMURIA may review, verify, approve, conditionally approve, reject, or suspend applications.</li>
                                    <li>Approval is required before receiving work orders or being included in trade pricing workflows.</li>
                                    <li>Uploaded documents may be used for compliance review and work-readiness assessment.</li>
                                </ul>
                                <label class="mt-4 flex items-start gap-3" style="display: flex; margin-top: 18px;">
                                    <input type="checkbox" name="terms_accepted" value="1" required style="margin-top: 4px;" {{ old('terms_accepted') ? 'checked' : '' }}>
                                    <span>I agree to the EMURIA trade partner submission terms and confirm that the information provided is true and complete.</span>
                                </label>
                            </div>
                        </section>

                        <div class="wizard-actions">
                            <button type="button" class="btn-wizard btn-secondary" id="prevStep">Back</button>
                            <div>
                                <a href="/home/index.html" class="btn-wizard btn-secondary" style="display: inline-flex; align-items: center; text-decoration: none;">Cancel</a>
                                <button type="button" class="btn-wizard btn-primary" id="nextStep">Continue</button>
                                <button type="submit" class="btn-wizard btn-primary" id="submitWizard" style="display: none;">Submit Application</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </main>

    <script>
        (function () {
            var currentStep = 0;
            var steps = Array.prototype.slice.call(document.querySelectorAll('.form-step'));
            var indicators = Array.prototype.slice.call(document.querySelectorAll('.step-indicator'));
            var prev = document.getElementById('prevStep');
            var next = document.getElementById('nextStep');
            var submit = document.getElementById('submitWizard');
            var form = document.getElementById('tradeWizard');
            var coveragePicker = document.getElementById('coverageSystemPicker');
            var addCoverageSystem = document.getElementById('addCoverageSystem');
            var coverageEmpty = document.getElementById('coverageEmpty');
            var coverageCards = Array.prototype.slice.call(document.querySelectorAll('[data-coverage-system]'));
            var customCoverageRows = document.getElementById('customCoverageRows');
            var addCustomCoverage = document.getElementById('addCustomCoverage');
            var initialStep = {{ (int) $initialStep }};

            function showStep(index) {
                currentStep = Math.max(0, Math.min(index, steps.length - 1));
                steps.forEach(function (step, i) {
                    step.classList.toggle('is-active', i === currentStep);
                });
                indicators.forEach(function (indicator, i) {
                    indicator.classList.toggle('is-active', i === currentStep);
                });
                prev.style.visibility = currentStep === 0 ? 'hidden' : 'visible';
                next.style.display = currentStep === steps.length - 1 ? 'none' : 'inline-flex';
                submit.style.display = currentStep === steps.length - 1 ? 'inline-flex' : 'none';
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }

            function updateCoverageEmptyState() {
                if (!coverageEmpty) {
                    return;
                }

                var hasSelectedSystem = coverageCards.some(function (card) {
                    return !card.classList.contains('is-hidden');
                });

                coverageEmpty.hidden = hasSelectedSystem;
            }

            function setPickerOptionDisabled(systemId, disabled) {
                if (!coveragePicker) {
                    return;
                }

                var option = coveragePicker.querySelector('option[value="' + systemId + '"]');
                if (option) {
                    option.disabled = disabled;
                }
            }

            function addSystem(systemId) {
                if (!systemId) {
                    return;
                }

                var card = document.querySelector('[data-coverage-system="' + systemId + '"]');
                if (!card) {
                    return;
                }

                card.classList.remove('is-hidden');
                card.classList.add('is-selected');

                var systemCheckbox = card.querySelector('input[name="system_ids[]"]');
                if (systemCheckbox) {
                    systemCheckbox.checked = true;
                }

                setPickerOptionDisabled(systemId, true);
                if (coveragePicker) {
                    coveragePicker.value = '';
                }
                updateCoverageEmptyState();
                syncSubsystemPricing();
                card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }

            function removeSystem(systemId) {
                var card = document.querySelector('[data-coverage-system="' + systemId + '"]');
                if (!card) {
                    return;
                }

                card.classList.add('is-hidden');
                card.classList.remove('is-selected');

                card.querySelectorAll('input[type="checkbox"]').forEach(function (checkbox) {
                    checkbox.checked = false;
                });
                card.querySelectorAll('input[type="number"], input[type="text"], input:not([type]), textarea').forEach(function (field) {
                    field.value = '';
                });
                card.querySelectorAll('select').forEach(function (field) {
                    field.value = '';
                });

                setPickerOptionDisabled(systemId, false);
                updateCoverageEmptyState();
                syncSubsystemPricing();
            }

            function clearClientErrors(scope) {
                scope.querySelectorAll('.client-error').forEach(function (node) {
                    node.remove();
                });
                scope.querySelectorAll('.is-invalid').forEach(function (field) {
                    field.classList.remove('is-invalid');
                });
            }

            function addClientError(field, message) {
                field.classList.add('is-invalid');
                if (!field.parentElement.querySelector('.client-error')) {
                    var error = document.createElement('div');
                    error.className = 'client-error';
                    error.textContent = message;
                    field.parentElement.appendChild(error);
                }
            }

            function syncSubsystemPricing() {
                document.querySelectorAll('[data-subsystem-checkbox]').forEach(function (checkbox) {
                    var subsystemId = checkbox.getAttribute('data-subsystem-checkbox');
                    var row = document.querySelector('[data-subsystem-pricing="' + subsystemId + '"]');
                    if (!row) {
                        return;
                    }

                    row.hidden = !checkbox.checked;
                    if (!checkbox.checked) {
                        row.querySelectorAll('input, select, textarea').forEach(function (field) {
                            field.classList.remove('is-invalid');
                        });
                        row.querySelectorAll('.client-error').forEach(function (node) {
                            node.remove();
                        });
                    }
                });
            }

            function rowHasAnyValue(row) {
                return Array.prototype.slice.call(row.querySelectorAll('input, select, textarea')).some(function (field) {
                    return String(field.value || '').trim() !== '';
                });
            }

            function addCustomCoverageRow() {
                if (!customCoverageRows) {
                    return;
                }

                var rows = customCoverageRows.querySelectorAll('[data-custom-coverage-row]');
                var template = rows[rows.length - 1];
                if (!template) {
                    return;
                }

                var nextIndex = rows.length;
                var clone = template.cloneNode(true);
                clone.querySelectorAll('input, select, textarea').forEach(function (field) {
                    field.value = '';
                    field.classList.remove('is-invalid');
                    field.name = field.name.replace(/custom_coverage\[\d+\]/, 'custom_coverage[' + nextIndex + ']');
                });
                clone.querySelectorAll('.field-error, .client-error').forEach(function (node) {
                    node.remove();
                });

                customCoverageRows.appendChild(clone);
                clone.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }

            function validateCustomCoverageRows() {
                var firstInvalid = null;
                var hasCompleteCustomCoverage = false;

                document.querySelectorAll('[data-custom-coverage-row]').forEach(function (row) {
                    if (!rowHasAnyValue(row)) {
                        return;
                    }

                    var systemField = row.querySelector('input[name$="[system_name]"]');
                    var subsystemField = row.querySelector('input[name$="[subsystem_name]"]');
                    var unitField = row.querySelector('select[name$="[pricing_unit]"]');
                    var rateField = row.querySelector('input[name$="[typical_rate]"]');
                    var maximumField = row.querySelector('input[name$="[maximum_charge]"]');
                    var rowComplete = true;

                    [
                        [systemField, 'Enter the other system name.'],
                        [subsystemField, 'Enter the other subsystem or work type.'],
                        [unitField, 'Choose the pricing unit for this other coverage.'],
                        [rateField, 'Enter the typical CAD rate for this other coverage.']
                    ].forEach(function (pair) {
                        if (pair[0] && !pair[0].value) {
                            addClientError(pair[0], pair[1]);
                            firstInvalid = firstInvalid || pair[0];
                            rowComplete = false;
                        }
                    });

                    if (maximumField && maximumField.value && rateField && rateField.value && parseFloat(maximumField.value) < parseFloat(rateField.value)) {
                        addClientError(maximumField, 'Maximum charge must be greater than the typical trade rate.');
                        firstInvalid = firstInvalid || maximumField;
                        rowComplete = false;
                    }

                    if (rowComplete) {
                        hasCompleteCustomCoverage = true;
                    }
                });

                return {
                    firstInvalid: firstInvalid,
                    hasCompleteCustomCoverage: hasCompleteCustomCoverage
                };
            }

            function validateCurrentStep() {
                var step = steps[currentStep];
                var firstInvalid = null;
                clearClientErrors(step);

                step.querySelectorAll('input, select, textarea').forEach(function (field) {
                    if (field.offsetParent === null || field.disabled) {
                        return;
                    }

                    if (!field.checkValidity()) {
                        firstInvalid = firstInvalid || field;
                    }
                });

                if (currentStep === 1) {
                    var selectedSystems = document.querySelectorAll('input[name="system_ids[]"]:checked');
                    var selectedSubsystems = document.querySelectorAll('input[name="subsystem_ids[]"]:checked');
                    var customCoverageValidation = validateCustomCoverageRows();
                    var hasCustomCoverage = customCoverageValidation.hasCompleteCustomCoverage;

                    if (selectedSystems.length === 0 && !hasCustomCoverage) {
                        var picker = document.getElementById('coverageSystemPicker');
                        addClientError(picker, 'Choose a listed system or complete the other system/subsystem section.');
                        firstInvalid = firstInvalid || picker;
                    }

                    if (selectedSubsystems.length === 0 && !hasCustomCoverage) {
                        var systemCard = document.querySelector('[data-coverage-system]:not(.is-hidden)');
                        if (systemCard) {
                            var message = document.createElement('div');
                            message.className = 'client-error';
                            message.textContent = 'Choose at least one subsystem and add its CAD pricing, or complete the other coverage section.';
                            systemCard.appendChild(message);
                        }
                        firstInvalid = firstInvalid || systemCard || customCoverageRows;
                    }

                    firstInvalid = firstInvalid || customCoverageValidation.firstInvalid;

                    selectedSubsystems.forEach(function (checkbox) {
                        var subsystemId = checkbox.value;
                        var row = document.querySelector('[data-subsystem-pricing="' + subsystemId + '"]');
                        if (!row) {
                            return;
                        }

                        row.hidden = false;
                        row.querySelectorAll('[data-subsystem-required="' + subsystemId + '"]').forEach(function (field) {
                            if (!field.value) {
                                addClientError(field, field.tagName === 'SELECT'
                                    ? 'Choose the pricing unit for this subsystem.'
                                    : 'Enter the typical CAD rate for this subsystem.');
                                firstInvalid = firstInvalid || field;
                            }
                        });
                    });
                }

                if (firstInvalid && firstInvalid.focus) {
                    firstInvalid.focus({ preventScroll: true });
                    firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    return false;
                }

                return true;
            }

            function showStepForField(field) {
                var step = field.closest('.form-step');
                if (!step) {
                    return;
                }

                showStep(steps.indexOf(step));
            }

            indicators.forEach(function (indicator) {
                indicator.addEventListener('click', function () {
                    showStep(parseInt(indicator.getAttribute('data-step-target'), 10));
                });
            });

            prev.addEventListener('click', function () {
                showStep(currentStep - 1);
            });

            next.addEventListener('click', function () {
                if (validateCurrentStep()) {
                    showStep(currentStep + 1);
                }
            });

            if (addCoverageSystem && coveragePicker) {
                addCoverageSystem.addEventListener('click', function () {
                    addSystem(coveragePicker.value);
                });

                coveragePicker.addEventListener('change', function () {
                    addSystem(coveragePicker.value);
                });
            }

            document.querySelectorAll('[data-remove-system]').forEach(function (button) {
                button.addEventListener('click', function () {
                    removeSystem(button.getAttribute('data-remove-system'));
                });
            });

            document.querySelectorAll('[data-subsystem-checkbox]').forEach(function (checkbox) {
                checkbox.addEventListener('change', syncSubsystemPricing);
            });

            if (addCustomCoverage) {
                addCustomCoverage.addEventListener('click', addCustomCoverageRow);
            }

            if (form) {
                form.addEventListener('submit', function (event) {
                    syncSubsystemPricing();
                    for (var i = 0; i < steps.length; i++) {
                        showStep(i);
                        if (!validateCurrentStep()) {
                            event.preventDefault();
                            return;
                        }
                    }

                    var invalidField = form.querySelector(':invalid');
                    if (invalidField) {
                        event.preventDefault();
                        showStepForField(invalidField);
                        invalidField.reportValidity();
                    }
                });
            }

            updateCoverageEmptyState();
            syncSubsystemPricing();
            showStep(initialStep);
        })();
    </script>
</body>
</html>
