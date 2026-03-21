@extends('admin.layout')

@section('title', 'Complete Regenerative Home Inspection Report')

@section('content')
<div class="content-wrapper">
    <div class="row">
        <div class="col-12">
            <div class="card mb-3 border-0 shadow-sm" style="background: linear-gradient(135deg, #5b67ca 0%, #4854b8 100%);">
                <div class="card-body text-white p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-2 fw-bold">
                                <i class="mdi mdi-home-city-outline me-2"></i>Complete Regenerative Home Inspection Report
                            </h3>
                            <p class="mb-1 opacity-75">
                                <span class="badge bg-light text-dark me-2">{{ $property->property_code }}</span>
                                {{ $property->property_name }}
                            </p>
                            <p class="mb-0 opacity-75">
                                <i class="mdi mdi-map-marker me-1"></i>{{ $property->property_address }}, {{ $property->city }}
                            </p>
                        </div>
                        <div>
                            <span class="badge bg-warning text-dark fs-6 px-3 py-2">
                                {{ ucfirst(str_replace('_', ' ', $property->type)) }} Property
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <form action="{{ route('inspections.store') }}" method="POST" enctype="multipart/form-data" id="inspectionSystemsForm">
                @csrf
                <input type="hidden" name="property_id" value="{{ $property->id }}">
                <input type="hidden" name="status" value="in_progress">

                <div class="card mb-4">
                    <div class="card-header" style="background: #5b67ca; color: white;">
                        <h5 class="mb-0">
                            <i class="mdi mdi-information-outline me-2"></i>Inspection Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Inspection Date & Time <span class="text-danger">*</span></label>
                                    <input type="datetime-local" name="inspection_date" class="form-control" value="{{ old('inspection_date', optional($inspection->scheduled_date)->format('Y-m-d\\TH:i') ?? now()->format('Y-m-d\\TH:i')) }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Inspector</label>
                                    <input type="text" class="form-control" value="{{ Auth::user()->name }}" readonly>
                                    <input type="hidden" name="inspector_id" value="{{ Auth::id() }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Weather Condition</label>
                                    <select name="weather_conditions" class="form-control">
                                        <option value="" {{ old('weather_conditions', $inspection->weather_conditions ?? '') === '' ? 'selected' : '' }}>Select weather</option>
                                        <option value="clear" {{ old('weather_conditions', $inspection->weather_conditions ?? '') === 'clear' ? 'selected' : '' }}>Clear</option>
                                        <option value="cloudy" {{ old('weather_conditions', $inspection->weather_conditions ?? '') === 'cloudy' ? 'selected' : '' }}>Cloudy</option>
                                        <option value="rainy" {{ old('weather_conditions', $inspection->weather_conditions ?? '') === 'rainy' ? 'selected' : '' }}>Rainy</option>
                                        <option value="snowy" {{ old('weather_conditions', $inspection->weather_conditions ?? '') === 'snowy' ? 'selected' : '' }}>Snowy</option>
                                        <option value="windy" {{ old('weather_conditions', $inspection->weather_conditions ?? '') === 'windy' ? 'selected' : '' }}>Windy</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">
                        <h6 class="text-primary">Property Owner</h6>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Name</label>
                                    <input type="text" class="form-control" value="{{ $property->user->name ?? 'N/A' }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="text" class="form-control" value="{{ $property->user->email ?? 'N/A' }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Phone</label>
                                    <input type="text" class="form-control" value="{{ $property->owner_phone ?: (($property->user->phone ?? null) ?: ($property->admin_phone ?: 'N/A')) }}" readonly>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">
                        <h6 class="text-primary">Property Information</h6>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Property Type</label>
                                    <input type="text" class="form-control" value="{{ ucfirst(str_replace('_', ' ', $property->type)) }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Year Built</label>
                                    <input type="text" class="form-control" value="{{ $property->year_built ?? 'N/A' }}" readonly>
                                </div>
                            </div>
                            @if(in_array($property->type, ['residential', 'mixed_use']))
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Residential Units</label>
                                        <input type="text" class="form-control" value="{{ $property->residential_units ?? 0 }}" readonly>
                                    </div>
                                </div>
                            @endif
                            @if(in_array($property->type, ['commercial', 'mixed_use']))
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Commercial Insights (SqFt)</label>
                                        <input type="text" class="form-control" value="{{ $property->square_footage_interior ?? 0 }}" readonly>
                                    </div>
                                </div>
                            @endif
                            @if($property->type === 'mixed_use')
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Commercial Weight (%)</label>
                                        <input type="text" class="form-control" value="{{ $property->mixed_use_commercial_weight ?? 0 }}" readonly>
                                    </div>
                                </div>
                            @endif
                        </div>


                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="mdi mdi-view-list-outline me-2 text-primary"></i>Property Systems Inspection
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">Add findings per system — each finding is a card showing all fields at a glance.</p>

                        @if($systems->isEmpty())
                            <div class="alert alert-warning mb-0">
                                No systems found. Run database seeding for systems/subsystems first.
                            </div>
                        @else
                            @foreach($systems as $system)
                                <div class="card mb-3 border">
                                    <div class="card-header d-flex justify-content-between align-items-center" style="background:#f8f9fc;">
                                        <div>
                                            <strong>{{ $system->name }}</strong>
                                            @if($system->description)
                                                <span class="text-muted ms-2 small">{{ $system->description }}</span>
                                            @endif
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addSystemFindingRow({{ $system->id }})">
                                            <i class="mdi mdi-plus"></i> Add Finding
                                        </button>
                                    </div>
                                    <div class="card-body p-2" id="system-rows-{{ $system->id }}">
                                        <p class="text-muted small mb-0 px-1" id="system-empty-{{ $system->id }}">No findings added yet. Click <strong>Add Finding</strong> to record an issue.</p>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="mdi mdi-clipboard-text me-2 text-primary"></i>Overall Assessment
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Overall Condition</label>
                                    <select name="overall_condition" class="form-control">
                                        <option value="">Select condition</option>
                                        <option value="excellent" {{ old('overall_condition', $inspection->overall_condition ?? '') === 'excellent' ? 'selected' : '' }}>Excellent</option>
                                        <option value="good" {{ old('overall_condition', $inspection->overall_condition ?? '') === 'good' ? 'selected' : '' }}>Good</option>
                                        <option value="fair" {{ old('overall_condition', $inspection->overall_condition ?? '') === 'fair' ? 'selected' : '' }}>Fair</option>
                                        <option value="poor" {{ old('overall_condition', $inspection->overall_condition ?? '') === 'poor' ? 'selected' : '' }}>Poor</option>
                                        <option value="critical" {{ old('overall_condition', $inspection->overall_condition ?? '') === 'critical' ? 'selected' : '' }}>Critical</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Inspector Notes</label>
                            <textarea name="inspector_notes" class="form-control" rows="3" placeholder="Inspector observations">{{ old('inspector_notes', $inspection->inspector_notes ?? '') }}</textarea>
                        </div>
                        <div class="form-group">
                            <label>Recommendations</label>
                            <textarea name="recommendations" class="form-control" rows="3" placeholder="Final recommendations">{{ old('recommendations', $inspection->recommendations ?? '') }}</textarea>
                        </div>
                        <div class="form-group mb-0">
                            <label>Risk Summary</label>
                            <textarea name="risk_summary" class="form-control" rows="3" placeholder="Major risks identified">{{ old('risk_summary', $inspection->risk_summary ?? '') }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="mdi mdi-camera me-2 text-primary"></i>Photos
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="form-group mb-0">
                            <label>Upload Photos</label>
                            <input type="file" name="photos[]" class="form-control" multiple accept="image/*" id="photoUpload">
                            <small class="text-muted">Max 10MB per photo.</small>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('inspections.index') }}" class="btn btn-secondary">
                                <i class="mdi mdi-arrow-left me-1"></i>Cancel
                            </a>
                            <div>
                                <button type="submit" class="btn btn-warning me-2">
                                    <i class="mdi mdi-content-save me-1"></i>Save as Draft
                                </button>
                                <button type="submit" name="next_stage" value="phar" class="btn btn-success">
                                    <i class="mdi mdi-arrow-right-bold-circle me-1"></i>Save & Next: PHAR Assessment/Pricing
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@php
    $findingTemplatesRaw = \App\Models\FindingTemplateSetting::query()
        ->where('is_active', true)
        ->orderBy('sort_order')
        ->get(['task_question', 'system_id', 'subsystem_id']);

    $systemsConfig = $systems->map(function ($system) use ($findingTemplatesRaw) {
        $systemLevelFindings = $findingTemplatesRaw
            ->where('system_id', $system->id)
            ->whereNull('subsystem_id')
            ->pluck('task_question')
            ->values()
            ->all();

        return [
            'id' => $system->id,
            'name' => $system->name,
            'recommended_actions' => collect($system->recommended_actions ?? [])->values()->all(),
            'findings' => $systemLevelFindings,
            'subsystems' => $system->subsystems->map(function ($subsystem) use ($findingTemplatesRaw) {
                return [
                    'id' => $subsystem->id,
                    'name' => $subsystem->name,
                    'recommended_actions' => collect($subsystem->recommended_actions ?? [])->values()->all(),
                    'findings' => $findingTemplatesRaw
                        ->where('subsystem_id', $subsystem->id)
                        ->pluck('task_question')
                        ->values()
                        ->all(),
                ];
            })->values()->all(),
        ];
    })->values()->all();
