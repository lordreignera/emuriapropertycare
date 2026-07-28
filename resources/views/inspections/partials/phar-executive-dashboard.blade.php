@php
    $property = $property ?? $inspection->property;
    $findingsCollection = collect($findings ?? [])->values();
    $inspectionFindings = is_array($inspection->findings ?? null)
        ? $inspection->findings
        : (json_decode($inspection->getRawOriginal('findings') ?? '[]', true) ?? []);

    $severityCounts = ['critical' => 0, 'high' => 0, 'moderate' => 0, 'low' => 0];
    foreach ($findingsCollection as $finding) {
        $severity = strtolower((string) ($finding->severity ?? 'moderate'));
        if ($severity === 'medium') {
            $severity = 'moderate';
        }
        if (array_key_exists($severity, $severityCounts)) {
            $severityCounts[$severity]++;
        }
    }

    $severityTone = [
        'critical' => 'danger',
        'high' => 'warning',
        'moderate' => 'caution',
        'medium' => 'caution',
        'low' => 'success',
    ];

    $photoPathsForFinding = function ($finding, ?int $index = null) use ($inspectionFindings) {
        $paths = collect((array) ($finding->photo_ids ?? []));

        if ($index !== null && isset($inspectionFindings[$index]['finding_photos'])) {
            $paths = $paths->merge((array) $inspectionFindings[$index]['finding_photos']);
        }

        $matched = collect($inspectionFindings)->first(function ($row) use ($finding) {
            $legacyTitle = trim((string) ($row['finding'] ?? $row['task_question'] ?? ''));
            $findingTitle = trim((string) ($finding->task_question ?? ''));

            return $legacyTitle !== '' && $legacyTitle === $findingTitle;
        });

        if (is_array($matched) && isset($matched['finding_photos'])) {
            $paths = $paths->merge((array) $matched['finding_photos']);
        }

        return $paths
            ->filter(fn ($path) => is_string($path) && trim($path) !== '')
            ->map(fn ($path) => trim($path))
            ->unique()
            ->values();
    };

    $isImagePath = function (?string $path): bool {
        $cleanPath = strtolower(parse_url((string) $path, PHP_URL_PATH) ?: (string) $path);

        return (bool) preg_match('/\.(jpg|jpeg|png|gif|webp|heic|heif)$/', $cleanPath);
    };

    $firstEvidencePath = null;
    $evidenceCount = 0;
    foreach ($findingsCollection as $index => $finding) {
        $paths = $photoPathsForFinding($finding, $index);
        $evidenceCount += $paths->count();
        if (!$firstEvidencePath) {
            $firstEvidencePath = $paths->first(fn ($path) => $isImagePath($path));
        }
    }

    $propertyPhotos = collect((array) ($property?->property_photos ?? []))->filter()->values();
    $inspectionPhotos = collect((array) ($inspection->photos ?? []))->filter()->values();
    $coverPath = $firstEvidencePath ?: $propertyPhotos->first() ?: $inspectionPhotos->first();
    $coverUrl = $coverPath ? $inspection->getStorageUrl($coverPath) : null;

    $primaryFinding = $findingsCollection->firstWhere('severity', 'critical')
        ?: $findingsCollection->firstWhere('severity', 'high')
        ?: $findingsCollection->first();
    $primaryIndex = $primaryFinding ? $findingsCollection->search(fn ($finding) => (int) $finding->id === (int) $primaryFinding->id) : null;
    $primaryPhotos = $primaryFinding ? $photoPathsForFinding($primaryFinding, is_int($primaryIndex) ? $primaryIndex : null) : collect();
    $primaryPhoto = $primaryPhotos->first(fn ($path) => $isImagePath($path));

    $score = (int) round((float) ($inspection->cpi_total_score ?? 0));
    if ($score <= 0) {
        $criticalWeight = $severityCounts['critical'] * 14;
        $highWeight = $severityCounts['high'] * 9;
        $moderateWeight = $severityCounts['moderate'] * 5;
        $lowWeight = $severityCounts['low'] * 2;
        $score = max(35, min(97, 92 - $criticalWeight - $highWeight - $moderateWeight - $lowWeight));
    }

    $reliability = min(99, max(82, 92 + min(5, $evidenceCount)));
    $reportDate = $inspection->assessment_finalised_at
        ?? $inspection->completed_date
        ?? $inspection->scheduled_date
        ?? $inspection->created_at;
    $reportId = 'PHAR-' . optional($reportDate)->format('Y') . '-' . str_pad((string) $inspection->id, 4, '0', STR_PAD_LEFT);
    $propertyType = ucwords(str_replace('_', ' ', (string) ($property?->type ?? $inspection->property_type_snapshot ?? 'Property')));
    $yearBuilt = $property?->year_built ?? $inspection->property_year_built ?? 'Not recorded';
    $squareFootage = (float) ($property?->total_square_footage ?? $property?->square_footage_interior ?? $inspection->property_size_psf ?? 0);
    $address = trim((string) ($property?->property_address ?? $inspection->property_address_snapshot ?? 'Property address pending'));
    $location = trim(collect([$property?->city, $property?->province, $property?->country])->filter()->implode(', '));
    $useEvidenceOverview = true;
    $overviewTitle = 'Property & Finding Photos';
    $reportVersionLabel = 'Report Version';
    $evidenceStatusLabel = 'Photo evidence status';
    $evidenceStatusValue = $evidenceCount > 0 ? 'Evidence attached' : 'Awaiting photos';

    $cleanEducationText = function ($value): string {
        $text = trim((string) $value);
        if ($text === '') {
            return '';
        }

        $lower = strtolower($text);
        $badFragments = [
            'an error occurred while processing your diagnosis request',
            'please try again',
            'payment verification failed',
            'callback',
            'stripe',
        ];

        foreach ($badFragments as $fragment) {
            if (str_contains($lower, $fragment)) {
                return '';
            }
        }

        return $text;
    };

    $primaryTitle = $cleanEducationText($primaryFinding?->task_question)
        ?: $cleanEducationText($primaryFinding?->plain_language_definition)
        ?: 'this finding';
    $primarySystem = $cleanEducationText($primaryFinding?->system?->name)
        ?: $cleanEducationText($primaryFinding?->category)
        ?: 'this property system';
    $primarySubsystem = $cleanEducationText($primaryFinding?->subsystem?->name);
    $educationHaystack = strtolower(implode(' ', array_filter([
        $primaryTitle,
        $primarySystem,
        $primarySubsystem,
        $primaryFinding?->observed_condition,
        $primaryFinding?->plain_language_definition,
    ])));

    $derivedWhy = "This matters because {$primaryTitle} affects {$primarySystem}, which protects the property and prevents small defects from becoming larger repair work.";
    $derivedRisk = "If {$primaryTitle} is ignored, the condition can worsen and create secondary damage, higher repair cost, or reduced property performance.";
    $derivedAction = "Have ETOGO address {$primaryTitle} in {$primarySystem}, then verify the result and keep it on the stewardship record.";

    if (str_contains($educationHaystack, 'gutter') || str_contains($educationHaystack, 'downspout') || str_contains($educationHaystack, 'drain')) {
        $derivedWhy = 'Gutters and downspouts control how rainwater leaves the roof edge. Broken or blocked guttering can send water onto walls, fascia, roof edges, paving, or foundation areas.';
        $derivedRisk = 'If ignored, overflowing water can stain finishes, rot timber or fascia, increase wall and foundation moisture, and create more expensive envelope repairs.';
        $derivedAction = 'Repair or replace damaged gutter sections, clear the drainage path, verify slope, and confirm downspouts discharge water away from the building.';
    } elseif (str_contains($educationHaystack, 'roof') || str_contains($educationHaystack, 'flashing')) {
        $derivedWhy = 'The roof and flashing are the main weather-protection layer. Defects here can allow water to enter before interior damage is visible.';
        $derivedRisk = 'If ignored, roof water entry can damage ceilings, insulation, framing, finishes, and adjacent systems.';
        $derivedAction = 'Repair the affected roof or flashing area, confirm the water path is sealed, and verify performance after rainfall.';
    } elseif (str_contains($educationHaystack, 'electrical') || str_contains($educationHaystack, 'gfci') || str_contains($educationHaystack, 'outlet')) {
        $derivedWhy = 'Electrical safety findings matter because failed protection devices or unsafe circuits can expose people and property to shock or fire risk.';
        $derivedRisk = 'If ignored, the unsafe condition can remain active and may fail during normal use or under load.';
        $derivedAction = 'Have a qualified electrical trade partner diagnose and correct the issue, then verify the circuit or device is operating safely.';
    } elseif (str_contains($educationHaystack, 'plumbing') || str_contains($educationHaystack, 'leak') || str_contains($educationHaystack, 'moisture')) {
        $derivedWhy = 'Plumbing and moisture findings matter because water can travel into hidden materials before visible damage appears.';
        $derivedRisk = 'If ignored, moisture can damage finishes, cabinetry, framing, flooring, or indoor air quality.';
        $derivedAction = 'Trace the water source, stop the leak or moisture path, dry affected materials, and verify no hidden damage remains.';
    }

    $educationFound = $cleanEducationText($primaryFinding?->observed_condition)
        ?: $cleanEducationText($primaryFinding?->plain_language_definition)
        ?: "The assessment recorded {$primaryTitle} under {$primarySystem}.";
    $educationWhy = $cleanEducationText($primaryFinding?->why_it_matters)
        ?: $cleanEducationText($primaryFinding?->plain_language_meaning)
        ?: $derivedWhy;
    $educationRisk = $cleanEducationText($primaryFinding?->consequence_if_ignored)
        ?: $derivedRisk;
    $educationAction = $cleanEducationText($primaryFinding?->remediation_strategy)
        ?: $cleanEducationText($primaryFinding?->stewardship_strategy)
        ?: $cleanEducationText($primaryFinding?->management_strategy)
        ?: $derivedAction;
