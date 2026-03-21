<?php

namespace App\Support;

class PharCatalog
{
    private static ?array $entries = null;

    public static function entries(): array
    {
        if (self::$entries !== null) {
            return self::$entries;
        }

        $lines = preg_split('/\r\n|\r|\n/', trim(self::rawTsv()));
        $headers = array_map([self::class, 'normalizeHeader'], explode("\t", array_shift($lines)));
        $entries = [];
        $sortOrder = 1;

        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }

            $columns = explode("\t", $line);
            $row = array_combine($headers, array_pad($columns, count($headers), null));

            $entries[] = [
                'finding_id' => trim((string) ($row['finding_id'] ?? '')),
                'system' => trim((string) ($row['system'] ?? '')),
                'subsystem' => trim((string) ($row['subsystem'] ?? '')),
                'finding' => trim((string) ($row['finding'] ?? '')),
                'finding_detail' => trim((string) ($row['finding_detail'] ?? '')),
                'priority_category' => trim((string) ($row['priority_category'] ?? '')),
                'priority_weight' => (int) self::toFloat($row['priority_weight'] ?? 0),
                'severity_level' => trim((string) ($row['severity_level'] ?? '')),
                'priority' => self::mapPriority($row['priority_category'] ?? null, $row['severity_level'] ?? null),
                'cpi_deduction' => self::toFloat($row['cpi_deduction'] ?? 0),
                'material_name' => trim((string) ($row['material'] ?? '')),
                'material_quantity' => self::toFloat($row['material_quantity'] ?? 0),
                'unit' => trim((string) ($row['unit'] ?? 'ea')),
                'unit_cost' => self::toMoney($row['unit_cost'] ?? 0),
                'material_cost' => self::toMoney($row['material_cost'] ?? 0),
                'labour_hours' => self::toFloat($row['labour_hours'] ?? 0),
                'loaded_labour_rate' => self::toMoney($row['loaded_labour_rate'] ?? 0),
                'labour_cost' => self::toMoney($row['labour_cost'] ?? 0),
                'trc' => self::toMoney($row['trc'] ?? 0),
                'scope_type' => trim((string) ($row['scope_type'] ?? 'Repair')),
                'trade_required' => trim((string) ($row['trade_required'] ?? 'General')),
                'sort_order' => $sortOrder++,
            ];
        }

        return self::$entries = $entries;
    }

    /**
     * System weights as defined in the PHAR methodology.
     * Structural is the heaviest (20); Garage is the lightest (4).
     * Total = 197 points.
     */
    public static function systemWeights(): array
    {
        return [
            'Structural'    => 20,
            'Foundation'    => 15,
            'Basement'      => 15,
            'Roof'          => 15,
            'Electrical'    => 10,
            'Plumbing'      => 10,
            'HVAC'          => 10,
            'Exterior Wall' => 10,
            'Windows'       => 8,
            'Doors'         => 8,
            'Site Drainage' => 8,
            'Gutters'       => 6,
            'Kitchen'       => 6,
            'Exterior'      => 6,
            'Stairs'        => 6,
            'Crawlspace'    => 10,
            'Floor'         => 5,
            'Walls'         => 5,
            'Ceilings'      => 5,
            'Safety'        => 5,
            'Accessibility' => 5,
            'Pest'          => 5,
            'Garage'        => 4,
        ];
    }

    public static function systemMap(): array
    {
        $map = [];

        foreach (self::entries() as $entry) {
            $map[$entry['system']][] = $entry['subsystem'];
        }

        foreach ($map as $system => $subsystems) {
            $map[$system] = array_values(array_unique($subsystems));
        }

        return $map;
    }

    public static function materials(): array
    {
        $materials = [];

        foreach (self::entries() as $entry) {
            $nameKey = self::normalizeText($entry['material_name']);
            if ($nameKey === '') {
                continue;
            }
            $key = $nameKey . '|' . self::normalizeText($entry['system']) . '|' . self::normalizeText($entry['subsystem']);
            if (isset($materials[$key])) {
                continue;
            }

            $materials[$key] = [
                'material_name' => $entry['material_name'],
                'default_unit' => $entry['unit'],
                'default_unit_cost' => $entry['unit_cost'],
                'description' => $entry['trade_required'] . ' / ' . $entry['scope_type'],
                'system_name' => $entry['system'],
                'subsystem_name' => $entry['subsystem'],
                'sort_order' => count($materials) + 1,
            ];
        }

        return array_values($materials);
    }

    public static function findingTemplates(): array
    {
        return array_map(function (array $entry): array {
            return [
                'task_question' => $entry['finding'],
                'system_name' => $entry['system'],
                'subsystem_name' => $entry['subsystem'],
                'category' => $entry['trade_required'],
                'default_included' => true,
                'default_notes' => self::buildDefaultNotes($entry),
                'sort_order' => $entry['sort_order'],
            ];
        }, self::entries());
    }

    public static function categories(): array
    {
        return array_values(array_unique(array_map(
            static fn(array $entry): string => $entry['trade_required'],
            self::entries()
        )));
    }

    public static function materialUnits(): array
    {
        return array_values(array_unique(array_map(
            static fn(array $entry): string => $entry['unit'],
            self::entries()
        )));
    }

    public static function recommendedActionsForSystem(string $systemName): array
    {
        return self::recommendationsForEntries(array_filter(
            self::entries(),
            static fn(array $entry): bool => $entry['system'] === $systemName
        ));
    }

    public static function recommendedActionsForSubsystem(string $systemName, string $subsystemName): array
    {
        return self::recommendationsForEntries(array_filter(
            self::entries(),
            static fn(array $entry): bool => $entry['system'] === $systemName && $entry['subsystem'] === $subsystemName
        ));
    }

    public static function applyDefaultsToFindings(array $findings): array
    {
        return array_map(function (array $finding): array {
            $match = self::findBestMatch($finding);

            if ($match === null) {
                return $finding;
            }

            $hasExistingMaterials = !empty($finding['phar_materials']);
            $hasExistingNotes = isset($finding['phar_notes']) && trim((string) $finding['phar_notes']) !== '';

            $finding['catalog_finding_id'] = $finding['catalog_finding_id'] ?? $match['finding_id'];
            $finding['priority'] = $finding['priority'] ?? $match['priority'];
            $finding['phar_category'] = $finding['phar_category'] ?? $match['trade_required'];

            if (!array_key_exists('phar_included_yn', $finding)) {
                $finding['phar_included_yn'] = true;
            }

            if (!array_key_exists('phar_labour_hours', $finding)) {
                $finding['phar_labour_hours'] = $match['labour_hours'];
            }

            if (!$hasExistingNotes) {
                $finding['phar_notes'] = $match['finding_detail'];
            }

            if (!$hasExistingMaterials) {
                $finding['phar_materials'] = [[
                    'material_name' => $match['material_name'],
                    'quantity' => $match['material_quantity'],
                    'unit' => $match['unit'],
                    'unit_cost' => $match['unit_cost'],
                    'line_total' => $match['material_cost'],
                    'notes' => $match['scope_type'] . ' / ' . $match['trade_required'],
                    'category' => $match['trade_required'],
                ]];
            }

            return $finding;
        }, $findings);
    }

    public static function findBestMatch(array $finding): ?array
    {
        $system = self::normalizeText($finding['system'] ?? '');
        $subsystem = self::normalizeText($finding['subsystem'] ?? '');
        $issue = self::normalizeText($finding['issue'] ?? ($finding['task_question'] ?? ''));

        if ($issue === '') {
            return null;
        }

        $matches = [];

        foreach (self::entries() as $entry) {
            $entrySystem = self::normalizeText($entry['system']);
            $entrySubsystem = self::normalizeText($entry['subsystem']);
            $entryFinding = self::normalizeText($entry['finding']);

            if ($entryFinding !== $issue) {
                continue;
            }

            $score = 1;
            if ($system !== '' && $entrySystem === $system) {
                $score += 2;
            }
            if ($subsystem !== '' && $entrySubsystem === $subsystem) {
                $score += 4;
            }

            $matches[] = ['score' => $score, 'entry' => $entry];
        }

        if ($matches === []) {
            return null;
        }

        usort($matches, static fn(array $left, array $right): int => $right['score'] <=> $left['score']);

        return $matches[0]['entry'];
    }

    private static function recommendationsForEntries(array $entries): array
    {
        $recommendations = [];

        foreach ($entries as $entry) {
            $recommendations[] = self::recommendationForEntry($entry);
        }

        $recommendations = array_values(array_unique(array_filter(array_map('trim', $recommendations))));

        return array_slice($recommendations, 0, 12);
    }

    private static function recommendationForEntry(array $entry): string
    {
        return match (strtolower($entry['scope_type'])) {
            'replace' => 'Replace ' . $entry['finding'],
            'specialist review' => 'Arrange specialist review for ' . $entry['finding'],
            default => 'Repair ' . $entry['finding'],
        };
    }

    private static function buildDefaultNotes(array $entry): string
    {
        return sprintf(
            '%s Material: %s (%s %s @ $%0.2f). Scope: %s. Trade: %s. CPI deduction: %s.',
            $entry['finding_detail'],
            $entry['material_name'],
            self::trimTrailingZeroes($entry['material_quantity']),
            $entry['unit'],
            $entry['unit_cost'],
            $entry['scope_type'],
            $entry['trade_required'],
            self::trimTrailingZeroes($entry['cpi_deduction'])
        );
    }

    private static function normalizeHeader(string $header): string
    {
        $header = strtolower(trim($header));

        return trim((string) preg_replace('/[^a-z0-9]+/', '_', $header), '_');
    }

    private static function normalizeText(?string $value): string
    {
        $value = strtolower(trim((string) $value));
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value);

        return trim((string) $value);
    }

    private static function toFloat(mixed $value): float
    {
        return (float) str_replace([',', '$'], '', trim((string) $value));
    }

    private static function toMoney(mixed $value): float
    {
        return round(self::toFloat($value), 2);
    }

    private static function mapPriority(?string $priorityCategory, ?string $severityLevel): int
    {
        $priorityCategory = strtoupper(trim((string) $priorityCategory));
        $severityLevel = strtolower(trim((string) $severityLevel));

        return match (true) {
            in_array($priorityCategory, ['SH', 'UR'], true) => 1,
            $priorityCategory === 'NOI' => 2,
            $priorityCategory === 'VD' => 3,
            in_array($severityLevel, ['critical', 'high'], true) => 1,
            $severityLevel === 'moderate' => 2,
            default => 3,
        };
    }

    private static function trimTrailingZeroes(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }

    private static function rawTsv(): string
    {
        return <<<'TSV'
Finding ID	System	Subsystem	Finding	Finding Detail	Priority Category	Priority Weight	Severity Level	CPI Deduction	Material	Material Quantity	Unit	Unit Cost	Material Cost	Labour Hours	Loaded Labour Rate	Labour Cost	TRC	Scope Type	Trade Required
PHAR-001	Roof	Shingles	Missing shingles	Observed issue in roof / shingles: missing shingles.	UR	80	Moderate	5.4	Asphalt shingles	6	pcs	$4.00	$24.00	2.0	$165.00	$330.00	$354.00	Repair	Roofing / Exterior
PHAR-002	Roof	Shingles	Cracked shingles	Observed issue in roof / shingles: cracked shingles.	UR	80	Moderate	5.4	Asphalt shingles	8	pcs	$4.00	$32.00	2.0	$165.00	$330.00	$362.00	Repair	Roofing / Exterior
PHAR-003	Roof	Shingles	Curling shingles	Observed issue in roof / shingles: curling shingles.	UR	80	Moderate	5.4	Asphalt shingles	10	pcs	$4.00	$40.00	2.0	$165.00	$330.00	$370.00	Repair	Roofing / Exterior
PHAR-004	Roof	Surface	Roof puncture	Observed issue in roof / surface: roof puncture.	UR	80	Moderate	5.4	Patch kit	1	kit	$45.00	$45.00	2.0	$165.00	$330.00	$375.00	Repair	Roofing / Exterior
PHAR-005	Roof	Flashing	Damaged flashing	Observed issue in roof / flashing: damaged flashing.	UR	80	Moderate	5.4	Flashing kit	1	kit	$55.00	$55.00	3.0	$165.00	$495.00	$550.00	Repair	Roofing / Exterior
PHAR-006	Roof	Flashing	Chimney flashing failure	Observed issue in roof / flashing: chimney flashing failure.	UR	80	Moderate	5.4	Flashing kit	1	kit	$60.00	$60.00	3.0	$165.00	$495.00	$555.00	Replace	Roofing / Exterior
PHAR-007	Roof	Ventilation	Roof vent blocked	Observed issue in roof / ventilation: roof vent blocked.	NOI	60	Moderate	4.5	Vent cover	1	ea	$25.00	$25.00	2.0	$165.00	$330.00	$355.00	Repair	Roofing / Exterior
PHAR-008	Roof	Ventilation	Attic ventilation failure	Observed issue in roof / ventilation: attic ventilation failure.	NOI	60	High	7.5	Vent kit	1	kit	$90.00	$90.00	4.0	$165.00	$660.00	$750.00	Replace	Roofing / Exterior
PHAR-009	Roof	Soffit	Soffit damage	Observed issue in roof / soffit: soffit damage.	VD	40	Moderate	3.6	Soffit panel	3	pcs	$20.00	$60.00	3.0	$165.00	$495.00	$555.00	Repair	Roofing / Exterior
PHAR-010	Roof	Fascia	Fascia rot	Observed issue in roof / fascia: fascia rot.	VD	40	Moderate	3.6	Lumber board	2	pcs	$35.00	$70.00	4.0	$165.00	$660.00	$730.00	Repair	Roofing / Exterior
PHAR-011	Roof	Insulation	Ice dam damage	Observed issue in roof / insulation: ice dam damage.	UR	80	Moderate	5.4	Insulation pack	2	packs	$45.00	$90.00	4.0	$165.00	$660.00	$750.00	Repair	Roofing / Exterior
PHAR-012	Roof	Structure	Roof sagging	Observed issue in roof / structure: roof sagging.	SH	100	Critical	16.8	Structural lumber	4	pcs	$50.00	$200.00	6.0	$165.00	$990.00	$1,190.00	Specialist Review	Roofing / Exterior
PHAR-013	Roof	Skylight	Skylight leak	Observed issue in roof / skylight: skylight leak.	UR	80	Moderate	5.4	Seal kit	1	kit	$55.00	$55.00	3.0	$165.00	$495.00	$550.00	Repair	Roofing / Exterior
PHAR-014	Roof	Skylight	Broken skylight	Observed issue in roof / skylight: broken skylight.	UR	80	High	9.0	Skylight unit	1	ea	$260.00	$260.00	4.0	$165.00	$660.00	$920.00	Repair	Roofing / Exterior
PHAR-015	Roof	Ridge	Loose ridge cap	Observed issue in roof / ridge: loose ridge cap.	UR	80	Moderate	5.4	Ridge shingles	6	pcs	$6.00	$36.00	2.0	$165.00	$330.00	$366.00	Repair	Roofing / Exterior
PHAR-016	Roof	Surface	Moss growth	Observed issue in roof / surface: moss growth.	VD	40	Low	2.4	Treatment chemical	1	bottle	$45.00	$45.00	2.0	$165.00	$330.00	$375.00	Repair	Roofing / Exterior
PHAR-017	Roof	Deck	Roof deck rot	Observed issue in roof / deck: roof deck rot.	SH	100	Critical	16.8	Plywood	2	sheets	$60.00	$120.00	5.0	$165.00	$825.00	$945.00	Repair	Carpentry / Exterior
PHAR-018	Roof	Fasteners	Nail pop	Observed issue in roof / fasteners: nail pop.	VD	40	Low	2.4	Roofing nails	1	pack	$15.00	$15.00	1.0	$165.00	$165.00	$180.00	Repair	Roofing / Exterior
PHAR-019	Roof	Membrane	Roof membrane tear	Observed issue in roof / membrane: roof membrane tear.	UR	80	Moderate	5.4	Membrane patch	1	kit	$70.00	$70.00	3.0	$165.00	$495.00	$565.00	Repair	Roofing / Exterior
PHAR-020	Roof	Lifecycle	Roof aging / end-of-life	Observed issue in roof / lifecycle: roof aging / end-of-life.	UR	80	High	9.0	Replacement patch materials	1	allowance	$150.00	$150.00	5.0	$165.00	$825.00	$975.00	Replace	Roofing / Exterior
PHAR-021	Gutters	Gutter	Blocked gutter	Observed issue in gutters / gutter: blocked gutter.	UR	80	Moderate	1.8	Cleaning kit	1	kit	$15.00	$15.00	1.0	$165.00	$165.00	$180.00	Repair	Roofing / Exterior
PHAR-022	Gutters	Gutter	Detached gutter	Observed issue in gutters / gutter: detached gutter.	UR	80	Moderate	1.8	Bracket	4	pcs	$7.00	$28.00	2.0	$165.00	$330.00	$358.00	Repair	Roofing / Exterior
PHAR-023	Gutters	Gutter	Broken gutter section	Observed issue in gutters / gutter: broken gutter section.	UR	80	Moderate	1.8	Gutter section	1	ea	$75.00	$75.00	3.0	$165.00	$495.00	$570.00	Repair	Roofing / Exterior
PHAR-024	Gutters	Downspout	Downspout disconnected	Observed issue in gutters / downspout: downspout disconnected.	UR	80	Moderate	1.8	Downspout bracket	2	pcs	$10.00	$20.00	1.0	$165.00	$165.00	$185.00	Repair	Roofing / Exterior
PHAR-025	Gutters	Downspout	Downspout clog	Observed issue in gutters / downspout: downspout clog.	UR	80	Moderate	1.8	Drain auger	1	ea	$25.00	$25.00	1.0	$165.00	$165.00	$190.00	Repair	Roofing / Exterior
PHAR-026	Gutters	Downspout	Missing extension	Observed issue in gutters / downspout: missing extension.	UR	80	Moderate	1.8	Extension pipe	1	ea	$30.00	$30.00	1.0	$165.00	$165.00	$195.00	Repair	Roofing / Exterior
PHAR-027	Gutters	Gutter	Gutter corrosion	Observed issue in gutters / gutter: gutter corrosion.	VD	40	Moderate	1.2	Gutter section	1	ea	$80.00	$80.00	3.0	$165.00	$495.00	$575.00	Repair	Roofing / Exterior
PHAR-028	Gutters	Gutter	Gutter sagging	Observed issue in gutters / gutter: gutter sagging.	UR	80	Moderate	1.8	Hanger brackets	6	pcs	$6.00	$36.00	2.0	$165.00	$330.00	$366.00	Repair	Roofing / Exterior
PHAR-029	Gutters	Gutter	Ice-damaged gutter	Observed issue in gutters / gutter: ice-damaged gutter.	UR	80	Moderate	1.8	Replacement gutter	1	ea	$120.00	$120.00	4.0	$165.00	$660.00	$780.00	Repair	Roofing / Exterior
PHAR-030	Gutters	Slope	Improper gutter slope	Observed issue in gutters / slope: improper gutter slope.	UR	80	Moderate	1.8	Refastening hardware	1	allowance	$30.00	$30.00	3.0	$165.00	$495.00	$525.00	Repair	Roofing / Exterior
PHAR-031	Gutters	Seal	Gutter leak	Observed issue in gutters / seal: gutter leak.	UR	80	Moderate	1.8	Sealant	1	tube	$15.00	$15.00	1.0	$165.00	$165.00	$180.00	Repair	Roofing / Exterior
PHAR-032	Gutters	Overflow	Gutter overflow	Observed issue in gutters / overflow: gutter overflow.	UR	80	Moderate	1.8	Cleaning materials	1	allowance	$15.00	$15.00	1.0	$165.00	$165.00	$180.00	Repair	Roofing / Exterior
PHAR-033	Site Drainage	Discharge	Water discharge near foundation	Observed issue in site drainage / discharge: water discharge near foundation.	UR	80	Moderate	3.6	Drain pipe	2	pcs	$20.00	$40.00	2.0	$165.00	$330.00	$370.00	Repair	General
PHAR-034	Gutters	Guard	Missing gutter guard	Observed issue in gutters / guard: missing gutter guard.	VD	40	Low	0.8	Guard kit	1	kit	$60.00	$60.00	2.0	$165.00	$330.00	$390.00	Repair	Roofing / Exterior
PHAR-035	Gutters	Guard	Broken gutter guard	Observed issue in gutters / guard: broken gutter guard.	VD	40	Low	0.8	Guard section	1	ea	$40.00	$40.00	2.0	$165.00	$330.00	$370.00	Repair	Roofing / Exterior
PHAR-036	Exterior Wall	Siding	Cracked siding	Observed issue in exterior wall / siding: cracked siding.	VD	40	Low	1.6	Siding panel	2	pcs	$25.00	$50.00	2.0	$165.00	$330.00	$380.00	Repair	Roofing / Exterior
PHAR-037	Exterior Wall	Siding	Loose siding	Observed issue in exterior wall / siding: loose siding.	VD	40	Low	1.6	Fasteners	1	allowance	$20.00	$20.00	2.0	$165.00	$330.00	$350.00	Repair	Roofing / Exterior
PHAR-038	Exterior Wall	Moisture	Water intrusion	Observed issue in exterior wall / moisture: water intrusion.	UR	80	High	6.0	Flashing	2	pcs	$30.00	$60.00	4.0	$165.00	$660.00	$720.00	Repair	Roofing / Exterior
PHAR-039	Exterior Wall	Framing	Exterior rot	Observed issue in exterior wall / framing: exterior rot.	UR	80	Moderate	3.6	Lumber	2	pcs	$40.00	$80.00	4.0	$165.00	$660.00	$740.00	Repair	Roofing / Exterior
PHAR-040	Exterior Wall	Sealant	Failed exterior caulking	Observed issue in exterior wall / sealant: failed exterior caulking.	VD	40	Low	1.6	Sealant	1	tube	$25.00	$25.00	2.0	$165.00	$330.00	$355.00	Repair	Roofing / Exterior
PHAR-041	Exterior Wall	Masonry	Brick mortar deterioration	Observed issue in exterior wall / masonry: brick mortar deterioration.	VD	40	Moderate	2.4	Mortar mix	1	bag	$40.00	$40.00	3.0	$165.00	$495.00	$535.00	Repair	Roofing / Exterior
PHAR-042	Exterior Wall	Masonry	Brick crack	Observed issue in exterior wall / masonry: brick crack.	UR	80	Moderate	3.6	Masonry repair material	1	allowance	$70.00	$70.00	4.0	$165.00	$660.00	$730.00	Repair	Roofing / Exterior
PHAR-043	Exterior Wall	Stucco	Stucco cracking	Observed issue in exterior wall / stucco: stucco cracking.	VD	40	Moderate	2.4	Stucco kit	1	kit	$45.00	$45.00	3.0	$165.00	$495.00	$540.00	Repair	Roofing / Exterior
PHAR-044	Exterior Wall	Cladding	Cladding separation	Observed issue in exterior wall / cladding: cladding separation.	UR	80	Moderate	3.6	Anchors	1	allowance	$35.00	$35.00	3.0	$165.00	$495.00	$530.00	Repair	Roofing / Exterior
PHAR-045	Exterior Wall	Insulation	Exterior insulation failure	Observed issue in exterior wall / insulation: exterior insulation failure.	NOI	60	High	5.0	Insulation board	2	pcs	$35.00	$70.00	4.0	$165.00	$660.00	$730.00	Replace	Roofing / Exterior
PHAR-046	Exterior Wall	Finish	Paint failure	Observed issue in exterior wall / finish: paint failure.	VD	40	Moderate	2.4	Exterior paint	1	allowance	$50.00	$50.00	3.0	$165.00	$495.00	$545.00	Replace	Roofing / Exterior
PHAR-047	Exterior Wall	Structure	Wall warping	Observed issue in exterior wall / structure: wall warping.	SH	100	High	7.0	Structural reinforcement material	1	allowance	$180.00	$180.00	5.0	$165.00	$825.00	$1,005.00	Repair	Roofing / Exterior
PHAR-048	Exterior Wall	Drainage Plane	Moisture behind siding	Observed issue in exterior wall / drainage plane: moisture behind siding.	UR	80	High	6.0	Wrap / membrane	1	allowance	$75.00	$75.00	5.0	$165.00	$825.00	$900.00	Repair	Roofing / Exterior
PHAR-049	Exterior Wall	Pest	Exterior termite damage	Observed issue in exterior wall / pest: exterior termite damage.	SH	100	High	7.0	Pest treatment	1	treatment	$150.00	$150.00	4.0	$165.00	$660.00	$810.00	Repair	Roofing / Exterior
PHAR-050	Exterior Wall	Impact	Impact damage	Observed issue in exterior wall / impact: impact damage.	VD	40	Moderate	2.4	Replacement panel	1	allowance	$55.00	$55.00	3.0	$165.00	$495.00	$550.00	Repair	Roofing / Exterior
PHAR-051	Exterior Wall	UV Wear	UV damage to siding	Observed issue in exterior wall / uv wear: uv damage to siding.	VD	40	Low	1.6	Siding panel	2	pcs	$25.00	$50.00	2.0	$165.00	$330.00	$380.00	Repair	Roofing / Exterior
PHAR-052	Exterior Wall	Fastening	Nail pop in siding	Observed issue in exterior wall / fastening: nail pop in siding.	VD	40	Low	1.6	Fasteners	1	allowance	$20.00	$20.00	1.5	$165.00	$247.50	$267.50	Repair	Roofing / Exterior
PHAR-053	Exterior Wall	Surface	Exterior mold	Observed issue in exterior wall / surface: exterior mold.	SH	100	High	7.0	Mold treatment	1	allowance	$60.00	$60.00	3.0	$165.00	$495.00	$555.00	Repair	Roofing / Exterior
PHAR-054	Exterior Wall	Structure	Structural wall crack	Observed issue in exterior wall / structure: structural wall crack.	SH	100	Critical	11.2	Structural repair material	1	allowance	$180.00	$180.00	5.0	$165.00	$825.00	$1,005.00	Specialist Review	Roofing / Exterior
PHAR-055	Exterior Wall	Joint	Expansion joint failure	Observed issue in exterior wall / joint: expansion joint failure.	UR	80	Moderate	3.6	Joint sealant	2	tubes	$18.00	$36.00	2.0	$165.00	$330.00	$366.00	Replace	Roofing / Exterior
PHAR-056	Windows	Glass	Broken window glass	Observed issue in windows / glass: broken window glass.	UR	80	Moderate	2.7	Glass panel	1	ea	$80.00	$80.00	2.0	$165.00	$330.00	$410.00	Repair	Carpentry / Doors
PHAR-057	Windows	Seal	Window seal failure	Observed issue in windows / seal: window seal failure.	VD	40	Low	1.2	Window unit	1	ea	$120.00	$120.00	2.0	$165.00	$330.00	$450.00	Replace	Carpentry / Doors
PHAR-058	Windows	Frame	Window frame rot	Observed issue in windows / frame: window frame rot.	UR	80	Moderate	2.7	Lumber	2	pcs	$30.00	$60.00	3.0	$165.00	$495.00	$555.00	Repair	Carpentry / Doors
PHAR-059	Windows	Draft	Window air leak	Observed issue in windows / draft: window air leak.	NOI	60	Moderate	2.2	Weather stripping	1	kit	$25.00	$25.00	1.0	$165.00	$165.00	$190.00	Repair	Carpentry / Doors
PHAR-060	Windows	Hardware	Window hardware failure	Observed issue in windows / hardware: window hardware failure.	VD	40	Low	1.2	Hardware kit	1	kit	$30.00	$30.00	1.0	$165.00	$165.00	$195.00	Replace	Carpentry / Doors
PHAR-061	Doors	Alignment	Door misalignment	Observed issue in doors / alignment: door misalignment.	NOI	60	Moderate	1.5	Hinge kit	1	kit	$20.00	$20.00	1.0	$165.00	$165.00	$185.00	Repair	Carpentry / Doors
PHAR-062	Doors	Frame	Door frame rot	Observed issue in doors / frame: door frame rot.	UR	80	Moderate	1.8	Lumber	2	pcs	$35.00	$70.00	3.0	$165.00	$495.00	$565.00	Repair	Carpentry / Doors
PHAR-063	Doors	Seal	Door seal failure	Observed issue in doors / seal: door seal failure.	VD	40	Low	0.8	Weather strip	1	kit	$20.00	$20.00	1.0	$165.00	$165.00	$185.00	Replace	Carpentry / Doors
PHAR-064	Doors	Sliding Track	Sliding track damage	Observed issue in doors / sliding track: sliding track damage.	NOI	60	Moderate	1.5	Track kit	1	kit	$45.00	$45.00	2.0	$165.00	$330.00	$375.00	Repair	Carpentry / Doors
PHAR-065	Doors	Patio	Patio door leak	Observed issue in doors / patio: patio door leak.	UR	80	Moderate	1.8	Sealant	1	tube	$25.00	$25.00	2.0	$165.00	$330.00	$355.00	Repair	Carpentry / Doors
PHAR-066	Doors	Glass	Broken door glass	Observed issue in doors / glass: broken door glass.	UR	80	Moderate	1.8	Glass panel	1	ea	$80.00	$80.00	2.0	$165.00	$330.00	$410.00	Repair	Carpentry / Doors
PHAR-067	Windows	Locking	Window latch failure	Observed issue in windows / locking: window latch failure.	VD	40	Low	1.2	Latch kit	1	kit	$22.00	$22.00	1.0	$165.00	$165.00	$187.00	Replace	Carpentry / Doors
PHAR-068	Windows	Condensation	Window condensation between panes	Observed issue in windows / condensation: window condensation between panes.	VD	40	Low	1.2	IGU unit	1	ea	$120.00	$120.00	2.0	$165.00	$330.00	$450.00	Repair	Carpentry / Doors
PHAR-069	Doors	Exterior Slab	Exterior door rot	Observed issue in doors / exterior slab: exterior door rot.	UR	80	High	3.0	Door slab	1	ea	$180.00	$180.00	4.0	$165.00	$660.00	$840.00	Repair	Carpentry / Doors
PHAR-070	Doors	Interior Slab	Interior door damage	Observed issue in doors / interior slab: interior door damage.	VD	40	Low	0.8	Patch materials	1	allowance	$35.00	$35.00	2.0	$165.00	$330.00	$365.00	Repair	Carpentry / Doors
PHAR-071	Doors	Threshold	Door threshold failure	Observed issue in doors / threshold: door threshold failure.	UR	80	Moderate	1.8	Threshold	1	ea	$35.00	$35.00	2.0	$165.00	$330.00	$365.00	Replace	Carpentry / Doors
PHAR-072	Windows	Flashing	Window flashing failure	Observed issue in windows / flashing: window flashing failure.	UR	80	Moderate	2.7	Flashing kit	1	kit	$45.00	$45.00	3.0	$165.00	$495.00	$540.00	Replace	Carpentry / Doors
PHAR-073	Doors	Storm Door	Storm door damage	Observed issue in doors / storm door: storm door damage.	VD	40	Low	0.8	Replacement part	1	allowance	$60.00	$60.00	2.0	$165.00	$330.00	$390.00	Repair	Carpentry / Doors
PHAR-074	Doors	Security	Lock failure	Observed issue in doors / security: lock failure.	SH	100	High	3.5	Lock kit	1	kit	$40.00	$40.00	1.0	$165.00	$165.00	$205.00	Replace	Carpentry / Doors
PHAR-075	Doors	Hinges	Hinge corrosion	Observed issue in doors / hinges: hinge corrosion.	VD	40	Low	0.8	Hinges	3	pcs	$8.00	$24.00	1.0	$165.00	$165.00	$189.00	Repair	Carpentry / Doors
PHAR-076	Foundation	Wall	Hairline crack	Observed issue in foundation / wall: hairline crack.	UR	80	Moderate	6.3	Epoxy kit	1	kit	$70.00	$70.00	3.0	$165.00	$495.00	$565.00	Repair	Structural / Waterproofing
PHAR-077	Foundation	Structure	Structural foundation crack	Observed issue in foundation / structure: structural foundation crack.	SH	100	Critical	19.6	Structural epoxy	2	kits	$65.00	$130.00	4.0	$165.00	$660.00	$790.00	Specialist Review	Structural / Waterproofing
PHAR-078	Basement	Waterproofing	Water seepage	Observed issue in basement / waterproofing: water seepage.	UR	80	High	9.0	Waterproof membrane	2	rolls	$70.00	$140.00	6.0	$165.00	$990.00	$1,130.00	Repair	Structural / Waterproofing
PHAR-079	Basement	Flooding	Flooding risk	Observed issue in basement / flooding: flooding risk.	UR	80	High	9.0	Drain pipe	4	pcs	$45.00	$180.00	6.0	$165.00	$990.00	$1,170.00	Repair	Structural / Waterproofing
PHAR-080	Foundation	Settlement	Settlement signs	Observed issue in foundation / settlement: settlement signs.	SH	100	High	12.2	Steel reinforcement	3	pcs	$100.00	$300.00	6.0	$165.00	$990.00	$1,290.00	Specialist Review	Structural / Waterproofing
PHAR-081	Foundation	Surface	Foundation spalling	Observed issue in foundation / surface: foundation spalling.	VD	40	Moderate	4.2	Repair mortar	2	bags	$25.00	$50.00	3.0	$165.00	$495.00	$545.00	Repair	Structural / Waterproofing
PHAR-082	Basement	Mold	Basement mold	Observed issue in basement / mold: basement mold.	SH	100	High	10.5	Mold treatment	2	units	$45.00	$90.00	4.0	$165.00	$660.00	$750.00	Repair	Structural / Waterproofing
PHAR-083	Basement	Salts	Efflorescence	Observed issue in basement / salts: efflorescence.	VD	40	Low	2.4	Cleaning chemicals	1	allowance	$40.00	$40.00	2.0	$165.00	$330.00	$370.00	Repair	Structural / Waterproofing
PHAR-084	Basement	Slab	Basement floor crack	Observed issue in basement / slab: basement floor crack.	VD	40	Moderate	3.6	Concrete patch	2	bags	$30.00	$60.00	3.0	$165.00	$495.00	$555.00	Repair	Structural / Waterproofing
PHAR-085	Basement	Slab	Basement slab settlement	Observed issue in basement / slab: basement slab settlement.	UR	80	High	9.0	Leveling material	3	bags	$45.00	$135.00	5.0	$165.00	$825.00	$960.00	Repair	Structural / Waterproofing
PHAR-086	Basement	Drainage	Drainage failure	Observed issue in basement / drainage: drainage failure.	UR	80	High	9.0	Drain pipe	4	pcs	$45.00	$180.00	5.0	$165.00	$825.00	$1,005.00	Replace	Structural / Waterproofing
PHAR-087	Basement	Moisture	High humidity	Observed issue in basement / moisture: high humidity.	NOI	60	Moderate	4.5	Dehumidifier allowance	1	allowance	$90.00	$90.00	1.5	$165.00	$247.50	$337.50	Repair	Structural / Waterproofing
PHAR-088	Foundation	Wall	Wall bowing	Observed issue in foundation / wall: wall bowing.	SH	100	Critical	19.6	Brace materials	1	allowance	$220.00	$220.00	6.0	$165.00	$990.00	$1,210.00	Specialist Review	Structural / Waterproofing
PHAR-089	Basement	Joint	Joint water penetration	Observed issue in basement / joint: joint water penetration.	UR	80	Moderate	5.4	Sealant	2	tubes	$18.00	$36.00	3.0	$165.00	$495.00	$531.00	Repair	Structural / Waterproofing
PHAR-090	Foundation	Block	Block foundation crack	Observed issue in foundation / block: block foundation crack.	UR	80	Moderate	6.3	Mortar	2	bags	$20.00	$40.00	4.0	$165.00	$660.00	$700.00	Repair	Structural / Waterproofing
PHAR-091	Basement	Insulation	Basement insulation failure	Observed issue in basement / insulation: basement insulation failure.	NOI	60	High	7.5	Insulation	2	packs	$35.00	$70.00	3.0	$165.00	$495.00	$565.00	Replace	Structural / Waterproofing
PHAR-092	Basement	Wall	Wall moisture intrusion	Observed issue in basement / wall: wall moisture intrusion.	UR	80	High	9.0	Membrane	1	allowance	$80.00	$80.00	5.0	$165.00	$825.00	$905.00	Repair	Structural / Waterproofing
PHAR-093	Basement	Odor	Basement odor / poor ventilation	Observed issue in basement / odor: basement odor / poor ventilation.	NOI	60	Moderate	4.5	Ventilation materials	1	allowance	$50.00	$50.00	2.0	$165.00	$330.00	$380.00	Repair	Structural / Waterproofing
PHAR-094	Basement	Pest	Pest intrusion	Observed issue in basement / pest: pest intrusion.	SH	100	High	10.5	Pest barrier	2	units	$40.00	$80.00	3.0	$165.00	$495.00	$575.00	Repair	Structural / Waterproofing
PHAR-095	Structure	Beam	Structural beam rot	Observed issue in structure / beam: structural beam rot.	SH	100	Critical	8.4	Lumber	3	pcs	$60.00	$180.00	5.0	$165.00	$825.00	$1,005.00	Specialist Review	General
PHAR-096	Basement	Window	Basement window leak	Observed issue in basement / window: basement window leak.	UR	80	Moderate	5.4	Sealant	1	tube	$25.00	$25.00	2.0	$165.00	$330.00	$355.00	Repair	Structural / Waterproofing
PHAR-097	Basement	Window Well	Window well flooding	Observed issue in basement / window well: window well flooding.	UR	80	High	9.0	Drain materials	1	allowance	$70.00	$70.00	3.0	$165.00	$495.00	$565.00	Repair	Structural / Waterproofing
PHAR-098	Basement	Window Well	Window well rust	Observed issue in basement / window well: window well rust.	VD	40	Low	2.4	Metal well section	1	ea	$60.00	$60.00	2.0	$165.00	$330.00	$390.00	Repair	Structural / Waterproofing
PHAR-099	Basement	Door	Basement door leak	Observed issue in basement / door: basement door leak.	UR	80	Moderate	5.4	Sealant	1	tube	$25.00	$25.00	2.0	$165.00	$330.00	$355.00	Repair	Structural / Waterproofing
PHAR-100	Basement	Ceiling	Basement ceiling water stain	Observed issue in basement / ceiling: basement ceiling water stain.	UR	80	Moderate	5.4	Drywall patch materials	1	allowance	$45.00	$45.00	3.0	$165.00	$495.00	$540.00	Repair	Structural / Waterproofing
PHAR-101	Floor	Level	Uneven flooring	Observed issue in floor / level: uneven flooring.	NOI	60	High	3.8	Level compound	2	bags	$40.00	$80.00	4.0	$165.00	$660.00	$740.00	Repair	Carpentry / Finishes
PHAR-102	Floor	Surface	Floor cracks	Observed issue in floor / surface: floor cracks.	VD	40	Moderate	1.8	Patch compound	2	bags	$30.00	$60.00	3.0	$165.00	$495.00	$555.00	Repair	Carpentry / Finishes
PHAR-103	Floor	Finish	Loose flooring	Observed issue in floor / finish: loose flooring.	VD	40	Low	1.2	Adhesive	1	unit	$40.00	$40.00	2.0	$165.00	$330.00	$370.00	Repair	Carpentry / Finishes
PHAR-104	Floor	Tile	Damaged tiles	Observed issue in floor / tile: damaged tiles.	VD	40	Moderate	1.8	Tile pieces	8	pcs	$10.00	$80.00	3.0	$165.00	$495.00	$575.00	Repair	Carpentry / Finishes
PHAR-105	Floor	Safety	Slippery surface	Observed issue in floor / safety: slippery surface.	SH	100	High	5.2	Anti-slip coating	1	unit	$50.00	$50.00	2.0	$165.00	$330.00	$380.00	Repair	Carpentry / Finishes
PHAR-106	Floor	Wood	Warped wood flooring	Observed issue in floor / wood: warped wood flooring.	UR	80	Moderate	2.7	Boards	6	pcs	$20.00	$120.00	4.0	$165.00	$660.00	$780.00	Repair	Carpentry / Finishes
PHAR-107	Floor	Laminate	Laminate damage	Observed issue in floor / laminate: laminate damage.	VD	40	Moderate	1.8	Boards	8	pcs	$10.00	$80.00	3.0	$165.00	$495.00	$575.00	Repair	Carpentry / Finishes
PHAR-108	Floor	Subfloor	Subfloor rot	Observed issue in floor / subfloor: subfloor rot.	SH	100	Critical	8.4	Plywood	2	sheets	$70.00	$140.00	5.0	$165.00	$825.00	$965.00	Repair	Carpentry / Finishes
PHAR-109	Floor	Carpet	Carpet damage	Observed issue in floor / carpet: carpet damage.	VD	40	Low	1.2	Carpet patch	1	allowance	$60.00	$60.00	2.0	$165.00	$330.00	$390.00	Repair	Carpentry / Finishes
PHAR-110	Floor	Framing	Squeaking floor	Observed issue in floor / framing: squeaking floor.	NOI	60	Moderate	2.2	Screws	1	pack	$20.00	$20.00	2.0	$165.00	$330.00	$350.00	Repair	Carpentry / Finishes
PHAR-111	Floor	Moisture	Floor moisture	Observed issue in floor / moisture: floor moisture.	UR	80	Moderate	2.7	Vapor barrier	1	allowance	$75.00	$75.00	4.0	$165.00	$660.00	$735.00	Repair	Carpentry / Finishes
PHAR-112	Floor	Slope	Floor slope	Observed issue in floor / slope: floor slope.	SH	100	High	5.2	Structural adjustment materials	1	allowance	$180.00	$180.00	5.0	$165.00	$825.00	$1,005.00	Repair	Carpentry / Finishes
PHAR-113	Floor	Threshold	Threshold damage	Observed issue in floor / threshold: threshold damage.	VD	40	Low	1.2	Threshold	1	ea	$35.00	$35.00	2.0	$165.00	$330.00	$365.00	Repair	Carpentry / Finishes
PHAR-114	Floor	Transition	Trip hazard transition	Observed issue in floor / transition: trip hazard transition.	SH	100	High	5.2	Transition strip	1	ea	$20.00	$20.00	1.0	$165.00	$165.00	$185.00	Repair	Carpentry / Finishes
PHAR-115	Floor	Tile	Tile grout failure	Observed issue in floor / tile: tile grout failure.	VD	40	Low	1.2	Grout	1	unit	$15.00	$15.00	2.0	$165.00	$330.00	$345.00	Replace	Carpentry / Finishes
PHAR-116	Floor	Tile	Tile crack	Observed issue in floor / tile: tile crack.	VD	40	Low	1.2	Tile pieces	4	pcs	$10.00	$40.00	2.0	$165.00	$330.00	$370.00	Repair	Carpentry / Finishes
PHAR-117	Floor	Finish	Flooring separation	Observed issue in floor / finish: flooring separation.	VD	40	Low	1.2	Adhesive	1	unit	$40.00	$40.00	2.0	$165.00	$330.00	$370.00	Repair	Carpentry / Finishes
PHAR-118	Floor	Hardwood	Hardwood scratches	Observed issue in floor / hardwood: hardwood scratches.	VD	40	Moderate	1.8	Refinish materials	1	allowance	$50.00	$50.00	2.5	$165.00	$412.50	$462.50	Repair	Carpentry / Finishes
PHAR-119	Floor	Hardwood	Hardwood dents	Observed issue in floor / hardwood: hardwood dents.	VD	40	Moderate	1.8	Sanding materials	1	allowance	$45.00	$45.00	2.5	$165.00	$412.50	$457.50	Repair	Carpentry / Finishes
PHAR-120	Floor	Hardwood	Hardwood gouges	Observed issue in floor / hardwood: hardwood gouges.	VD	40	Moderate	1.8	Board replacement	3	pcs	$20.00	$60.00	4.0	$165.00	$660.00	$720.00	Repair	Carpentry / Finishes
PHAR-121	Walls	Drywall	Wall crack	Observed issue in walls / drywall: wall crack.	VD	40	Low	0.8	Joint compound	1	unit	$18.00	$18.00	2.0	$165.00	$330.00	$348.00	Repair	Carpentry / Finishes
PHAR-122	Walls	Drywall	Wall dent	Observed issue in walls / drywall: wall dent.	VD	40	Low	0.8	Patch kit	1	kit	$20.00	$20.00	1.0	$165.00	$165.00	$185.00	Repair	Carpentry / Finishes
PHAR-123	Walls	Drywall	Wall gouge	Observed issue in walls / drywall: wall gouge.	VD	40	Low	0.8	Drywall patch	1	kit	$25.00	$25.00	2.0	$165.00	$330.00	$355.00	Repair	Carpentry / Finishes
PHAR-124	Walls	Finish	Wall scratch	Observed issue in walls / finish: wall scratch.	VD	40	Low	0.8	Paint	1	allowance	$20.00	$20.00	1.0	$165.00	$165.00	$185.00	Repair	Carpentry / Finishes
PHAR-125	Walls	Structure	Structural wall crack	Observed issue in walls / structure: structural wall crack.	SH	100	Critical	5.6	Structural epoxy	1	allowance	$180.00	$180.00	5.0	$165.00	$825.00	$1,005.00	Specialist Review	Carpentry / Finishes
PHAR-126	Ceilings	Finish	Ceiling crack	Observed issue in ceilings / finish: ceiling crack.	VD	40	Low	0.8	Joint compound	1	unit	$18.00	$18.00	2.0	$165.00	$330.00	$348.00	Repair	Carpentry / Finishes
PHAR-127	Ceilings	Moisture	Ceiling water stain	Observed issue in ceilings / moisture: ceiling water stain.	UR	80	Moderate	1.8	Drywall materials	1	allowance	$45.00	$45.00	3.0	$165.00	$495.00	$540.00	Repair	Carpentry / Finishes
PHAR-128	Ceilings	Structure	Ceiling sag	Observed issue in ceilings / structure: ceiling sag.	SH	100	Critical	5.6	Structural repair materials	1	allowance	$120.00	$120.00	4.0	$165.00	$660.00	$780.00	Specialist Review	Carpentry / Finishes
PHAR-129	Ceilings	Mold	Ceiling mold	Observed issue in ceilings / mold: ceiling mold.	SH	100	High	3.5	Mold treatment	1	allowance	$60.00	$60.00	3.0	$165.00	$495.00	$555.00	Repair	Carpentry / Finishes
PHAR-130	Ceilings	Insulation	Ceiling insulation gap	Observed issue in ceilings / insulation: ceiling insulation gap.	NOI	60	Moderate	1.5	Insulation	1	pack	$35.00	$35.00	2.0	$165.00	$330.00	$365.00	Repair	Carpentry / Finishes
PHAR-131	Walls	Paint	Paint peeling	Observed issue in walls / paint: paint peeling.	VD	40	Low	0.8	Paint	1	allowance	$30.00	$30.00	2.0	$165.00	$330.00	$360.00	Repair	Carpentry / Finishes
PHAR-132	Walls	Wallpaper	Wallpaper peeling	Observed issue in walls / wallpaper: wallpaper peeling.	VD	40	Low	0.8	Adhesive	1	unit	$18.00	$18.00	2.0	$165.00	$330.00	$348.00	Repair	Carpentry / Finishes
PHAR-133	Walls	Moisture	Wall moisture	Observed issue in walls / moisture: wall moisture.	UR	80	Moderate	1.8	Sealant	1	tube	$20.00	$20.00	3.0	$165.00	$495.00	$515.00	Repair	Carpentry / Finishes
PHAR-134	Walls	Corner	Corner bead damage	Observed issue in walls / corner: corner bead damage.	VD	40	Low	0.8	Bead kit	1	kit	$25.00	$25.00	2.0	$165.00	$330.00	$355.00	Repair	Carpentry / Finishes
PHAR-135	Walls	Joint	Joint tape failure	Observed issue in walls / joint: joint tape failure.	VD	40	Low	0.8	Joint tape	1	roll	$12.00	$12.00	2.0	$165.00	$330.00	$342.00	Replace	Carpentry / Finishes
PHAR-136	Ceilings	Fixture	Ceiling fixture damage	Observed issue in ceilings / fixture: ceiling fixture damage.	NOI	60	Moderate	1.5	Fixture	1	ea	$45.00	$45.00	2.0	$165.00	$330.00	$375.00	Repair	Carpentry / Finishes
PHAR-137	Ceilings	Acoustic	Acoustic ceiling damage	Observed issue in ceilings / acoustic: acoustic ceiling damage.	VD	40	Low	0.8	Tile	2	pcs	$15.00	$30.00	2.0	$165.00	$330.00	$360.00	Repair	Carpentry / Finishes
PHAR-138	Ceilings	Acoustic	Missing ceiling tile	Observed issue in ceilings / acoustic: missing ceiling tile.	VD	40	Low	0.8	Ceiling tile	2	pcs	$10.00	$20.00	1.0	$165.00	$165.00	$185.00	Repair	Carpentry / Finishes
PHAR-139	Walls	Plaster	Interior plaster crack	Observed issue in walls / plaster: interior plaster crack.	VD	40	Moderate	1.2	Plaster	1	allowance	$35.00	$35.00	3.0	$165.00	$495.00	$530.00	Repair	Carpentry / Finishes
PHAR-140	Walls	Mold	Interior mold growth	Observed issue in walls / mold: interior mold growth.	SH	100	High	3.5	Remediation material	1	allowance	$60.00	$60.00	3.0	$165.00	$495.00	$555.00	Repair	Carpentry / Finishes
PHAR-141	Walls	Insulation	Wall insulation damage	Observed issue in walls / insulation: wall insulation damage.	NOI	60	Moderate	1.5	Insulation	1	pack	$35.00	$35.00	3.0	$165.00	$495.00	$530.00	Repair	Carpentry / Finishes
PHAR-142	Walls	Moisture	Interior wall moisture	Observed issue in walls / moisture: interior wall moisture.	UR	80	Moderate	1.8	Sealant	1	tube	$20.00	$20.00	3.0	$165.00	$495.00	$515.00	Repair	Carpentry / Finishes
PHAR-143	Walls	Framing	Wall framing rot	Observed issue in walls / framing: wall framing rot.	SH	100	High	3.5	Lumber	2	pcs	$40.00	$80.00	5.0	$165.00	$825.00	$905.00	Repair	Carpentry / Finishes
PHAR-144	Walls	Structure	Wall bowing	Observed issue in walls / structure: wall bowing.	SH	100	Critical	5.6	Structural repair materials	1	allowance	$180.00	$180.00	5.0	$165.00	$825.00	$1,005.00	Specialist Review	Carpentry / Finishes
PHAR-145	Walls	Finish	Interior paint failure	Observed issue in walls / finish: interior paint failure.	VD	40	Moderate	1.2	Paint	1	allowance	$35.00	$35.00	3.0	$165.00	$495.00	$530.00	Replace	Carpentry / Finishes
PHAR-146	Electrical	Panel	Outdated electrical panel	Observed issue in electrical / panel: outdated electrical panel.	SH	100	High	10.5	Panel	1	ea	$400.00	$400.00	8.0	$165.00	$1,320.00	$1,720.00	Repair	Electrical
PHAR-147	Electrical	Breaker	Overloaded breaker	Observed issue in electrical / breaker: overloaded breaker.	SH	100	High	10.5	Breaker	2	pcs	$30.00	$60.00	2.0	$165.00	$330.00	$390.00	Repair	Electrical
PHAR-148	Electrical	Wiring	Exposed wiring	Observed issue in electrical / wiring: exposed wiring.	SH	100	High	10.5	Wire roll	1	roll	$80.00	$80.00	3.0	$165.00	$495.00	$575.00	Repair	Electrical
PHAR-149	Electrical	Safety	Electrical shock hazard	Observed issue in electrical / safety: electrical shock hazard.	SH	100	Critical	16.8	Wiring repair material	1	allowance	$90.00	$90.00	4.0	$165.00	$660.00	$750.00	Repair	Electrical
PHAR-150	Electrical	Outlet	Missing GFCI	Observed issue in electrical / outlet: missing gfci.	SH	100	High	10.5	GFCI outlet	1	ea	$30.00	$30.00	1.0	$165.00	$165.00	$195.00	Repair	Electrical
PHAR-151	Electrical	Outlet	Faulty outlet	Observed issue in electrical / outlet: faulty outlet.	NOI	60	Moderate	4.5	Outlet	1	ea	$15.00	$15.00	1.0	$165.00	$165.00	$180.00	Repair	Electrical
PHAR-152	Electrical	Lighting	Light fixture failure	Observed issue in electrical / lighting: light fixture failure.	NOI	60	High	7.5	Fixture	1	ea	$60.00	$60.00	1.0	$165.00	$165.00	$225.00	Replace	Electrical
PHAR-153	Electrical	Lighting	Flickering lights	Observed issue in electrical / lighting: flickering lights.	UR	80	Moderate	5.4	Wiring repair material	1	allowance	$40.00	$40.00	2.0	$165.00	$330.00	$370.00	Repair	Electrical
PHAR-154	Electrical	Switch	Switch failure	Observed issue in electrical / switch: switch failure.	NOI	60	High	7.5	Switch	1	ea	$12.00	$12.00	1.0	$165.00	$165.00	$177.00	Replace	Electrical
PHAR-155	Electrical	Box	Loose electrical box	Observed issue in electrical / box: loose electrical box.	SH	100	High	10.5	Box kit	1	kit	$20.00	$20.00	2.0	$165.00	$330.00	$350.00	Repair	Electrical
PHAR-156	Electrical	Grounding	Grounding failure	Observed issue in electrical / grounding: grounding failure.	SH	100	High	10.5	Ground rod	1	ea	$35.00	$35.00	3.0	$165.00	$495.00	$530.00	Replace	Electrical
PHAR-157	Electrical	Wiring	Aluminum wiring present	Observed issue in electrical / wiring: aluminum wiring present.	SH	100	High	10.5	Retrofit materials	1	allowance	$180.00	$180.00	6.0	$165.00	$990.00	$1,170.00	Repair	Electrical
PHAR-158	Electrical	Load	Circuit imbalance	Observed issue in electrical / load: circuit imbalance.	UR	80	Moderate	5.4	Breaker adjustment materials	1	allowance	$25.00	$25.00	2.0	$165.00	$330.00	$355.00	Repair	Electrical
PHAR-159	Electrical	Surge	Surge risk	Observed issue in electrical / surge: surge risk.	NOI	60	Moderate	4.5	Surge protector	1	ea	$70.00	$70.00	2.0	$165.00	$330.00	$400.00	Repair	Electrical
PHAR-160	Electrical	Exterior Outlet	Outdoor outlet failure	Observed issue in electrical / exterior outlet: outdoor outlet failure.	SH	100	High	10.5	GFCI outlet	1	ea	$30.00	$30.00	2.0	$165.00	$330.00	$360.00	Replace	Electrical
PHAR-161	Electrical	Panel	Panel corrosion	Observed issue in electrical / panel: panel corrosion.	SH	100	High	10.5	Panel	1	ea	$400.00	$400.00	8.0	$165.00	$1,320.00	$1,720.00	Replace	Electrical
PHAR-162	Electrical	Panel	Obsolete fuse panel	Observed issue in electrical / panel: obsolete fuse panel.	SH	100	High	10.5	Panel upgrade	1	allowance	$420.00	$420.00	8.0	$165.00	$1,320.00	$1,740.00	Replace	Electrical
PHAR-163	Electrical	Usage	Extension cord misuse / insufficient outlets	Observed issue in electrical / usage: extension cord misuse / insufficient outlets.	SH	100	High	10.5	New outlet materials	1	allowance	$40.00	$40.00	2.0	$165.00	$330.00	$370.00	Repair	Electrical
PHAR-164	Electrical	Lighting Circuit	Lighting circuit overload	Observed issue in electrical / lighting circuit: lighting circuit overload.	SH	100	High	10.5	Breaker upgrade	1	allowance	$45.00	$45.00	3.0	$165.00	$495.00	$540.00	Repair	Electrical
PHAR-165	Electrical	Meter	Electrical meter issue	Observed issue in electrical / meter: electrical meter issue.	UR	80	Moderate	5.4	Meter repair allowance	1	allowance	$60.00	$60.00	2.0	$165.00	$330.00	$390.00	Repair	Electrical
PHAR-166	HVAC	Furnace	Furnace failure	Observed issue in hvac / furnace: furnace failure.	NOI	60	High	6.2	Furnace unit	1	ea	$2,000.00	$2,000.00	8.0	$165.00	$1,320.00	$3,320.00	Replace	HVAC
PHAR-167	HVAC	Furnace	Furnace inefficiency	Observed issue in hvac / furnace: furnace inefficiency.	NOI	60	Moderate	3.8	Maintenance kit	1	kit	$60.00	$60.00	3.0	$165.00	$495.00	$555.00	Repair	HVAC
PHAR-168	HVAC	Filter	Dirty air filter	Observed issue in hvac / filter: dirty air filter.	NOI	60	Moderate	3.8	HVAC filter	1	ea	$20.00	$20.00	0.5	$165.00	$82.50	$102.50	Repair	HVAC
PHAR-169	HVAC	Ventilation	Vent blockage	Observed issue in hvac / ventilation: vent blockage.	NOI	60	Moderate	3.8	Cleaning kit	1	kit	$40.00	$40.00	2.0	$165.00	$330.00	$370.00	Repair	HVAC
PHAR-170	HVAC	Ducting	Duct leakage	Observed issue in hvac / ducting: duct leakage.	NOI	60	Moderate	3.8	Duct seal materials	1	allowance	$35.00	$35.00	3.0	$165.00	$495.00	$530.00	Repair	HVAC
PHAR-171	HVAC	Controls	Thermostat failure	Observed issue in hvac / controls: thermostat failure.	NOI	60	High	6.2	Thermostat	1	ea	$60.00	$60.00	1.0	$165.00	$165.00	$225.00	Replace	HVAC
PHAR-172	HVAC	Cooling	AC compressor failure	Observed issue in hvac / cooling: ac compressor failure.	NOI	60	High	6.2	Compressor allowance	1	allowance	$450.00	$450.00	5.0	$165.00	$825.00	$1,275.00	Replace	HVAC
PHAR-173	HVAC	Cooling	Refrigerant leak	Observed issue in hvac / cooling: refrigerant leak.	NOI	60	Moderate	3.8	Refrigerant	1	allowance	$120.00	$120.00	3.0	$165.00	$495.00	$615.00	Repair	HVAC
PHAR-174	HVAC	Drainage	Condensate drain blockage	Observed issue in hvac / drainage: condensate drain blockage.	UR	80	Moderate	4.5	Cleaning materials	1	allowance	$25.00	$25.00	2.0	$165.00	$330.00	$355.00	Repair	HVAC
PHAR-175	HVAC	Heat Exchanger	Heat exchanger crack	Observed issue in hvac / heat exchanger: heat exchanger crack.	SH	100	High	8.8	Replacement allowance	1	allowance	$350.00	$350.00	5.0	$165.00	$825.00	$1,175.00	Specialist Review	HVAC
PHAR-176	HVAC	Safety	Carbon monoxide risk	Observed issue in hvac / safety: carbon monoxide risk.	SH	100	Critical	14.0	CO detector	1	ea	$35.00	$35.00	0.5	$165.00	$82.50	$117.50	Repair	HVAC
PHAR-177	HVAC	Vent Pipe	Vent pipe leak	Observed issue in hvac / vent pipe: vent pipe leak.	UR	80	Moderate	4.5	Vent pipe materials	1	allowance	$55.00	$55.00	3.0	$165.00	$495.00	$550.00	Repair	HVAC
PHAR-178	HVAC	Exhaust	Exhaust fan failure	Observed issue in hvac / exhaust: exhaust fan failure.	NOI	60	High	6.2	Fan	1	ea	$55.00	$55.00	2.0	$165.00	$330.00	$385.00	Replace	HVAC
PHAR-179	HVAC	Bath Fan	Bathroom fan blockage	Observed issue in hvac / bath fan: bathroom fan blockage.	NOI	60	Moderate	3.8	Cleaning kit	1	kit	$25.00	$25.00	2.0	$165.00	$330.00	$355.00	Repair	HVAC
PHAR-180	HVAC	Air Balance	Ventilation imbalance	Observed issue in hvac / air balance: ventilation imbalance.	NOI	60	Moderate	3.8	Damper	1	ea	$35.00	$35.00	2.0	$165.00	$330.00	$365.00	Repair	HVAC
PHAR-181	Plumbing	Sink	Leaking sink pipe	Observed issue in plumbing / sink: leaking sink pipe.	UR	80	Moderate	4.5	Pipe fittings	3	pcs	$15.00	$45.00	2.0	$165.00	$330.00	$375.00	Repair	Plumbing
PHAR-182	Plumbing	Faucet	Faucet leak	Observed issue in plumbing / faucet: faucet leak.	UR	80	Moderate	4.5	Faucet kit	1	kit	$50.00	$50.00	1.0	$165.00	$165.00	$215.00	Repair	Plumbing
PHAR-183	Plumbing	Drain	Drain blockage	Observed issue in plumbing / drain: drain blockage.	UR	80	Moderate	4.5	Drain auger	1	ea	$25.00	$25.00	1.0	$165.00	$165.00	$190.00	Repair	Plumbing
PHAR-184	Kitchen	Disposal	Garbage disposal failure	Observed issue in kitchen / disposal: garbage disposal failure.	NOI	60	High	2.5	Disposal unit	1	ea	$180.00	$180.00	2.0	$165.00	$330.00	$510.00	Replace	Carpentry / Finishes
PHAR-185	Kitchen	Cabinets	Cabinet damage	Observed issue in kitchen / cabinets: cabinet damage.	VD	40	Moderate	1.2	Cabinet panels	2	pcs	$40.00	$80.00	3.0	$165.00	$495.00	$575.00	Repair	Carpentry / Finishes
PHAR-186	Kitchen	Countertop	Countertop crack	Observed issue in kitchen / countertop: countertop crack.	VD	40	Low	0.8	Repair kit	1	kit	$60.00	$60.00	2.0	$165.00	$330.00	$390.00	Repair	Carpentry / Finishes
PHAR-187	Plumbing	Pressure	Low water pressure	Observed issue in plumbing / pressure: low water pressure.	NOI	60	Moderate	3.8	Regulator	1	ea	$140.00	$140.00	2.0	$165.00	$330.00	$470.00	Repair	Plumbing
PHAR-188	Plumbing	Dishwasher	Dishwasher leak	Observed issue in plumbing / dishwasher: dishwasher leak.	UR	80	Moderate	4.5	Hose kit	1	kit	$40.00	$40.00	2.0	$165.00	$330.00	$370.00	Repair	Plumbing
PHAR-189	Kitchen	Under-Sink	Mold under sink	Observed issue in kitchen / under-sink: mold under sink.	SH	100	High	3.5	Mold treatment	1	allowance	$45.00	$45.00	3.0	$165.00	$495.00	$540.00	Repair	Carpentry / Finishes
PHAR-190	Pest	General	Pest infestation	Observed issue in pest / general: pest infestation.	SH	100	High	7.0	Pest treatment	1	treatment	$120.00	$120.00	4.0	$165.00	$660.00	$780.00	Repair	General / Specialist
PHAR-191	Pest	Rodent	Rodent intrusion	Observed issue in pest / rodent: rodent intrusion.	SH	100	High	7.0	Pest barrier	2	units	$40.00	$80.00	3.0	$165.00	$495.00	$575.00	Repair	General / Specialist
PHAR-192	Safety	Flooring	Trip hazard	Observed issue in safety / flooring: trip hazard.	SH	100	High	8.8	Floor repair kit	1	kit	$60.00	$60.00	2.0	$165.00	$330.00	$390.00	Repair	General / Specialist
PHAR-193	Safety	Surface	Slippery surface	Observed issue in safety / surface: slippery surface.	SH	100	High	8.8	Anti-slip	1	unit	$50.00	$50.00	2.0	$165.00	$330.00	$380.00	Repair	General / Specialist
PHAR-194	Accessibility	Rail	Missing handrail	Observed issue in accessibility / rail: missing handrail.	SH	100	High	7.0	Handrail kit	1	kit	$80.00	$80.00	2.0	$165.00	$330.00	$410.00	Repair	General / Specialist
PHAR-195	Stairs	Framing	Stair damage	Observed issue in stairs / framing: stair damage.	SH	100	High	7.0	Wood repair	1	allowance	$70.00	$70.00	3.0	$165.00	$495.00	$565.00	Repair	Structural / Waterproofing
PHAR-196	Crawlspace	Moisture	Crawlspace moisture	Observed issue in crawlspace / moisture: crawlspace moisture.	SH	100	High	8.8	Vapor barrier	1	allowance	$140.00	$140.00	5.0	$165.00	$825.00	$965.00	Repair	Structural / Waterproofing
PHAR-197	Crawlspace	Water	Crawlspace standing water	Observed issue in crawlspace / water: crawlspace standing water.	SH	100	Critical	14.0	Drainage materials	1	allowance	$200.00	$200.00	6.0	$165.00	$990.00	$1,190.00	Repair	Structural / Waterproofing
PHAR-198	Crawlspace	Pest	Crawlspace pest activity	Observed issue in crawlspace / pest: crawlspace pest activity.	SH	100	High	8.8	Pest treatment	1	treatment	$120.00	$120.00	4.0	$165.00	$660.00	$780.00	Repair	Structural / Waterproofing
PHAR-199	Garage	Door	Garage door malfunction	Observed issue in garage / door: garage door malfunction.	NOI	60	Moderate	1.5	Spring kit	1	kit	$120.00	$120.00	2.0	$165.00	$330.00	$450.00	Repair	Carpentry / Doors
PHAR-200	Garage	Safety	Garage door sensor failure	Observed issue in garage / safety: garage door sensor failure.	SH	100	High	3.5	Sensor	1	ea	$40.00	$40.00	1.0	$165.00	$165.00	$205.00	Replace	Carpentry / Doors
PHAR-201	Garage	Track	Garage track misaligned	Observed issue in garage / track: garage track misaligned.	NOI	60	Moderate	1.5	Track bracket	1	allowance	$55.00	$55.00	2.0	$165.00	$330.00	$385.00	Repair	Carpentry / Doors
PHAR-202	Garage	Seal	Garage door seal worn	Observed issue in garage / seal: garage door seal worn.	VD	40	Low	0.8	Weather seal	1	ea	$35.00	$35.00	1.0	$165.00	$165.00	$200.00	Repair	Carpentry / Doors
PHAR-203	Exterior	Deck	Deck board rot	Observed issue in exterior / deck: deck board rot.	SH	100	High	5.2	Pressure-treated board	3	pcs	$40.00	$120.00	3.0	$165.00	$495.00	$615.00	Repair	Carpentry / Exterior
PHAR-204	Exterior	Deck Rail	Deck railing loose	Observed issue in exterior / deck rail: deck railing loose.	SH	100	High	5.2	Fasteners	1	allowance	$25.00	$25.00	2.0	$165.00	$330.00	$355.00	Repair	Carpentry / Exterior
PHAR-205	Exterior	Balcony	Balcony waterproofing failure	Observed issue in exterior / balcony: balcony waterproofing failure.	UR	80	High	4.5	Membrane	1	allowance	$180.00	$180.00	4.0	$165.00	$660.00	$840.00	Replace	Carpentry / Exterior
PHAR-206	Exterior	Fence	Fence leaning	Observed issue in exterior / fence: fence leaning.	VD	40	Moderate	1.8	Post and concrete	1	allowance	$90.00	$90.00	3.0	$165.00	$495.00	$585.00	Repair	Carpentry / Exterior
PHAR-207	Exterior	Fence	Broken fence board	Observed issue in exterior / fence: broken fence board.	VD	40	Low	1.2	Fence board	2	pcs	$20.00	$40.00	2.0	$165.00	$330.00	$370.00	Repair	Carpentry / Exterior
PHAR-208	Plumbing	Water Heater	Water heater leak	Observed issue in plumbing / water heater: water heater leak.	NOI	60	High	6.2	Heater unit allowance	1	allowance	$650.00	$650.00	4.0	$165.00	$660.00	$1,310.00	Repair	Plumbing
PHAR-209	Plumbing	Supply	Pipe corrosion	Observed issue in plumbing / supply: pipe corrosion.	UR	80	Moderate	4.5	Copper pipe	2	lengths	$60.00	$120.00	3.0	$165.00	$495.00	$615.00	Replace	Plumbing
PHAR-210	Plumbing	Pressure	Pressure irregularity	Observed issue in plumbing / pressure: pressure irregularity.	NOI	60	Moderate	3.8	Pressure regulator	1	ea	$140.00	$140.00	2.0	$165.00	$330.00	$470.00	Repair	Plumbing
PHAR-211	Plumbing	Toilet	Toilet leak	Observed issue in plumbing / toilet: toilet leak.	UR	80	Moderate	4.5	Wax ring kit	1	kit	$20.00	$20.00	2.0	$165.00	$330.00	$350.00	Repair	Plumbing
PHAR-212	Plumbing	Toilet	Toilet flush failure	Observed issue in plumbing / toilet: toilet flush failure.	NOI	60	High	6.2	Flush kit	1	kit	$35.00	$35.00	1.0	$165.00	$165.00	$200.00	Replace	Plumbing
PHAR-213	Plumbing	Shower	Shower valve leak	Observed issue in plumbing / shower: shower valve leak.	UR	80	Moderate	4.5	Valve cartridge	1	ea	$60.00	$60.00	2.0	$165.00	$330.00	$390.00	Repair	Plumbing
PHAR-214	Plumbing	Tub	Bathtub drain leak	Observed issue in plumbing / tub: bathtub drain leak.	UR	80	Moderate	4.5	Drain kit	1	kit	$50.00	$50.00	2.0	$165.00	$330.00	$380.00	Repair	Plumbing
PHAR-215	Plumbing	Laundry	Laundry drain blockage	Observed issue in plumbing / laundry: laundry drain blockage.	UR	80	Moderate	4.5	Drain auger	1	ea	$25.00	$25.00	1.0	$165.00	$165.00	$190.00	Repair	Plumbing
PHAR-216	Plumbing	Exterior	Outdoor hose bib leak	Observed issue in plumbing / exterior: outdoor hose bib leak.	UR	80	Moderate	4.5	Hose bib	1	ea	$45.00	$45.00	2.0	$165.00	$330.00	$375.00	Repair	Plumbing
PHAR-217	Structural	Beam	Beam crack	Observed issue in structural / beam: beam crack.	SH	100	Critical	22.4	Steel plate	1	allowance	$220.00	$220.00	5.0	$165.00	$825.00	$1,045.00	Specialist Review	Structural / Waterproofing
PHAR-218	Structural	Column	Column instability	Observed issue in structural / column: column instability.	SH	100	Critical	22.4	Steel post	1	ea	$260.00	$260.00	5.0	$165.00	$825.00	$1,085.00	Repair	Structural / Waterproofing
PHAR-219	Structural	Joists	Joist rot	Observed issue in structural / joists: joist rot.	SH	100	Critical	22.4	Lumber	3	pcs	$60.00	$180.00	4.0	$165.00	$660.00	$840.00	Repair	Structural / Waterproofing
PHAR-220	Structural	Stairs	Stair stringer crack	Observed issue in structural / stairs: stair stringer crack.	SH	100	Critical	22.4	Lumber	2	pcs	$60.00	$120.00	3.0	$165.00	$495.00	$615.00	Specialist Review	Structural / Waterproofing
TSV;
    }
}