@endphp

<script>
const systemsConfig = @json($systemsConfig);

const initialFindings = @json(old('system_findings', $inspection->findings ?? []));
let findingIndex = 0;

function escapeHtml(value) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };

    return String(value || '').replace(/[&<>"']/g, function (match) {
        return map[match];
    });
}

function getSystemConfig(systemId) {
    return systemsConfig.find(system => String(system.id) === String(systemId));
}

function buildSubsystemOptions(systemId, selectedSubsystemId = '') {
    const system = getSystemConfig(systemId);
    let options = '<option value="">General</option>';

    if (!system || !Array.isArray(system.subsystems)) {
        return options;
    }

    system.subsystems.forEach(subsystem => {
        const selected = String(subsystem.id) === String(selectedSubsystemId) ? 'selected' : '';
        options += `<option value="${subsystem.id}" ${selected}>${escapeHtml(subsystem.name)}</option>`;
    });

    return options;
}

function getSubsystemConfig(systemId, subsystemId) {
    const system = getSystemConfig(systemId);
    if (!system || !Array.isArray(system.subsystems)) {
        return null;
    }

    return system.subsystems.find(subsystem => String(subsystem.id) === String(subsystemId)) || null;
}

function collectRecommendationOptions(systemId, subsystemId = '') {
    const system = getSystemConfig(systemId);
    const subsystem = subsystemId ? getSubsystemConfig(systemId, subsystemId) : null;
    const systemRecommendations = Array.isArray(system?.recommended_actions) ? system.recommended_actions : [];
    const subsystemRecommendations = Array.isArray(subsystem?.recommended_actions) ? subsystem.recommended_actions : [];

    const unique = new Set();
    [...subsystemRecommendations, ...systemRecommendations].forEach(item => {
        const value = String(item || '').trim();
        if (value) {
            unique.add(value);
        }
    });

    return Array.from(unique);
}

