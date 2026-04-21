@if(($inspection->units_for_calculation ?? 1) > 1)
<div class="mb-4">
    <h6 class="text-primary border-bottom pb-2">
        <i class="mdi mdi-home-group me-2"></i>Per-Unit Cost Breakdown ({{ $inspection->units_for_calculation }} Units)
    </h6>
    <div class="table-responsive">
        <table class="table table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>Cost Component</th>
                    <th class="text-end">Annual Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>BDC</td>
                    <td class="text-end">${{ number_format($inspection->bdc_annual ?? 0, 2) }}</td>
                </tr>
                <tr>
                    <td>FRLC</td>
                    <td class="text-end">${{ number_format($inspection->frlc_annual ?? 0, 2) }}</td>
                </tr>
                <tr>
                    <td>FMC</td>
                    <td class="text-end">${{ number_format($inspection->fmc_annual ?? 0, 2) }}</td>
                </tr>
                <tr class="table-secondary">
                    <td><strong>TRC <small class="text-muted fw-normal">(BDC+FRLC+FMC)</small></strong></td>
                    <td class="text-end"><strong>${{ number_format($inspection->trc_annual ?? 0, 2) }}</strong></td>
                </tr>
            </tbody>
            <tfoot>
                @php
                    $isPerVisit = ($inspection->work_payment_cadence ?? 'full') === 'per_visit';
                    $visits = max(1, (int)($inspection->bdc_visits_per_year ?? 1));
                    $finalCharge = $isPerVisit
                        ? (float)($inspection->trc_per_visit ?? round(($inspection->trc_annual ?? 0) / $visits, 2))
                        : (float)($inspection->trc_annual ?? 0);
                @endphp
                <tr style="background:#198754;color:white;">
                    <td>
                        <strong>
                            Final Charge
                            <small style="font-weight:normal;opacity:.85;">({{ $isPerVisit ? 'Per Visit' : 'Lump Sum' }})</small>
                        </strong>
                    </td>
                    <td class="text-end"><strong>${{ number_format($finalCharge, 2) }}{{ $isPerVisit ? '/visit' : ' total' }}</strong></td>
                </tr>
            </tfoot>
        </table>
    </div>
    <p class="text-muted small mt-1">
        <strong>Final Charge</strong> = {{ $isPerVisit ? 'TRC ÷ '.$visits.' visits' : 'Full TRC paid at once (lump sum)' }}.
    </p>
</div>
@endif
