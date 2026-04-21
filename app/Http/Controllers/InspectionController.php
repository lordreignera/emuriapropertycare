<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\Inspection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\BDCCalculator;
use App\Services\AgreementScheduleService;
use App\Services\InspectionInvoiceSyncService;
use App\Models\InspectionSystem;
use App\Support\PharCatalog;
use Illuminate\Support\Carbon;

class InspectionController extends Controller
{
    public function __construct(
        private readonly AgreementScheduleService $agreementScheduleService,
        private readonly InspectionInvoiceSyncService $inspectionInvoiceSyncService,
    )
    {
    }

    /**
     * Display a listing of inspections.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $countsBaseQuery = Inspection::query()->whereNotNull('property_id');

        if ($user->hasRole('Inspector')) {
            $countsBaseQuery->where('inspector_id', $user->id);
        }

        $scheduledCount = (clone $countsBaseQuery)
            ->where('inspection_fee_status', 'paid')
            ->where('status', 'scheduled')
            ->whereHas('property')
            ->whereDoesntHave('property.inspections', function ($q) {
                $q->where('status', 'completed');
            })
            ->count();

        $inProgressCount = (clone $countsBaseQuery)
            ->where('status', 'in_progress')
            ->count();

        $latestCompletedByProperty = Inspection::query()
            ->selectRaw('MAX(id) as id')
            ->where('status', 'completed')
            ->whereNotNull('property_id')
            ->groupBy('property_id');

        if ($user->hasRole('Inspector')) {
            $latestCompletedByProperty->where('inspector_id', $user->id);
        }

        $completedCount = (clone $countsBaseQuery)
            ->where('status', 'completed')
            ->whereIn('id', $latestCompletedByProperty)
            ->count();
        
        // Base query for inspections
        $query = Inspection::with(['property.user', 'property.projectManager', 'inspector', 'assignedBy', 'project.manager'])
            ->whereNotNull('property_id');

        // Filter by status if provided
        if ($request->filled('status')) {
            if ($request->status === 'scheduled') {
                // Show inspections that are scheduled and paid but not yet completed
                $query->where('inspection_fee_status', 'paid')
                      ->where('status', 'scheduled')
                      ->whereHas('property')
                      ->whereDoesntHave('property.inspections', function ($q) {
                          $q->where('status', 'completed');
                      });
            } elseif ($request->status === 'in_progress') {
                $query->where('status', 'in_progress');
            } elseif ($request->status === 'completed') {
                $latestCompletedByProperty = Inspection::query()
                    ->selectRaw('MAX(id) as id')
                    ->where('status', 'completed')
                    ->groupBy('property_id');

                $query->where('status', 'completed')
                    ->whereIn('id', $latestCompletedByProperty);
            }
        } else {
            // By default, show scheduled and in_progress inspections
            $query->whereIn('status', ['scheduled', 'in_progress']);
        }

        // Project Scheduling view: countersigned but no visit schedule set yet
        if ($request->get('view') === 'needs-schedule') {
            $query = Inspection::with(['property.user', 'property.projectManager', 'inspector', 'assignedBy', 'project.manager'])
                ->whereNotNull('property_id')
                ->whereNotNull('etogo_signed_at')
                ->where(function ($q) {
                    $q->whereNull('work_schedule')->orWhere('work_schedule', '[]');
                });

            if ($user->hasRole('Inspector')) {
                $query->where('inspector_id', $user->id);
            }
        }

        // Pending Etogo signature: client signed + paid, waiting for Etogo countersign
        if ($request->get('view') === 'pending-etogo') {
            $query = Inspection::with(['property.user', 'property.projectManager', 'inspector', 'assignedBy', 'project.manager'])
                ->whereNotNull('property_id')
                ->whereNotNull('client_signature')
                ->where('work_payment_status', 'paid')
                ->whereNull('etogo_signed_at');

            if ($user->hasRole('Inspector')) {
                $query->where('inspector_id', $user->id);
            }
        }

        // If user is an inspector, only show inspections assigned to them
        if ($user->hasRole('Inspector')) {
            $query->where('inspector_id', $user->id);
        }

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('property', function($q) use ($search) {
                $q->where('property_name', 'like', "%{$search}%")
                  ->orWhere('property_code', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%");
            });
        }

        $inspections = $query->orderBy('scheduled_date', 'asc')
            ->paginate(15);

        $inspectors = \App\Models\User::role('Inspector')
            ->orderBy('name')
            ->get(['id', 'name']);

        $projectManagers = \App\Models\User::role('Project Manager')
            ->orderBy('name')
            ->get(['id', 'name']);

        $technicians = \App\Models\User::role('Technician')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.inspections.index', compact('inspections', 'scheduledCount', 'inProgressCount', 'completedCount', 'inspectors', 'projectManagers', 'technicians'));
    }

    /**
     * Show the form for creating a new inspection.
     */
    public function create(Request $request)
    {
        $propertyId = $request->get('property_id');
        
        if (!$propertyId) {
            return redirect()->route('inspections.index')
                ->with('error', 'Property ID is required to start an inspection.');
        }

        $property = Property::with(['user', 'inspector', 'projectManager'])
            ->findOrFail($propertyId);

        // Check if user has permission to inspect this property
        $user = Auth::user();
        if ($user->hasRole('Inspector')) {
            $isAssignedToProperty = (int) ($property->inspector_id ?? 0) === (int) $user->id;
            $isAssignedToInspection = Inspection::where('property_id', $property->id)
                ->where('inspector_id', $user->id)
                ->whereIn('status', ['scheduled', 'in_progress', 'completed'])
                ->exists();
            $hasPermission = $user->can('create inspections');

            if (!$isAssignedToProperty && !$isAssignedToInspection && !$hasPermission) {
                abort(403, 'You are not assigned to inspect this property.');
            }
        }

        // Get existing inspection if it exists
        $inspection = Inspection::where('property_id', $property->id)
            ->where('inspection_fee_status', 'paid')
            ->first();

        $systems = collect();
        if (Schema::hasTable('systems') && Schema::hasTable('subsystems')) {
            $systems = InspectionSystem::with(['subsystems' => function ($query) {
                $query->where('is_active', true)->orderBy('sort_order')->orderBy('name');
            }])
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();
        }

        $dbMaterialSettings = \App\Models\FmcMaterialSetting::active()->get([
            'material_name', 'default_unit', 'default_unit_cost', 'hst_rate', 'pst_rate', 'system_id', 'subsystem_id',
        ]);
        $materialUnits = array_values(array_unique(array_merge(
            config('phar.material_units', []),
            PharCatalog::materialUnits(),
            $dbMaterialSettings->pluck('default_unit')->filter()->unique()->toArray()
        )));
        $catalogMaterialSettings = collect(PharCatalog::materials())->map(
            static fn(array $row) => (object) [
                'material_name'     => $row['material_name'],
                'default_unit'      => $row['default_unit'],
                'default_unit_cost' => $row['default_unit_cost'],
                'hst_rate'          => $row['hst_rate']  ?? 5.00,
                'pst_rate'          => $row['pst_rate']  ?? 7.00,
                'system_id'         => null,
                'subsystem_id'      => null,
            ]
        );
        // DB records take precedence — exclude catalog entries whose name is already in the DB list
        $dbNames = $dbMaterialSettings->pluck('material_name')->map('strtolower')->flip();
        $fmcMaterialSettings = $dbMaterialSettings
            ->concat($catalogMaterialSettings->reject(fn($c) => $dbNames->has(strtolower($c->material_name))))
            ->values();

        $pharCategories = array_values(array_unique(array_merge(
            config('phar.categories', []),
            PharCatalog::categories()
        )));

        return view('admin.inspections.form-cpi', compact(
            'property',
            'inspection',
            'systems',
            'materialUnits',
            'fmcMaterialSettings',
            'pharCategories'
        ));
    }

