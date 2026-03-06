<?php

namespace App\Services;

use App\Models\PackagePricing;
use App\Models\Parameter;
use App\Models\PricingPackage;

class BaseServicePricingService
{
    public function getPackageBasePrice(string $packageName, string $propertyTypeCode): ?float
    {
        $normalizedType = $this->normalizePropertyType($propertyTypeCode);
        $normalizedPackage = $this->normalizePackageName($packageName);

        if ($normalizedType === 'mixed_use') {
            $res = $this->calculateFromParameters($normalizedPackage, 'residential');
            $com = $this->calculateFromParameters($normalizedPackage, 'commercial');

            if ($res !== null && $com !== null) {
                return ($res + $com) / 2;
            }

            return $this->fallbackPrice($normalizedPackage, 'mixed_use');
        }

        $calculated = $this->calculateFromParameters($normalizedPackage, $normalizedType);
        if ($calculated !== null) {
            return $calculated;
        }

        return $this->fallbackPrice($normalizedPackage, $normalizedType);
    }

    protected function calculateFromParameters(string $packageName, string $propertyTypeCode): ?float
    {
        $prefix = $propertyTypeCode === 'commercial' ? 'COM' : 'RES';

        $factorKey = match ($packageName) {
            'essentials' => "{$prefix}_ESS_FACTOR",
            'premium' => "{$prefix}_PREMIUM_BASE_FACTOR",
            'white-glove' => "{$prefix}_WHITE_FACTOR",
            default => null,
        };

        if (!$factorKey) {
            return null;
        }

        $factor = Parameter::getValue($factorKey);
        $premiumBase = $this->getPremiumBasePrice($propertyTypeCode);

        if ($factor === null || $premiumBase === null) {
            return null;
        }

        return $premiumBase * $factor;
    }

    protected function getPremiumBasePrice(string $propertyTypeCode): ?float
    {
        $premiumPackage = PricingPackage::query()
            ->where('is_active', true)
            ->whereRaw('LOWER(package_name) = ?', ['premium'])
            ->first();

        if (!$premiumPackage) {
            return null;
        }

        return $this->fallbackPrice($premiumPackage->package_name, $propertyTypeCode);
    }

    protected function fallbackPrice(string $packageName, string $propertyTypeCode): ?float
    {
        $package = PricingPackage::query()
            ->where('is_active', true)
            ->whereRaw('LOWER(package_name) = ?', [strtolower($packageName)])
            ->first();

        if (!$package) {
            return null;
        }

        $typeCode = $this->normalizePropertyType($propertyTypeCode);
        $propertyTypeId = \Illuminate\Support\Facades\DB::table('property_types')
            ->where('type_code', $typeCode)
            ->value('id');

        if ($propertyTypeId) {
            $price = PackagePricing::query()
                ->where('pricing_package_id', $package->id)
                ->where('property_type_id', $propertyTypeId)
                ->where('is_active', true)
                ->value('base_monthly_price');

            if ($price !== null) {
                return (float) $price;
            }
        }

        return (float) (PackagePricing::query()
            ->where('pricing_package_id', $package->id)
            ->where('is_active', true)
            ->value('base_monthly_price') ?? 0);
    }

    protected function normalizePropertyType(string $propertyTypeCode): string
    {
        $value = strtolower($propertyTypeCode);

        if (str_contains($value, 'mixed')) {
            return 'mixed_use';
        }

        if (str_contains($value, 'commercial')) {
            return 'commercial';
        }

        return 'residential';
    }

    protected function normalizePackageName(string $packageName): string
    {
        return strtolower(trim($packageName));
    }
}
