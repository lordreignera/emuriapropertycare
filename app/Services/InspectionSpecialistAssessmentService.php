<?php

namespace App\Services;

use App\Models\InspectionSystem;
use App\Models\Property;
use App\Models\TradeApplication;
use Illuminate\Support\Str;

class InspectionSpecialistAssessmentService
{
    private const MARGIN_RATE = 0.35;
    private const SUPPORT_HOURS = 1.5;
    private const MAX_AUTO_CATEGORIES = 3;

    /**
     * Client wording stays generic. Internal labels identify the trade category.
     */
    private array $rules = [
        'roofing' => [
            'priority' => 95,
            'internal_label' => 'Roofing envelope assessment support',
            'client_reason' => 'Exterior or water-control concerns were flagged.',
            'system_keywords' => ['roof', 'gutter', 'drainage'],
            'default_trade_cost' => 180.00,
            'keywords' => ['roof', 'shingle', 'gutter', 'flashing', 'skylight', 'soffit', 'fascia', 'attic', 'ceiling leak', 'ceiling stain'],
            'care_goals' => ['gutter_cleaning_drainage', 'moisture_leak_prevention'],
            'sensitivities' => ['water_damage_risk'],
            'property_flags' => ['has_high_pitched_roof'],
        ],
        'plumbing' => [
            'priority' => 90,
            'internal_label' => 'Plumbing assessment support',
            'client_reason' => 'Water, pipe, fixture, or drainage concerns were flagged.',
            'system_keywords' => ['plumbing', 'water', 'bathroom', 'kitchen'],
            'default_trade_cost' => 165.00,
            'keywords' => ['plumbing', 'pipe', 'faucet', 'toilet', 'sink', 'shower', 'tub', 'water heater', 'drain', 'sewer', 'pipe leak', 'leaking pipe', 'faucet leak', 'toilet leak', 'low pressure'],
            'care_goals' => ['moisture_leak_prevention'],
            'sensitivities' => ['water_damage_risk'],
        ],
        'electrical' => [
            'priority' => 88,
            'internal_label' => 'Electrical safety assessment support',
            'client_reason' => 'Electrical safety concerns were flagged.',
            'system_keywords' => ['electrical', 'lighting'],
            'default_trade_cost' => 175.00,
            'keywords' => ['electrical', 'breaker', 'panel', 'outlet', 'plug', 'wiring', 'wire', 'spark', 'shock', 'light flicker', 'flickering light', 'burning smell'],
            'care_goals' => ['electrical_safety'],
        ],
        'hvac' => [
            'priority' => 78,
            'internal_label' => 'HVAC and ventilation assessment support',
            'client_reason' => 'Heating, cooling, ventilation, or air-quality concerns were flagged.',
            'system_keywords' => ['hvac', 'ventilation', 'heating', 'cooling'],
            'default_trade_cost' => 180.00,
            'keywords' => ['hvac', 'furnace', 'heat pump', 'air conditioning', 'air conditioner', 'cooling', 'heating', 'ventilation', 'duct', 'thermostat', 'poor air'],
            'care_goals' => ['hvac_filters_program'],
            'sensitivities' => ['allergies_air_quality'],
        ],
        'drainage_foundation' => [
            'priority' => 92,
            'internal_label' => 'Drainage and foundation assessment support',
            'client_reason' => 'Ground water, foundation, or crawl-space concerns were flagged.',
            'system_keywords' => ['foundation', 'drainage', 'sump', 'exterior'],
            'default_trade_cost' => 220.00,
            'keywords' => ['basement water', 'standing water', 'foundation', 'crack', 'crawl space', 'crawlspace', 'sump', 'grading', 'drainage', 'moisture', 'mould', 'mold', 'rot'],
            'care_goals' => ['moisture_leak_prevention', 'gutter_cleaning_drainage'],
            'sensitivities' => ['water_damage_risk'],
            'property_flags' => ['has_crawl_space'],
        ],
        'pest_envelope' => [
            'priority' => 65,
            'internal_label' => 'Pest and sealing assessment support',
            'client_reason' => 'Pest, entry-point, or sealing concerns were flagged.',
            'system_keywords' => ['pest', 'exterior', 'doors', 'windows'],
            'default_trade_cost' => 150.00,
            'keywords' => ['pest', 'rodent', 'mouse', 'mice', 'insect', 'termite', 'ant', 'entry point', 'gap', 'seal'],
            'care_goals' => ['pest_prevention_sealing'],
        ],
        'interior_finish' => [
            'priority' => 45,
            'internal_label' => 'Interior finish assessment support',
            'client_reason' => 'Interior finish or surface concerns were flagged.',
            'system_keywords' => ['drywall', 'painting', 'floor', 'interior', 'wall'],
            'default_trade_cost' => 135.00,
            'keywords' => ['drywall', 'wall damage', 'paint', 'floor', 'flooring', 'tile', 'trim', 'baseboard', 'door', 'cabinet'],
            'care_goals' => ['walls_paint_care', 'trim_woodwork_finishing', 'flooring_care_patching'],
        ],
    ];