function isKnownFinding(systemId, subsystemId, issue) {
    if (!issue) return true;
    const system = getSystemConfig(systemId);
    if (subsystemId) {
        const sub = getSubsystemConfig(systemId, subsystemId);
        if (Array.isArray(sub?.findings) && sub.findings.includes(issue)) return true;
    }
    return Array.isArray(system?.findings) && system.findings.includes(issue);
}

function buildFindingOptions(systemId, subsystemId, selectedValue) {
    selectedValue = selectedValue || '';
    const system = getSystemConfig(systemId);
    let findings = [];
    if (subsystemId) {
        const sub = getSubsystemConfig(systemId, subsystemId);
        findings = Array.isArray(sub?.findings) ? sub.findings : [];
    }
    if (findings.length === 0 && system) {
        findings = Array.isArray(system.findings) ? system.findings : [];
    }

    let options = '<option value="">-- Select Issue / Finding --</option>';
    findings.forEach(function (finding) {
        const esc = escapeHtml(finding);
        const sel = finding === selectedValue ? 'selected' : '';
        options += `<option value="${esc}" ${sel}>${esc}</option>`;
    });
    const isCustomSelected = selectedValue !== '' && !findings.includes(selectedValue);
    options += `<option value="__custom__" ${isCustomSelected ? 'selected' : ''}>Custom / Other...</option>`;
    return options;
}

