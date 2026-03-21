<?php
/**
 * Seed Inspection — One Finding Per System
 * =========================================
 * Creates a complete inspection for property_id=1 with exactly one
 * finding per active building system, spread across severity levels so
 * the CPI, ASI and pricing pipeline can be exercised end-to-end.
 *
 * Usage (from project root):
 *   php artisan tinker --execute="require base_path('tests/scripts/seed_inspection_all_systems.php');"
 *
 * Or run it directly (bootstraps Laravel itself):
 *   php tests/scripts/seed_inspection_all_systems.php
 */

// ── Bootstrap ──────────────────────────────────────────────────────────────
if (!defined('LARAVEL_START')) {
    define('LARAVEL_START', microtime(true));
    require __DIR__ . '/../../vendor/autoload.php';
    $app = require __DIR__ . '/../../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
}

use App\Models\Inspection;
use App\Models\InspectionSystem;
use App\Models\Property;
use App\Models\User;
use App\Http\Controllers\InspectionController;
use Illuminate\Http\Request;

// ── Config ─────────────────────────────────────────────────────────────────
$propertyId      = 1;
$servicePackageId = 1;  // Essentials
$inspectionDate  = now()->format('Y-m-d\TH:i');

// ── Validate prerequisites ─────────────────────────────────────────────────
$property = Property::find($propertyId);
if (!$property) {
    echo "ERROR: Property ID {$propertyId} not found.\n";
    exit(1);
}

$admin = User::whereHas('roles', fn($q) => $q->where('name', 'admin'))
            ->orWhere('email', 'admin@example.com')
            ->orWhereHas('roles', fn($q) => $q->where('name', 'inspector'))
            ->first() ?? User::first();

if (!$admin) {
    echo "ERROR: No user found to act as inspector.\n";
    exit(1);
}

// ── One representative finding per system ─────────────────────────────────
// Severity distribution: rotate through severity levels so different systems
// carry different deduction weights — this makes the CPI non-trivial.
$severityCycle = ['low', 'medium', 'noi_protection', 'high', 'critical'];

// System-specific issues (realistic content per system type)
$systemIssues = [
    'Roof'          => ['issue' => 'Missing/lifted shingles on south slope',    'location' => 'South-facing slope',      'spot' => 'Ridge line, mid-section'],
    'Gutters'       => ['issue' => 'Gutters pulling away from fascia board',    'location' => 'Front elevation',         'spot' => 'North-east corner'],
    'Site Drainage' => ['issue' => 'Standing water pooling near foundation',    'location' => 'North side yard',         'spot' => '3 ft from foundation wall'],
    'Exterior Wall' => ['issue' => 'Siding separation and paint peeling',       'location' => 'West exterior wall',      'spot' => 'Below window sill, 4 ft section'],
    'Windows'       => ['issue' => 'Failed window seal, condensation between panes', 'location' => 'Master bedroom',    'spot' => 'South-facing window'],
    'Doors'         => ['issue' => 'Entry door not sealing — visible light gap', 'location' => 'Front entry',           'spot' => 'Bottom door sweep'],
    'Foundation'    => ['issue' => 'Hairline vertical crack in foundation wall', 'location' => 'North basement wall',   'spot' => '18 inches from corner'],
    'Basement'      => ['issue' => 'Efflorescence and moisture staining',        'location' => 'Basement floor slab',   'spot' => 'Near sump pit area'],
    'Structure'     => ['issue' => 'Sagging floor joist — possible moisture damage', 'location' => 'First floor, kitchen area', 'spot' => 'Joist bay 4 from south'],
    'Floor'         => ['issue' => 'Squeaking hardwood, loose subfloor section', 'location' => 'Living room',           'spot' => 'Near fireplace, 2 ft patch'],
    'Walls'         => ['issue' => 'Drywall crack at window corner',             'location' => 'Dining room',           'spot' => 'Top-left corner of south window'],
    'Ceilings'      => ['issue' => 'Water stain ring on drywall ceiling',        'location' => 'Second floor hallway',  'spot' => 'Above bathroom'],
    'Electrical'    => ['issue' => 'Double-tapped breaker in main panel',        'location' => 'Electrical panel',      'spot' => '20A breaker, slot 8'],
    'HVAC'          => ['issue' => 'Filter clogged, heat exchanger unserviced',  'location' => 'Mechanical room',       'spot' => 'Furnace unit'],
    'Plumbing'      => ['issue' => 'Slow drain and partial blockage',            'location' => 'Master bathroom',       'spot' => 'Shower drain'],
    'Kitchen'       => ['issue' => 'Cabinet hinge failure, door misaligned',     'location' => 'Kitchen',               'spot' => 'Upper cabinet, above dishwasher'],
    'Pest'          => ['issue' => 'Evidence of rodent activity — droppings',    'location' => 'Garage/utility area',   'spot' => 'Along east perimeter wall'],
    'Safety'        => ['issue' => 'Smoke detector non-functional',              'location' => 'Master bedroom',        'spot' => 'Ceiling mount'],
    'Accessibility' => ['issue' => 'Missing handrail on stairs',                 'location' => 'Interior staircase',    'spot' => 'Upper landing'],
    'Stairs'        => ['issue' => 'Loose tread on third step',                  'location' => 'Main staircase',        'spot' => 'Third step from bottom'],
    'Crawlspace'    => ['issue' => 'Vapour barrier torn, exposed soil',          'location' => 'Crawlspace',            'spot' => 'South-west quadrant'],
    'Garage'        => ['issue' => 'Garage door weatherstripping missing',       'location' => 'Garage',                'spot' => 'Bottom seal, full width'],
    'Exterior'      => ['issue' => 'Deck boards warped and splitting',           'location' => 'Rear deck',             'spot' => 'Three boards near stairs'],
    'Structural'    => ['issue' => 'Beam bearing point showing compression failure', 'location' => 'Garage/main floor beam', 'spot' => 'Bearing point at mid-span post'],
];