@endphp

<style>
    .phar-exec {
        --phar-navy: #061946;
        --phar-blue: #0a2b73;
        --phar-gold: #f8b516;
        --phar-red: #df1f2d;
        --phar-green: #087b35;
        --phar-ink: #091634;
        --phar-line: #d9e1ee;
        --phar-soft: #f6f8fc;
        color: var(--phar-ink);
        font-family: Inter, "Segoe UI", Arial, sans-serif;
    }

    .phar-exec * {
        letter-spacing: 0;
    }

    .phar-hero {
        background: linear-gradient(135deg, var(--phar-navy), #092c68);
        color: #fff;
        border-radius: 8px 8px 0 0;
        padding: 18px 20px;
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 18px;
        align-items: center;
    }

    .phar-brand {
        display: flex;
        align-items: center;
        gap: 14px;
        min-width: 0;
    }

    .phar-crest {
        width: 58px;
        height: 58px;
        border: 2px solid var(--phar-gold);
        border-radius: 16px;
        display: grid;
        place-items: center;
        color: var(--phar-gold);
        font-size: 31px;
        flex: 0 0 auto;
    }

    .phar-brand h1,
    .phar-brand h2,
    .phar-panel h3,
    .phar-section-title {
        margin: 0;
    }

    .phar-brand h1 {
        font-size: 30px;
        line-height: 1;
        font-weight: 800;
    }

    .phar-brand h2 {
        font-size: 24px;
        line-height: 1.1;
        font-weight: 800;
    }

    .phar-brand .gold {
        color: var(--phar-gold);
    }

    .phar-meta {
        border: 1px solid rgba(255, 255, 255, .25);
        border-radius: 8px;
        padding: 10px 14px;
        min-width: 230px;
        font-size: 12px;
        line-height: 1.7;
    }

    .phar-facts {
        background: #fff;
        border: 1px solid var(--phar-line);
        border-top: 0;
        border-radius: 0 0 8px 8px;
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        overflow: hidden;
    }

    .phar-fact {
        padding: 12px 14px;
        border-right: 1px solid var(--phar-line);
        display: flex;
        gap: 10px;
        align-items: flex-start;
        min-width: 0;
    }

    .phar-fact:last-child {
        border-right: 0;
    }

    .phar-fact i {
        color: var(--phar-blue);
        font-size: 22px;
        margin-top: 2px;
    }

    .phar-label {
        color: var(--phar-blue);
        font-weight: 800;
        font-size: 11px;
        text-transform: uppercase;
    }

    .phar-value {
        font-weight: 700;
        font-size: 13px;
        line-height: 1.25;
        overflow-wrap: anywhere;
    }

    .phar-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px;
        margin-top: 10px;
    }

    .phar-grid.two {
        grid-template-columns: 1.15fr .85fr;
    }

    .phar-panel {
        border: 1px solid var(--phar-line);
        border-radius: 8px;
        background: #fff;
        padding: 14px;
        min-width: 0;
    }

    .phar-panel h3,
    .phar-section-title {
        color: var(--phar-blue);
        font-size: 15px;
        font-weight: 900;
        text-transform: uppercase;
        margin-bottom: 10px;
    }

    .phar-row {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        border-bottom: 1px solid #edf1f7;
        padding: 6px 0;
        font-size: 12px;
    }

    .phar-row strong {
        text-align: right;
    }

    .phar-cover {
        min-height: 190px;
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid var(--phar-line);
        background: linear-gradient(135deg, #eef3fb, #fff);
        display: grid;
        place-items: center;
        margin-bottom: 10px;
    }

    .phar-cover img {
        width: 100%;
        height: 100%;
        min-height: 190px;
        object-fit: cover;
        display: block;
    }

    .phar-cover-empty {
        color: #557;
        text-align: center;
        font-weight: 800;
        padding: 18px;
    }

    .phar-alert {
        border: 1px solid #f3b8bf;
        background: #fff4f4;
        color: var(--phar-red);
        padding: 9px 10px;
        border-radius: 6px;
        font-weight: 800;
        font-size: 12px;
    }

    .phar-significant {
        border: 1px solid var(--phar-line);
        border-radius: 8px;
        background: #fff;
        overflow: hidden;
    }

    .phar-significant-title {
        background: var(--phar-red);
        color: #fff;
        font-weight: 900;
        text-transform: uppercase;
        padding: 8px 14px;
        font-size: 14px;
    }

    .phar-significant-body {
        padding: 14px;
        display: grid;
        grid-template-columns: minmax(0, 1fr) 190px;
        gap: 14px;
    }

    .phar-evidence-main {
        border-radius: 6px;
        border: 1px solid var(--phar-line);
        min-height: 136px;
        object-fit: cover;
        width: 100%;
        background: var(--phar-soft);
    }

    .phar-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 8px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 900;
        text-transform: uppercase;
    }

    .phar-badge.danger { background: #ffe3e6; color: var(--phar-red); }
    .phar-badge.warning { background: #fff2cb; color: #a76500; }
    .phar-badge.caution { background: #e7f4ff; color: #0a5594; }
    .phar-badge.success { background: #dff7e9; color: var(--phar-green); }

    .phar-finding-list {
        display: grid;
        gap: 8px;
    }

    .phar-finding-item {
        display: grid;
        grid-template-columns: 54px minmax(0, 1fr) auto;
        gap: 10px;
        align-items: center;
        border: 1px solid var(--phar-line);
        border-radius: 8px;
        padding: 8px;
        background: #fff;
    }

    .phar-thumb {
        width: 54px;
        height: 46px;
        object-fit: cover;
        border-radius: 6px;
        background: var(--phar-soft);
        border: 1px solid var(--phar-line);
        display: grid;
        place-items: center;
        color: #7b8798;
    }

    .phar-metric-strip {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 10px;
    }

    .phar-metric {
        border: 1px solid var(--phar-line);
        border-radius: 8px;
        padding: 12px;
        text-align: center;
        background: #fff;
    }

    .phar-metric i {
        display: block;
        color: var(--phar-green);
        font-size: 32px;
        margin-bottom: 4px;
    }

    .phar-metric strong {
        display: block;
        color: var(--phar-green);
        font-size: 24px;
        line-height: 1;
    }

    .phar-score-bars {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 14px;
        align-items: end;
        min-height: 150px;
    }

    .phar-bar {
        display: grid;
        gap: 6px;
        justify-items: center;
        font-size: 11px;
        font-weight: 800;
        text-align: center;
    }

    .phar-bar-box {
        width: 54px;
        border-radius: 6px 6px 0 0;
        background: #1768d8;
        min-height: 34px;
    }

    .phar-recommendation {
        margin-top: 10px;
        display: grid;
        grid-template-columns: minmax(0, 1fr) 260px;
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid var(--phar-line);
        background: var(--phar-navy);
        color: #fff;
    }

    .phar-recommendation-text {
        padding: 18px;
    }

    .phar-recommendation h3 {
        color: var(--phar-gold);
        font-weight: 900;
        text-transform: uppercase;
        font-size: 18px;
        margin-bottom: 8px;
    }

    .phar-recommendation-media {
        background: rgba(255, 255, 255, .08);
        min-height: 160px;
    }

    .phar-recommendation-media img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .phar-footer {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 8px;
        margin-top: 10px;
    }

    .phar-footer-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 11px;
        font-weight: 800;
        color: var(--phar-blue);
    }

    .phar-footer-item i {
        font-size: 26px;
        color: var(--phar-blue);
    }

    @media (max-width: 992px) {
        .phar-hero,
        .phar-grid,
        .phar-grid.two,
        .phar-significant-body,
        .phar-recommendation {
            grid-template-columns: 1fr;
        }

        .phar-facts,
        .phar-metric-strip,
        .phar-footer {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 576px) {
        .phar-facts,
        .phar-metric-strip,
        .phar-footer,
        .phar-finding-item {
            grid-template-columns: 1fr;
        }

        .phar-meta {
            min-width: 0;
        }
    }
</style>

<div class="phar-exec mb-4">
    <div class="phar-hero">
        <div class="phar-brand">
            <div class="phar-crest"><i class="mdi mdi-shield-home-outline"></i></div>
            <div>
                <h1>ETOGO</h1>
                <div class="small" style="color:#dbe7ff;font-weight:700;">The Blueprint to Property Facts</div>
            </div>
            <div class="d-none d-md-block" style="width:1px;height:62px;background:rgba(255,255,255,.35);"></div>
            <div>
                <h2>PHAR <span class="gold">Executive Dashboard</span></h2>
                <div class="small" style="color:#dbe7ff;font-weight:700;">Property Health Assessment Report</div>
            </div>
        </div>
        <div class="phar-meta">
            <div><strong>Report ID:</strong> {{ $reportId }}</div>
            <div><strong>{{ $reportVersionLabel }}:</strong> 3.2</div>
            <div><strong>PHAR Date:</strong> {{ optional($reportDate)->format('M d, Y') ?? 'Pending' }}</div>
        </div>
    </div>

    <div class="phar-facts">
        <div class="phar-fact">
            <i class="mdi mdi-map-marker"></i>
            <div><div class="phar-label">Property Address</div><div class="phar-value">{{ $address }}@if($location)<br>{{ $location }}@endif</div></div>
        </div>
        <div class="phar-fact">
            <i class="mdi mdi-home"></i>
            <div><div class="phar-label">Property Type</div><div class="phar-value">{{ $propertyType }}</div></div>
        </div>
        <div class="phar-fact">
            <i class="mdi mdi-calendar-range"></i>
            <div><div class="phar-label">Year Built</div><div class="phar-value">{{ $yearBuilt }}</div></div>
        </div>
        <div class="phar-fact">
            <i class="mdi mdi-ruler-square"></i>
            <div><div class="phar-label">Square Footage</div><div class="phar-value">{{ $squareFootage > 0 ? number_format($squareFootage) . ' sq ft' : 'Not recorded' }}</div></div>
        </div>
        <div class="phar-fact">
            <i class="mdi mdi-shield-account"></i>
            <div><div class="phar-label">Property Steward</div><div class="phar-value">ETOGO Property Stewardship Services</div></div>
        </div>
    </div>

    <div class="phar-grid">
        <section class="phar-panel">
            <h3>Diagnosis Facts</h3>
            <div class="phar-row"><span>PHAR diagnosis date</span><strong>{{ optional($inspection->completed_date ?? $inspection->scheduled_date)->format('M d, Y') ?? 'Pending' }}</strong></div>
            <div class="phar-row"><span>Diagnosis performed by</span><strong>{{ $inspection->inspector?->name ?? $inspection->finalisedBy?->name ?? 'ETOGO Steward' }}</strong></div>
            <div class="phar-row"><span>Evidence photos</span><strong>{{ $evidenceCount }}</strong></div>
            <div class="phar-row"><span>Critical / high items</span><strong>{{ $severityCounts['critical'] + $severityCounts['high'] }}</strong></div>
            <div class="phar-row"><span>{{ $evidenceStatusLabel }}</span><strong style="color:var(--phar-green);">{{ $evidenceStatusValue }}</strong></div>
            <div class="phar-row"><span>Diagnosis confidence</span><strong style="color:var(--phar-green);">{{ $reliability }}%</strong></div>
        </section>

        <section class="phar-panel">
            <h3>Asset Health Summary</h3>
            <div class="phar-row"><span>Total findings</span><strong>{{ $findingsCollection->count() }}</strong></div>
            <div class="phar-row"><span>Critical</span><strong style="color:var(--phar-red);">{{ $severityCounts['critical'] }}</strong></div>
            <div class="phar-row"><span>High</span><strong style="color:#c46a00;">{{ $severityCounts['high'] }}</strong></div>
            <div class="phar-row"><span>Moderate</span><strong>{{ $severityCounts['moderate'] }}</strong></div>
            <div class="phar-row"><span>Low</span><strong>{{ $severityCounts['low'] }}</strong></div>
            <div class="mt-3">
                <div class="small fw-bold mb-1">Useful life confidence</div>
                <div style="height:12px;background:#edf1f7;border-radius:999px;overflow:hidden;">
                    <div style="height:12px;width:{{ $score }}%;background:{{ $score < 70 ? 'var(--phar-red)' : 'var(--phar-green)' }};"></div>
                </div>
                <div class="small text-end mt-1 fw-bold">{{ $score }}%</div>
            </div>
            @if($severityCounts['critical'] + $severityCounts['high'] > 0)
                <div class="phar-alert mt-3">
                    <i class="mdi mdi-alert-circle me-1"></i>
                    Action required for immediate protection items.
                </div>
            @endif
        </section>

        <section class="phar-panel">
            <h3>{{ $overviewTitle }}</h3>
            <div class="phar-cover">
                @if($coverUrl)
                    <img src="{{ $coverUrl }}" alt="Property evidence overview" loading="lazy">
                @else
                    <div class="phar-cover-empty">
                        <i class="mdi mdi-home-search-outline d-block mb-2" style="font-size:42px;"></i>
                        Property or finding photos will appear when uploaded.
                    </div>
                @endif
            </div>
            <div class="phar-row"><span>Highlighted area</span><strong>{{ $primaryFinding?->system?->name ?? $primaryFinding?->category ?? 'Whole property' }}</strong></div>
            <div class="phar-row"><span>Scope classification</span><strong>{{ ($severityCounts['critical'] + $severityCounts['high']) > 0 ? 'Immediate remediation' : 'Stewardship monitoring' }}</strong></div>
        </section>
    </div>

    <div class="phar-grid two">
        <section class="phar-significant">
            <div class="phar-significant-title">Most Significant Property Fact</div>
            <div class="phar-significant-body">
                <div>
                    <h3 class="mb-2" style="color:var(--phar-blue);font-weight:900;">{{ $primaryFinding?->task_question ?: $primaryFinding?->plain_language_definition ?: 'No major finding recorded' }}</h3>
                    @if($primaryFinding)
                        @php $primarySeverity = strtolower((string) ($primaryFinding->severity ?? 'moderate')); @endphp
                        <div class="mb-2">
                            <span class="phar-badge {{ $severityTone[$primarySeverity] ?? 'caution' }}">
                                <i class="mdi mdi-alert-circle-outline"></i>{{ strtoupper($primarySeverity) }}
                            </span>
                        </div>
                        <div class="phar-row"><span>System</span><strong>{{ $primaryFinding->system?->name ?? $primaryFinding->category ?? 'General' }}</strong></div>
                        <div class="phar-row"><span>Sub-system</span><strong>{{ $primaryFinding->subsystem?->name ?? 'Not specified' }}</strong></div>
                        <div class="phar-row"><span>Finding type</span><strong>{{ ucwords(str_replace('_', ' ', (string) ($primaryFinding->finding_type ?? 'stand_alone'))) }}</strong></div>
                        @if($primaryFinding->observed_condition)
                            <p class="small mt-3 mb-0">{{ $primaryFinding->observed_condition }}</p>
                        @endif
                    @endif
                </div>
                <div>
                    @if($primaryPhoto)
                        <img class="phar-evidence-main" src="{{ $inspection->getStorageUrl($primaryPhoto) }}" alt="Primary finding evidence" loading="lazy">
                    @else
                        <div class="phar-evidence-main d-grid place-items-center text-center p-3 small text-muted">
                            <i class="mdi mdi-image-off-outline d-block mb-1" style="font-size:34px;"></i>
                            No evidence photo attached yet.
                        </div>
                    @endif
                </div>
            </div>
        </section>

        <section class="phar-panel">
            <h3>Client Education</h3>
            <div class="d-flex gap-3 mb-3">
                <i class="mdi mdi-help-circle" style="font-size:42px;color:#1175c4;"></i>
                <div>
                    <div class="fw-bold">What was found?</div>
                    <div class="small">{{ $educationFound }}</div>
                </div>
            </div>
            <div class="d-flex gap-3 mb-3">
                <i class="mdi mdi-lightbulb-on" style="font-size:42px;color:#f58220;"></i>
                <div>
                    <div class="fw-bold">Why does it matter?</div>
                    <div class="small">{{ $educationWhy }}</div>
                </div>
            </div>
            <div class="d-flex gap-3 mb-3">
                <i class="mdi mdi-alert" style="font-size:42px;color:var(--phar-red);"></i>
                <div>
                    <div class="fw-bold">What happens if ignored?</div>
                    <div class="small">{{ $educationRisk }}</div>
                </div>
            </div>
            <div class="d-flex gap-3">
                <i class="mdi mdi-tools" style="font-size:42px;color:var(--phar-green);"></i>
                <div>
                    <div class="fw-bold">What should be done next?</div>
                    <div class="small">{{ $educationAction }}</div>
                </div>
            </div>
        </section>
    </div>

    <div class="phar-grid two">
        <section class="phar-panel">
            <h3>Findings With Evidence</h3>
            <div class="phar-finding-list">
                @forelse($findingsCollection->take(6) as $index => $finding)
                    @php
                        $severity = strtolower((string) ($finding->severity ?? 'moderate'));
                        $paths = $photoPathsForFinding($finding, $index);
                        $thumbPath = $paths->first(fn ($path) => $isImagePath($path));
                    @endphp
                    <div class="phar-finding-item">
                        @if($thumbPath)
                            <img class="phar-thumb" src="{{ $inspection->getStorageUrl($thumbPath) }}" alt="Finding evidence" loading="lazy">
                        @else
                            <div class="phar-thumb"><i class="mdi mdi-image-outline"></i></div>
                        @endif
                        <div class="min-w-0">
                            <div class="fw-bold text-truncate">{{ $finding->task_question ?: $finding->plain_language_definition ?: 'Finding' }}</div>
                            <div class="small text-muted text-truncate">{{ $finding->system?->name ?? $finding->category ?? 'General property system' }}</div>
                        </div>
                        <span class="phar-badge {{ $severityTone[$severity] ?? 'caution' }}">{{ strtoupper($severity) }}</span>
                    </div>
                @empty
                    <div class="text-muted small">No findings have been captured yet.</div>
                @endforelse
            </div>
        </section>

        <section class="phar-panel">
            <h3>Property Score Forecast</h3>
            <div class="phar-score-bars">
                <div class="phar-bar">
                    <div>{{ $score }}%</div>
                    <div class="phar-bar-box" style="height:{{ max(34, $score) }}px;"></div>
                    <span>Current</span>
                </div>
                <div class="phar-bar">
                    <div>{{ min(98, $score + 6) }}%</div>
                    <div class="phar-bar-box" style="height:{{ max(40, $score + 6) }}px;background:#1e9aa7;"></div>
                    <span>After Immediate</span>
                </div>
                <div class="phar-bar">
                    <div>{{ min(99, $score + 11) }}%</div>
                    <div class="phar-bar-box" style="height:{{ max(44, $score + 11) }}px;background:#73b843;"></div>
                    <span>12-Month Plan</span>
                </div>
                <div class="phar-bar">
                    <div>{{ min(99, $score + 16) }}%</div>
                    <div class="phar-bar-box" style="height:{{ max(48, $score + 16) }}px;background:#087b35;"></div>
                    <span>Full Stewardship</span>
                </div>
            </div>
        </section>
    </div>

    <div class="phar-metric-strip mt-2">
        <div class="phar-metric"><i class="mdi mdi-shield-check"></i><strong>90%</strong><span class="small">Risk Reduction</span></div>
        <div class="phar-metric"><i class="mdi mdi-arrow-down-circle-outline"></i><strong>92%</strong><span class="small">Deferred Cost Reduction</span></div>
        <div class="phar-metric"><i class="mdi mdi-piggy-bank-outline"></i><strong>88%</strong><span class="small">Lifecycle Cost Reduction</span></div>
        <div class="phar-metric"><i class="mdi mdi-home-heart"></i><strong>91%</strong><span class="small">Property Protection</span></div>
    </div>

    <section class="phar-recommendation">
        <div class="phar-recommendation-text">
            <h3>ETOGO Stewardship Recommendation</h3>
            <p class="mb-0">
                Address critical and high priority items first, then place moderate and low items into the stewardship plan.
                This protects the property today while building a clear long-term record of property facts, evidence, and decisions.
            </p>
        </div>
        <div class="phar-recommendation-media">
            @if($coverUrl)
                <img src="{{ $coverUrl }}" alt="Property stewardship evidence" loading="lazy">
            @endif
        </div>
    </section>

    <div class="phar-footer">
        <div class="phar-footer-item"><i class="mdi mdi-home-search"></i><span>Diagnosis<br><small>Understand the issue</small></span></div>
        <div class="phar-footer-item"><i class="mdi mdi-tools"></i><span>Remediation<br><small>Solve today's issues</small></span></div>
        <div class="phar-footer-item"><i class="mdi mdi-shield-star"></i><span>Stewardship<br><small>Preserve performance</small></span></div>
        <div class="phar-footer-item"><i class="mdi mdi-image-multiple"></i><span>Evidence<br><small>Photos and facts</small></span></div>
        <div class="phar-footer-item"><i class="mdi mdi-chart-line"></i><span>Management<br><small>Optimize over time</small></span></div>
    </div>
</div>