function normalizeRecommendationItems(value) {
    if (Array.isArray(value)) {
        return value.map(item => String(item || '').trim()).filter(item => item.length > 0);
    }

    return String(value || '')
        .split(/\r\n|\r|\n|\|/)
        .map(item => item.trim())
        .filter(item => item.length > 0);
}

function addSystemFindingRow(systemId, prefill = {}) {
    const body = document.getElementById(`system-rows-${systemId}`);
    if (!body) {
        return;
    }

    // Hide the empty-state placeholder
    const emptyMsg = document.getElementById(`system-empty-${systemId}`);
    if (emptyMsg) emptyMsg.style.display = 'none';

    const currentIndex = findingIndex++;
    const findingNumber = body.querySelectorAll('.finding-card').length + 1;
    const subsystemOptions = buildSubsystemOptions(systemId, prefill.subsystem_id || '');
    const severityAliasMap = {
        urgent:                    'critical',
        health_safety_threatening: 'high',
        value_depreciation:        'medium',
        non_urgent:                'low'
    };
    const severity = severityAliasMap[prefill.severity] || prefill.severity || 'low';
    const recommendationItems = normalizeRecommendationItems(prefill.recommendations);

    const severityColors = {
        critical:        '#dc3545',
        high:            '#fd7e14',
        noi_protection:  '#7c3aed',
        medium:          '#ffc107',
        low:             '#198754'
    };
    const severityLabels = {
        critical:        'Safety & Health',
        high:            'Urgent',
        noi_protection:  'NOI Protection',
        medium:          'Value Depreciation',
        low:             'Non-Urgent'
    };

    const card = document.createElement('div');
    card.className = 'finding-card border rounded mb-2 bg-white';
    card.style.cssText = 'border-left: 4px solid ' + (severityColors[severity] || '#6c757d') + ' !important;';
    card.innerHTML = `
        <input type="hidden" name="system_findings[${currentIndex}][system_id]" value="${systemId}">
        <!-- Card header -->
        <div class="d-flex justify-content-between align-items-center px-3 py-2" style="background:#f8f9fc; border-bottom:1px solid #e9ecef; border-radius:0.25rem 0.25rem 0 0;">
            <span class="fw-semibold small text-secondary">Finding #${findingNumber}</span>
            <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="removeSystemFindingRow(this)" title="Remove finding">
                <i class="mdi mdi-delete-outline"></i> Remove
            </button>
        </div>
        <!-- Row 1: Subsystem | Issue | Severity -->
        <div class="row g-2 px-3 pt-2">
            <div class="col-md-4">
                <label class="form-label small fw-semibold text-muted mb-1">Subsystem</label>
                <select name="system_findings[${currentIndex}][subsystem_id]" class="form-select form-select-sm">
                    ${subsystemOptions}
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold text-muted mb-1">Issue / Finding</label>
                <select class="form-select form-select-sm issue-preset-select">
                    ${buildFindingOptions(systemId, prefill.subsystem_id || '', prefill.issue || '')}
                </select>
                <input type="text" class="form-control form-control-sm mt-1 issue-custom-text" placeholder="Describe the issue" style="display:none;">
                <input type="hidden" name="system_findings[${currentIndex}][issue]" class="issue-hidden-value" value="${escapeHtml(prefill.issue || '')}">
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold text-muted mb-1">Severity</label>
                <select name="system_findings[${currentIndex}][severity]" class="form-select form-select-sm severity-select">
                    <option value="critical"       ${severity === 'critical'       ? 'selected' : ''}>&#x1F534; Safety &amp; Health (100)</option>
                    <option value="high"           ${severity === 'high'           ? 'selected' : ''}>&#x1F7E0; Urgent (80)</option>
                    <option value="noi_protection" ${severity === 'noi_protection' ? 'selected' : ''}>&#x1F7E3; NOI Protection (60)</option>
                    <option value="medium"         ${severity === 'medium'         ? 'selected' : ''}>&#x1F7E1; Value Depreciation (40)</option>
                    <option value="low"            ${severity === 'low'            ? 'selected' : ''}>&#x1F7E2; Non-Urgent (0)</option>
                </select>
            </div>
        </div>
        <!-- Row 2: Location | Spot -->
        <div class="row g-2 px-3 pt-2">
            <div class="col-md-6">
                <label class="form-label small fw-semibold text-muted mb-1">Location</label>
                <input type="text" name="system_findings[${currentIndex}][location]" class="form-control form-control-sm" value="${escapeHtml(prefill.location || '')}" placeholder="e.g. North wall, Basement">
            </div>
            <div class="col-md-6">
                <label class="form-label small fw-semibold text-muted mb-1">Spot</label>
                <input type="text" name="system_findings[${currentIndex}][spot]" class="form-control form-control-sm" value="${escapeHtml(prefill.spot || '')}" placeholder="e.g. Top-left corner">
            </div>
        </div>
        <!-- Row 3: Notes | Recommendations -->
        <div class="row g-2 px-3 pt-2 pb-3">
            <div class="col-md-6">
                <label class="form-label small fw-semibold text-muted mb-1">Notes</label>
                <textarea name="system_findings[${currentIndex}][notes]" class="form-control form-control-sm" rows="3" placeholder="Detailed observations...">${escapeHtml(prefill.notes || '')}</textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label small fw-semibold text-muted mb-1">Recommendations</label>
                <div class="recommendation-builder" data-index="${currentIndex}">
                    <div class="input-group input-group-sm mb-1">
                        <select class="form-select form-select-sm recommendation-select">
                            <option value="">Select suggested recommendation</option>
                        </select>
                        <button type="button" class="btn btn-outline-primary btn-sm recommendation-add-selected">Add</button>
                    </div>
                    <div class="input-group input-group-sm mb-1">
                        <input type="text" class="form-control recommendation-input" placeholder="Or type a custom recommendation">
                        <button type="button" class="btn btn-outline-secondary btn-sm recommendation-add">Add</button>
                    </div>
                    <div class="recommendation-list small mt-1"></div>
                    <div class="recommendation-hidden-inputs"></div>
                </div>
            </div>
        </div>
    `;

    body.appendChild(card);

    // Wire issue preset select → hidden value
    const issuePresetSelect = card.querySelector('.issue-preset-select');
    const issueCustomText = card.querySelector('.issue-custom-text');
    const issueHiddenValue = card.querySelector('.issue-hidden-value');

    if (issuePresetSelect) {
        // If prefill issue is a custom (not in preset list), show the text input
        if (prefill.issue && issuePresetSelect.value === '__custom__') {
            issueCustomText.style.display = '';
            issueCustomText.value = prefill.issue;
        }

        issuePresetSelect.addEventListener('change', function () {
            if (this.value === '__custom__') {
                issueCustomText.style.display = '';
                issueCustomText.value = '';
                issueHiddenValue.value = '';
                issueCustomText.focus();
            } else {
                issueCustomText.style.display = 'none';
                issueCustomText.value = '';
                issueHiddenValue.value = this.value;
            }
        });

        issueCustomText.addEventListener('input', function () {
            issueHiddenValue.value = this.value;
        });

        // Refresh issue options when subsystem changes
        const subsystemSelForIssue = card.querySelector(`select[name="system_findings[${currentIndex}][subsystem_id]"]`);
        if (subsystemSelForIssue) {
            subsystemSelForIssue.addEventListener('change', function () {
                const currentIssue = issueHiddenValue.value;
                issuePresetSelect.innerHTML = buildFindingOptions(systemId, this.value, currentIssue);
                if (issuePresetSelect.value === '__custom__') {
                    issueCustomText.style.display = '';
                    issueCustomText.value = currentIssue;
                } else {
                    issueCustomText.style.display = 'none';
                    issueHiddenValue.value = issuePresetSelect.value;
                }
            });
        }
    }

    // Update border colour when severity changes
    const severitySelect = card.querySelector('.severity-select');
    severitySelect.addEventListener('change', function () {
        card.style.cssText = 'border-left: 4px solid ' + (severityColors[this.value] || '#6c757d') + ' !important;';
    });

    initRecommendationBuilder(card, currentIndex, recommendationItems, systemId, prefill.subsystem_id || '');
}