    /**
     * Store a newly created inspection in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'property_id' => 'required|exists:properties,id',
            'status' => 'required|in:scheduled,in_progress,completed',
            'inspection_date' => 'required|date',
            'inspector_id' => 'nullable|exists:users,id',
            'weather_conditions' => 'nullable|string|max:120',
            'summary' => 'nullable|string',
            
            // Overall Assessment
            'overall_condition' => 'nullable|in:excellent,good,fair,poor,critical',
            'inspector_notes' => 'nullable|string',
            'recommendations' => 'nullable|string',
            'risk_summary' => 'nullable|string',
            
            // Photos (overall inspection)
            'photos.*' => 'nullable|image|max:10240',

            // Per-finding photos (indexed by system_findings input index)
            'finding_photos'       => 'nullable|array',
            'finding_photos.*'     => 'nullable|array',
            'finding_photos.*.*'   => 'nullable|file|mimetypes:image/jpeg,image/png,image/webp,image/gif,image/heic,image/heif,video/mp4,video/webm,video/quicktime,video/x-msvideo,video/x-matroska|max:51200',

            // Existing saved photo paths passed back as hidden inputs to preserve on re-submit
            'existing_finding_photos'     => 'nullable|array',
            'existing_finding_photos.*'   => 'nullable|array',
            'existing_finding_photos.*.*' => 'nullable|string',
            
            // Findings Array
            'findings' => 'nullable|array',
            'findings.*.task_question' => 'nullable|string',
            'findings.*.category' => 'nullable|string',
            'findings.*.priority' => 'nullable|in:1,2,3',
            'findings.*.included_yn' => 'nullable|boolean',
            'findings.*.labour_hours' => 'nullable|numeric|min:0',
            'findings.*.material_cost' => 'nullable|numeric|min:0',
            'findings.*.notes' => 'nullable|string',
            'findings.*.property_id' => 'nullable|exists:properties,id',
            'system_findings' => 'nullable|array',
            'system_findings.*.system_id' => 'nullable|exists:systems,id',
            'system_findings.*.subsystem_id' => 'nullable|exists:subsystems,id',
            'system_findings.*.issue' => 'nullable|string|max:255',
            'system_findings.*.location' => 'nullable|string|max:255',
            'system_findings.*.spot' => 'nullable|string|max:255',
            'system_findings.*.severity' => 'nullable|in:low,medium,high,critical,noi_protection,urgent,health_safety_threatening,value_depreciation,non_urgent',
            'system_findings.*.notes' => 'nullable|string',
            'system_findings.*.recommendations' => 'nullable',
            'system_findings.*.recommendations.*' => 'nullable|string|max:500',
            'system_findings.*.phar_labour_hours'              => 'nullable|numeric|min:0',
            'system_findings.*.materials'                      => 'nullable|array',
            'system_findings.*.materials.*.material_name'      => 'nullable|string|max:255',
            'system_findings.*.materials.*.quantity'           => 'nullable|numeric|min:0',
            'system_findings.*.materials.*.unit'               => 'nullable|string|max:50',
            'system_findings.*.materials.*.unit_cost'          => 'nullable|numeric|min:0',
            'system_findings.*.materials.*.line_total'         => 'nullable|numeric|min:0',
            'system_findings.*.materials.*.notes'              => 'nullable|string|max:500',
            'system_findings.*.risk_impact'                     => 'nullable|string|max:1000',
            'system_findings.*.phar_category'                  => 'nullable|string|max:255',
            'system_findings.*.phar_included_yn'               => 'nullable|boolean',
            'system_findings.*.phar_notes'                     => 'nullable|string',
        ]);

        $property = Property::findOrFail($validated['property_id']);

        // Create or find project for this property
        $project = \App\Models\Project::firstOrCreate(
            ['property_id' => $property->id],
            [
                'title' => 'Property Inspection - ' . $property->property_name,
                'description' => 'CPI Inspection for ' . $property->property_name,
                'status' => 'pending',
                'user_id' => $property->user_id, // Client/Owner
                'managed_by' => $property->project_manager_id, // PM
                'created_by' => Auth::id(),
                'project_number' => 'PRJ-' . strtoupper(\Illuminate\Support\Str::random(8)),
            ]
        );

        // Reuse an existing paid inspection for this property to avoid duplicate records
        $inspection = Inspection::where('property_id', $property->id)
            ->where('inspection_fee_status', 'paid')
            ->whereIn('status', ['scheduled', 'in_progress', 'completed'])
            ->latest('id')
            ->first();

        if (!$inspection) {
            $inspection = new Inspection();
            $inspection->property_id = $property->id;
            $inspection->project_id = $project->id;
            $inspection->inspector_id = $validated['inspector_id'] ?? Auth::id();
            $inspection->assigned_by = $property->project_manager_id ?? Auth::id();
            $inspection->scheduled_date = $validated['inspection_date'];
        } else {
            $inspection->project_id = $inspection->project_id ?: $project->id;
            $inspection->inspector_id = $validated['inspector_id'] ?? ($inspection->inspector_id ?: Auth::id());
            $inspection->assigned_by = $inspection->assigned_by ?: ($property->project_manager_id ?? Auth::id());
            $inspection->scheduled_date = $validated['inspection_date'];
        }
        
        if ($validated['status'] === 'completed') {
            $inspection->completed_date = now();
        }
        
        $inspection->status = $validated['status'];

        // Service package is NOT assigned at Step 1 — it is selected later in the sales/quoting process.

        $inspection->weather_conditions = $validated['weather_conditions'] ?? null;

        $inspection->owner_name = $property->user->name ?? null;
        $inspection->owner_email = $property->user->email ?? null;
        $inspection->owner_phone = $property->owner_phone
            ?: (($property->user->phone ?? null)
                ?: ($property->admin_phone ?: null));
        $inspection->property_code = $property->property_code;
        $inspection->property_name = $property->property_name;
        $inspection->property_address_snapshot = trim(($property->property_address ?? '') . ', ' . ($property->city ?? ''));
        $inspection->property_type_snapshot = $property->type;
        $inspection->residential_units_snapshot = $property->residential_units;
        $inspection->commercial_sqft_snapshot = $property->square_footage_interior;
        $inspection->mixed_use_weight_snapshot = $property->mixed_use_commercial_weight;

        // Persist only general page-1 inspection snapshot fields.
        $inspection->property_year_built = $request->input('property_year_built');

        $systemFindings = collect($request->input('system_findings', []));
        $systemNameMap = collect();
        $systemSlugMap = collect();
        $subsystemNameMap = collect();

        if (Schema::hasTable('systems') && Schema::hasTable('subsystems') && $systemFindings->isNotEmpty()) {
            $systemIds = $systemFindings->pluck('system_id')->filter()->unique()->values();
            $subsystemIds = $systemFindings->pluck('subsystem_id')->filter()->unique()->values();
            $systemNameMap = InspectionSystem::whereIn('id', $systemIds)->pluck('name', 'id');
            $systemSlugMap = InspectionSystem::whereIn('id', $systemIds)->pluck('slug', 'id');
            $subsystemNameMap = \App\Models\InspectionSubsystem::whereIn('id', $subsystemIds)->pluck('name', 'id');
        }

        $severityAliases = [
            'urgent'                      => 'critical',
            'health_safety_threatening'   => 'high',
            'value_depreciation'          => 'medium',
            'non_urgent'                  => 'low',
        ];

        $allowedSeverities = ['critical', 'high', 'noi_protection', 'medium', 'low'];

        $priorityScores = [
            'critical'       => 100, // Safety & Health
            'high'           => 80,  // Urgent
            'noi_protection' => 60,  // NOI Protection
            'medium'         => 40,  // Value Depreciation
            'low'            => 10,  // Non-Urgent
        ];

        $disk = config('filesystems.default', 's3');

        // Upload per-finding photos before normalizing findings (keyed by system_findings input index)
        // Some clients submit files as finding_photos[idx][] while others can submit them under
        // system_findings[idx][finding_photos] — handle both shapes.
        $findingPhotoFiles = [];

        foreach ((array) $request->file('finding_photos', []) as $idx => $photos) {
            $findingPhotoFiles[$idx] = array_merge($findingPhotoFiles[$idx] ?? [], (array) $photos);
        }

        foreach ((array) $request->file('system_findings', []) as $idx => $findingPayload) {
            $nested = (array) ($findingPayload['finding_photos'] ?? []);
            if (!empty($nested)) {
                $findingPhotoFiles[$idx] = array_merge($findingPhotoFiles[$idx] ?? [], $nested);
            }
        }

        $findingPhotoPaths = [];
        foreach ($findingPhotoFiles as $idx => $photos) {
            $paths = [];
            foreach ((array) $photos as $photo) {
                if ($photo && $photo->isValid()) {
                    $paths[] = $photo->store('inspections/finding-photos', $disk);
                }
            }
            if (!empty($paths)) {
                $findingPhotoPaths[$idx] = $paths;
            }
        }

        // Preserved existing photo paths submitted as hidden inputs (so they survive re-submit without new upload)
        $preservedPhotoPaths = [];
        foreach ((array) $request->input('existing_finding_photos', []) as $idx => $paths) {
            $clean = array_values(array_filter((array) $paths, fn($p) => is_string($p) && $p !== ''));
            if (!empty($clean)) {
                $preservedPhotoPaths[$idx] = $clean;
            }
        }

        // Also load previously saved photos from the existing inspection as a final fallback
        $savedInspectionPhotos = [];
        if ($inspection) {
            foreach ((array) ($inspection->findings ?? []) as $fi => $f) {
                if (!empty($f['finding_photos'])) {
                    $savedInspectionPhotos[$fi] = array_values(array_filter((array) $f['finding_photos']));
                }
            }
        }

        $normalizedFindings = $systemFindings
            ->map(function ($finding, $idx) use ($systemNameMap, $systemSlugMap, $subsystemNameMap, $severityAliases, $allowedSeverities, $findingPhotoPaths, $preservedPhotoPaths, $savedInspectionPhotos) {
                $systemId = $finding['system_id'] ?? null;
                $subsystemId = $finding['subsystem_id'] ?? null;
                $rawSeverity = (string) ($finding['severity'] ?? 'low');
                $normalizedSeverity = $severityAliases[$rawSeverity] ?? $rawSeverity;

                return [
                    'system_id' => $systemId,
                    'system' => $systemNameMap[$systemId] ?? null,
                    'system_slug' => $systemSlugMap[$systemId] ?? null,
                    'subsystem_id' => $subsystemId,
                    'subsystem' => $subsystemNameMap[$subsystemId] ?? null,
                    'issue' => trim((string) ($finding['issue'] ?? '')),
                    'location' => trim((string) ($finding['location'] ?? '')),
                    'spot' => trim((string) ($finding['spot'] ?? '')),
                    'severity' => in_array($normalizedSeverity, $allowedSeverities, true) ? $normalizedSeverity : 'low',
                    'notes' => trim((string) ($finding['notes'] ?? '')),
                    'recommendations' => collect(is_array($finding['recommendations'] ?? null)
                        ? ($finding['recommendations'] ?? [])
                        : preg_split('/\r\n|\r|\n|\|/', (string) ($finding['recommendations'] ?? '')))
                        ->map(fn ($item) => trim((string) $item))
                        ->filter()
                        ->values()
                        ->all(),
                    'type'           => $systemSlugMap[$systemId] ?? null,
                    'finding_photos' => array_values(array_unique(array_merge(
                        $savedInspectionPhotos[$idx] ?? [],
                        $preservedPhotoPaths[$idx] ?? [],
                        $findingPhotoPaths[$idx] ?? []
                    ))),
                    'risk_impact'       => trim((string) ($finding['risk_impact'] ?? '')),
                    'phar_labour_hours' => (float) ($finding['phar_labour_hours'] ?? 0),
                    'phar_category'     => trim((string) ($finding['phar_category'] ?? '')),
                    'phar_included_yn'  => isset($finding['phar_included_yn']) ? (bool) $finding['phar_included_yn'] : true,
                    'phar_notes'        => trim((string) ($finding['phar_notes'] ?? '')),
                    'phar_materials'    => collect($finding['materials'] ?? [])
                        ->filter(fn($m) => !empty($m['material_name']))
                        ->map(fn($m) => [
                            'material_name' => trim((string) ($m['material_name'] ?? '')),
                            'quantity'      => (float) ($m['quantity'] ?? 1),
                            'unit'          => (string) ($m['unit'] ?? 'ea'),
                            'unit_cost'     => (float) ($m['unit_cost'] ?? 0),
                            'line_total'    => (float) ($m['line_total'] ?? 0),
                            'notes'         => trim((string) ($m['notes'] ?? '')),
                            'property_id'   => (int) ($m['property_id'] ?? 0),
                        ])
                        ->values()
                        ->all(),
                ];
            })
            ->filter(function ($finding) {
                return $finding['system_id']
                    && ($finding['issue'] !== ''
                        || $finding['notes'] !== ''
                        || !empty($finding['recommendations']));
            })
            ->values()
            ->all();

        // Store overall assessment
        $inspection->summary = $validated['summary'] ?? ('Inspection for ' . $property->property_name);
        $inspection->overall_condition = $validated['overall_condition'] ?? null;
        $inspection->inspector_notes = $validated['inspector_notes'] ?? null;
        $inspection->recommendations = $validated['recommendations'] ?? null;
        $inspection->risk_summary = $validated['risk_summary'] ?? null;
        $inspection->findings = $normalizedFindings;

        // ==== COMPUTE WEIGHTED CPI FROM FINDINGS × SYSTEM WEIGHTS ====
        $this->computeWeightedCPI($inspection, $normalizedFindings, $priorityScores);
        $this->computeASI($inspection);

        // Handle photos upload
        if ($request->hasFile('photos')) {
            $photosPaths = [];
            foreach ($request->file('photos') as $photo) {
                $path = $photo->store('inspections/photos', $disk);
                $photosPaths[] = $path;
            }
            $inspection->photos = $photosPaths;
        }

        $inspection->save();

        // ==== FINDINGS & MATERIALS ARE NOW COLLECTED ON PAGE 2 (PHAR DATA FORM) ====
        // Findings processing moved to storePharData() method
        // This keeps the two-page workflow clean: Page 1 = CPI scoring, Page 2 = PHAR data

        // NOTE: We don't run full calculations here anymore - only basic save
        // Full calculations happen after PHAR data collection in storePharData()

        $proceedToPhar = $request->input('next_stage') === 'phar';

        $message = $proceedToPhar
            ? 'CPI scoring saved. Proceed to PHAR assessment/pricing.'
            : 'CPI scoring saved as draft successfully!';

        // Redirect to PHAR data form (Page 2) when user chooses next stage
        if ($proceedToPhar) {
            return redirect()->route('inspections.phar-data', $inspection->id)
                ->with('success', $message);
        }

        return redirect()->route('inspections.index')
            ->with('success', $message);
    }

    /**
     * Compute the weighted CPI (0–100) from findings × system weights and
     * persist it plus the per-system breakdown on the Inspection model.
     *
     * Formula per finding:
     *   CPI Deduction = (SystemWeight × PriorityScore × 9) / (MaxSystemWeight × 100)
     *
     * Per system:
     *   SystemScore = max(0, 100 − Σ deductions)
     *
     * Overall CPI:
     *   CPI = Σ(SystemScore × SystemWeight) / Σ(SystemWeights)
     */
    protected function computeWeightedCPI(
        \App\Models\Inspection $inspection,
        array $findings,
        array $priorityScores
    ): void {
        $maxSystemWeight = 20;   // Structural — highest weight
        $scalingFactor   = 9;    // Max possible deduction for a single finding

        $allSystems  = InspectionSystem::where('is_active', true)->get(['id', 'name', 'weight']);
        $totalWeight = $allSystems->sum('weight');

        $systemScores = [];

        foreach ($allSystems as $system) {
            $systemFindings = array_filter(
                $findings,
                fn($f) => (int) ($f['system_id'] ?? 0) === (int) $system->id
            );

            $totalDeduction = 0.0;
            foreach ($systemFindings as $finding) {
                $priorityScore = (float) ($priorityScores[$finding['severity'] ?? 'low'] ?? 0);
                $weight        = (int) $system->weight;
                $totalDeduction += ($weight * $priorityScore * $scalingFactor) / ($maxSystemWeight * 100);
            }

            $systemScore = max(0.0, 100.0 - $totalDeduction);

            $systemScores[(string) $system->id] = [
                'name'      => $system->name,
                'weight'    => $system->weight,
                'deduction' => round($totalDeduction, 2),
                'score'     => round($systemScore, 1),
            ];
        }

        $weightedSum = 0.0;
        foreach ($systemScores as $data) {
            $weightedSum += $data['score'] * $data['weight'];
        }

        $cpi = $totalWeight > 0 ? round($weightedSum / $totalWeight, 1) : 100.0;

        $inspection->cpi_total_score = $cpi;
        $inspection->system_scores   = $systemScores;
    }

