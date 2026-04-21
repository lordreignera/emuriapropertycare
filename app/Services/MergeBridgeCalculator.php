<?php

namespace App\Services;

use App\Models\Inspection;
use App\Models\Property;
use App\Models\PHARFinding;
use App\Models\BDCSetting;

class MergeBridgeCalculator
{
    protected $bdcCalculator;

    public function __construct(BDCCalculator $bdcCalculator)
    {
        $this->bdcCalculator = $bdcCalculator;
    }

    /**
     * Calculate complete pricing for an inspection
     * 
     * @param Inspection $inspection
     * @return array Complete calculation breakdown
     */
    public function calculate(Inspection $inspection): array
    {
        $property = $inspection->property;
        
        // Ensure property exists
        if (!$property) {
            throw new \Exception("Inspection must have an associated property.");
        }
        
        // Step 1: Get BDC (Base Deployment Cost)
        // Use travel-based BDC when travel inputs are present; fall back to labour-based.
        $bdcParams = [];
        if ($inspection->bdc_distance_km !== null && $inspection->bdc_time_minutes !== null) {
            $bdcParams['travel_distance_km']  = (float) $inspection->bdc_distance_km;
            $bdcParams['travel_time_minutes'] = (float) $inspection->bdc_time_minutes;
            if ($inspection->bdc_visits_per_year  !== null) $bdcParams['visits_per_year']  = (float) $inspection->bdc_visits_per_year;
            if ($inspection->bdc_rate_per_km      !== null) $bdcParams['rate_per_km']      = (float) $inspection->bdc_rate_per_km;
            if ($inspection->bdc_rate_per_minute  !== null) $bdcParams['rate_per_minute']  = (float) $inspection->bdc_rate_per_minute;
        } else {
            if ($inspection->bdc_visits_per_year  !== null) $bdcParams['visits_per_year']  = (float) $inspection->bdc_visits_per_year;
            if ($inspection->estimated_task_hours !== null) $bdcParams['hours_per_visit']  = (float) $inspection->estimated_task_hours;
        }

        $bdcResult = empty($bdcParams)
            ? $this->bdcCalculator->calculate($property)
            : $this->bdcCalculator->calculateWithParams($bdcParams);
        $bdcAnnual        = $bdcResult['bdc_annual'];
        $bdcMonthly       = $bdcResult['bdc_monthly'];
        $labourHourlyRate = $inspection->labour_hourly_rate ?? ($bdcResult['loaded_hourly_rate'] ?? 0);

        // Step 2: FRLC & FMC from findings and materials
        $findings        = PHARFinding::where('inspection_id', $inspection->id)->get();
        $frlcCalculation = $this->calculateFRLC($findings, $labourHourlyRate);
        $fmcCalculation  = $this->calculateFMC($inspection);

        // Step 3: TRC (Total Remediation Cost)
        $trcAnnual   = $bdcAnnual + $frlcCalculation['annual'] + $fmcCalculation['annual'];
        $trcMonthly  = $trcAnnual;
        $visitsPerYear = max(1, (float) ($bdcResult['visits_per_year'] ?? $inspection->bdc_visits_per_year ?? 1));
        $trcPerVisit = round($trcAnnual / $visitsPerYear, 2);

        // Step 4: Final charge depends on customer payment choice.
        // 'per_visit' → client pays per visit (cost is per visit, total is annual TRC)
        // 'full'      → client pays full TRC at once
        // MergeBridgeCalculator always computes both; the locked amount is recorded at payment time.
        $paymentMode = ($inspection->work_payment_cadence === 'per_visit') ? 'per_visit' : 'lump_sum';
        $finalCharge = ($paymentMode === 'per_visit') ? $trcPerVisit : $trcAnnual;

        // Step 5: Per-Unit Breakdown (multi-unit properties)
        $perUnitBreakdown = $this->calculatePerUnitBreakdown(
            $property,
            $bdcAnnual,
            $frlcCalculation['annual'],
            $fmcCalculation['annual'],
            $trcAnnual,
            $trcMonthly
        );

        $bdcPerVisit = $bdcResult['bdc_per_visit'] ?? ($bdcResult['visits_per_year'] > 0
            ? round($bdcAnnual / $bdcResult['visits_per_year'], 2)
            : 0);

        return [
            // BDC
            'bdc_per_visit'       => round($bdcPerVisit, 2),
            'bdc_annual'          => round($bdcAnnual, 2),
            'bdc_monthly'         => round($bdcMonthly, 2),  // derived; not persisted
            'labour_hourly_rate'  => round($labourHourlyRate, 2),

            // FRLC
            'frlc_annual'      => round($frlcCalculation['annual'], 2),
            'frlc_monthly'     => round($frlcCalculation['monthly'], 2),
            'frlc_total_hours' => round($frlcCalculation['total_hours'], 2),

            // FMC
            'fmc_annual'  => round($fmcCalculation['annual'], 2),
            'fmc_monthly' => round($fmcCalculation['monthly'], 2),

            // TRC (always computed; both views available)
            'trc_annual'     => round($trcAnnual, 2),
            'trc_monthly'    => round($trcMonthly, 2),
            'trc_per_visit'  => round($trcPerVisit, 2),

            // Payment mode & final charge
            'payment_mode'  => $paymentMode,
            'final_charge'  => round($finalCharge, 2),   // what the client actually owes
            'arp_monthly'   => round($trcMonthly, 2),    // kept for backward compat
            'arp_annual'    => round($trcAnnual, 2),

            // Per-Unit Breakdown
            'units_for_calculation'   => $perUnitBreakdown['units'],
            'bdc_per_unit_annual'     => round($perUnitBreakdown['bdc_per_unit'], 2),
            'frlc_per_unit_annual'    => round($perUnitBreakdown['frlc_per_unit'], 2),
            'fmc_per_unit_annual'     => round($perUnitBreakdown['fmc_per_unit'], 2),
            'trc_per_unit_annual'     => round($perUnitBreakdown['trc_per_unit'], 2),
            'final_monthly_per_unit'  => round($perUnitBreakdown['final_monthly_per_unit'], 2),
        ];
    }

