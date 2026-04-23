<?php

namespace App\Console\Commands;

use App\Models\Inspection;
use App\Models\InspectionQuotation;
use App\Services\BDCCalculator;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BackfillApprovedQuotationPricing extends Command
{
    /**
     * One-time repair utility for legacy approved quotations.
     */
    protected $signature = 'quotations:backfill-approved-pricing
                            {--inspection-id= : Limit to a single inspection ID}
                            {--quote-id= : Limit to a single quotation ID}
                            {--write : Persist changes (default is dry-run)}';

    protected $description = 'Backfill approved quotation totals and synced inspection pricing from approved findings scope';

    public function handle(): int
    {
        $inspectionId = $this->option('inspection-id');
        $quoteId = $this->option('quote-id');
        $write = (bool) $this->option('write');

        $query = InspectionQuotation::query()
            ->where('status', 'approved')
            ->with(['inspection.pharFindings']);

        if (!empty($inspectionId)) {
            $query->where('inspection_id', (int) $inspectionId);
        }

        if (!empty($quoteId)) {
            $query->where('id', (int) $quoteId);
        }

        $quotations = $query->orderBy('id')->get();

        if ($quotations->isEmpty()) {
            $this->warn('No approved quotations found for the given filter.');
            return self::SUCCESS;
        }

        $this->info(sprintf(
            '%s mode: processing %d approved quotation(s).',
            $write ? 'WRITE' : 'DRY-RUN',
            $quotations->count()
        ));

        $changed = 0;
        $rows = [];

        foreach ($quotations as $quotation) {
            $inspection = $quotation->inspection;
            if (!$inspection instanceof Inspection) {
                $rows[] = [$quotation->id, $quotation->inspection_id, 'SKIP', 'Inspection missing'];
                continue;
            }

            [$repairedSnapshot, $approvedLabour, $approvedMaterial] = $this->computeApprovedScopeFromSnapshot($inspection, $quotation);

            // Recalculate visits and BDC from approved labour hours (1 visit = 11 working hours)
            $approvedIds = collect($quotation->approved_finding_ids ?? [])->map(fn($id) => (int) $id);
            $approvedSnapshot = $repairedSnapshot
                ->filter(fn($f) => $approvedIds->contains((int) ($f['id'] ?? 0)))
                ->values();

            $approvedLabourHours = round((float) $approvedSnapshot->sum(fn($f) => (float) ($f['labour_hours'] ?? 0)), 2);
            if ($approvedLabourHours <= 0) {
                $approvedLabourHours = round((float) ($approvedLabour / (float) ($inspection->labour_hourly_rate ?? 165)), 2);
            }
            $approvedVisits = max(1, (int) ceil($approvedLabourHours / 11));

            $bdcCalc = new BDCCalculator();
            $bdcResult = $bdcCalc->calculateWithParams([
                'travel_distance_km'  => (float) ($inspection->bdc_distance_km ?? null),
                'travel_time_minutes' => (float) ($inspection->bdc_time_minutes ?? null),
                'visits_per_year'     => (float) $approvedVisits,
                'rate_per_km'         => (float) ($inspection->bdc_rate_per_km ?? 1.50),
                'rate_per_minute'     => (float) ($inspection->bdc_rate_per_minute ?? 1.65),
            ]);
            $approvedBdc = round((float) ($bdcResult['bdc_annual'] ?? 0), 2);
            
            $approvedTotal = round($approvedLabour + $approvedMaterial + $approvedBdc, 2);

            $quotationNeedsUpdate =
                round((float) ($quotation->approved_labour_cost ?? 0), 2) !== $approvedLabour ||
                round((float) ($quotation->approved_material_cost ?? 0), 2) !== $approvedMaterial ||
                round((float) ($quotation->approved_bdc_cost ?? 0), 2) !== $approvedBdc ||
                round((float) ($quotation->approved_total ?? 0), 2) !== $approvedTotal ||
                $this->snapshotChanged($quotation->findings_snapshot ?? [], $repairedSnapshot->all());

            $inspectionNeedsUpdate =
                round((float) ($inspection->frlc_annual ?? 0), 2) !== $approvedLabour ||
                round((float) ($inspection->fmc_annual ?? 0), 2) !== $approvedMaterial ||
                round((float) ($inspection->bdc_annual ?? 0), 2) !== $approvedBdc ||
                round((float) ($inspection->bdc_visits_per_year ?? 0), 2) !== $approvedVisits ||
                round((float) ($inspection->estimated_task_hours ?? 0), 2) !== $approvedLabourHours ||
                round((float) ($inspection->trc_annual ?? 0), 2) !== $approvedTotal ||
                round((float) ($inspection->trc_monthly ?? 0), 2) !== $approvedTotal ||
                round((float) ($inspection->trc_per_visit ?? 0), 2) !== round($approvedTotal / $approvedVisits, 2) ||
                round((float) ($inspection->arp_monthly ?? 0), 2) !== $approvedTotal;

            if (!($quotationNeedsUpdate || $inspectionNeedsUpdate)) {
                $rows[] = [$quotation->id, $inspection->id, 'OK', 'Already consistent'];
                continue;
            }

            $changed++;

            if ($write) {
                DB::transaction(function () use (
                    $quotation,
                    $inspection,
                    $repairedSnapshot,
                    $approvedLabour,
                    $approvedMaterial,
                    $approvedBdc,
                    $approvedTotal,
                    $approvedVisits,
                    $approvedLabourHours
                ): void {
                    $quotation->update([
                        'findings_snapshot' => $repairedSnapshot->all(),
                        'approved_labour_cost' => $approvedLabour,
                        'approved_material_cost' => $approvedMaterial,
                        'approved_bdc_cost' => $approvedBdc,
                        'approved_total' => $approvedTotal,
                    ]);

                    $inspection->update([
                        'frlc_annual' => $approvedLabour,
                        'fmc_annual' => $approvedMaterial,
                        'bdc_annual' => $approvedBdc,
                        'bdc_visits_per_year' => $approvedVisits,
                        'estimated_task_hours' => $approvedLabourHours,
                        'trc_annual' => $approvedTotal,
                        'trc_monthly' => $approvedTotal,
                        'trc_per_visit' => round($approvedTotal / $approvedVisits, 2),
                        'arp_monthly' => $approvedTotal,
                        'scientific_final_monthly' => $approvedTotal,
                        'scientific_final_annual' => $approvedTotal,
                        'arp_equivalent_final' => $approvedTotal,
                        'base_package_price_snapshot' => $approvedTotal,
                    ]);
                });
            }

            $rows[] = [
                $quotation->id,
                $inspection->id,
                $write ? 'UPDATED' : 'WOULD_UPDATE',
                sprintf('Labour %.2f | Material %.2f | BDC %.2f | Total %.2f', $approvedLabour, $approvedMaterial, $approvedBdc, $approvedTotal),
            ];
        }

        $this->table(['Quote ID', 'Inspection ID', 'Status', 'Details'], $rows);

        if ($write) {
            $this->info("Backfill completed. Updated {$changed} quotation(s).");
        } else {
            $this->warn("Dry-run finished. {$changed} quotation(s) would be updated. Re-run with --write to persist.");
        }

        return self::SUCCESS;
    }

    /**
     * @return array{0: Collection<int, array<string, mixed>>, 1: float, 2: float}
     */
    private function computeApprovedScopeFromSnapshot(Inspection $inspection, InspectionQuotation $quotation): array
    {
        $approvedIds = collect($quotation->approved_finding_ids ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values();

        $pharMaterialById = $inspection->pharFindings
            ->mapWithKeys(fn ($f) => [(int) $f->id => (float) ($f->material_cost ?? 0)]);

        $inspectionFindings = collect($inspection->findings ?? [])->values();

        $repairedSnapshot = collect($quotation->findings_snapshot ?? [])->values()->map(
            function ($finding, $index) use ($pharMaterialById, $inspectionFindings) {
                if (!is_array($finding)) {
                    return [];
                }

                $materialCost = (float) ($finding['material_cost'] ?? 0);

                if ($materialCost <= 0) {
                    $findingId = (int) ($finding['id'] ?? 0);
                    $materialCost = (float) ($pharMaterialById->get($findingId, 0));
                }

                if ($materialCost <= 0) {
                    $jsonFinding = $inspectionFindings->get($index, []);
                    $materialCost = (float) collect($jsonFinding['phar_materials'] ?? [])
                        ->sum(fn ($m) => (float) ($m['line_total'] ?? 0));
                }

                $finding['material_cost'] = round($materialCost, 2);
                return $finding;
            }
        )->values();

        $approvedFindings = $repairedSnapshot
            ->filter(fn ($f) => $approvedIds->contains((int) ($f['id'] ?? 0)))
            ->values();

        $approvedLabour = round((float) $approvedFindings->sum(fn ($f) => (float) ($f['labour_cost'] ?? 0)), 2);
        $approvedMaterial = round((float) $approvedFindings->sum(fn ($f) => (float) ($f['material_cost'] ?? 0)), 2);

        if ($approvedLabour <= 0) {
            $approvedLabour = round((float) ($quotation->approved_labour_cost ?? 0), 2);
        }

        if ($approvedMaterial <= 0 && (float) ($quotation->approved_material_cost ?? 0) > 0) {
            $approvedMaterial = round((float) $quotation->approved_material_cost, 2);
        }

        return [$repairedSnapshot, $approvedLabour, $approvedMaterial];
    }

    /**
     * Compare snapshots after normalization to avoid noisy writes.
     *
     * @param array<int, mixed> $original
     * @param array<int, mixed> $repaired
     */
    private function snapshotChanged(array $original, array $repaired): bool
    {
        return json_encode($original) !== json_encode($repaired);
    }
}