    /**
     * Compute ASI (Asset Stability Index) from CPI + TUS and attach rating labels.
     * Must be called after computeWeightedCPI() so cpi_total_score is set.
     * Does NOT call $inspection->save() — caller is responsible.
     */
    protected function computeASI(\App\Models\Inspection $inspection): void
    {
        $cpiWeight = (float) (\App\Models\BDCSetting::getValue('cpi_weight', 0.60) ?? 0.60);
        $tusWeight  = (float) (\App\Models\BDCSetting::getValue('tus_weight', 0.40) ?? 0.40);

        $cpi = (float) ($inspection->cpi_total_score ?? 100.0);
        $tus = (float) ($inspection->tus_score ?? 75.0);

        $asi = round($cpi * $cpiWeight + $tus * $tusWeight, 1);

        $cpiRating = match (true) {
            $cpi >= 90 => 'Excellent',
            $cpi >= 75 => 'Good',
            $cpi >= 60 => 'Fair',
            $cpi >= 40 => 'Poor',
            default    => 'Critical',
        };

        $asiRating = match (true) {
            $asi >= 90 => 'Highly stable asset',
            $asi >= 80 => 'Stable asset',
            $asi >= 70 => 'Moderate stability',
            $asi >= 60 => 'Vulnerable stability',
            $asi >= 50 => 'Unstable asset',
            default    => 'Severe instability',
        };

        $inspection->asi_score  = $asi;
        $inspection->cpi_rating = $cpiRating;
        $inspection->asi_rating = $asiRating;
    }

