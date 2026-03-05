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
        $bdcParams = [];
        if ($inspection->bdc_visits_per_year !== null) {
            $bdcParams['visits_per_year'] = (float) $inspection->bdc_visits_per_year;
        }
        if ($inspection->estimated_task_hours !== null) {
            $bdcParams['hours_per_visit'] = (float) $inspection->estimated_task_hours;
        }

        $bdcResult = empty($bdcParams)
            ? $this->bdcCalculator->calculate($property)
            : $this->bdcCalculator->calculateWithParams($bdcParams);
        $bdcAnnual = $bdcResult['bdc_annual'];
        $bdcMonthly = $bdcResult['bdc_monthly'];
        $labourHourlyRate = $inspection->labour_hourly_rate ?? $bdcResult['loaded_hourly_rate'];
        
        // Step 2: Calculate FRLC & FMC from findings and materials
        $findings = PHARFinding::where('inspection_id', $inspection->id)->get();
        $frlcCalculation = $this->calculateFRLC($findings, $labourHourlyRate);
        $fmcCalculation = $this->calculateFMC($inspection);
        
        // Step 3: Calculate TRC (Total Remediation Cost)
        $trcAnnual = $bdcAnnual + $frlcCalculation['annual'] + $fmcCalculation['annual'];
        $trcMonthly = $trcAnnual / 12;
        
        // Step 4: Calculate ARP (Annual Recurring Price - monthly)
        $arpMonthly = $trcMonthly;
        
        // Step 5: Map CPI Score to Condition Score (0-100)
        $conditionScore = $this->mapCPItoConditionScore($inspection->cpi_total_score ?? 0);
        
        // Step 6: Dual-Gate Tier Assignment
        $tierScore = $this->getTierFromConditionScore($conditionScore);
        $tierARP = $this->getTierFromARP($arpMonthly, $inspection);
        $tierFinal = $this->selectFinalTier($tierScore, $tierARP);
        
        // Step 7: Get multiplier for final tier
        $multiplierFinal = $this->getMultiplierForTier($tierFinal);
        
        // Step 8: Apply multiplier
        $arpEquivalentFinal = $arpMonthly * $multiplierFinal;
        
        // Step 9: Get base package price (floor) - Use selected package from inspection
        $basePackagePrice = $this->getBasePackagePrice($inspection);
        
        // Step 10: Scientific Final Monthly (apply floor)
        $scientificFinalMonthly = max($arpEquivalentFinal, $basePackagePrice);
        $scientificFinalAnnual = $scientificFinalMonthly * 12;
        
        // Step 11: Per-Unit Breakdown (if multi-unit property)
        $perUnitBreakdown = $this->calculatePerUnitBreakdown(
            $property,
            $bdcAnnual,
            $frlcCalculation['annual'],
            $fmcCalculation['annual'],
            $trcAnnual,
            $scientificFinalMonthly
        );
        
        return [
            // BDC
            'bdc_annual' => round($bdcAnnual, 2),
            'bdc_monthly' => round($bdcMonthly, 2),
            'labour_hourly_rate' => round($labourHourlyRate, 2),
            
            // FRLC
            'frlc_annual' => round($frlcCalculation['annual'], 2),
            'frlc_monthly' => round($frlcCalculation['monthly'], 2),
            'frlc_total_hours' => round($frlcCalculation['total_hours'], 2),
            
            // FMC
            'fmc_annual' => round($fmcCalculation['annual'], 2),
            'fmc_monthly' => round($fmcCalculation['monthly'], 2),
            
            // TRC
            'trc_annual' => round($trcAnnual, 2),
            'trc_monthly' => round($trcMonthly, 2),
            
            // ARP
            'arp_monthly' => round($arpMonthly, 2),
            
            // Condition & Tiers
            'condition_score' => $conditionScore,
            'tier_score' => $tierScore,
            'tier_arp' => $tierARP,
            'tier_final' => $tierFinal,
            
            // Multiplier & Final
            'multiplier_final' => round($multiplierFinal, 2),
            'arp_equivalent_final' => round($arpEquivalentFinal, 2),
            'base_package_price' => round($basePackagePrice, 2),
            'scientific_final_monthly' => round($scientificFinalMonthly, 2),
            'scientific_final_annual' => round($scientificFinalAnnual, 2),
            
            // Per-Unit Breakdown
            'units_for_calculation' => $perUnitBreakdown['units'],
            'bdc_per_unit_annual' => round($perUnitBreakdown['bdc_per_unit'], 2),
            'frlc_per_unit_annual' => round($perUnitBreakdown['frlc_per_unit'], 2),
            'fmc_per_unit_annual' => round($perUnitBreakdown['fmc_per_unit'], 2),
            'trc_per_unit_annual' => round($perUnitBreakdown['trc_per_unit'], 2),
            'final_monthly_per_unit' => round($perUnitBreakdown['final_monthly_per_unit'], 2),
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
            'monthly' => $annualCost / 12,
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
            'monthly' => $totalMaterialCost / 12,
        ];
    }

    /**
     * Map CPI Score (0-27) to Condition Score (0-100)
     */
    protected function mapCPItoConditionScore(int $cpiScore): int
    {
        if ($cpiScore <= 2) return 95;  // CPI-0: Excellent (90-100)
        if ($cpiScore <= 5) return 82;  // CPI-1: Good (75-89)
        if ($cpiScore <= 8) return 67;  // CPI-2: Fair (60-74)
        if ($cpiScore <= 11) return 50; // CPI-3: Poor (40-59)
        return 30;                       // CPI-4: Critical (0-39)
    }

    /**
     * Determine tier from condition score (Gate 1)
     */
    protected function getTierFromConditionScore(int $conditionScore): string
    {
        if ($conditionScore >= 90) return 'Essentials';
        if ($conditionScore >= 75) return 'Essentials';
        if ($conditionScore >= 60) return 'White-Glove';
        if ($conditionScore >= 40) return 'White-Glove';
        return 'Critical Care';
    }

    /**
     * Determine tier from ARP (Gate 2 - cost pressure)
     */
    protected function getTierFromARP(float $arpMonthly, Inspection $inspection): string
    {
        $propertyTypeId = $this->resolvePropertyTypeId($this->resolveInspectionPropertyTypeCode($inspection));
        $selectedPackageName = null;

        if (!empty($inspection->service_package_name)) {
            $selectedPackageName = $inspection->service_package_name;
        }

        $prices = \App\Models\PricingPackage::query()
            ->where('is_active', true)
            ->with(['packagePricing' => function ($query) use ($propertyTypeId) {
                $query->where('is_active', true);
                if ($propertyTypeId) {
                    $query->where('property_type_id', $propertyTypeId);
                }
            }])
            ->orderBy('sort_order')
            ->get()
            ->mapWithKeys(function ($package) use (&$selectedPackageName, $inspection, $propertyTypeId) {
                $price = null;

                if ($propertyTypeId) {
                    $price = $package->getPriceForPropertyType($propertyTypeId);
                }

                if ($price === null) {
                    $price = optional($package->packagePricing->first())->base_monthly_price;
                }

                if ((int) $package->id === (int) $inspection->service_package_id) {
                    $selectedPackageName = $package->package_name;
                }

                return [$package->package_name => (float) ($price ?? 0)];
            })
            ->toArray();

        if (empty($prices)) {
            return 'Essentials';
        }

        asort($prices);
        $tier = array_key_first($prices) ?: 'Essentials';
        foreach ($prices as $packageName => $price) {
            if ($arpMonthly >= $price) {
                $tier = $packageName;
            }
        }

        if ($selectedPackageName) {
            $selectedPackageThreshold = $prices[$selectedPackageName] ?? (float) ($inspection->base_price_snapshot ?? 0);
            if ($arpMonthly < $selectedPackageThreshold) {
                return $selectedPackageName;
            }
        }

        return $tier;
    }

    /**
     * Select final tier (max of both gates)
     */
    protected function selectFinalTier(string $tierScore, string $tierARP): string
    {
        $tierRanking = [
            'Essentials' => 1,
            'Premium' => 2,
            'White-Glove' => 3,
            'Critical Care' => 4,
        ];
        
        $scoreRank = $tierRanking[$tierScore] ?? 1;
        $arpRank = $tierRanking[$tierARP] ?? 1;
        
        $finalRank = max($scoreRank, $arpRank);
        
        // Return tier name from rank
        return array_search($finalRank, $tierRanking);
    }

    /**
     * Get multiplier for tier
     */
    protected function getMultiplierForTier(string $tier): float
    {
        $multipliers = [
            'Essentials' => 1.00,
            'Premium' => 1.15,
            'White-Glove' => 1.35,
            'Critical Care' => 1.55,
        ];
        
        return $multipliers[$tier] ?? 1.00;
    }

    /**
     * Get base package price for tier
     */
    protected function getBasePackagePrice($inspection): float
    {
        if ($inspection->base_price_snapshot !== null) {
            return (float) $inspection->base_price_snapshot;
        }

        if (!$inspection->service_package_id) {
            return 0.0;
        }

        $package = \App\Models\PricingPackage::with('packagePricing')->find($inspection->service_package_id);
        if (!$package) {
            return 0.0;
        }

        $propertyTypeId = $this->resolvePropertyTypeId($this->resolveInspectionPropertyTypeCode($inspection));

        if ($propertyTypeId) {
            $price = $package->getPriceForPropertyType($propertyTypeId);
            if ($price !== null) {
                return (float) $price;
            }
        }

        return (float) (optional($package->packagePricing->where('is_active', true)->first())->base_monthly_price ?? 0.0);
    }

    protected function resolvePropertyTypeId(?string $propertyType): ?int
    {
        $normalized = strtolower((string) $propertyType);

        $typeCode = 'residential';
        if (str_contains($normalized, 'mixed')) {
            $typeCode = 'mixed_use';
        } elseif (str_contains($normalized, 'commercial')) {
            $typeCode = 'commercial';
        }

        return \Illuminate\Support\Facades\DB::table('property_types')
            ->where('type_code', $typeCode)
            ->value('id');
    }

    protected function resolveInspectionPropertyTypeCode(Inspection $inspection): ?string
    {
        if (!empty($inspection->property_type_snapshot)) {
            return (string) $inspection->property_type_snapshot;
        }

        return $inspection->property?->type;
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
        } elseif ($property->type === 'commercial' && $property->commercial_units) {
            $units = $property->commercial_units;
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
            'bdc_annual' => $calculation['bdc_annual'],
            'bdc_monthly' => $calculation['bdc_monthly'],
            'labour_hourly_rate' => $calculation['labour_hourly_rate'],
            'frlc_annual' => $calculation['frlc_annual'],
            'frlc_monthly' => $calculation['frlc_monthly'],
            'fmc_annual' => $calculation['fmc_annual'],
            'fmc_monthly' => $calculation['fmc_monthly'],
            'trc_annual' => $calculation['trc_annual'],
            'trc_monthly' => $calculation['trc_monthly'],
            'arp_monthly' => $calculation['arp_monthly'],
            'condition_score' => $calculation['condition_score'],
            'tier_score' => $calculation['tier_score'],
            'tier_arp' => $calculation['tier_arp'],
            'tier_final' => $calculation['tier_final'],
            'multiplier_final' => $calculation['multiplier_final'],
            'arp_equivalent_final' => $calculation['arp_equivalent_final'],
            'base_package_price_snapshot' => $calculation['base_package_price'],
            'units_for_calculation' => $calculation['units_for_calculation'],
            'bdc_per_unit_annual' => $calculation['bdc_per_unit_annual'],
            'frlc_per_unit_annual' => $calculation['frlc_per_unit_annual'],
            'fmc_per_unit_annual' => $calculation['fmc_per_unit_annual'],
            'trc_per_unit_annual' => $calculation['trc_per_unit_annual'],
            'final_monthly_per_unit' => $calculation['final_monthly_per_unit'],
        ]);
    }
}
