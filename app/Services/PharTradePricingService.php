<?php

namespace App\Services;

use App\Models\Inspection;
use App\Models\TradePartner;
use Illuminate\Support\Str;

class PharTradePricingService
{
    private const MARGIN_RATE = 0.35;

    private array $defaults = [
        'roof' => 220.00,
        'roofing' => 220.00,
        'electrical' => 175.00,
        'plumbing' => 165.00,
        'hvac' => 180.00,
        'drainage' => 220.00,
        'foundation' => 250.00,
        'floor' => 150.00,
        'flooring' => 150.00,
        'drywall' => 140.00,
        'painting' => 135.00,
        'windows' => 150.00,
        'doors' => 150.00,
        'exterior' => 160.00,
        'general' => 150.00,
    ];

    public function priceFinding(Inspection $inspection, array $finding, int $index): array
    {
        $systemId = !empty($finding['system_id']) ? (int) $finding['system_id'] : null;
        $subsystemId = !empty($finding['subsystem_id']) ? (int) $finding['subsystem_id'] : null;
        $systemName = trim((string) ($finding['system'] ?? ''));
        $issue = trim((string) ($finding['issue'] ?? $finding['task_question'] ?? 'PHAR finding'));
        $fulfillmentType = $this->normalizeFulfillmentType($finding['fulfillment_type'] ?? null);
        $selectedTradeApplicationId = !empty($finding['trade_application_id']) ? (int) $finding['trade_application_id'] : null;
        $quantity = max(1.0, (float) ($finding['trade_quantity'] ?? 1));
        $submittedUnit = trim((string) ($finding['trade_unit'] ?? ''));
        $scopeArea = trim((string) ($finding['trade_scope_area'] ?? $finding['location'] ?? ''));
        $estimatedDurationHours = isset($finding['trade_duration_hours'])
            ? max(0.0, (float) $finding['trade_duration_hours'])
            : null;

        $rate = $this->approvedTradeRate($systemId, $subsystemId, $selectedTradeApplicationId);
        if (!$rate) {
            $rate = $this->defaultRate($systemName);
        }

        $tradeUnitCost = round((float) $rate['trade_unit_cost'], 2);
        $tradeTotalCost = round($tradeUnitCost * $quantity, 2);
        if (!empty($rate['minimum_charge'])) {
            $tradeTotalCost = max($tradeTotalCost, round((float) $rate['minimum_charge'], 2));
        }
        if (!empty($rate['maximum_charge'])) {
            $tradeTotalCost = min($tradeTotalCost, round((float) $rate['maximum_charge'], 2));
        }
        $clientPrice = $this->applyMargin($tradeTotalCost);

        return [
            'inspection_id' => $inspection->id,
            'property_id' => $inspection->property_id,
            'finding_index' => $index,
            'system_id' => $systemId,
            'subsystem_id' => $subsystemId,
            'trade_application_id' => $rate['trade_application_id'],
            'trade_company_name' => $rate['trade_company_name'],
            'fulfillment_type' => $fulfillmentType,
            'activity' => $issue,
            'scope_area' => $scopeArea !== '' ? $scopeArea : null,
            'unit' => $submittedUnit !== '' ? $submittedUnit : $rate['unit'],
            'quantity' => $quantity,
            'estimated_duration_hours' => $estimatedDurationHours,
            'trade_unit_cost' => $tradeUnitCost,
            'trade_total_cost' => $tradeTotalCost,
            'etogo_client_price' => $clientPrice,
            'etogo_margin_amount' => round($clientPrice - $tradeTotalCost, 2),
            'margin_rate' => self::MARGIN_RATE,
            'pricing_source' => $rate['source'],
            'approval_status' => 'draft',
            'notes' => trim(implode(' ', array_filter([
                $rate['notes'],
                trim((string) ($finding['trade_notes'] ?? '')),
            ]))),
        ];
    }

    public function shouldPriceFinding(array $finding): bool
    {
        $fulfillmentType = $this->normalizeFulfillmentType($finding['fulfillment_type'] ?? null);
        if ($fulfillmentType === 'etogo_team') {
            return false;
        }

        if ($fulfillmentType === 'trade_partner') {
            return true;
        }

        if (array_key_exists('requires_trade_pricing', $finding)) {
            return (bool) $finding['requires_trade_pricing'];
        }

        $systemName = Str::lower((string) ($finding['system'] ?? ''));
        $issueText = Str::lower(trim(implode(' ', array_filter([
            (string) ($finding['issue'] ?? ''),
            (string) ($finding['issue_description'] ?? ''),
            (string) ($finding['recommendation_details'] ?? ''),
            (string) ($finding['phar_notes'] ?? ''),
        ]))));

        $tradeSystems = ['roof', 'electrical', 'plumbing', 'hvac', 'foundation', 'drainage'];
        if (Str::contains($systemName, $tradeSystems)) {
            return true;
        }

        return Str::contains($issueText, [
            'licensed trade',
            'contractor',
            'electrician',
            'plumber',
            'roofer',
            'hvac',
            'foundation',
            'specialist',
        ]);
    }