    /**
     * Calculate FRLC (Findings Remediation Labour Cost)
     */
    protected function calculateFRLC($findings, $hourlyRate): array
    {
        $totalHours = $findings->sum('labour_hours');
        $annualCost = $totalHours * $hourlyRate;
        
        return [
            'total_hours' => $totalHours,
            'annual' => $annualCost,
            'monthly' => $annualCost,
        ];
    }

    /**
     * Calculate FMC (Findings Material Cost)
     */
    /**
     * Calculate FMC (Findings Material Cost) from inspection materials
     */
    protected function calculateFMC(Inspection $inspection): array
    {
        $materials = \App\Models\InspectionMaterial::where('inspection_id', $inspection->id)->get();
        $totalMaterialCost = $materials->sum('line_total');
        
        return [
            'annual' => $totalMaterialCost,
            'monthly' => $totalMaterialCost,
        ];
    }

    /**
     * Calculate per-unit breakdown
     */
    protected function calculatePerUnitBreakdown(
        Property $property,
        float $bdcAnnual,
        float $frlcAnnual,
        float $fmcAnnual,
        float $trcAnnual,
        float $finalMonthly
    ): array {
        // Determine unit count
        $units = 1;
        if ($property->type === 'residential' && $property->residential_units) {
            $units = $property->residential_units;
        } elseif ($property->type === 'commercial' && $property->number_of_units) {
            $units = $property->number_of_units;
        }
        
        $units = max(1, $units); // At least 1
        
        return [
            'units' => $units,
            'bdc_per_unit' => $bdcAnnual / $units,
            'frlc_per_unit' => $frlcAnnual / $units,
            'fmc_per_unit' => $fmcAnnual / $units,
            'trc_per_unit' => $trcAnnual / $units,
            'final_monthly_per_unit' => $finalMonthly / $units,
        ];
    }

    /**
     * Save calculation results to inspection
     */
    public function saveToInspection(Inspection $inspection, array $calculation): void
    {
        $inspection->update([
            'bdc_per_visit'          => $calculation['bdc_per_visit'],
            'bdc_annual'             => $calculation['bdc_annual'],
            // bdc_monthly equals bdc_annual — no division by 12
            'labour_hourly_rate'     => $calculation['labour_hourly_rate'],
            'frlc_annual'            => $calculation['frlc_annual'],
            'frlc_monthly'           => $calculation['frlc_monthly'],
            'fmc_annual'             => $calculation['fmc_annual'],
            'fmc_monthly'            => $calculation['fmc_monthly'],
            'trc_annual'                  => $calculation['trc_annual'],
            'trc_monthly'                 => $calculation['trc_monthly'],
            'trc_per_visit'               => $calculation['trc_per_visit'],
            'arp_monthly'                 => $calculation['arp_monthly'],
            'scientific_final_monthly'    => $calculation['trc_monthly'],
            'scientific_final_annual'     => $calculation['trc_annual'],
            'arp_equivalent_final'        => $calculation['trc_annual'],
            'base_package_price_snapshot' => $calculation['arp_monthly'],
            'units_for_calculation'       => $calculation['units_for_calculation'],
            'bdc_per_unit_annual'    => $calculation['bdc_per_unit_annual'],
            'frlc_per_unit_annual'   => $calculation['frlc_per_unit_annual'],
            'fmc_per_unit_annual'    => $calculation['fmc_per_unit_annual'],
            'trc_per_unit_annual'    => $calculation['trc_per_unit_annual'],
            'final_monthly_per_unit' => $calculation['final_monthly_per_unit'],
        ]);
    }
}
