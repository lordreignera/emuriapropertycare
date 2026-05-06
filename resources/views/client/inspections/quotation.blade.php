@extends('client.layout')

@section('title', 'Review Quotation')

@section('content')
@php
    $status = (string) ($quotation->status ?? 'shared');
    $isLocked = (bool) ($isLocked ?? false);
    $preSelected = collect(old('approved_finding_ids', $approvedIds ?? []))->map(fn ($v) => (int) $v)->all();
    $initialLabour = 0;
    $initialMaterial = 0;
    foreach ($snapshotFindings as $finding) {
        if (in_array((int) ($finding['id'] ?? 0), $preSelected, true)) {
            $initialLabour += (float) ($finding['labour_cost'] ?? 0);
            $initialMaterial += (float) ($finding['material_cost'] ?? 0);
        }
    }
    $initialTotal = $initialLabour + $initialMaterial;
@endphp

<div class="row justify-content-center">
    <div class="col-xl-10">
        <div class="card mb-3 border-info">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">
                    <i class="mdi mdi-file-check-outline me-2"></i>Quotation Review
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <small class="text-muted d-block">Quote Number</small>
                        <strong>{{ $quotation->quote_number }}</strong>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">Status</small>
                        <span class="badge bg-primary text-uppercase">{{ str_replace('_', ' ', $status) }}</span>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">Valid Until</small>
                        <strong>{{ optional($quotation->valid_until)->format('M d, Y') ?? 'N/A' }}</strong>
                    </div>
                </div>
                <hr>
                @if($isLocked)
                    <div class="alert alert-success mb-0">
                        You already approved this quotation. Admin will now finalize the report and agreement using your approved findings.
                    </div>
                @else
                    <div class="alert alert-warning mb-0">
                        <strong>Approval Notice:</strong> The findings you select and approve below are the exact work items that will be charged.
                        Labour and materials in your selected findings form your approved project cost.
                    </div>
                @endif
            </div>
        </div>

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

        <form method="POST" action="{{ route('client.inspections.quotation.respond', $inspection->id) }}" id="quotation-form">
            @csrf

            <div class="card mb-3 border-0 shadow-sm">
                <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2" style="background:#f8faff;border-radius:.5rem;">
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-success btn-sm fw-semibold px-3" id="select-all-btn" {{ $isLocked ? 'disabled' : '' }}>
                            <i class="mdi mdi-check-all me-1"></i>Select All
                        </button>
                        <button type="button" class="btn btn-danger btn-sm fw-semibold px-3" id="clear-all-btn" {{ $isLocked ? 'disabled' : '' }}>
                            <i class="mdi mdi-close-box-multiple-outline me-1"></i>Clear All
                        </button>
                    </div>
                    <div class="small text-muted">
                        Selected Findings: <strong id="selected-count" class="text-primary fs-6">0</strong>
                    </div>
                </div>
            </div>

            @forelse($snapshotFindings as $finding)
                @php
                    $findingId = (int) ($finding['id'] ?? 0);
                    $isChecked = in_array($findingId, $preSelected, true);
                    $labourCost = (float) ($finding['labour_cost'] ?? 0);
                    $materialCost = (float) ($finding['material_cost'] ?? 0);
                    $severity = (string) ($finding['priority'] ?? '2');
                    $severityLabel = $severity === '1' ? 'High' : ($severity === '2' ? 'Medium' : 'Low');
                    $badgeClass = $severity === '1' ? 'bg-danger' : ($severity === '2' ? 'bg-warning text-dark' : 'bg-success');
                @endphp
                <div class="card mb-3 finding-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                            <div>
                                <div class="form-check">
                                    <input
                                        class="form-check-input finding-checkbox"
                                        type="checkbox"
                                        name="approved_finding_ids[]"
                                        value="{{ $findingId }}"
                                        id="finding-{{ $findingId }}"
                                        data-labour="{{ number_format($labourCost, 2, '.', '') }}"
                                        data-material="{{ number_format($materialCost, 2, '.', '') }}"
                                        {{ $isChecked ? 'checked' : '' }}
                                        {{ $isLocked ? 'disabled' : '' }}
                                    >
                                    <label class="form-check-label fw-semibold" for="finding-{{ $findingId }}">
                                        {{ $finding['task_question'] ?? 'Untitled Finding' }}
                                    </label>
                                </div>
                                <div class="small text-muted mt-1">
                                    <span class="badge {{ $badgeClass }} me-1">{{ $severityLabel }}</span>
                                    <span class="me-2">Category: {{ $finding['category'] ?? 'General' }}</span>
                                    <span class="me-2">Labour Hours: {{ number_format((float) ($finding['labour_hours'] ?? 0), 2) }}</span>
                                </div>
                                @if(!empty($finding['notes']))
                                    <p class="small mb-0 mt-2 text-muted">{{ $finding['notes'] }}</p>
                                @endif

                                @if(!empty($finding['issue_description']))
                                    <p class="small mb-0 mt-2"><strong>Issue Description:</strong> {{ $finding['issue_description'] }}</p>
                                @endif

                                @if(!empty($finding['recommendations']) && is_array($finding['recommendations']))
                                    <div class="small mb-0 mt-2">
                                        <strong>Recommendations:</strong>
                                        <ul class="mb-0 mt-1 ps-3">
                                            @foreach($finding['recommendations'] as $recItem)
                                                <li>{{ $recItem }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                @if(!empty($finding['recommendation']))
                                    <p class="small mb-0 mt-2"><strong>Recommendation:</strong> {{ $finding['recommendation'] }}</p>
                                @endif

                                @if(!empty($finding['recommendation_details']))
                                    <p class="small mb-0 mt-2"><strong>Recommendation Description:</strong> {{ $finding['recommendation_details'] }}</p>
                                @endif

                                @php
                                    $findingPhotos = collect($finding['finding_photos'] ?? [])
                                        ->filter(fn($p) => is_string($p) && trim($p) !== '')
                                        ->values();
                                @endphp
                                @if($findingPhotos->isNotEmpty())
                                    <div class="mt-3">
                                        <div class="small fw-semibold mb-1">Attached Photos</div>
                                        <div class="d-flex flex-wrap gap-2">
                                            @foreach($findingPhotos as $fp)
                                                <a href="{{ $inspection->getStorageUrl($fp) }}" target="_blank" title="View photo">
                                                    <img src="{{ $inspection->getStorageUrl($fp) }}" alt="Finding photo" style="height:72px;width:72px;object-fit:cover;border-radius:6px;border:1px solid #dee2e6;">
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                @php
                                    $materials = collect($finding['materials'] ?? [])->filter(fn($m) => !empty($m['material_name']))->values();
                                @endphp
                                @if($materials->isNotEmpty())
                                    <div class="table-responsive mt-3">
                                        <table class="table table-sm table-bordered mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Material</th>
                                                    <th class="text-end">Qty</th>
                                                    <th>Unit</th>
                                                    <th class="text-end">Unit Cost</th>
                                                    <th class="text-end">Line Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($materials as $material)
                                                    <tr>
                                                        <td>
                                                            {{ $material['material_name'] ?? '-' }}
                                                            @if(!empty($material['notes']))
                                                                <div class="small text-muted">{{ $material['notes'] }}</div>
                                                            @endif
                                                        </td>
                                                        <td class="text-end">{{ number_format((float) ($material['quantity'] ?? 0), 2) }}</td>
                                                        <td>{{ $material['unit'] ?? 'ea' }}</td>
                                                        <td class="text-end">${{ number_format((float) ($material['unit_cost'] ?? 0), 2) }}</td>
                                                        <td class="text-end">${{ number_format((float) ($material['line_total'] ?? 0), 2) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <p class="small mb-0 mt-2 text-muted">No material lines assigned for this finding.</p>
                                @endif
                            </div>
                            <div class="text-end">
                                <div><small class="text-muted">Labour</small> <strong>${{ number_format($labourCost, 2) }}</strong></div>
                                <div><small class="text-muted">Material</small> <strong>${{ number_format($materialCost, 2) }}</strong></div>
                                <div class="border-top mt-1 pt-1"><small class="text-muted">Subtotal</small> <strong>${{ number_format($labourCost + $materialCost, 2) }}</strong></div>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="alert alert-warning">No findings available in this quotation snapshot.</div>
            @endforelse

            <div class="card mb-3 border-primary">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="mdi mdi-calculator-variant-outline me-1"></i>Selected Pricing Summary</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-4">
                            <small class="text-muted d-block">Labour</small>
                            <strong id="sum-labour">${{ number_format($initialLabour, 2) }}</strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">Materials</small>
                            <strong id="sum-material">${{ number_format($initialMaterial, 2) }}</strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">Total</small>
                            <strong class="text-success" id="sum-total">${{ number_format($initialTotal, 2) }}</strong>
                        </div>
                    </div>
                    <div class="alert alert-info mt-3 mb-0 py-2">
                        <strong>By clicking Approve Quotation:</strong> you confirm the selected findings above as the approved and chargeable scope of work.
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    <label for="client_notes" class="form-label fw-semibold">Notes for Admin (optional)</label>
                    <textarea class="form-control" name="client_notes" id="client_notes" rows="4" placeholder="Add any priorities, budget constraints, or scheduling notes..." {{ $isLocked ? 'readonly' : '' }}>{{ old('client_notes', $quotation->client_notes) }}</textarea>
                </div>
            </div>

            <div class="d-flex justify-content-between mb-4">
                <a href="{{ route('client.inspections.index') }}" class="btn btn-light">Back to Inspections</a>
                @if(!$isLocked)
                    <button type="submit" class="btn btn-primary" id="submit-quotation-btn">
                        <i class="mdi mdi-check-decagram-outline me-1"></i>Approve Quotation
                    </button>
                @else
                    <span class="btn btn-success disabled" aria-disabled="true">
                        <i class="mdi mdi-check-decagram-outline me-1"></i>Quotation Approved
                    </span>
                @endif
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const checkboxes = Array.from(document.querySelectorAll('.finding-checkbox'));
    const selectAllBtn = document.getElementById('select-all-btn');
    const clearAllBtn = document.getElementById('clear-all-btn');
    const selectedCountEl = document.getElementById('selected-count');
    const labourEl = document.getElementById('sum-labour');
    const materialEl = document.getElementById('sum-material');
    const totalEl = document.getElementById('sum-total');
    const submitBtn = document.getElementById('submit-quotation-btn');

    function formatCurrency(amount) {
        return '$' + amount.toFixed(2);
    }

    function refreshSummary() {
        let labour = 0;
        let material = 0;
        let count = 0;

        checkboxes.forEach((checkbox) => {
            if (checkbox.checked) {
                count += 1;
                labour += Number(checkbox.dataset.labour || 0);
                material += Number(checkbox.dataset.material || 0);
            }
        });

        const total = labour + material;

        selectedCountEl.textContent = String(count);
        labourEl.textContent = formatCurrency(labour);
        materialEl.textContent = formatCurrency(material);
        totalEl.textContent = formatCurrency(total);

        if (submitBtn) {
            submitBtn.disabled = count === 0;
        }
    }

    selectAllBtn?.addEventListener('click', () => {
        checkboxes.forEach((checkbox) => { checkbox.checked = true; });
        refreshSummary();
    });

    clearAllBtn?.addEventListener('click', () => {
        checkboxes.forEach((checkbox) => { checkbox.checked = false; });
        refreshSummary();
    });

    checkboxes.forEach((checkbox) => {
        checkbox.addEventListener('change', refreshSummary);
    });

    refreshSummary();
})();
</script>
@endpush