// ── Build system_findings payload ─────────────────────────────────────────
$systems = InspectionSystem::where('is_active', true)->orderBy('id')->get();
$systemFindings = [];
$idx = 0;

foreach ($systems as $system) {
    $content = $systemIssues[$system->name] ?? [
        'issue'    => "Routine observation — requires monitoring",
        'location' => 'General',
        'spot'     => 'N/A',
    ];

    $severity = $severityCycle[$idx % count($severityCycle)];

    $systemFindings[] = [
        'system_id'       => $system->id,
        'subsystem_id'    => null,
        'issue'           => $content['issue'],
        'location'        => $content['location'],
        'spot'            => $content['spot'],
        'severity'        => $severity,
        'notes'           => "W{$system->weight} system. Identified during routine visual inspection on {$inspectionDate}.",
        'recommendations' => [
            "Assess and document condition immediately",
            "Schedule remediation within 30 days",
        ],
    ];

    $idx++;
}

// ── Submit via controller ──────────────────────────────────────────────────
$requestData = [
    'property_id'       => $propertyId,
    'status'            => 'in_progress',
    'inspection_date'   => now()->format('Y-m-d\TH:i'),
    'inspector_id'      => $admin->id,
    'weather_conditions'=> 'clear',
    'overall_condition' => 'fair',
    'service_package_id'=> $servicePackageId,
    'inspector_notes'   => 'Full property walk-through with one representative finding logged per building system.',
    'recommendations'   => 'Prioritise Safety (smoke detector), Electrical (double-tap) and Structural (beam) items. Schedule all Critical/High findings within 30 days.',
    'risk_summary'      => 'Multiple systems show signs of deferred maintenance. Structural and Electrical items require immediate review.',
    'summary'           => "Test inspection: one finding per system — property {$propertyId}",
    'system_findings'   => $systemFindings,
    'next_stage'        => 'phar',   // will redirect to PHAR data form after save
];

// Fake the authenticated user so controller auth guards pass
\Illuminate\Support\Facades\Auth::loginUsingId($admin->id);

$request = Request::create(
    route('inspections.store'),
    'POST',
    $requestData
);

$request->setLaravelSession(app('session')->driver());
\Illuminate\Support\Facades\Session::start();

// Add CSRF token so middleware doesn't reject it
$token = \Illuminate\Support\Str::random(40);
\Illuminate\Support\Facades\Session::put('_token', $token);
$request->headers->set('X-CSRF-TOKEN', $token);

try {
    $controller = app(InspectionController::class);
    $response   = $controller->store($request);

    // Extract inspection ID from redirect URL
    $location = $response->headers->get('Location', '');
    preg_match('/inspections\/(\d+)|property_id=(\d+)/', $location, $matches);

    echo "\n✅ Inspection created successfully!\n";
    echo "   Inspector : {$admin->name} (ID {$admin->id})\n";
        $propName = $property->name ?? $property->property_name ?? "ID {$propertyId}";
        echo "   Property  : {$propName}\n";
    echo "   Findings  : " . count($systemFindings) . " (one per system)\n";
    echo "   Redirect  : {$location}\n\n";

    // Fetch the newest inspection for this property and print scores
    $inspection = Inspection::where('property_id', $propertyId)
        ->orderByDesc('id')
        ->first();

    if ($inspection) {
        echo "   Inspection ID  : {$inspection->id}\n";
        echo "   CPI Score      : " . ($inspection->cpi_total_score ?? 'pending') . "\n";
        echo "   CPI Rating     : " . ($inspection->cpi_rating ?? 'pending') . "\n";
        echo "   ASI Score      : " . ($inspection->asi_score ?? 'pending') . "\n";
        echo "   ASI Rating     : " . ($inspection->asi_rating ?? 'pending') . "\n";
        echo "   TUS Score      : " . ($inspection->tus_score ?? 'default 75') . "\n\n";

        if ($inspection->system_scores) {
            echo "   System-by-System CPI Breakdown:\n";
            echo "   " . str_repeat('─', 52) . "\n";
            foreach ($inspection->system_scores as $data) {
                printf("   %-18s  W%-2s  deduction:%-5s  score: %s\n",
                    $data['name'], $data['weight'],
                    $data['deduction'], $data['score']
                );
            }
        }
        echo "\n   View at: " . url("/admin/inspections/{$inspection->id}") . "\n";
        echo "   Phar at: " . url("/admin/inspections/{$inspection->id}/phar-data") . "\n";
    }

} catch (\Illuminate\Validation\ValidationException $e) {
    echo "\n❌ Validation failed:\n";
    foreach ($e->errors() as $field => $messages) {
        echo "   {$field}: " . implode(', ', $messages) . "\n";
    }
    exit(1);
} catch (\Throwable $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "   " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}