    /**
     * Display the specified inspection.
     */
    public function show(string $id)
    {
        $inspection = Inspection::with(['property.user', 'project', 'inspector', 'assignedBy', 'etogoRepresentative', 'toolAssignments.toolSetting'])
            ->findOrFail($id);

        if (($inspection->status ?? null) === 'completed') {
            $inspection = $this->agreementScheduleService->refresh($inspection);
        }
        
        // Load findings for this inspection with inspection relationship
        $findings = \App\Models\PHARFinding::with('inspection')
            ->where('inspection_id', $inspection->id)
            ->get();

        $materials = \App\Models\InspectionMaterial::where('inspection_id', $inspection->id)
            ->orderBy('id')
            ->get();

        // Ensure property exists
        if (!$inspection->property) {
            return redirect()->route('inspections.index')
                ->with('error', 'Property not found for this inspection.');
        }
        
        // Check if calculations are missing and recalculate if needed
        if ($inspection->status === 'completed' && 
            ($inspection->bdc_annual === null || $inspection->bdc_annual == 0)) {
            try {
                $bdcCalculator = new \App\Services\BDCCalculator();
                $calculator = new \App\Services\MergeBridgeCalculator($bdcCalculator);
                $results = $calculator->calculate($inspection);
                $calculator->saveToInspection($inspection, $results);
                $inspection->refresh();
            } catch (\Exception $e) {
                // Log error but continue to show the view
                \Log::error('Failed to recalculate inspection: ' . $e->getMessage());
            }
        }
        
        $toolAssignments = $inspection->toolAssignments->where('quantity', '>', 0);

        return view('admin.inspections.show', compact('inspection', 'findings', 'materials', 'toolAssignments'));
    }

    /**
     * Show the form for editing the specified inspection.
     */
    public function edit(string $id)
    {
        $inspection = Inspection::findOrFail($id);

        return redirect()->route('inspections.create', ['property_id' => $inspection->property_id]);
    }

