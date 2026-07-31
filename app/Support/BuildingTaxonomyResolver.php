<?php

namespace App\Support;

use App\Models\BuildingComponent;
use App\Models\BuildingSubsystem;
use App\Models\BuildingSystem;
use Illuminate\Support\Collection;

class BuildingTaxonomyResolver
{
    private const SYSTEM_CODES = [
        'accessibility' => 'SPC',
        'basement' => 'STR',
        'ceilings' => 'INT',
        'crawlspace' => 'STR',
        'doors' => 'ENV',
        'electrical' => 'ELE',
        'exterior' => 'ENV',
        'exterior wall' => 'ENV',
        'floor' => 'INT',
        'foundation' => 'STR',
        'garage' => 'SITE',
        'gutters' => 'ENV',
        'hvac' => 'MEC',
        'kitchen' => 'INT',
        'pest' => 'SITE',
        'plumbing' => 'PLB',
        'roof' => 'ENV',
        'safety' => 'FLS',
        'site drainage' => 'SITE',
        'stairs' => 'STR',
        'structural' => 'STR',
        'walls' => 'INT',
        'windows' => 'ENV',
    ];

    private const SUBSYSTEM_CODES = [
        'accessibility' => 'SPC-MOD',
        'basement' => 'STR-SLAB',
        'ceilings' => 'INT-CEIL',
        'crawlspace' => 'STR-FND',
        'doors' => 'ENV-DOOR',
        'electrical' => 'ELE-DIST',
        'exterior' => 'ENV-APP',
        'exterior wall' => 'ENV-WALL',
        'floor' => 'INT-FFIN',
        'foundation' => 'STR-FND',
        'garage' => 'SITE-STRUCT',
        'gutters' => 'ENV-ROOF',
        'hvac' => 'MEC-HEAT',
        'kitchen' => 'INT-CAB',
        'pest' => 'SITE-LAND',
        'plumbing' => 'PLB-WATER',
        'roof' => 'ENV-ROOF',
        'safety' => 'FLS-EGR',
        'site drainage' => 'SITE-DRAIN',
        'stairs' => 'STR-EXT',
        'structural' => 'STR-FRAME',
        'walls' => 'INT-WFIN',
        'windows' => 'ENV-FEN',
    ];

    private const COMPONENT_CODES = [
        'roof|shingles' => 'ENV-ROOF-001',
        'roof|flashing' => 'ENV-ROOF-018',
        'roof|soffit' => 'ENV-ROOF-034',
        'roof|fascia' => 'ENV-ROOF-033',
        'roof|insulation' => 'ENV-ROOF-015',
        'roof|structure' => 'STR-ROOF-002',
        'roof|skylight' => 'ENV-FEN-018',
        'roof|ridge' => 'ENV-ROOF-023',
        'roof|membrane' => 'ENV-ROOF-008',
        'roof|deck' => 'STR-ROOF-006',
        'gutters|gutter' => 'ENV-ROOF-026',
        'gutters|downspout' => 'ENV-ROOF-029',
        'gutters|slope' => 'ENV-ROOF-026',
        'gutters|seal' => 'ENV-ROOF-026',
        'gutters|overflow' => 'ENV-ROOF-026',
        'gutters|guard' => 'ENV-ROOF-026',
        'exterior wall|siding' => 'ENV-WALL-003',
        'exterior wall|masonry' => 'ENV-WALL-001',
        'exterior wall|stucco' => 'ENV-WALL-007',
        'exterior wall|cladding' => 'ENV-WALL-015',
        'exterior wall|sealant' => 'ENV-WALL-020',
        'exterior wall|insulation' => 'ENV-CTRL-001',
        'windows|glass' => 'ENV-FEN-011',
        'windows|seal' => 'ENV-FEN-017',
        'windows|frame' => 'ENV-FEN-009',
        'windows|hardware' => 'ENV-FEN-012',
        'doors|door' => 'ENV-DOOR-001',
        'doors|hardware' => 'ENV-DOOR-009',
        'floor|finish' => 'INT-FFIN-014',
        'walls|paint' => 'INT-WFIN-001',
        'walls|finish' => 'INT-WFIN-006',
        'ceilings|finish' => 'INT-CEIL-001',
        'ceilings|acoustic' => 'INT-CEIL-003',
        'plumbing|water heater' => 'PLB-DHW-001',
        'plumbing|toilet' => 'PLB-FIX-001',
        'electrical|outlet' => 'ELE-BRCH-002',
        'hvac|furnace' => 'MEC-HEAT-001',
    ];

    public static function resolve(?string $systemName, ?string $subsystemName = null): array
    {
        $systemCode = self::SYSTEM_CODES[self::key($systemName)] ?? null;
        $subsystemCode = self::componentSubsystemCode($systemName, $subsystemName)
            ?? self::SUBSYSTEM_CODES[self::key($systemName)] ?? null;
        $componentCode = self::COMPONENT_CODES[self::key($systemName) . '|' . self::key($subsystemName)] ?? null;

        $system = $systemCode ? BuildingSystem::query()->where('code', $systemCode)->first(['id']) : null;
        $subsystem = $subsystemCode ? BuildingSubsystem::query()->where('code', $subsystemCode)->first(['id', 'building_system_id']) : null;
        $component = $componentCode ? BuildingComponent::query()->where('code', $componentCode)->first(['id', 'building_subsystem_id']) : null;

        if ($component && !$subsystem) {
            $subsystem = BuildingSubsystem::query()->whereKey($component->building_subsystem_id)->first(['id', 'building_system_id']);
        }

        if ($subsystem && (!$system || (int) $system->id !== (int) $subsystem->building_system_id)) {
            $system = BuildingSystem::query()->whereKey($subsystem->building_system_id)->first(['id']);
        }

        return [
            'building_system_id' => $system?->id,
            'building_subsystem_id' => $subsystem?->id,
            'building_component_id' => $component?->id,
        ];
    }

    public static function applyToRows(array $rows): array
    {
        return array_map(static function (array $row): array {
            return $row + self::resolve($row['system_name'] ?? null, $row['subsystem_name'] ?? null);
        }, $rows);
    }

    public static function weightsByBuildingSystem(Collection $systems): array
    {
        $weights = [];

        foreach (PharCatalog::systemWeights() as $legacySystem => $weight) {
            $systemCode = self::SYSTEM_CODES[self::key($legacySystem)] ?? null;
            $system = $systemCode ? $systems->firstWhere('code', $systemCode) : null;

            if ($system) {
                $weights[$system->id] = ($weights[$system->id] ?? 0) + $weight;
            }
        }

        return $weights;
    }

    private static function componentSubsystemCode(?string $systemName, ?string $subsystemName): ?string
    {
        $componentCode = self::COMPONENT_CODES[self::key($systemName) . '|' . self::key($subsystemName)] ?? null;

        if (!$componentCode) {
            return null;
        }

        return implode('-', array_slice(explode('-', $componentCode), 0, 2));
    }

    private static function key(?string $value): string
    {
        return strtolower(trim((string) $value));
    }
}
