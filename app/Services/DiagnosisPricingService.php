<?php

namespace App\Services;

use App\Models\Property;

class DiagnosisPricingService
{
    private const BASE_FEE_PER_UNIT = 299;
    private const HIGH_PITCHED_ROOF_FEE = 75;
    private const CRAWL_SPACE_FEE = 50;
    private const TEST_CHARGE_CENTS = 100;

    public function calculate(Property $property): array
    {
        $units = max(1, (int) ($property->residential_units ?? $property->number_of_units ?? 1));
        $baseFee = self::BASE_FEE_PER_UNIT * $units;
        $roofSurcharge = $property->has_high_pitched_roof ? self::HIGH_PITCHED_ROOF_FEE : 0;
        $crawlSurcharge = $property->has_crawl_space ? self::CRAWL_SPACE_FEE : 0;
        $totalFee = $baseFee + $roofSurcharge + $crawlSurcharge;

        return [
            'units' => $units,
            'base_fee' => $baseFee,
            'roof_surcharge' => $roofSurcharge,
            'crawl_surcharge' => $crawlSurcharge,
            'currency' => strtoupper((string) config('cashier.currency', 'usd')),
            'total_dollars' => $totalFee,
            'invoice_dollars' => $totalFee,
            'charge_cents' => self::TEST_CHARGE_CENTS,
            'charge_dollars' => self::TEST_CHARGE_CENTS / 100,
            'is_test_mode' => true,
        ];
    }
}