    /**
     * Update the specified inspection in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified inspection from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    /**
     * Download inspection report as PDF invoice
     */
    public function downloadInvoice(string $id)
    {
        $inspection = Inspection::with(['property.user', 'property.projectManager', 'project.manager', 'inspector', 'assignedBy'])
            ->findOrFail($id);

        if (($inspection->status ?? null) === 'completed') {
            $inspection = $this->agreementScheduleService->refresh($inspection);
        }
        
        // Load findings for this inspection with inspection relationship
        $findings = \App\Models\PHARFinding::with('inspection')
            ->where('inspection_id', $inspection->id)
            ->get();

        $materials = \App\Models\InspectionMaterial::where('inspection_id', $inspection->id)
            ->orderBy('id')
            ->get();

        // Ensure property exists
        if (!$inspection->property) {
            return redirect()->route('inspections.index')
                ->with('error', 'Property not found for this inspection.');
        }
        
        // Resolve photo URLs for DomPDF (signed S3 URLs or local file:// paths)
        $disk   = config('filesystems.default', 's3');
        $driver = config("filesystems.disks.{$disk}.driver");
        $rawPhotos = is_array($inspection->photos) ? $inspection->photos : [];
        $photoUrls = collect($rawPhotos)->map(function ($path) use ($disk, $driver) {
            if ($driver === 'local') {
                return 'file:///' . str_replace('\\', '/', storage_path('app/public/' . $path));
            }
            return \Illuminate\Support\Facades\Storage::disk($disk)->temporaryUrl($path, now()->addMinutes(30));
        })->all();

        // Pre-resolve per-finding photo URLs for DomPDF
        $rawFindings = is_array($inspection->findings) ? $inspection->findings : [];
        $findingPhotoUrls = [];
        foreach ($rawFindings as $fi => $finding) {
            $fps = is_array($finding['finding_photos'] ?? null) ? $finding['finding_photos'] : [];
            $findingPhotoUrls[$fi] = array_map(function ($path) use ($disk, $driver) {
                if ($driver === 'local') {
                    return 'file:///' . str_replace('\\', '/', storage_path('app/public/' . $path));
                }
                return \Illuminate\Support\Facades\Storage::disk($disk)->temporaryUrl($path, now()->addMinutes(30));
            }, $fps);
        }

        // Generate PDF
        $isRemote = ($driver !== 'local');
        $pdf = Pdf::loadView('admin.inspections.invoice-pdf', compact('inspection', 'findings', 'materials', 'photoUrls', 'findingPhotoUrls'))
            ->setPaper('a4', 'landscape')
            ->setOption('margin-top', 10)
            ->setOption('margin-right', 10)
            ->setOption('margin-bottom', 10)
            ->setOption('margin-left', 10)
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', $isRemote);
        
        $clientName = Str::slug((string) ($inspection->property?->user?->name ?? 'client'));
        $propertyName = Str::slug((string) ($inspection->property?->property_name ?? $inspection->property?->property_code ?? 'property'));
        $filename = 'Inspection_Report_' . $clientName . '_' . $propertyName . '.pdf';
        
        return $pdf->download($filename);
    }

    /**
     * Show Stripe payment page for post-inspection work start.
     */
    public function workPayment(string $id)
    {
        $inspection = Inspection::with(['property.user', 'project'])->findOrFail($id);

        if ($inspection->status !== 'completed') {
            return redirect()->route('inspections.index', ['status' => 'completed'])
                ->with('error', 'Work payment is only available after inspection completion.');
        }

        if (($inspection->work_payment_status ?? null) === 'paid') {
            return redirect()->route('inspections.show', $inspection->id)
                ->with('info', 'Work payment is already completed for this inspection.');
        }

        $workAmount = (float) max(
            (float) ($inspection->scientific_final_monthly ?? 0),
            (float) ($inspection->arp_equivalent_final ?? 0),
            (float) ($inspection->base_package_price_snapshot ?? 0)
        );

        if ($workAmount <= 0) {
            return redirect()->route('inspections.show', $inspection->id)
                ->with('error', 'Cannot start payment because calculated work amount is zero. Complete PHAR calculation first.');
        }

        $stripe = new \Stripe\StripeClient(config('cashier.secret'));
        $paymentIntent = $stripe->paymentIntents->create([
            'amount' => (int) round($workAmount * 100),
            'currency' => 'usd',
            'metadata' => [
                'inspection_id' => $inspection->id,
                'property_id' => $inspection->property_id,
                'project_id' => $inspection->project_id,
                'payment_type' => 'work_start',
            ],
        ]);

        return view('admin.inspections.work-payment', [
            'inspection' => $inspection,
            'workAmount' => $workAmount,
            'clientSecret' => $paymentIntent->client_secret,
            'stripeKey' => config('cashier.key'),
        ]);
    }

