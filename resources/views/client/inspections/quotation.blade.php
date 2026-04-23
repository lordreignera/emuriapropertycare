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
    $initialBdc = (float) ($inspection->bdc_annual ?? 0);
    $initialTotal = $initialLabour + $initialMaterial + $initialBdc;
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
                    <p class="mb-0 text-muted">
                        Select the findings you want approved for current remediation. Pricing updates based on your selected items.
                    </p>
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
                        <div class="col-md-3">
                            <small class="text-muted d-block">Labour</small>
                            <strong id="sum-labour">${{ number_format($initialLabour, 2) }}</strong>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">Materials</small>
                            <strong id="sum-material">${{ number_format($initialMaterial, 2) }}</strong>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">BDC</small>
                            <strong id="sum-bdc">${{ number_format($initialBdc, 2) }}</strong>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">Total</small>
                            <strong class="text-success" id="sum-total">${{ number_format($initialTotal, 2) }}</strong>
                        </div>
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
                        <i class="mdi mdi-send-check-outline me-1"></i>Submit Selected Findings
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
    const bdcEl = document.getElementById('sum-bdc');
    const totalEl = document.getElementById('sum-total');
    const submitBtn = document.getElementById('submit-quotation-btn');

    const bdcBase = Number({{ json_encode((float) ($inspection->bdc_annual ?? 0)) }} || 0);

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

        const total = labour + material + bdcBase;

        selectedCountEl.textContent = String(count);
        labourEl.textContent = formatCurrency(labour);
        materialEl.textContent = formatCurrency(material);
        bdcEl.textContent = formatCurrency(bdcBase);
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