    private function approvedTradeRate(?int $systemId, ?int $subsystemId = null, ?int $selectedTradeApplicationId = null): ?array
    {
        if (!$systemId) {
            return null;
        }

        $partners = TradePartner::query()
            ->with('application')
            ->where('status', TradePartner::STATUS_ACTIVE)
            ->when($selectedTradeApplicationId, fn ($query) => $query->where('trade_application_id', $selectedTradeApplicationId))
            ->get()
            ->filter(fn (TradePartner $partner) => in_array($systemId, array_map('intval', $partner->system_ids ?? []), true));

        $candidates = [];
        foreach ($partners as $partner) {
            $pricing = [];
            $source = 'active_trade_partner';
            $application = $partner->application;
            $agreedSubsystemPricing = $partner->agreed_subsystem_pricing ?? [];
            $submittedSubsystemPricing = $application?->subsystem_pricing ?? [];
            $submittedSystemPricing = $application?->system_pricing ?? [];

            if ($subsystemId && in_array($subsystemId, array_map('intval', $partner->subsystem_ids ?? []), true)) {
                $agreedPricing = $agreedSubsystemPricing[(string) $subsystemId]
                    ?? $agreedSubsystemPricing[$subsystemId]
                    ?? [];
                $submittedPricing = $submittedSubsystemPricing[(string) $subsystemId]
                    ?? $submittedSubsystemPricing[$subsystemId]
                    ?? [];
                $pricing = $agreedPricing ?: $submittedPricing;

                if (!empty($pricing)) {
                    $source = !empty($agreedPricing)
                        ? 'agreed_trade_subsystem_pricing'
                        : 'approved_trade_subsystem_pricing';
                }
            }

            if (empty($pricing)) {
                $pricing = $submittedSystemPricing[(string) $systemId]
                    ?? $submittedSystemPricing[$systemId]
                    ?? [];
            }

            $unitCost = isset($pricing['typical_rate']) ? (float) $pricing['typical_rate'] : 0.0;
            if ($unitCost <= 0) {
                continue;
            }

            $minimum = max(
                isset($pricing['minimum_charge']) ? (float) $pricing['minimum_charge'] : 0.0,
                (float) ($application?->minimum_service_charge ?? 0)
            );
            $maximum = isset($pricing['maximum_charge']) ? (float) $pricing['maximum_charge'] : 0.0;

            $candidates[] = [
                'trade_unit_cost' => $unitCost,
                'minimum_charge' => $minimum,
                'maximum_charge' => $maximum,
                'unit' => $pricing['pricing_unit'] ?? $pricing['rate_unit'] ?? 'ls',
                'source' => $source,
                'trade_application_id' => $partner->trade_application_id,
                'trade_company_name' => $partner->company_name,
                'notes' => trim((string) ($pricing['notes'] ?? $application?->pricing_notes ?? '')),
            ];
        }

        return collect($candidates)->sortBy('trade_unit_cost')->first();
    }

    private function defaultRate(string $systemName): array
    {
        $normalized = Str::lower($systemName);
        $matchedKey = collect(array_keys($this->defaults))
            ->first(fn ($key) => Str::contains($normalized, $key));

        $unitCost = $this->defaults[$matchedKey ?: 'general'];

        return [
            'trade_unit_cost' => $unitCost,
            'unit' => 'ls',
            'source' => 'default_rule',
            'trade_application_id' => null,
            'trade_company_name' => null,
            'notes' => $matchedKey
                ? 'Default PHAR trade rate for ' . ($systemName ?: $matchedKey) . '.'
                : 'Default PHAR trade rate. Review before sharing quotation.',
        ];
    }

    private function applyMargin(float $tradeCost): float
    {
        if ($tradeCost <= 0) {
            return 0.0;
        }

        return round($tradeCost / (1 - self::MARGIN_RATE), 2);
    }

    private function normalizeFulfillmentType(?string $value): string
    {
        $value = trim((string) $value);

        return in_array($value, ['etogo_team', 'trade_partner', 'decide_later'], true)
            ? $value
            : 'decide_later';
    }
}