    /**
     * Confirm Stripe work payment and start project work.
     */
    public function processWorkPayment(Request $request, string $id)
    {
        $inspection = Inspection::with('project')->findOrFail($id);

        $validated = $request->validate([
            'payment_intent_id' => 'required|string',
        ]);

        try {
            DB::beginTransaction();

            $stripe = new \Stripe\StripeClient(config('cashier.secret'));
            $paymentIntent = $stripe->paymentIntents->retrieve($validated['payment_intent_id']);

            if (($paymentIntent->status ?? null) !== 'succeeded') {
                throw new \RuntimeException('Payment not completed successfully.');
            }

            $inspection->update([
                'work_payment_status' => 'paid',
                'work_payment_paid_at' => now(),
                'work_payment_amount' => ((float) $paymentIntent->amount_received) / 100,
                'work_stripe_payment_intent_id' => $paymentIntent->id,
            ]);

            $this->inspectionInvoiceSyncService->syncProjectInvoice($inspection->fresh(['property', 'project']));

            $inspection = $this->agreementScheduleService->refresh($inspection);

            if ($inspection->project) {
                $inspection->project->update([
                    'status' => 'in_progress',
                    'actual_start_date' => $inspection->project->actual_start_date ?: now()->toDateString(),
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment successful. Work has been started.',
                'redirect' => route('inspections.show', $inspection->id),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Work payment processing failed', [
                'inspection_id' => $inspection->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment verification failed. Please try again.',
            ], 400);
        }
    }

    public function staffSignAgreement(Request $request, Inspection $inspection)
    {
        $user = Auth::user();
        if (!$user || !$user->hasAnyRole(['Super Admin', 'Administrator', 'Admin', 'Project Manager', 'Inspector', 'Technician', 'Finance Officer'])) {
            abort(403, 'You are not authorized to sign this agreement as Etogo staff.');
        }

        if (($inspection->status ?? null) !== 'completed') {
            return back()->with('error', 'Agreement can only be signed after inspection completion.');
        }

        if (!$inspection->approved_by_client || !$inspection->client_approved_at) {
            return back()->with('error', 'Etogo staff can only sign after the client signs.');
        }

        if ($inspection->etogo_signed_at) {
            return back()->with('info', 'Agreement has already been signed by Etogo staff.');
        }

        $inspection->update([
            'etogo_signed_by' => $user->id,
            'etogo_signed_at' => now(),
        ]);

        $this->agreementScheduleService->refresh($inspection);

        return back()->with('success', 'Agreement signed by Etogo staff (' . $user->name . ').');
    }

    public function countersignAgreement(Request $request, Inspection $inspection)
    {
        $user = Auth::user();
        if (!$user || !$user->hasAnyRole(['Super Admin', 'Admin', 'Project Manager'])) {
            abort(403, 'You are not authorized to countersign this agreement.');
        }

        if (($inspection->status ?? null) !== 'completed') {
            return back()->with('error', 'Agreement countersign is only available for completed inspections.');
        }

        if (!$inspection->approved_by_client || !$inspection->client_approved_at) {
            return back()->with('error', 'Client must sign the agreement before Etogo countersign.');
        }

        if (($inspection->work_payment_status ?? 'pending') !== 'paid') {
            return back()->with('error', 'Deposit/work payment must be confirmed before Etogo countersign.');
        }

        if ($inspection->etogo_signed_at) {
            return back()->with('info', 'Agreement has already been countersigned by Etogo.');
        }

        $inspection->update([
            'etogo_signed_by' => Auth::id(),
            'etogo_signed_at' => now(),
        ]);

        $this->agreementScheduleService->refresh($inspection);

        return back()->with('success', 'Agreement countersigned by ' . Auth::user()->name . '.');
    }

    /**
     * Save (or replace) the work visit schedule for a fully-executed inspection.
     * All dates must be Mon–Sat. Work hours are 7 AM – 6 PM.
     */
    public function storeWorkSchedule(Request $request, Inspection $inspection)
    {
        $user = Auth::user();
        if (!$user || !$user->hasAnyRole(['Super Admin', 'Admin', 'Project Manager'])) {
            abort(403);
        }

        if (!$inspection->etogo_signed_at) {
            return back()->with('error', 'The agreement must be countersigned by Etogo before scheduling work visits.');
        }

        $validated = $request->validate([
            'visit_dates'   => 'required|array|min:1',
            'visit_dates.*' => 'required|date',
        ]);

        $badDates = collect($validated['visit_dates'])->filter(function (string $date) {
            // Working week is Monday–Saturday; only reject Sunday
            return Carbon::parse($date)->dayOfWeek === Carbon::SUNDAY;
        })->values();

        if ($badDates->isNotEmpty()) {
            return back()->with('error', 'Visit dates must be Monday – Saturday (no Sundays). Invalid: ' . $badDates->implode(', '));
        }

        $schedule = collect($validated['visit_dates'])
            ->map(fn($d) => Carbon::parse($d)->toDateString())
            ->unique()
            ->sort()
            ->values()
            ->map(fn($d) => ['date' => $d, 'status' => 'scheduled'])
            ->all();

        $updates = ['work_schedule' => $schedule];

        if (!empty($schedule)) {
            $dates = collect($schedule)->pluck('date');
            $updates['planned_start_date']      = $dates->first();
            $updates['target_completion_date']  = $dates->last();
            $updates['schedule_blocked_reason'] = null;
        }

        $inspection->update($updates);

        return back()->with('success', count($schedule) . ' visit(s) scheduled successfully.');
    }

    /**
     * Display PHAR data collection form (Page 2 of inspection workflow)
     */
    public function pharData(string $id)
    {
        $inspection = Inspection::with(['property', 'pharFindings'])->findOrFail($id);
        $property = $inspection->property;

        // Sort Phase 1 findings by severity: critical → high → noi_protection → medium → low
        $severityOrder  = ['critical' => 0, 'high' => 1, 'noi_protection' => 2, 'medium' => 3, 'low' => 4];
        $sortedFindings = collect($inspection->findings ?? [])
            ->sortBy(fn($f) => $severityOrder[$f['severity'] ?? 'low'] ?? 99)
            ->values()
            ->all();
        $sortedFindings = PharCatalog::applyDefaultsToFindings($sortedFindings);

        // System weights keyed by name for display in the finding header
        $systemWeightsMap = InspectionSystem::where('is_active', true)->pluck('weight', 'name')->toArray();

        // Default property size from registered property record
        $defaultPropertySizePsf = $property->total_square_footage
            ?? $property->square_footage_interior
            ?? 0;

        // Fetch BDC settings for display in the form
        $bdcSettings = \App\Models\BDCSetting::pluck('setting_value', 'setting_key')->toArray();

        // Config-driven dropdown options (easy to extend)
        $pharCategories = array_values(array_unique(array_merge(
            config('phar.categories', []),
            PharCatalog::categories()
        )));
        $dbMaterialSettings = \App\Models\FmcMaterialSetting::active()->get([
            'material_name',
            'default_unit',
            'default_unit_cost',
            'hst_rate',
            'pst_rate',
            'system_id',
            'subsystem_id',
        ]);
        $materialUnits = array_values(array_unique(array_merge(
            config('phar.material_units', []),
            PharCatalog::materialUnits(),
            $dbMaterialSettings->pluck('default_unit')->filter()->unique()->toArray()
        )));
        $catalogMaterialSettings = collect(PharCatalog::materials())->map(static fn(array $row) => (object) [
            'material_name'     => $row['material_name'],
            'default_unit'      => $row['default_unit'],
            'default_unit_cost' => $row['default_unit_cost'],
            'hst_rate'          => $row['hst_rate'] ?? 5.00,
            'pst_rate'          => $row['pst_rate'] ?? 7.00,
            'system_id'         => null,
            'subsystem_id'      => null,
        ]);
        // DB records take precedence — exclude catalog entries whose name is already in the DB list
        $dbNames = $dbMaterialSettings->pluck('material_name')->map('strtolower')->flip();
        $fmcMaterialSettings = $dbMaterialSettings
            ->concat($catalogMaterialSettings->reject(fn($c) => $dbNames->has(strtolower($c->material_name))))
            ->values();

        $dbFindingTemplateSettings = \App\Models\FindingTemplateSetting::active()->get([
            'task_question',
            'category',
            'default_included',
            'default_notes',
        ]);
        $catalogFindingTemplateSettings = collect(PharCatalog::findingTemplates())->map(static fn(array $row) => (object) $row);
        $findingTemplateSettings = $dbFindingTemplateSettings
            ->concat($catalogFindingTemplateSettings)
            ->unique('task_question')
            ->values();

        // Reconcile stored BDC/TRC against the saved travel inputs on every page load.
        // Only writes to the DB when the stored value actually differs.
        $this->syncBdcAndTrc($inspection, onlyIfChanged: true);

        return view('admin.inspections.form-phar-data', compact(
            'inspection',
            'property',
            'bdcSettings',
            'pharCategories',
            'materialUnits',
            'fmcMaterialSettings',
            'findingTemplateSettings',
            'defaultPropertySizePsf',
            'sortedFindings',
            'systemWeightsMap'
        ));
    }

    /**
     * Store PHAR data (findings + materials) and trigger final calculations.
     * Supports draft saving (action=save_draft_back) to persist and return to Step 1.
     */
    public function storePharData(Request $request, string $id)
    {
        $inspection = Inspection::findOrFail($id);
        $property = $inspection->property;
        $isDraft = $request->input('action') === 'save_draft_back';

        $validated = $request->validate([
            // PHAR Inputs
            'property_size_psf'       => 'nullable|numeric|min:0',
            'bdc_visits_per_year'     => 'nullable|numeric|min:0|max:365',
            'estimated_task_hours'    => 'nullable|numeric|min:0',
            'minimum_required_hours'  => 'nullable|numeric|min:0',
            'tus_score'               => 'nullable|numeric|min:0|max:100',

            // Travel-based BDC calibration
            'bdc_distance_km'    => 'nullable|numeric|min:0',
            'bdc_time_min'       => 'nullable|numeric|min:0',

            // Findings array — all nullable so draft can be partial
            'findings'                          => 'nullable|array',
            'findings.*.task_question'          => 'nullable|string',
            'findings.*.labour_hours'           => 'nullable|numeric|min:0',
            'findings.*.priority'               => 'nullable|in:1,2,3',
            'findings.*.included_yn'            => 'nullable',
            'findings.*.category'               => 'nullable|string',
            'findings.*.notes'                  => 'nullable|string',
            'findings.*.property_id'            => 'nullable|exists:properties,id',

            // Per-finding materials
            'findings.*.materials'              => 'nullable|array',
            'findings.*.materials.*.material_name' => 'nullable|string',
            'findings.*.materials.*.quantity'   => 'nullable|numeric|min:0',
            'findings.*.materials.*.unit'       => 'nullable|string',
            'findings.*.materials.*.unit_cost'  => 'nullable|numeric|min:0',
            'findings.*.materials.*.line_total' => 'nullable|numeric|min:0',
            'findings.*.materials.*.notes'      => 'nullable|string',
            'findings.*.materials.*.property_id' => 'nullable|exists:properties,id',
        ]);

        $loadedHourlyRate = (float) (\App\Models\BDCSetting::getValue('loaded_hourly_rate', 165) ?? 165);

        // Update inspection PHAR input parameters (only non-null values)
        $pharParams = array_filter([
            'property_size_psf'      => $validated['property_size_psf'] ?? null,
            'bdc_visits_per_year'    => $validated['bdc_visits_per_year'] ?? null,
            'estimated_task_hours'   => $validated['estimated_task_hours'] ?? null,
            'minimum_required_hours' => $validated['minimum_required_hours'] ?? null,
            'labour_hourly_rate'     => $loadedHourlyRate,
            'tus_score'              => isset($validated['tus_score']) ? (float) $validated['tus_score'] : null,
        ], fn($v) => $v !== null);

        if (!empty($pharParams)) {
            $inspection->update($pharParams);
        }

        // ==== TRAVEL-BASED BDC CALIBRATION ====
        $travelDistanceKm  = isset($validated['bdc_distance_km'])  ? (float) $validated['bdc_distance_km']  : null;
        $travelTimeMinutes = isset($validated['bdc_time_min'])      ? (float) $validated['bdc_time_min']      : null;
        // Always read rates from BDC Settings — not user-editable on this form
        $ratePerKm     = (float) (\App\Models\BDCSetting::getValue('rate_per_km', 1.50) ?? 1.50);
        $ratePerMinute = (float) (\App\Models\BDCSetting::getValue('rate_per_minute', 1.65) ?? 1.65);
        // Visits/year already saved via bdc_visits_per_year in $pharParams above
        $visitsPerYear = $inspection->fresh()->bdc_visits_per_year;

        $travelUpdate = array_filter([
            'bdc_distance_km'     => $travelDistanceKm,
            'bdc_time_minutes'    => $travelTimeMinutes,
            'bdc_rate_per_km'     => $ratePerKm,
            'bdc_rate_per_minute' => $ratePerMinute,
        ], fn($v) => $v !== null);

        if (!empty($travelUpdate)) {
            $inspection->update($travelUpdate);
        }

        // ==== MERGE PHAR DATA BACK INTO inspection->findings JSON ====
        // Apply catalog defaults first so phar_labour_hours and phar_materials are populated
        // even when the findings table is display-only and no labour_hours inputs are submitted.
        $rawFindings      = $inspection->fresh()->findings ?? [];
        $currentFindings  = collect(PharCatalog::applyDefaultsToFindings($rawFindings));
        $submittedFindings = collect($validated['findings'] ?? []);

        $mergedFindings = $currentFindings->map(function ($finding, $index) use ($submittedFindings) {
            $phar = $submittedFindings->get($index, []);
            $pharMaterials = collect($phar['materials'] ?? [])
                ->filter(fn($m) => !empty($m['material_name']))
                ->values()
                ->all();

            return array_merge($finding, [
                'phar_labour_hours' => isset($phar['labour_hours']) ? (float) $phar['labour_hours'] : ($finding['phar_labour_hours'] ?? 0),
                'phar_category'     => $phar['category'] ?? ($finding['phar_category'] ?? null),
                'phar_included_yn'  => isset($phar['included_yn']) ? (bool) $phar['included_yn'] : ($finding['phar_included_yn'] ?? true),
                'phar_notes'        => $phar['notes'] ?? ($finding['phar_notes'] ?? ''),
                'phar_materials'    => !empty($pharMaterials) ? $pharMaterials : ($finding['phar_materials'] ?? []),
            ]);
        })->all();

        $inspection->findings = $mergedFindings;

        // Auto-derive visits from total finding labour hours: 1 visit = 11 working hours (7AM–6PM)
        $totalLabourHoursFromFindings = collect($mergedFindings)
            ->sum(fn(array $f) => (float) ($f['phar_labour_hours'] ?? 0));
        $derivedVisits = max(1, (int) ceil($totalLabourHoursFromFindings / 11));
        $inspection->bdc_visits_per_year      = $derivedVisits;
        $inspection->estimated_task_hours     = $totalLabourHoursFromFindings ?: ($validated['estimated_task_hours'] ?? $inspection->estimated_task_hours);

        $inspection->save();

        // Keep BDC/TRC in sync on every save (including draft) so the Final PHAR
        // Dashboard always matches the Live Cost Preview.
        $this->syncBdcAndTrc($inspection->fresh());

        // Draft: save and go back to Step 1 without running calculations
        if ($isDraft) {
            return redirect()->route('inspections.create', ['property_id' => $inspection->property_id])
                ->with('success', 'Step 2 progress saved. You can review or add more findings in Step 1 and return here at any time.');
        }

        // ==== FINAL SAVE: process into relational tables ====
        $inspection->pharFindings()->delete();
        $inspection->materials()->delete();

        foreach ($mergedFindings as $findingData) {
            if (empty($findingData['issue']) && empty($findingData['phar_labour_hours'])) {
                continue;
            }

            \App\Models\PHARFinding::create([
                'inspection_id' => $inspection->id,
                'property_id'   => $property->id,
                'task_question' => $findingData['task_question'] ?? ($findingData['issue'] ?? ''),
                'category'      => $findingData['phar_category'] ?? 'General',
                'priority'      => $findingData['priority'] ?? 3,
                'included_yn'   => $findingData['phar_included_yn'] ?? true,
                'labour_hours'  => $findingData['phar_labour_hours'] ?? 0,
                'material_cost' => 0,
                'notes'         => $findingData['phar_notes'] ?? null,
                'photo_ids'     => !empty($findingData['finding_photos']) ? $findingData['finding_photos'] : null,
            ]);

            // Per-finding materials → InspectionMaterial records
            foreach ($findingData['phar_materials'] ?? [] as $materialData) {
                if (empty($materialData['material_name'])) {
                    continue;
                }
                \App\Models\InspectionMaterial::create([
                    'inspection_id' => $inspection->id,
                    'property_id'   => $property->id,
                    'material_name' => $materialData['material_name'],
                    'description'   => $materialData['notes'] ?? null,
                    'quantity'      => $materialData['quantity'] ?? 1,
                    'unit'          => $materialData['unit'] ?? 'ea',
                    'unit_cost'     => $materialData['unit_cost'] ?? 0,
                    'line_total'    => $materialData['line_total'] ?? 0,
                    'notes'         => $materialData['notes'] ?? null,
                    'category'      => $materialData['category'] ?? ($findingData['phar_category'] ?? 'General'),
                ]);
            }
        }

        // ==== CALCULATE PRICING PREVIEW (BDC + FRLC + FMC + TIERS) ====
        // Re-compute ASI now that tus_score is persisted
        $inspection->refresh();
        $this->computeASI($inspection);
        $inspection->save();

        $bdcCalculator = new \App\Services\BDCCalculator();
        $calculator = new \App\Services\MergeBridgeCalculator($bdcCalculator);
        $results = $calculator->calculate($inspection);
        $calculator->saveToInspection($inspection, $results);

        // Do NOT mark as completed yet — send admin back to phar-data so they can
        // preview the report and contract draft before finalising.
        return redirect()->route('inspections.phar-data', $inspection->id)
            ->with('success', 'Pricing calculated successfully! Review the preview below, then click Complete Assessment when ready.');
    }

    /**
     * Finalise the assessment: mark as completed, sync schedule, generate invoice.
     * Called only when admin is satisfied with the pricing preview.
     */
    public function completeAssessment(Inspection $inspection)
    {
        if (($inspection->bdc_annual ?? 0) <= 0) {
            return redirect()->route('inspections.phar-data', $inspection->id)
                ->with('error', 'Please save and preview pricing first before completing the assessment.');
        }

        if ($inspection->status === 'completed') {
            return redirect()->route('inspections.show', $inspection->id)
                ->with('info', 'This assessment has already been completed.');
        }

        $inspection->update([
            'status'         => 'completed',
            'completed_date' => now(),
        ]);

        $inspection = $this->agreementScheduleService->refresh($inspection);
        $this->ensureClientInvoiceFromInspection($inspection->fresh(['property', 'project']));

        return redirect()->route('inspections.show', $inspection->id)
            ->with('success', 'Assessment completed successfully! The client has been notified.');
    }

    /**
     * Admin-facing finding photo upload (used from preview-report).
     */
    public function addFindingPhotos(Request $request, Inspection $inspection, int $findingIndex)
    {
        $validated = $request->validate([
            'finding_photos'   => 'required|array|min:1',
            'finding_photos.*' => 'required|image|max:10240',
        ]);

        $findings = is_array($inspection->findings)
            ? $inspection->findings
            : (json_decode($inspection->getRawOriginal('findings') ?? '[]', true) ?? []);

        if (!array_key_exists($findingIndex, $findings)) {
            return back()->with('error', 'Finding not found.');
        }

        $existingPhotos = is_array($findings[$findingIndex]['finding_photos'] ?? null)
            ? $findings[$findingIndex]['finding_photos']
            : [];

        $disk = config('filesystems.default', 's3');
        $newPaths = [];
        foreach ((array) ($validated['finding_photos'] ?? []) as $photo) {
            if ($photo && $photo->isValid()) {
                $newPaths[] = $photo->store('inspections/finding-photos', $disk);
            }
        }

        $findings[$findingIndex]['finding_photos'] = array_values(array_filter(array_merge($existingPhotos, $newPaths)));
        $inspection->findings = $findings;
        $inspection->save();

        return back()->with('success', count($newPaths) . ' photo(s) uploaded successfully.');
    }

    /**
     * Admin preview of the client-facing inspection report (read-only, no auth check on ownership).
     */
    public function previewReport(Inspection $inspection)
    {
        $inspection = $this->agreementScheduleService->refresh($inspection);

        $findings = \App\Models\PHARFinding::where('inspection_id', $inspection->id)
            ->orderBy('id')
            ->get();

        $materials = \App\Models\InspectionMaterial::where('inspection_id', $inspection->id)
            ->orderBy('id')
            ->get();

        $adminPreview = true;

        return view('client.inspections.report', compact('inspection', 'findings', 'materials', 'adminPreview'));
    }

    /**
     * Admin preview of the client-facing agreement/contract (read-only, no auth check on ownership).
     */
    public function previewAgreement(Inspection $inspection)
    {
        $inspection = $this->agreementScheduleService->refresh($inspection);
        $adminPreview = true;
        return view('client.inspections.agreement', compact('inspection', 'adminPreview'));
    }

    protected function ensureClientInvoiceFromInspection(Inspection $inspection): void
    {
        $this->inspectionInvoiceSyncService->syncProjectInvoice($inspection);
    }

    /**
     * Recalculate BDC and TRC from the inspection's stored travel/labour inputs
     * and persist the result. Used both on page-load reconciliation and on every
     * save so the Final PHAR Dashboard always matches the Live Cost Preview.
     *
     * @param  Inspection  $inspection     Should be a fresh() model when called after a save.
     * @param  bool        $onlyIfChanged  When true, skips the DB write if bdc_annual is already correct.
     */
    private function syncBdcAndTrc(Inspection $inspection, bool $onlyIfChanged = false): void
    {
        $calc = new \App\Services\BDCCalculator();

        if ($inspection->bdc_distance_km !== null && $inspection->bdc_time_minutes !== null) {
            $result = $calc->calculateWithParams([
                'travel_distance_km'  => (float) $inspection->bdc_distance_km,
                'travel_time_minutes' => (float) $inspection->bdc_time_minutes,
                'visits_per_year'     => (float) ($inspection->bdc_visits_per_year ?? 1),
                'rate_per_km'         => (float) ($inspection->bdc_rate_per_km    ?? 1.50),
                'rate_per_minute'     => (float) ($inspection->bdc_rate_per_minute ?? 1.65),
            ]);
        } else {
            $result = $calc->calculateWithParams([
                'visits_per_year' => (float) ($inspection->bdc_visits_per_year ?? 1),
                'hours_per_visit' => (float) ($inspection->estimated_task_hours ?? 5),
            ]);
        }

        $bdc    = $result['bdc_annual'];
        $visits = max(1.0, (float) ($inspection->bdc_visits_per_year ?? 1));
        $trc    = $bdc
            + (float) ($inspection->frlc_annual ?? 0)
            + (float) ($inspection->fmc_annual  ?? 0);

        if ($onlyIfChanged && round((float) $inspection->bdc_annual, 2) === round($bdc, 2)) {
            return;
        }

        $inspection->update([
            'bdc_annual'                  => $bdc,
            'bdc_per_visit'               => round($bdc / $visits, 2),
            'trc_annual'                  => $trc,
            'trc_per_visit'               => round($trc / $visits, 2),
            'trc_monthly'                 => $trc,
            'arp_monthly'                 => $trc,
            'scientific_final_monthly'    => $trc,
            'scientific_final_annual'     => $trc,
            'arp_equivalent_final'        => $trc,
            'base_package_price_snapshot' => $trc,
        ]);

        $inspection->refresh();
    }

}