function initRecommendationBuilder(row, rowIndex, initialItems = [], systemId = '', initialSubsystemId = '') {
    const builder = row.querySelector('.recommendation-builder');
    if (!builder) {
        return;
    }

    const subsystemSelect = row.querySelector(`select[name="system_findings[${rowIndex}][subsystem_id]"]`);
    const recommendationSelect = builder.querySelector('.recommendation-select');
    const addSelectedButton = builder.querySelector('.recommendation-add-selected');
    const input = builder.querySelector('.recommendation-input');
    const addButton = builder.querySelector('.recommendation-add');
    const list = builder.querySelector('.recommendation-list');
    const hiddenInputs = builder.querySelector('.recommendation-hidden-inputs');

    let recommendations = normalizeRecommendationItems(initialItems);

    function addRecommendationItem(value) {
        const normalizedValue = String(value || '').trim();
        if (!normalizedValue) {
            return;
        }

        const exists = recommendations.some(item => item.toLowerCase() === normalizedValue.toLowerCase());
        if (!exists) {
            recommendations.push(normalizedValue);
            renderRecommendations();
        }
    }

    function refreshRecommendationDropdown(selectedSubsystemId = '') {
        const options = collectRecommendationOptions(systemId, selectedSubsystemId || '');
        recommendationSelect.innerHTML = '<option value="">Select recommendation</option>';

        options.forEach(optionValue => {
            const option = document.createElement('option');
            option.value = optionValue;
            option.textContent = optionValue;
            recommendationSelect.appendChild(option);
        });
    }

    function renderRecommendations() {
        list.innerHTML = '';
        hiddenInputs.innerHTML = '';

        recommendations.forEach((item, itemIndex) => {
            const badge = document.createElement('span');
            badge.className = 'badge me-1 mb-1';
            badge.style.cssText = 'color:#212529 !important; background-color:#f8f9fa !important; border:1px solid #ced4da !important;';
            badge.innerHTML = `${escapeHtml(item)} <button type="button" class="btn btn-sm p-0 ms-1 recommendation-remove" data-item-index="${itemIndex}" style="line-height:1; border:none; background:transparent; color:#dc3545 !important;">&times;</button>`;
            list.appendChild(badge);

            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = `system_findings[${rowIndex}][recommendations][]`;
            hiddenInput.value = item;
            hiddenInputs.appendChild(hiddenInput);
        });

        const removeButtons = list.querySelectorAll('.recommendation-remove');
        removeButtons.forEach(button => {
            button.addEventListener('click', function () {
                const itemIndex = parseInt(this.dataset.itemIndex, 10);
                if (!isNaN(itemIndex)) {
                    recommendations.splice(itemIndex, 1);
                    renderRecommendations();
                }
            });
        });
    }

    function addRecommendation() {
        const value = String(input.value || '').trim();
        if (!value) {
            return;
        }

        addRecommendationItem(value);

        input.value = '';
    }

    function addSelectedRecommendations() {
        const selectedValue = String(recommendationSelect.value || '').trim();
        if (!selectedValue) {
            return;
        }

        addRecommendationItem(selectedValue);
        recommendationSelect.value = '';
    }

    addSelectedButton.addEventListener('click', addSelectedRecommendations);
    addButton.addEventListener('click', addRecommendation);
    input.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            addRecommendation();
        }
    });

    if (subsystemSelect) {
        subsystemSelect.addEventListener('change', function () {
            refreshRecommendationDropdown(this.value || '');
        });
    }

    refreshRecommendationDropdown(initialSubsystemId || (subsystemSelect ? subsystemSelect.value : ''));

    renderRecommendations();
}

function removeSystemFindingRow(button) {
    const card = button.closest('.finding-card');
    if (!card) return;
    const container = card.parentElement;
    card.remove();
    // If no findings left, restore the empty-state message
    if (container && container.querySelectorAll('.finding-card').length === 0) {
        const emptyMsg = container.querySelector('[id^="system-empty-"]');
        if (emptyMsg) emptyMsg.style.display = '';
    }
}

// Severity order: critical (urgent) first, then high (H&S), then medium (value dep.), then low (non-urgent)
const severityOrder = { critical: 0, high: 1, medium: 2, low: 3 };

document.addEventListener('DOMContentLoaded', function () {
    if (Array.isArray(initialFindings) && initialFindings.length > 0) {
        // Sort by severity priority before rendering so most critical findings appear first
        const sorted = [...initialFindings].sort((a, b) => {
            const aOrder = severityOrder[a.severity] ?? 99;
            const bOrder = severityOrder[b.severity] ?? 99;
            return aOrder - bOrder;
        });

        sorted.forEach(finding => {
            if (!finding || !finding.system_id) {
                return;
            }

            addSystemFindingRow(finding.system_id, finding);
        });
    }
});
</script>
@endsection
