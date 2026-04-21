<?php

namespace App\Services;

use App\Models\BDCSetting;
use App\Models\Property;

class BDCCalculator
{
    protected $loadedHourlyRate;
    protected $visitsPerYear;
    protected $hoursPerVisit;
    // Travel-based rates (defaults kept to match current business rules)
    protected $ratePerKm;
    protected $ratePerMinute;

    public function __construct()
    {
        $this->loadSettings();
    }

    /**
     * Load settings from database
     */
    protected function loadSettings(): void
    {
        $this->loadedHourlyRate = BDCSetting::getValue('loaded_hourly_rate', 165);
        $this->visitsPerYear = BDCSetting::getValue('visits_per_year', 8);
        $this->hoursPerVisit = BDCSetting::getValue('hours_per_visit', 4.5);
        $this->ratePerKm = BDCSetting::getValue('rate_per_km', 1.50);
        $this->ratePerMinute = BDCSetting::getValue('rate_per_minute', 1.65);
    }

    /**
     * Calculate BDC for a property
     * 
     * @param Property|null $property Optional property for customization
     * @return array
     */
    public function calculate(?Property $property = null): array
    {
        $result = $this->calculateLabourBdc(
            $this->visitsPerYear,
            $this->hoursPerVisit,
            $this->loadedHourlyRate
        );

        return array_merge([
            'loaded_hourly_rate' => round($this->loadedHourlyRate, 2),
            'visits_per_year'    => round($this->visitsPerYear, 2),
            'hours_per_visit'    => round($this->hoursPerVisit, 2),
        ], $result);
    }

    /**
     * Calculate BDC with custom parameters (for what-if scenarios)
     * 
     * @param array $customParams
     * @return array
     */
    public function calculateWithParams(array $customParams): array
    {
        // Allow for travel-based calculation when travel params are provided
        $loadedHourlyRate = $customParams['loaded_hourly_rate'] ?? $this->loadedHourlyRate;
        $visitsPerYear = $customParams['visits_per_year'] ?? $this->visitsPerYear;
        $hoursPerVisit = $customParams['hours_per_visit'] ?? $this->hoursPerVisit;
        // legacy infra/admin removed — labour fallback uses labour cost only

        // Travel params (if both provided, use travel-based BDC)
        $travelDistanceKm = array_key_exists('travel_distance_km', $customParams) ? $customParams['travel_distance_km'] : null;
        $travelTimeMinutes = array_key_exists('travel_time_minutes', $customParams) ? $customParams['travel_time_minutes'] : null;
        $ratePerKm = $customParams['rate_per_km'] ?? $this->ratePerKm;
        $ratePerMinute = $customParams['rate_per_minute'] ?? $this->ratePerMinute;

        if ($travelDistanceKm !== null && $travelTimeMinutes !== null) {
            // Travel-based per-visit calculation
            $travelCost = $travelDistanceKm * $ratePerKm;
            $timeCost = $travelTimeMinutes * $ratePerMinute;
            $bdcPerVisit = $travelCost + $timeCost;
            $bdcAnnual = $bdcPerVisit * $visitsPerYear;
            $bdcMonthly = $bdcAnnual;

            return [
                'mode' => 'travel',
                'rate_per_km' => round($ratePerKm, 2),
                'rate_per_minute' => round($ratePerMinute, 2),
                'travel_distance_km' => round((float)$travelDistanceKm, 2),
                'travel_time_minutes' => round((float)$travelTimeMinutes, 2),
                'travel_cost' => round($travelCost, 2),
                'time_cost' => round($timeCost, 2),
                'bdc_per_visit' => round($bdcPerVisit, 2),
                'bdc_annual' => round($bdcAnnual, 2),
                'bdc_monthly' => round($bdcMonthly, 2),
                'visits_per_year' => round($visitsPerYear, 2),
            ];
        }

        // Fallback: labour-only calculation
        return array_merge([
            'loaded_hourly_rate' => round($loadedHourlyRate, 2),
            'visits_per_year'    => round($visitsPerYear, 2),
            'hours_per_visit'    => round($hoursPerVisit, 2),
        ], $this->calculateLabourBdc($visitsPerYear, $hoursPerVisit, $loadedHourlyRate));
    }

    /**
     * Core labour-based BDC formula shared by calculate() and calculateWithParams().
     */
    private function calculateLabourBdc(float $visits, float $hoursPerVisit, float $hourlyRate): array
    {
        $labourHoursPerYear = $visits * $hoursPerVisit;
        $labourCostPerYear  = $labourHoursPerYear * $hourlyRate;

        return [
            'mode'                  => 'labour',
            'labour_hours_per_year' => round($labourHoursPerYear, 2),
            'labour_cost_per_year'  => round($labourCostPerYear, 2),
            'bdc_annual'            => round($labourCostPerYear, 2),
            'bdc_monthly'           => round($labourCostPerYear, 2),
        ];
    }

    /**
     * Get current BDC settings
     */
    public function getSettings(): array
    {
        return [
            'loaded_hourly_rate' => $this->loadedHourlyRate,
            'visits_per_year' => $this->visitsPerYear,
            'hours_per_visit' => $this->hoursPerVisit,
            'rate_per_km' => $this->ratePerKm,
            'rate_per_minute' => $this->ratePerMinute,
        ];
    }

    /**
     * Get breakdown as formatted text
     */
    public function getBreakdown(): string
    {
        $calc = $this->calculate();
        
        return "BDC Calculation Breakdown:\n" .
               "══════════════════════════════════════\n" .
                             "Loaded Hourly Rate: $" . $calc['loaded_hourly_rate'] . "/hr\n" .
               "Visits per Year: {$calc['visits_per_year']}\n" .
               "Hours per Visit: {$calc['hours_per_visit']}\n" .
             "Mode: " . ($calc['mode'] ?? 'labour') . "\n" .
               "──────────────────────────────────────\n" .
               "Labour Hours/Year: {$calc['labour_hours_per_year']}\n" .
                         "Labour Cost/Year: $" . $calc['labour_cost_per_year'] . "\n" .
               "══════════════════════════════════════\n" .
                             "BDC (Annual): $" . $calc['bdc_annual'] . "\n" .
                             "BDC (Monthly): $" . $calc['bdc_monthly'] . "\n";
    }
}
