<?php

namespace App\Services;

use App\Models\BDCSetting;
use App\Models\Property;

class BDCCalculator
{
    protected $loadedHourlyRate;
    protected $visitsPerYear;
    protected $hoursPerVisit;
    protected $infrastructurePercentage;
    protected $administrationPercentage;

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
        $this->infrastructurePercentage = BDCSetting::getValue('infrastructure_percentage', 0.30);
        $this->administrationPercentage = BDCSetting::getValue('administration_percentage', 0.12);
    }

    /**
     * Calculate BDC for a property
     * 
     * @param Property|null $property Optional property for customization
     * @return array
     */
    public function calculate(?Property $property = null): array
    {
        // Calculate labour hours per year
        $labourHoursPerYear = $this->visitsPerYear * $this->hoursPerVisit;
        
        // Calculate labour cost per year
        $labourCostPerYear = $labourHoursPerYear * $this->loadedHourlyRate;
        
        // Calculate overhead costs
        $infrastructureCost = $labourCostPerYear * $this->infrastructurePercentage;
        $administrationCost = $labourCostPerYear * $this->administrationPercentage;
        
        // Calculate total Base Deployment Cost (annual)
        $bdcAnnual = $labourCostPerYear + $infrastructureCost + $administrationCost;
        
        // Calculate monthly BDC
        $bdcMonthly = $bdcAnnual / 12;

        return [
            // Inputs
            'loaded_hourly_rate' => round($this->loadedHourlyRate, 2),
            'visits_per_year' => round($this->visitsPerYear, 2),
            'hours_per_visit' => round($this->hoursPerVisit, 2),
            'infrastructure_percentage' => round($this->infrastructurePercentage, 4),
            'administration_percentage' => round($this->administrationPercentage, 4),
            
            // Calculated values
            'labour_hours_per_year' => round($labourHoursPerYear, 2),
            'labour_cost_per_year' => round($labourCostPerYear, 2),
            'infrastructure_cost' => round($infrastructureCost, 2),
            'administration_cost' => round($administrationCost, 2),
            
            // Final BDC
            'bdc_annual' => round($bdcAnnual, 2),
            'bdc_monthly' => round($bdcMonthly, 2),
        ];
    }

    /**
     * Calculate BDC with custom parameters (for what-if scenarios)
     * 
     * @param array $customParams
     * @return array
     */
    public function calculateWithParams(array $customParams): array
    {
        $loadedHourlyRate = $customParams['loaded_hourly_rate'] ?? $this->loadedHourlyRate;
        $visitsPerYear = $customParams['visits_per_year'] ?? $this->visitsPerYear;
        $hoursPerVisit = $customParams['hours_per_visit'] ?? $this->hoursPerVisit;
        $infrastructurePercentage = $customParams['infrastructure_percentage'] ?? $this->infrastructurePercentage;
        $administrationPercentage = $customParams['administration_percentage'] ?? $this->administrationPercentage;

        $labourHoursPerYear = $visitsPerYear * $hoursPerVisit;
        $labourCostPerYear = $labourHoursPerYear * $loadedHourlyRate;
        $infrastructureCost = $labourCostPerYear * $infrastructurePercentage;
        $administrationCost = $labourCostPerYear * $administrationPercentage;
        $bdcAnnual = $labourCostPerYear + $infrastructureCost + $administrationCost;
        $bdcMonthly = $bdcAnnual / 12;

        return [
            'loaded_hourly_rate' => round($loadedHourlyRate, 2),
            'visits_per_year' => round($visitsPerYear, 2),
            'hours_per_visit' => round($hoursPerVisit, 2),
            'infrastructure_percentage' => round($infrastructurePercentage, 4),
            'administration_percentage' => round($administrationPercentage, 4),
            'labour_hours_per_year' => round($labourHoursPerYear, 2),
            'labour_cost_per_year' => round($labourCostPerYear, 2),
            'infrastructure_cost' => round($infrastructureCost, 2),
            'administration_cost' => round($administrationCost, 2),
            'bdc_annual' => round($bdcAnnual, 2),
            'bdc_monthly' => round($bdcMonthly, 2),
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
            'infrastructure_percentage' => $this->infrastructurePercentage,
            'administration_percentage' => $this->administrationPercentage,
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
               "Loaded Hourly Rate: \${$calc['loaded_hourly_rate']}/hr\n" .
               "Visits per Year: {$calc['visits_per_year']}\n" .
               "Hours per Visit: {$calc['hours_per_visit']}\n" .
               "Infrastructure %: " . ($calc['infrastructure_percentage'] * 100) . "%\n" .
               "Administration %: " . ($calc['administration_percentage'] * 100) . "%\n" .
               "──────────────────────────────────────\n" .
               "Labour Hours/Year: {$calc['labour_hours_per_year']}\n" .
               "Labour Cost/Year: \${$calc['labour_cost_per_year']}\n" .
               "Infrastructure Cost: \${$calc['infrastructure_cost']}\n" .
               "Administration Cost: \${$calc['administration_cost']}\n" .
               "══════════════════════════════════════\n" .
               "BDC (Annual): \${$calc['bdc_annual']}\n" .
               "BDC (Monthly): \${$calc['bdc_monthly']}\n";
    }
}