    public function forProperty(Property $property): array
    {
        $signals = $this->collectSignals($property);
        $candidates = [];

        foreach ($this->rules as $category => $rule) {
            $triggeredBy = $this->matchRule($rule, $signals, $property);
            if (empty($triggeredBy)) {
                continue;
            }

            $costSource = $this->resolveTradeCost($rule);
            $tradeCost = round((float) $costSource['trade_cost'], 2);
            $clientPrice = $this->applyMargin($tradeCost);

            $candidates[] = [
                'category' => $category,
                'client_label' => 'Expanded assessment support',
                'internal_label' => $rule['internal_label'],
                'client_reason' => $rule['client_reason'],
                'triggered_by' => $triggeredBy,
                'trade_cost' => $tradeCost,
                'client_price' => $clientPrice,
                'margin_amount' => round($clientPrice - $tradeCost, 2),
                'margin_rate' => self::MARGIN_RATE,
                'source' => $costSource['source'],
                'matched_system_id' => $costSource['system_id'],
                'matched_system_name' => $costSource['system_name'],
                'matched_trade_application_id' => $costSource['trade_application_id'],
                'matched_trade_company' => $costSource['trade_company'],
                'priority' => (int) ($rule['priority'] ?? 0),
            ];
        }

        $ordered = collect($candidates)
            ->sortByDesc('priority')
            ->values();

        $addons = $ordered->take(self::MAX_AUTO_CATEGORIES)->values()->all();
        $heldForReview = $ordered->slice(self::MAX_AUTO_CATEGORIES)->values()->all();

        $tradeCostTotal = round(collect($addons)->sum('trade_cost'), 2);
        $clientPriceTotal = round(collect($addons)->sum('client_price'), 2);

        return [
            'client_label' => 'Expanded assessment support',
            'currency' => strtoupper((string) config('cashier.currency', 'usd')),
            'margin_rate' => self::MARGIN_RATE,
            'addons' => $addons,
            'held_for_manual_review' => $heldForReview,
            'requires_manual_review' => $heldForReview !== [],
            'trade_cost_total' => $tradeCostTotal,
            'client_price_total' => $clientPriceTotal,
            'margin_total' => round($clientPriceTotal - $tradeCostTotal, 2),
            'signals_summary' => [
                'known_problem_count' => count($signals['known_problems']),
                'care_goal_count' => count($signals['care_goals']),
                'has_known_problem_images' => !empty($property->known_problem_images),
            ],
        ];
    }

    private function collectSignals(Property $property): array
    {
        $knownProblems = $this->normalizeList($property->known_problems);
        $careGoals = $this->normalizeList($property->care_goals);
        $sensitivities = $this->normalizeList($property->sensitivities);
        $homeJourney = $this->normalizeList($property->home_journey);
        $homeFeel = $this->normalizeList($property->home_feel);
        $plannedProjects = $this->normalizeList($property->planned_projects);
        $knownProblemDetails = collect($property->known_problem_details ?? [])
            ->filter(fn ($item) => is_array($item))
            ->map(fn ($item) => [
                'area' => trim((string) ($item['area'] ?? 'Unknown / not sure')),
                'issue' => trim((string) ($item['issue'] ?? '')),
            ])
            ->filter(fn ($item) => $item['issue'] !== '')
            ->values()
            ->all();

        $text = Str::lower(implode(' ', array_filter([
            implode(' ', $knownProblems),
            collect($knownProblemDetails)->map(fn ($item) => $item['area'] . ' ' . $item['issue'])->join(' '),
            implode(' ', $plannedProjects),
            implode(' ', $homeJourney),
            implode(' ', $homeFeel),
            (string) ($property->personality_notes ?? ''),
        ])));

        return [
            'text' => $text,
            'known_problems' => $knownProblems,
            'known_problem_details' => $knownProblemDetails,
            'care_goals' => $careGoals,
            'sensitivities' => $sensitivities,
        ];
    }

    private function normalizeList(mixed $value): array
    {
        if (is_array($value)) {
            return collect($value)
                ->flatten()
                ->map(fn ($item) => trim((string) $item))
                ->filter()
                ->values()
                ->all();
        }

        $value = trim((string) $value);
        if ($value === '' || Str::lower($value) === 'null') {
            return [];
        }

        return collect(preg_split('/[,\n]+/', $value) ?: [])
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->values()
            ->all();
    }

    private function matchRule(array $rule, array $signals, Property $property): array
    {
        $strongMatches = [];
        $supportMatches = [];

        foreach ($rule['keywords'] ?? [] as $keyword) {
            if ($keyword !== '' && Str::contains($signals['text'], Str::lower($keyword))) {
                $strongMatches[] = 'Known issue: ' . $keyword;
            }
        }

        foreach ($signals['known_problem_details'] ?? [] as $detail) {
            $area = trim((string) ($detail['area'] ?? ''));
            if ($area === '' || Str::lower($area) === 'unknown / not sure') {
                continue;
            }

            foreach ($rule['system_keywords'] ?? [] as $keyword) {
                if ($keyword !== '' && Str::contains(Str::lower($area), Str::lower($keyword))) {
                    $strongMatches[] = 'Selected area: ' . $area;
                    break;
                }
            }
        }

        foreach ($rule['care_goals'] ?? [] as $goal) {
            if (in_array($goal, $signals['care_goals'], true)) {
                $supportMatches[] = 'Care goal: ' . str_replace('_', ' ', $goal);
            }
        }

        foreach ($rule['sensitivities'] ?? [] as $sensitivity) {
            if (in_array($sensitivity, $signals['sensitivities'], true)) {
                $supportMatches[] = 'Sensitivity: ' . str_replace('_', ' ', $sensitivity);
            }
        }

        foreach ($rule['property_flags'] ?? [] as $flag) {
            if ((bool) ($property->{$flag} ?? false)) {
                $supportMatches[] = 'Property flag: ' . str_replace('_', ' ', $flag);
            }
        }

        if ($strongMatches === []) {
            return [];
        }

        return array_values(array_unique(array_merge($strongMatches, $supportMatches)));
    }

    private function resolveTradeCost(array $rule): array
    {
        $system = $this->findSystemForRule($rule);
        $default = [
            'trade_cost' => (float) ($rule['default_trade_cost'] ?? 0),
            'source' => 'default_rule',
            'system_id' => $system?->id,
            'system_name' => $system?->name,
            'trade_application_id' => null,
            'trade_company' => null,
        ];

        if (!$system) {
            return $default;
        }

        $applications = TradeApplication::query()
            ->where('status', TradeApplication::STATUS_APPROVED)
            ->get()
            ->filter(function (TradeApplication $application) use ($system) {
                return in_array((int) $system->id, array_map('intval', $application->system_ids ?? []), true);
            });

        $candidates = [];
        foreach ($applications as $application) {
            $pricing = $application->system_pricing[(string) $system->id]
                ?? $application->system_pricing[$system->id]
                ?? [];

            $rate = isset($pricing['typical_rate']) ? (float) $pricing['typical_rate'] : 0.0;
            $rateUnit = Str::lower((string) ($pricing['rate_unit'] ?? ''));
            $systemMinimum = isset($pricing['minimum_charge']) ? (float) $pricing['minimum_charge'] : 0.0;
            $companyMinimum = (float) ($application->minimum_service_charge ?? 0);

            $estimatedRate = match ($rateUnit) {
                'hr' => $rate * self::SUPPORT_HOURS,
                'day' => $rate * 0.5,
                'sf', 'lf', 'ton' => 0.0,
                default => $rate,
            };

            $cost = max(array_filter([$estimatedRate, $systemMinimum, $companyMinimum], fn ($amount) => $amount > 0) ?: [0]);
            if ($cost <= 0) {
                continue;
            }

            $candidates[] = [
                'trade_cost' => round($cost, 2),
                'source' => 'approved_trade_application',
                'system_id' => $system->id,
                'system_name' => $system->name,
                'trade_application_id' => $application->id,
                'trade_company' => $application->company_name,
            ];
        }

        return collect($candidates)->sortBy('trade_cost')->first() ?: $default;
    }

    private function findSystemForRule(array $rule): ?InspectionSystem
    {
        $keywords = collect($rule['system_keywords'] ?? [])
            ->map(fn ($keyword) => Str::lower((string) $keyword))
            ->filter()
            ->values()
            ->all();

        if ($keywords === []) {
            return null;
        }

        return InspectionSystem::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->first(function (InspectionSystem $system) use ($keywords) {
                return Str::contains(Str::lower($system->name), $keywords);
            });
    }

    private function applyMargin(float $tradeCost): float
    {
        if ($tradeCost <= 0) {
            return 0.0;
        }

        return round($tradeCost / (1 - self::MARGIN_RATE), 2);
    }
}
