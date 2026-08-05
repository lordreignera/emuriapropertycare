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
use Illuminate\Validation\ValidationException;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\BDCCalculator;
use App\Services\AgreementScheduleService;
use App\Services\InspectionInvoiceSyncService;
use App\Models\FindingClientDecision;
use App\Models\BuildingComponent;
use App\Models\BuildingSubsystem;
use App\Models\BuildingSystem;
use App\Models\InspectionQuotation;
use App\Models\PHARFinding;
use App\Models\ServiceRequest;
use App\Models\TradePartner;
use App\Notifications\AssessmentCompletedNotification;
use App\Notifications\AssessmentScheduleUpdatedNotification;
use App\Notifications\QuotationSharedNotification;
use App\Notifications\ToolAssignmentReadyNotification;
use App\Notifications\WorkSchedulePublishedNotification;
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
        $diagnosedReportStatuses = ['findings_captured', 'findings_shared'];
        $pricingQueueStatuses = ['client_committed', 'estimation_in_progress', 'estimation_completed'];
        $defaultDiagnosisStatuses = array_merge(['scheduled', 'in_progress'], $diagnosedReportStatuses, $pricingQueueStatuses);
        $diagnosisLifecycleStatuses = array_merge($defaultDiagnosisStatuses, ['completed']);

        $latestByProperty = function (array $statuses, ?callable $callback = null) use ($user) {
            $query = Inspection::query()
                ->selectRaw('MAX(id) as id')
                ->whereNotNull('property_id')
                ->whereIn('status', $statuses);

            if ($callback) {
                $callback($query);
            }

            if ($user->hasRole('Inspector')) {
                $query->where('inspector_id', $user->id);
            }

            return $query->groupBy('property_id');
        };

        if ($user->hasRole('Inspector')) {
            $countsBaseQuery->where('inspector_id', $user->id);
        }

        $scheduledCount = (clone $countsBaseQuery)
            ->where('status', 'scheduled')
            ->whereHas('property')
            ->whereDoesntHave('property.inspections', function ($q) {
                $q->where('status', 'completed');
            })
            ->whereIn('id', $latestByProperty($diagnosisLifecycleStatuses))
            ->count();

        $inProgressCount = (clone $countsBaseQuery)
            ->where('status', 'in_progress')
            ->whereIn('id', $latestByProperty($diagnosisLifecycleStatuses))
            ->count();

        // ETOGO workflow queues
        $awaitingQuotationCount = (clone $countsBaseQuery)
            ->whereIn('status', $diagnosedReportStatuses)
            ->whereIn('id', $latestByProperty($diagnosisLifecycleStatuses))
            ->count();

        // Findings shared with client, waiting on the client to commit (informational)
        $awaitingClientCount = (clone $countsBaseQuery)
            ->where('status', 'findings_shared')
            ->whereIn('id', $latestByProperty($diagnosisLifecycleStatuses))
            ->count();

        // Client has committed findings — ready for admin to attach pricing (action needed)
        $awaitingEstimationCount = (clone $countsBaseQuery)
            ->whereIn('status', $pricingQueueStatuses)
            ->whereNotNull('client_committed_at')
            ->whereIn('id', $latestByProperty($diagnosisLifecycleStatuses))
            ->count();

        $completedCount = (clone $countsBaseQuery)
            ->where('status', 'completed')
            ->whereIn('id', $latestByProperty($diagnosisLifecycleStatuses))
            ->count();

        $inspectionListQuery = static fn () => Inspection::with(['property.user', 'property.projectManager', 'inspector', 'assignedBy', 'project.manager', 'toolAssignments', 'activeQuotation', 'activeMatterportModel', 'activeSpatialModels'])
            ->whereNotNull('property_id');
        
        // Base query for inspections
        $query = $inspectionListQuery();

        // Filter by status if provided
        if ($request->filled('status')) {
            if ($request->status === 'scheduled') {
                // Show scheduled diagnosis/site-visit records whether invoice is pending or paid.
                $query->where('status', 'scheduled')
                      ->whereHas('property')
                      ->whereDoesntHave('property.inspections', function ($q) {
                          $q->where('status', 'completed');
                      })
                      ->whereIn('id', $latestByProperty($diagnosisLifecycleStatuses));
            } elseif ($request->status === 'in_progress') {
                $query->where('status', 'in_progress')
                    ->whereIn('id', $latestByProperty($diagnosisLifecycleStatuses));
            } elseif ($request->status === 'completed') {
                $query->where('status', 'completed')
                    ->whereIn('id', $latestByProperty($diagnosisLifecycleStatuses));
            }
        } else {
            // By default, show all in-flight inspections (scheduled, in_progress, and the
            // ETOGO diagnosis/estimation phases) so none silently disappear from the list.
            $query->whereIn('status', $defaultDiagnosisStatuses)
                ->whereIn('id', $latestByProperty($diagnosisLifecycleStatuses));
        }

        // Ready-to-countersign view: client signed + paid + tools assigned + schedule set, but ETOGO not yet countersigned
        if ($request->get('view') === 'needs-schedule') {
            $query = $inspectionListQuery()
                ->whereNotNull('client_signature')
                ->where('work_payment_status', 'paid')
                ->whereNull('ETOGO_signed_at')
                ->whereHas('toolAssignments', function ($q) {
                    $q->whereNull('returned_at')->where('quantity', '>', 0);
                })
                ->where(function ($q) {
                    $q->whereNotNull('work_schedule')->where('work_schedule', '<>', '[]');
                });

            if ($user->hasRole('Inspector')) {
                $query->where('inspector_id', $user->id);
            }
        }

        // Pre-assessment view: quotation shared and waiting for client approval/response
        if ($request->get('view') === 'awaiting-quotation') {
            $query = $inspectionListQuery()
                ->whereIn('status', $diagnosedReportStatuses)
                ->whereIn('id', $latestByProperty($diagnosisLifecycleStatuses));

            if ($user->hasRole('Inspector')) {
                $query->where('inspector_id', $user->id);
            }
        }

        // ETOGO Stage D queue: client committed to findings — ready for admin to attach pricing
        if ($request->get('view') === 'awaiting-estimation') {
            $query = $inspectionListQuery()
                ->whereIn('status', $pricingQueueStatuses)
                ->whereNotNull('client_committed_at')
                ->whereIn('id', $latestByProperty($diagnosisLifecycleStatuses));

            if ($user->hasRole('Inspector')) {
                $query->where('inspector_id', $user->id);
            }
        }

        // Pre-sign setup queue: client signed + paid, but tools/schedule setup is incomplete
        if ($request->get('view') === 'pending-ETOGO') {
            $query = $inspectionListQuery()
                ->whereNotNull('client_signature')
                ->where('work_payment_status', 'paid')
                ->whereNull('ETOGO_signed_at')
                ->where(function ($q) {
                    $q->whereDoesntHave('toolAssignments', function ($tq) {
                        $tq->whereNull('returned_at')->where('quantity', '>', 0);
                    })
                    ->orWhereNull('work_schedule')
                    ->orWhere('work_schedule', '[]');
                });

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

        return view('admin.inspections.index', compact('inspections', 'scheduledCount', 'inProgressCount', 'completedCount', 'awaitingQuotationCount', 'awaitingClientCount', 'awaitingEstimationCount', 'inspectors', 'projectManagers', 'technicians'));
    }

    /**
     * Show the form for creating a new inspection.
     */
    public function create(Request $request)
    {
        $propertyId = $request->get('property_id');
        $serviceRequestId = $request->integer('service_request_id') ?: null;

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

        // Get the current editable diagnosis for this property. Pending drafts
        // are valid here; opening the form must not create another duplicate.
        $inspection = Inspection::where('property_id', $property->id)
            ->whereIn('status', ['in_progress', 'scheduled', 'findings_captured'])
            ->latest('id')
            ->first();

        if (!$inspection) {
            $inspection = Inspection::where('property_id', $property->id)
                ->where('inspection_fee_status', 'paid')
                ->whereIn('status', ['completed', 'findings_captured', 'findings_shared', 'client_committed', 'estimation_in_progress', 'estimation_completed'])
                ->latest('id')
                ->first();
        }

        // If the assessment has been finalised (locked), send staff to the PHAR
        // assessment report instead of the editable capture form. Reopen from the
        // report first if the findings genuinely need to change.
        if ($inspection && $inspection->assessment_finalised_at && !$serviceRequestId) {
            return redirect()->route('inspections.assessment-report', $inspection->id)
                ->with('info', 'This diagnosis is finalised. Reopen it from the report if you need to edit the findings.');
        }

        $systems = collect();
        if (Schema::hasTable('building_systems') && Schema::hasTable('building_subsystems')) {
            $systems = BuildingSystem::with(['subsystems' => function ($query) {
                $query->where('is_active', true)->orderBy('sort_order')->orderBy('name');
            }, 'subsystems.components' => function ($query) {
                $query->where('is_active', true)->orderBy('sort_order')->orderBy('name');
            }])
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();
        }

        $serviceRequest = null;
        $seededSystemFindings = [];
        $seededFindingsSource = null;
        $hasExistingFindings = !empty($inspection?->findings) && is_array($inspection?->findings);
        if ($serviceRequestId) {
            $serviceRequest = ServiceRequest::query()
                ->where('id', $serviceRequestId)
                ->where('property_id', $property->id)
                ->first();

            if (!$serviceRequest) {
                return redirect()->route('inspections.index')
                    ->with('error', 'Service request not found for this property.');
            }

            if (!$hasExistingFindings && $systems->isNotEmpty()) {
                $defaultSystemId = (int) ($systems->first()->id ?? 0);
                $urgencyToSeverity = [
                    'critical' => 'critical',
                    'high' => 'high',
                    'medium' => 'medium',
                    'low' => 'low',
                ];

                $seededSeverity = $urgencyToSeverity[(string) ($serviceRequest->urgency ?? 'medium')] ?? 'medium';
                $seededNotesPrefix = 'Seeded from client service request ' . $serviceRequest->request_number . '.';

                $rawItems = collect($serviceRequest->items_reported ?? []);
                if ($rawItems->isEmpty()) {
                    $rawItems = collect([['issue' => (string) ($serviceRequest->title ?? 'Reported issue')]]);
                }

                $seededSystemFindings = $rawItems
                    ->map(function ($item) use ($defaultSystemId, $seededSeverity, $seededNotesPrefix) {
                        $issue = is_array($item)
                            ? trim((string) ($item['issue'] ?? ''))
                            : trim((string) $item);

                        if ($issue === '') {
                            return null;
                        }

                        $location = is_array($item)
                            ? trim((string) ($item['location'] ?? ''))
                            : '';

                        return [
                            'building_system_id' => $defaultSystemId,
                            'building_subsystem_id' => null,
                            'building_component_id' => null,
                            'issue' => $issue,
                            'location' => $location,
                            'spot' => '',
                            'severity' => $seededSeverity,
                            'notes' => $seededNotesPrefix,
                            'recommendations' => [],
                            'risk_impact' => '',
                            'phar_labour_hours' => 0,
                            'phar_category' => '',
                            'phar_included_yn' => true,
                            'phar_notes' => '',
                            'materials' => [],
                        ];
                    })
                    ->filter()
                    ->values()
                    ->all();
                $seededFindingsSource = !empty($seededSystemFindings) ? 'service_request' : null;
            }
        }

        if (empty($seededSystemFindings) && !$hasExistingFindings && $systems->isNotEmpty()) {
            $seededSystemFindings = $this->seedFindingsFromPropertyKnownIssues($property, $systems);
            $seededFindingsSource = !empty($seededSystemFindings) ? 'property_known_issues' : null;
        }

        $savedSystemFindings = $hasExistingFindings
            ? array_values($inspection->findings ?? [])
            : [];
        $initialSystemFindings = !empty($savedSystemFindings)
            ? $savedSystemFindings
            : $seededSystemFindings;
        $initialSystemFindings = $this->sanitizeDiagnosisFindingsForForm($initialSystemFindings);

        if ($inspection) {
            foreach (['inspector_notes', 'recommendations', 'risk_summary', 'summary'] as $field) {
                $inspection->setAttribute($field, $this->sanitizeDiagnosisText($inspection->{$field} ?? ''));
            }
        }

        $dbMaterialSettings = \App\Models\FmcMaterialSetting::active()->get([
            'material_name', 'default_unit', 'default_unit_cost', 'hst_rate', 'pst_rate', 'building_system_id', 'building_subsystem_id',
        ])->map(static function ($row) {
            $base = (float) ($row->default_unit_cost ?? 0);
            $hst  = (float) ($row->hst_rate ?? 5.00);
            $pst  = (float) ($row->pst_rate ?? 7.00);

            $row->taxed_unit_cost = round($base * (1 + $hst / 100) * (1 + $pst / 100), 2);
            return $row;
        });
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
                'taxed_unit_cost'   => round(
                    (float) ($row['default_unit_cost'] ?? 0)
                    * (1 + (float) ($row['hst_rate'] ?? 5.00) / 100)
                    * (1 + (float) ($row['pst_rate'] ?? 7.00) / 100),
                    2
                ),
                'building_system_id'         => null,
                'building_subsystem_id'      => null,
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

        $activeTradePartners = TradePartner::query()
            ->where('status', TradePartner::STATUS_ACTIVE)
            ->orderBy('company_name')
            ->get(['id', 'partner_number', 'trade_application_id', 'company_name', 'system_ids', 'subsystem_ids', 'agreed_subsystem_pricing'])
            ->map(function (TradePartner $partner) {
                return [
                    'id' => $partner->id,
                    'partner_number' => $partner->partner_number,
                    'trade_application_id' => $partner->trade_application_id,
                    'company_name' => $partner->company_name,
                    'system_ids' => array_map('intval', $partner->system_ids ?? []),
                    'subsystem_ids' => array_map('intval', $partner->subsystem_ids ?? []),
                    'pricing' => $partner->agreed_subsystem_pricing ?? [],
                ];
            })
            ->values();

        return view('admin.inspections.form-cpi', compact(
            'property',
            'inspection',
            'systems',
            'materialUnits',
            'fmcMaterialSettings',
            'pharCategories',
            'serviceRequest',
            'seededSystemFindings',
            'seededFindingsSource',
            'initialSystemFindings',
            'activeTradePartners'
        ));
    }

    public function updateAssessmentSchedule(Request $request, Inspection $inspection)
    {
        $validated = $request->validate([
            'scheduled_date' => 'required|date',
        ]);

        $scheduledAt = Carbon::parse($validated['scheduled_date']);

        $inspection->update([
            'scheduled_date' => $scheduledAt,
        ]);

        if ($inspection->property) {
            $inspection->property->update([
                'inspection_scheduled_at' => $scheduledAt,
            ]);
        }

        $clientUser = $inspection->property?->user;
        if ($clientUser) {
            $clientUser->notify(new AssessmentScheduleUpdatedNotification(
                inspectionId: (int) $inspection->id,
                propertyId: (int) ($inspection->property_id ?? 0),
                propertyName: (string) ($inspection->property?->property_name ?? 'your property'),
                scheduledAt: $scheduledAt->format('M d, Y h:i A'),
                scheduledByName: (string) (Auth::user()?->name ?? 'Admin')
            ));
        }

        return back()->with('success', 'Diagnosis schedule updated and client has been notified.');
    }

    /**
     * Store a newly created inspection in storage.
     */
    public function autosaveDraft(Request $request)
    {
        $validated = $request->validate([
            'property_id' => 'required|exists:properties,id',
            'service_request_id' => 'nullable|exists:service_requests,id',
            'inspection_date' => 'nullable|date',
            'inspector_id' => 'nullable|exists:users,id',
            'weather_conditions' => 'nullable|string|max:120',
            'summary' => 'nullable|string',
            'overall_condition' => 'nullable|in:excellent,good,fair,poor,critical',
            'inspector_notes' => 'nullable|string',
            'recommendations' => 'nullable|string',
            'risk_summary' => 'nullable|string',
            'system_findings' => 'nullable|array',
            'system_findings.*.building_system_id' => 'nullable|exists:building_systems,id',
            'system_findings.*.building_subsystem_id' => 'nullable|exists:building_subsystems,id',
            'system_findings.*.building_component_id' => 'nullable|exists:building_components,id',
            'system_findings.*.affected_areas' => 'nullable|array',
            'system_findings.*.affected_areas.*.building_system_id' => 'nullable|exists:building_systems,id',
            'system_findings.*.affected_areas.*.building_subsystem_id' => 'nullable|exists:building_subsystems,id',
            'system_findings.*.affected_areas.*.building_component_id' => 'nullable|exists:building_components,id',
            'system_findings.*.affected_areas.*.location' => 'nullable|string|max:255',
            'system_findings.*.affected_areas.*.impact_description' => 'nullable|string|max:5000',
            'system_findings.*.affected_areas.*.severity' => 'nullable|in:low,medium,moderate,high,critical,noi_protection,urgent,health_safety_threatening,value_depreciation,non_urgent',
            'system_findings.*.issue' => 'nullable|string|max:255',
            'system_findings.*.issue_description' => 'nullable|string|max:5000',
            'system_findings.*.location' => 'nullable|string|max:255',
            'system_findings.*.spot' => 'nullable|string|max:255',
            'system_findings.*.severity' => 'nullable|in:low,medium,high,critical,noi_protection,urgent,health_safety_threatening,value_depreciation,non_urgent',
            'system_findings.*.notes' => 'nullable|string',
            'system_findings.*.recommendations' => 'nullable',
            'system_findings.*.recommendations.*' => 'nullable|string|max:500',
            'system_findings.*.recommendation_details' => 'nullable|string|max:5000',
            'system_findings.*.risk_impact' => 'nullable|string|max:1000',
            'system_findings.*.phar_labour_hours' => 'nullable|numeric|min:0',
            'system_findings.*.phar_category' => 'nullable|string|max:255',
            'system_findings.*.phar_included_yn' => 'nullable|boolean',
            'system_findings.*.phar_notes' => 'nullable|string',
            'system_findings.*.fulfillment_type' => 'nullable|in:ETOGO_team,trade_partner,decide_later',
            'system_findings.*.trade_application_id' => 'nullable|exists:trade_applications,id',
            'system_findings.*.trade_quantity' => 'nullable|numeric|min:0',
            'system_findings.*.trade_unit' => 'nullable|string|max:50',
            'system_findings.*.trade_scope_area' => 'nullable|string|max:255',
            'system_findings.*.trade_duration_hours' => 'nullable|numeric|min:0',
            'system_findings.*.trade_materials_included' => 'nullable|boolean',
            'system_findings.*.trade_notes' => 'nullable|string|max:1000',
            'system_findings.*.materials' => 'nullable|array',
            'system_findings.*.materials.*.material_name' => 'nullable|string|max:255',
            'system_findings.*.materials.*.quantity' => 'nullable|numeric|min:0',
            'system_findings.*.materials.*.unit' => 'nullable|string|max:50',
            'system_findings.*.materials.*.unit_cost' => 'nullable|numeric|min:0',
            'system_findings.*.materials.*.line_total' => 'nullable|numeric|min:0',
            'system_findings.*.materials.*.notes' => 'nullable|string|max:500',
            'system_findings.*.materials.*.property_id' => 'nullable|integer',
        ]);
        $this->validateBuildingTaxonomySelections($request);

        $property = Property::findOrFail((int) $validated['property_id']);

        $project = \App\Models\Project::firstOrCreate(
            ['property_id' => $property->id],
            [
                'title' => 'Property Inspection - ' . $property->property_name,
                'description' => 'CPI Inspection for ' . $property->property_name,
                'status' => 'pending',
                'user_id' => $property->user_id,
                'managed_by' => $property->project_manager_id,
                'created_by' => Auth::id(),
                'project_number' => 'PRJ-' . strtoupper(Str::random(8)),
            ]
        );

        $inspection = Inspection::where('property_id', $property->id)
            ->whereIn('status', ['in_progress', 'scheduled', 'findings_captured'])
            ->latest('id')
            ->first();

        if (!$inspection) {
            $inspection = new Inspection();
            $inspection->property_id = $property->id;
            $inspection->project_id = $project->id;
            $inspection->inspector_id = $validated['inspector_id'] ?? Auth::id();
            $inspection->assigned_by = $property->project_manager_id ?? Auth::id();
        } else {
            $inspection->project_id = $inspection->project_id ?: $project->id;
            $inspection->inspector_id = $validated['inspector_id'] ?? ($inspection->inspector_id ?: Auth::id());
            $inspection->assigned_by = $inspection->assigned_by ?: ($property->project_manager_id ?? Auth::id());
        }

        $inspection->scheduled_date = $validated['inspection_date']
            ?? $inspection->scheduled_date
            ?? now();
        if ($inspection->status !== 'findings_captured') {
            $inspection->status = 'in_progress';
        }
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
        $inspection->residential_units_snapshot = (int) ($property->number_of_units ?: $property->residential_units ?: 0);
        $inspection->commercial_sqft_snapshot = $property->square_footage_interior;
        $inspection->mixed_use_weight_snapshot = $property->mixed_use_commercial_weight;

        $systemFindings = collect($request->input('system_findings', []));
        $systemNameMap = collect();
        $systemSlugMap = collect();
        $subsystemNameMap = collect();
        $componentNameMap = collect();

        if (Schema::hasTable('building_systems') && Schema::hasTable('building_subsystems') && $systemFindings->isNotEmpty()) {
            $systemIds = $systemFindings->pluck('building_system_id')->filter()->unique()->values();
            $subsystemIds = $systemFindings->pluck('building_subsystem_id')->filter()->unique()->values();
            $componentIds = $systemFindings->pluck('building_component_id')->filter()->unique()->values();
            $systemNameMap = BuildingSystem::whereIn('id', $systemIds)->pluck('name', 'id');
            $systemSlugMap = BuildingSystem::whereIn('id', $systemIds)->pluck('slug', 'id');
            $subsystemNameMap = \App\Models\BuildingSubsystem::whereIn('id', $subsystemIds)->pluck('name', 'id');
            $componentNameMap = BuildingComponent::whereIn('id', $componentIds)->pluck('name', 'id');
        }

        $severityAliases = [
            'urgent'                    => 'critical',
            'health_safety_threatening' => 'high',
            'value_depreciation'        => 'medium',
            'non_urgent'                => 'low',
        ];

        $allowedSeverities = ['critical', 'high', 'noi_protection', 'medium', 'low'];

        $savedInspectionPhotos = [];
        if ($inspection) {
            foreach ((array) ($inspection->findings ?? []) as $fi => $findingRow) {
                if (!empty($findingRow['finding_photos'])) {
                    $savedInspectionPhotos[$fi] = array_values(array_filter((array) $findingRow['finding_photos']));
                }
            }
        }

        $normalizedFindings = $systemFindings
            ->map(function ($finding, $idx) use ($systemNameMap, $systemSlugMap, $subsystemNameMap, $componentNameMap, $severityAliases, $allowedSeverities, $savedInspectionPhotos) {
                $systemId = $finding['building_system_id'] ?? null;
                $subsystemId = $finding['building_subsystem_id'] ?? null;
                $componentId = $finding['building_component_id'] ?? null;
                $rawSeverity = (string) ($finding['severity'] ?? 'low');
                $normalizedSeverity = $severityAliases[$rawSeverity] ?? $rawSeverity;

                return [
                    'building_system_id' => $systemId,
                    'system' => $systemNameMap[$systemId] ?? null,
                    'system_slug' => $systemSlugMap[$systemId] ?? null,
                    'building_subsystem_id' => $subsystemId,
                    'building_component_id' => $componentId,
                    'subsystem' => $subsystemNameMap[$subsystemId] ?? null,
                    'component' => $componentNameMap[$componentId] ?? null,
                    'issue' => trim((string) ($finding['issue'] ?? '')),
                    'issue_description' => trim((string) ($finding['issue_description'] ?? '')),
                    'location' => trim((string) ($finding['location'] ?? '')),
                    'spot' => trim((string) ($finding['spot'] ?? '')),
                    'severity' => in_array($normalizedSeverity, $allowedSeverities, true) ? $normalizedSeverity : 'low',
                    'notes' => $this->sanitizeDiagnosisText($finding['notes'] ?? ''),
                    'recommendations' => collect(is_array($finding['recommendations'] ?? null)
                        ? ($finding['recommendations'] ?? [])
                        : preg_split('/\r\n|\r|\n|\|/', (string) ($finding['recommendations'] ?? '')))
                        ->map(fn ($item) => $this->sanitizeDiagnosisText($item))
                        ->filter()
                        ->values()
                        ->all(),
                    'recommendation_details' => $this->sanitizeDiagnosisText($finding['recommendation_details'] ?? ''),
                    'affected_areas' => $this->normalizeAffectedAreas((array) ($finding['affected_areas'] ?? []), $severityAliases),
                    'type' => $systemSlugMap[$systemId] ?? null,
                    'finding_photos' => $savedInspectionPhotos[$idx] ?? [],
                    'risk_impact' => trim((string) ($finding['risk_impact'] ?? '')),
                    'phar_labour_hours' => (float) ($finding['phar_labour_hours'] ?? 0),
                    'phar_category' => trim((string) ($finding['phar_category'] ?? '')),
                    'phar_included_yn' => isset($finding['phar_included_yn']) ? (bool) $finding['phar_included_yn'] : true,
                    'phar_notes' => trim((string) ($finding['phar_notes'] ?? '')),
                    'fulfillment_type' => in_array(($finding['fulfillment_type'] ?? ''), ['ETOGO_team', 'trade_partner', 'decide_later'], true)
                        ? $finding['fulfillment_type']
                        : 'decide_later',
                    'trade_application_id' => ($finding['fulfillment_type'] ?? '') === 'trade_partner' && !empty($finding['trade_application_id']) ? (int) $finding['trade_application_id'] : null,
                    'trade_quantity' => ($finding['fulfillment_type'] ?? '') === 'trade_partner' ? (float) ($finding['trade_quantity'] ?? 1) : 1,
                    'trade_unit' => ($finding['fulfillment_type'] ?? '') === 'trade_partner' ? trim((string) ($finding['trade_unit'] ?? '')) : '',
                    'trade_scope_area' => ($finding['fulfillment_type'] ?? '') === 'trade_partner' ? trim((string) ($finding['trade_scope_area'] ?? '')) : '',
                    'trade_duration_hours' => ($finding['fulfillment_type'] ?? '') === 'trade_partner' && isset($finding['trade_duration_hours']) ? (float) $finding['trade_duration_hours'] : null,
                    'trade_materials_included' => ($finding['fulfillment_type'] ?? '') === 'trade_partner' ? (bool) ($finding['trade_materials_included'] ?? false) : false,
                    'trade_notes' => ($finding['fulfillment_type'] ?? '') === 'trade_partner' ? trim((string) ($finding['trade_notes'] ?? '')) : '',
                    'phar_materials' => collect($finding['materials'] ?? [])
                        ->filter(fn($material) => !empty($material['material_name']))
                        ->map(fn($material) => [
                            'material_name' => trim((string) ($material['material_name'] ?? '')),
                            'quantity' => (float) ($material['quantity'] ?? 1),
                            'unit' => (string) ($material['unit'] ?? 'ea'),
                            'unit_cost' => (float) ($material['unit_cost'] ?? 0),
                            'line_total' => (float) ($material['line_total'] ?? 0),
                            'notes' => trim((string) ($material['notes'] ?? '')),
                            'property_id' => (int) ($material['property_id'] ?? 0),
                        ])
                        ->values()
                        ->all(),
                ];
            })
            ->filter(function ($finding) {
                return $finding['building_system_id']
                    && ($finding['issue'] !== ''
                        || $finding['issue_description'] !== ''
                        || $finding['notes'] !== ''
                        || !empty($finding['recommendations'])
                        || $finding['recommendation_details'] !== '');
            })
            ->values()
            ->all();

        $inspection->summary = $validated['summary'] ?? ('Inspection for ' . $property->property_name);
        $inspection->overall_condition = $validated['overall_condition'] ?? null;
        $inspection->inspector_notes = $validated['inspector_notes'] ?? null;
        $inspection->recommendations = $this->sanitizeDiagnosisText($validated['recommendations'] ?? '');
        $inspection->risk_summary = $validated['risk_summary'] ?? null;
        $inspection->findings = $normalizedFindings;

        $inspection->save();
        if (($property->status ?? null) !== 'archived') {
            $property->update(['status' => 'in_assessment']);
        }

        return response()->json([
            'ok' => true,
            'inspection_id' => $inspection->id,
            'saved_at' => now()->toIso8601String(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'property_id' => 'required|exists:properties,id',
            'service_request_id' => 'nullable|exists:service_requests,id',
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
            'system_findings.*.building_system_id' => 'nullable|exists:building_systems,id',
            'system_findings.*.building_subsystem_id' => 'nullable|exists:building_subsystems,id',
            'system_findings.*.building_component_id' => 'nullable|exists:building_components,id',
            'system_findings.*.affected_areas' => 'nullable|array',
            'system_findings.*.affected_areas.*.building_system_id' => 'nullable|exists:building_systems,id',
            'system_findings.*.affected_areas.*.building_subsystem_id' => 'nullable|exists:building_subsystems,id',
            'system_findings.*.affected_areas.*.building_component_id' => 'nullable|exists:building_components,id',
            'system_findings.*.affected_areas.*.location' => 'nullable|string|max:255',
            'system_findings.*.affected_areas.*.impact_description' => 'nullable|string|max:5000',
            'system_findings.*.affected_areas.*.severity' => 'nullable|in:low,medium,moderate,high,critical,noi_protection,urgent,health_safety_threatening,value_depreciation,non_urgent',
            'system_findings.*.issue' => 'nullable|string|max:255',
            'system_findings.*.issue_description' => 'nullable|string|max:5000',
            'system_findings.*.location' => 'nullable|string|max:255',
            'system_findings.*.spot' => 'nullable|string|max:255',
            'system_findings.*.severity' => 'nullable|in:low,medium,high,critical,noi_protection,urgent,health_safety_threatening,value_depreciation,non_urgent',
            'system_findings.*.notes' => 'nullable|string',
            'system_findings.*.recommendations' => 'nullable',
            'system_findings.*.recommendations.*' => 'nullable|string|max:500',
            'system_findings.*.recommendation_details' => 'nullable|string|max:5000',
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
            'system_findings.*.fulfillment_type'                => 'nullable|in:ETOGO_team,trade_partner,decide_later',
            'system_findings.*.trade_application_id'            => 'nullable|exists:trade_applications,id',
            'system_findings.*.trade_quantity'                  => 'nullable|numeric|min:0',
            'system_findings.*.trade_unit'                      => 'nullable|string|max:50',
            'system_findings.*.trade_scope_area'                => 'nullable|string|max:255',
            'system_findings.*.trade_duration_hours'            => 'nullable|numeric|min:0',
            'system_findings.*.trade_materials_included'        => 'nullable|boolean',
            'system_findings.*.trade_notes'                     => 'nullable|string|max:1000',
        ]);
        $this->validateBuildingTaxonomySelections($request);

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

        // Reuse the current editable diagnosis for this property. Do not key this
        // only to paid inspections; pending diagnosis drafts must also be updated
        // instead of creating a new row each time the inspector saves/previews.
        $inspection = Inspection::where('property_id', $property->id)
            ->whereIn('status', ['in_progress', 'scheduled', 'findings_captured'])
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
        $inspection->residential_units_snapshot = (int) ($property->number_of_units ?: $property->residential_units ?: 0);
        $inspection->commercial_sqft_snapshot = $property->square_footage_interior;
        $inspection->mixed_use_weight_snapshot = $property->mixed_use_commercial_weight;

        // Persist only general page-1 inspection snapshot fields.
        $inspection->property_year_built = $request->input('property_year_built');

        $systemFindings = collect($request->input('system_findings', []));
        $systemNameMap = collect();
        $systemSlugMap = collect();
        $subsystemNameMap = collect();
        $componentNameMap = collect();

        if (Schema::hasTable('building_systems') && Schema::hasTable('building_subsystems') && $systemFindings->isNotEmpty()) {
            $systemIds = $systemFindings->pluck('building_system_id')->filter()->unique()->values();
            $subsystemIds = $systemFindings->pluck('building_subsystem_id')->filter()->unique()->values();
            $componentIds = $systemFindings->pluck('building_component_id')->filter()->unique()->values();
            $systemNameMap = BuildingSystem::whereIn('id', $systemIds)->pluck('name', 'id');
            $systemSlugMap = BuildingSystem::whereIn('id', $systemIds)->pluck('slug', 'id');
            $subsystemNameMap = \App\Models\BuildingSubsystem::whereIn('id', $subsystemIds)->pluck('name', 'id');
            $componentNameMap = BuildingComponent::whereIn('id', $componentIds)->pluck('name', 'id');
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
            ->map(function ($finding, $idx) use ($systemNameMap, $systemSlugMap, $subsystemNameMap, $componentNameMap, $severityAliases, $allowedSeverities, $findingPhotoPaths, $preservedPhotoPaths, $savedInspectionPhotos) {
                $systemId = $finding['building_system_id'] ?? null;
                $subsystemId = $finding['building_subsystem_id'] ?? null;
                $componentId = $finding['building_component_id'] ?? null;
                $rawSeverity = (string) ($finding['severity'] ?? 'low');
                $normalizedSeverity = $severityAliases[$rawSeverity] ?? $rawSeverity;

                return [
                    'building_system_id' => $systemId,
                    'system' => $systemNameMap[$systemId] ?? null,
                    'system_slug' => $systemSlugMap[$systemId] ?? null,
                    'building_subsystem_id' => $subsystemId,
                    'building_component_id' => $componentId,
                    'subsystem' => $subsystemNameMap[$subsystemId] ?? null,
                    'component' => $componentNameMap[$componentId] ?? null,
                    'issue' => trim((string) ($finding['issue'] ?? '')),
                    'issue_description' => trim((string) ($finding['issue_description'] ?? '')),
                    'location' => trim((string) ($finding['location'] ?? '')),
                    'spot' => trim((string) ($finding['spot'] ?? '')),
                    'severity' => in_array($normalizedSeverity, $allowedSeverities, true) ? $normalizedSeverity : 'low',
                    'notes' => $this->sanitizeDiagnosisText($finding['notes'] ?? ''),
                    'recommendations' => collect(is_array($finding['recommendations'] ?? null)
                        ? ($finding['recommendations'] ?? [])
                        : preg_split('/\r\n|\r|\n|\|/', (string) ($finding['recommendations'] ?? '')))
                        ->map(fn ($item) => $this->sanitizeDiagnosisText($item))
                        ->filter()
                        ->values()
                        ->all(),
                    'recommendation_details' => $this->sanitizeDiagnosisText($finding['recommendation_details'] ?? ''),
                    'affected_areas' => $this->normalizeAffectedAreas((array) ($finding['affected_areas'] ?? []), $severityAliases),
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
                    'fulfillment_type' => in_array(($finding['fulfillment_type'] ?? ''), ['ETOGO_team', 'trade_partner', 'decide_later'], true)
                        ? $finding['fulfillment_type']
                        : 'decide_later',
                    'trade_application_id' => ($finding['fulfillment_type'] ?? '') === 'trade_partner' && !empty($finding['trade_application_id']) ? (int) $finding['trade_application_id'] : null,
                    'trade_quantity' => ($finding['fulfillment_type'] ?? '') === 'trade_partner' ? (float) ($finding['trade_quantity'] ?? 1) : 1,
                    'trade_unit' => ($finding['fulfillment_type'] ?? '') === 'trade_partner' ? trim((string) ($finding['trade_unit'] ?? '')) : '',
                    'trade_scope_area' => ($finding['fulfillment_type'] ?? '') === 'trade_partner' ? trim((string) ($finding['trade_scope_area'] ?? '')) : '',
                    'trade_duration_hours' => ($finding['fulfillment_type'] ?? '') === 'trade_partner' && isset($finding['trade_duration_hours']) ? (float) $finding['trade_duration_hours'] : null,
                    'trade_materials_included' => ($finding['fulfillment_type'] ?? '') === 'trade_partner' ? (bool) ($finding['trade_materials_included'] ?? false) : false,
                    'trade_notes' => ($finding['fulfillment_type'] ?? '') === 'trade_partner' ? trim((string) ($finding['trade_notes'] ?? '')) : '',
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
                return $finding['building_system_id']
                    && ($finding['issue'] !== ''
                        || $finding['issue_description'] !== ''
                        || $finding['notes'] !== ''
                        || !empty($finding['recommendations'])
                        || $finding['recommendation_details'] !== '');
            })
            ->values()
            ->all();

        // Store overall assessment
        $inspection->summary = $validated['summary'] ?? ('Inspection for ' . $property->property_name);
        $inspection->overall_condition = $validated['overall_condition'] ?? null;
        $inspection->inspector_notes = $validated['inspector_notes'] ?? null;
        $inspection->recommendations = $this->sanitizeDiagnosisText($validated['recommendations'] ?? '');
        $inspection->risk_summary = $validated['risk_summary'] ?? null;
        $inspection->findings = $normalizedFindings;

        // ==== COMPUTE WEIGHTED CPI FROM FINDINGS × SYSTEM WEIGHTS ====
        $this->computeWeightedCPI($inspection, $normalizedFindings, $priorityScores);
        $this->computeASI($inspection);

        $proceedToPhar = $request->input('next_stage') === 'phar';
        $proceedToPreview = $request->input('next_stage') === 'preview';

        if ($proceedToPreview && !empty($normalizedFindings) && in_array($inspection->status, ['scheduled', 'in_progress'], true)) {
            $inspection->status = 'findings_captured';
        }

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
        if (($property->status ?? null) !== 'archived') {
            $property->update([
                'status' => $inspection->status === 'completed' ? 'assessed' : 'in_assessment',
            ]);
        }

        if (!empty($validated['service_request_id'])) {
            $serviceRequest = ServiceRequest::query()
                ->where('id', (int) $validated['service_request_id'])
                ->where('property_id', $property->id)
                ->first();

            if ($serviceRequest) {
                $serviceRequest->update([
                    'inspection_id' => $inspection->id,
                    'created_inspection_id' => $inspection->id,
                    'project_id' => $inspection->project_id ?: $serviceRequest->project_id,
                    'status' => 'assessed',
                    'assessed_at' => $serviceRequest->assessed_at ?? now(),
                ]);
            }
        }

        // ==== ETOGO: persist findings as phar_findings at capture time ====
        // The findings report (and per-finding client commitments) read from
        // phar_findings, so we create/sync them here (descriptive + the 6
        // interpretation answers, no pricing). Pricing is added later in the
        // Estimation step without destroying these rows or client decisions.
        $this->persistPharFindings($inspection, $normalizedFindings, false);

        // ==== FINDINGS & MATERIALS ARE NOW COLLECTED ON PAGE 2 (PHAR DATA FORM) ====
        // Findings processing moved to storePharData() method
        // This keeps the two-page workflow clean: Page 1 = CPI scoring, Page 2 = PHAR data

        // NOTE: We don't run full calculations here anymore - only basic save
        // Full calculations happen after PHAR data collection in storePharData()

        $message = $proceedToPhar
            ? 'CPI scoring saved. Proceed to PHAR diagnosis.'
            : ($proceedToPreview
                ? 'Findings saved. Review the client-facing report below, then share it with the client.'
                : 'CPI scoring saved as draft successfully!');

        // ETOGO assessment flow: land on the findings preview so the inspector
        // can review the plain-language report before sharing it with the client.
        if ($proceedToPreview) {
            return redirect()->route('inspections.findings-preview', $inspection->id)
                ->with('success', $message);
        }

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

        $allSystems  = BuildingSystem::where('is_active', true)->get(['id', 'name', 'sort_order', 'metadata']);
        $totalWeight = $allSystems->sum(fn ($system) => $this->buildingSystemWeight($system));

        $systemScores = [];

        foreach ($allSystems as $system) {
            $systemFindings = array_filter(
                $findings,
                fn($f) => (int) ($f['building_system_id'] ?? 0) === (int) $system->id
            );

            $totalDeduction = 0.0;
            foreach ($systemFindings as $finding) {
                $priorityScore = (float) ($priorityScores[$finding['severity'] ?? 'low'] ?? 0);
                $weight        = $this->buildingSystemWeight($system);
                $totalDeduction += ($weight * $priorityScore * $scalingFactor) / ($maxSystemWeight * 100);
            }

            $systemScore = max(0.0, 100.0 - $totalDeduction);

            $systemScores[(string) $system->id] = [
                'name'      => $system->name,
                'weight'    => $this->buildingSystemWeight($system),
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

    private function buildingSystemWeight(BuildingSystem $system): int
    {
        $metadataWeight = (int) data_get($system->metadata, 'cpi_weight', 0);

        return $metadataWeight > 0 ? $metadataWeight : 10;
    }

    private function validateBuildingTaxonomySelections(Request $request): void
    {
        $errors = [];

        foreach ((array) $request->input('system_findings', []) as $index => $finding) {
            $systemId = (int) ($finding['building_system_id'] ?? 0);
            $subsystemId = (int) ($finding['building_subsystem_id'] ?? 0);
            $componentId = (int) ($finding['building_component_id'] ?? 0);

            if ($subsystemId > 0) {
                $validSubsystem = BuildingSubsystem::query()
                    ->where('id', $subsystemId)
                    ->where('building_system_id', $systemId)
                    ->where('is_active', true)
                    ->exists();

                if (!$validSubsystem) {
                    $errors["system_findings.$index.building_subsystem_id"] = 'Selected subsystem does not belong to the selected building system.';
                }
            }

            if ($componentId > 0) {
                $validComponent = BuildingComponent::query()
                    ->where('id', $componentId)
                    ->where('building_subsystem_id', $subsystemId)
                    ->where('is_active', true)
                    ->exists();

                if (!$validComponent) {
                    $errors["system_findings.$index.building_component_id"] = 'Selected component does not belong to the selected building subsystem.';
                }
            }

            foreach ((array) ($finding['affected_areas'] ?? []) as $areaIndex => $area) {
                $affectedSystemId = (int) ($area['building_system_id'] ?? 0);
                $affectedSubsystemId = (int) ($area['building_subsystem_id'] ?? 0);
                $affectedComponentId = (int) ($area['building_component_id'] ?? 0);

                if ($affectedSubsystemId > 0) {
                    $validAffectedSubsystem = BuildingSubsystem::query()
                        ->where('id', $affectedSubsystemId)
                        ->where('building_system_id', $affectedSystemId)
                        ->where('is_active', true)
                        ->exists();

                    if (!$validAffectedSubsystem) {
                        $errors["system_findings.$index.affected_areas.$areaIndex.building_subsystem_id"] = 'Selected affected subsystem does not belong to the selected affected building system.';
                    }
                }

                if ($affectedComponentId > 0) {
                    $validAffectedComponent = BuildingComponent::query()
                        ->where('id', $affectedComponentId)
                        ->where('building_subsystem_id', $affectedSubsystemId)
                        ->where('is_active', true)
                        ->exists();

                    if (!$validAffectedComponent) {
                        $errors["system_findings.$index.affected_areas.$areaIndex.building_component_id"] = 'Selected affected component does not belong to the selected affected subsystem.';
                    }
                }
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function normalizeAffectedAreas(array $affectedAreas, array $severityAliases): array
    {
        $severityMap = [
            'critical' => 'critical',
            'urgent' => 'critical',
            'high' => 'high',
            'health_safety_threatening' => 'high',
            'noi_protection' => 'high',
            'medium' => 'moderate',
            'moderate' => 'moderate',
            'value_depreciation' => 'moderate',
            'low' => 'low',
            'non_urgent' => 'low',
        ];

        return collect($affectedAreas)
            ->map(function ($area) use ($severityAliases, $severityMap) {
                $rawSeverity = (string) ($area['severity'] ?? 'moderate');
                $normalizedSeverity = $severityAliases[$rawSeverity] ?? $rawSeverity;
                $normalizedSeverity = $severityMap[$normalizedSeverity] ?? 'moderate';

                return [
                    'building_system_id' => !empty($area['building_system_id']) ? (int) $area['building_system_id'] : null,
                    'building_subsystem_id' => !empty($area['building_subsystem_id']) ? (int) $area['building_subsystem_id'] : null,
                    'building_component_id' => !empty($area['building_component_id']) ? (int) $area['building_component_id'] : null,
                    'location' => trim((string) ($area['location'] ?? '')),
                    'impact_description' => trim((string) ($area['impact_description'] ?? '')),
                    'severity' => $normalizedSeverity,
                ];
            })
            ->filter(fn ($area) => !empty($area['building_system_id']) || $area['impact_description'] !== '' || $area['location'] !== '')
            ->values()
            ->all();
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
        $inspection = Inspection::with(['property.user', 'project', 'inspector', 'assignedBy', 'ETOGORepresentative', 'toolAssignments.toolSetting'])
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

        $activeQuotation = null;
        if (!empty($inspection->active_quotation_id)) {
            $activeQuotation = InspectionQuotation::query()
                ->where('id', $inspection->active_quotation_id)
                ->where('inspection_id', $inspection->id)
                ->first();
        }

        if (($activeQuotation?->status ?? null) !== 'approved') {
            $approvedQuotation = InspectionQuotation::query()
                ->where('inspection_id', $inspection->id)
                ->where('status', 'approved')
                ->orderBy('id', 'desc')
                ->first();
            if ($approvedQuotation) {
                $activeQuotation = $approvedQuotation;
            }
        }

        $hasMaintenanceLogs = $inspection->maintenanceVisitLogs()->exists();
        $scheduleHasProgress = collect($inspection->work_schedule ?? [])->contains(function ($visit) {
            return in_array((string) ($visit['status'] ?? 'scheduled'), ['in_progress', 'completed'], true);
        });
        $scheduleLocked = $hasMaintenanceLogs || $scheduleHasProgress;

        return view('admin.inspections.show', compact('inspection', 'findings', 'materials', 'toolAssignments', 'activeQuotation', 'scheduleLocked'));
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

        $activeQuotation = null;
        if (!empty($inspection->active_quotation_id)) {
            $activeQuotation = InspectionQuotation::query()
                ->where('id', $inspection->active_quotation_id)
                ->where('inspection_id', $inspection->id)
                ->first();
        }

        if (($activeQuotation?->status ?? null) !== 'approved') {
            $approvedQuotation = InspectionQuotation::query()
                ->where('inspection_id', $inspection->id)
                ->where('status', 'approved')
                ->orderBy('id', 'desc')
                ->first();
            if ($approvedQuotation) {
                $activeQuotation = $approvedQuotation;
            }
        }

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
        $pdf = Pdf::loadView('admin.inspections.invoice-pdf', compact('inspection', 'findings', 'materials', 'photoUrls', 'findingPhotoUrls', 'activeQuotation'))
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

            // Notify Store Managers that tools are ready to be assigned for this property
            try {
                $freshInspection = $inspection->fresh(['property.user']);
                if ($freshInspection && $freshInspection->client_signature) {
                    $propertyName  = $freshInspection->property?->property_name
                                  ?? $freshInspection->property?->property_code
                                  ?? 'Property #' . $freshInspection->property_id;
                    $clientName    = $freshInspection->property?->user?->name ?? 'Client';
                    $storeManagers = \App\Models\User::role('Store Manager')->get();
                    $notification  = new ToolAssignmentReadyNotification(
                        $freshInspection->id,
                        $freshInspection->property_id,
                        $propertyName,
                        $clientName,
                    );
                    foreach ($storeManagers as $manager) {
                        $manager->notify($notification);
                    }
                }
            } catch (\Throwable $notifyEx) {
                Log::warning('ToolAssignmentReadyNotification failed', ['error' => $notifyEx->getMessage()]);
            }

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
            abort(403, 'You are not authorized to sign this agreement as ETOGO staff.');
        }

        if (($inspection->status ?? null) !== 'completed') {
            return back()->with('error', 'Agreement can only be signed after inspection completion.');
        }

        if (!$inspection->approved_by_client || !$inspection->client_approved_at) {
            return back()->with('error', 'ETOGO staff can only sign after the client signs.');
        }

        if ($inspection->ETOGO_signed_at) {
            return back()->with('info', 'Agreement has already been signed by ETOGO staff.');
        }

        $inspection->update([
            'ETOGO_signed_by' => $user->id,
            'ETOGO_signed_at' => now(),
        ]);

        $this->agreementScheduleService->refresh($inspection);

        return back()->with('success', 'Agreement signed by ETOGO staff (' . $user->name . ').');
    }

    public function countersignAgreement(Request $request, Inspection $inspection)
    {
        $user = Auth::user();
        if (!$user || !$user->hasAnyRole(['Super Admin', 'Administrator', 'Project Manager'])) {
            abort(403, 'You are not authorized to countersign this agreement.');
        }

        $validated = $request->validate([
            'staff_full_name' => 'required|string|max:255',
            'staff_acknowledgment' => 'required|accepted',
        ]);

        if (($inspection->status ?? null) !== 'completed') {
            return back()->with('error', 'Agreement countersign is only available for completed inspections.');
        }

        if (!$inspection->approved_by_client || !$inspection->client_approved_at) {
            return back()->with('error', 'Client must sign the agreement before ETOGO countersign.');
        }

        if (($inspection->work_payment_status ?? 'pending') !== 'paid') {
            return back()->with('error', 'Deposit/work payment must be confirmed before ETOGO countersign.');
        }

        $hasAssignedTools = $inspection->toolAssignments()
            ->whereNull('returned_at')
            ->where('quantity', '>', 0)
            ->exists();
        if (!$hasAssignedTools) {
            return back()->with('error', 'Assign tools first before ETOGO countersign.');
        }

        $hasScheduledVisits = collect($inspection->work_schedule ?? [])->isNotEmpty();
        if (!$hasScheduledVisits) {
            return back()->with('error', 'Set project visit schedule before ETOGO countersign.');
        }

        if ($inspection->ETOGO_signed_at) {
            return back()->with('info', 'Agreement has already been countersigned by ETOGO.');
        }

        $inspection->update([
            'ETOGO_signed_by' => Auth::id(),
            'ETOGO_signed_at' => now(),
            'ETOGO_signature_image_path' => Auth::user()->signature_path ?: null,
        ]);

        $this->agreementScheduleService->refresh($inspection);

        return back()->with('success', 'Agreement countersigned by ' . trim((string) $validated['staff_full_name']) . '.');
    }

    /**
     * Save (or replace) the work visit schedule for a fully-executed inspection.
     * All dates must be Mon–Sat. Work hours are 7 AM – 6 PM.
     */
    public function storeWorkSchedule(Request $request, Inspection $inspection)
    {
        $user = Auth::user();
        if (!$user || !$user->hasAnyRole(['Super Admin', 'Administrator', 'Project Manager'])) {
            abort(403);
        }

        if (!$inspection->approved_by_client || !$inspection->client_approved_at) {
            return back()->with('error', 'Client must sign the agreement before scheduling project visits.');
        }

        if (($inspection->work_payment_status ?? 'pending') !== 'paid') {
            return back()->with('error', 'Deposit/work payment must be confirmed before scheduling project visits.');
        }

        $hasAssignedTools = $inspection->toolAssignments()
            ->whereNull('returned_at')
            ->where('quantity', '>', 0)
            ->exists();

        if (!$hasAssignedTools) {
            return back()->with('error', 'Assign project tools first before scheduling visits.');
        }

        if ($inspection->ETOGO_signed_at) {
            return back()->with('error', 'Visit schedule is locked after ETOGO countersign.');
        }

        $hasMaintenanceLogs = $inspection->maintenanceVisitLogs()->exists();
        $scheduleHasProgress = collect($inspection->work_schedule ?? [])->contains(function ($visit) {
            return in_array((string) ($visit['status'] ?? 'scheduled'), ['in_progress', 'completed'], true);
        });

        if ($hasMaintenanceLogs || $scheduleHasProgress) {
            return back()->with('error', 'Visit schedule is locked because maintenance work has already started.');
        }

        $validated = $request->validate([
            'visit_dates'                         => 'required|array|min:1',
            'visit_dates.*'                       => 'required|date',
            'visit_deliverables'                  => 'nullable|array',
            'visit_deliverables.*'                => 'nullable|array',
            'visit_deliverables.*.*'              => 'nullable|array',
            'visit_deliverables.*.*.date'    => 'nullable|date',
            'visit_deliverables.*.*.tasks'   => 'nullable|array',
            'visit_deliverables.*.*.tasks.*' => 'nullable|string|max:500',
        ]);

        $badDates = collect($validated['visit_dates'])->filter(function (string $date) {
            // Working week is Monday–Saturday; only reject Sunday
            return Carbon::parse($date)->dayOfWeek === Carbon::SUNDAY;
        })->values();

        if ($badDates->isNotEmpty()) {
            return back()->with('error', 'Visit dates must be Monday – Saturday (no Sundays). Invalid: ' . $badDates->implode(', '));
        }

        $rawDeliverables = $validated['visit_deliverables'] ?? [];

        // Pair each date with its deliverables before deduplication/sort
        $visitEntries = collect($validated['visit_dates'])->map(function ($d, $i) use ($rawDeliverables) {
            $dateStr = Carbon::parse($d)->toDateString();
            $deliverables = collect($rawDeliverables[$i] ?? [])
                ->map(fn($dl) => [
                    'date'  => !empty($dl['date']) ? Carbon::parse($dl['date'])->toDateString() : $dateStr,
                    'tasks' => array_values(array_filter(array_map('trim', $dl['tasks'] ?? []))),
                ])
                ->filter(fn($dl) => !empty($dl['tasks']))
                ->values()
                ->map(fn($dl, $j) => array_merge(['day' => $j + 1], $dl))
                ->all();
            return ['date' => $dateStr, 'deliverables' => $deliverables];
        });

        $schedule = $visitEntries
            ->keyBy('date')
            ->sortKeys()
            ->values()
            ->map(fn($v) => [
                'date'         => $v['date'],
                'status'       => 'scheduled',
                'deliverables' => $v['deliverables'],
            ])
            ->all();

        $updates = ['work_schedule' => $schedule];

        if (!empty($schedule)) {
            $dates = collect($schedule)->pluck('date');
            $updates['planned_start_date']      = $dates->first();
            $updates['target_completion_date']  = $dates->last();
            $updates['schedule_blocked_reason'] = null;
        }

        $inspection->update($updates);
        $this->notifyClientWorkSchedulePublished($inspection->fresh(['property.user']), collect($schedule)->pluck('date')->all());

        return back()->with('success', count($schedule) . ' visit(s) scheduled successfully.');
    }

    /**
     * Display PHAR data collection form (Page 2 of inspection workflow)
     */
    public function pharData(string $id)
    {
        $inspection = Inspection::with(['property', 'pharFindings'])->findOrFail($id);
        $property = $inspection->property;
        $activeQuotation = null;
        if (!empty($inspection->active_quotation_id)) {
            $activeQuotation = \App\Models\InspectionQuotation::query()
                ->where('id', $inspection->active_quotation_id)
                ->where('inspection_id', $inspection->id)
                ->first();
        }

        // Total FMC from InspectionMaterial — used in locked pricing panel and completion.
        // Materials are stored at inspection level (no per-finding link), so the full sum
        // is used regardless of which findings were approved.
        $inspectionMaterialTotal = round(
            (float) \App\Models\InspectionMaterial::where('inspection_id', $inspection->id)->sum('line_total'),
            2
        );

        // Sort Phase 1 findings by severity: critical → high → noi_protection → medium → low
        $severityOrder  = ['critical' => 0, 'high' => 1, 'noi_protection' => 2, 'medium' => 3, 'low' => 4];
        $sortedFindings = collect($inspection->findings ?? [])
            ->sortBy(fn($f) => $severityOrder[$f['severity'] ?? 'low'] ?? 99)
            ->values()
            ->all();
        $sortedFindings = PharCatalog::applyDefaultsToFindings($sortedFindings);

        // System weights keyed by name for display in the finding header
        $systemWeightsMap = BuildingSystem::where('is_active', true)->pluck('weight', 'name')->toArray();

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
            'building_system_id',
            'building_subsystem_id',
            'building_component_id',
        ])->map(static function ($row) {
            $base = (float) ($row->default_unit_cost ?? 0);
            $hst  = (float) ($row->hst_rate ?? 5.00);
            $pst  = (float) ($row->pst_rate ?? 7.00);

            $row->taxed_unit_cost = round($base * (1 + $hst / 100) * (1 + $pst / 100), 2);
            return $row;
        });
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
            'taxed_unit_cost'   => round(
                (float) ($row['default_unit_cost'] ?? 0)
                * (1 + (float) ($row['hst_rate'] ?? 5.00) / 100)
                * (1 + (float) ($row['pst_rate'] ?? 7.00) / 100),
                2
            ),
            'building_system_id'         => null,
            'building_subsystem_id'      => null,
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

        // If client already approved the active quotation, ensure both quotation totals
        // and inspection pricing fields are aligned to the locked approved scope.
        $inspection = $this->reconcileApprovedQuotationPricing($inspection->fresh(), $activeQuotation);
        if ($activeQuotation) {
            $activeQuotation = $activeQuotation->fresh();
        }

        return view('admin.inspections.form-phar-data', compact(
            'inspection',
            'property',
            'activeQuotation',
            'inspectionMaterialTotal',
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

        if (!$isDraft && !$inspection->hasClientCommitted()) {
            return redirect()->route(
                $inspection->hasSharedFindingsReport() ? 'inspections.findings-preview' : 'inspections.assessment-report',
                $inspection->id
            )->with(
                $inspection->hasSharedFindingsReport() ? 'info' : 'error',
                $inspection->hasSharedFindingsReport()
                    ? 'The findings report has been shared. Wait for the client to choose which findings should be priced before saving work costing.'
                    : 'Share the diagnosis findings report with the client before saving deliverable costing.'
            );
        }

        // If the quotation is already approved by the client, the pricing and scope are locked.
        // No more edits (including draft saves) are allowed on this screen.
        if (($inspection->quotation_status ?? null) === 'approved') {
            return redirect()->route('inspections.phar-data', $inspection->id)
            ->with('info', 'This quotation is already approved and locked. Editing findings or recalculating pricing is disabled. Complete the diagnosis or create a follow-up quotation from deferred findings.');
        }

        // Preview requires complete PHAR + BDC inputs. Draft mode can stay partial.
        if (!$isDraft) {
            $request->validate([
                'property_size_psf'      => 'required|numeric|min:0.01',
                'minimum_required_hours' => 'required|numeric|min:0.1',
                'tus_score'              => 'required|numeric|min:0|max:100',
                'bdc_distance_km'        => 'required|numeric|min:0.01',
                'bdc_time_min'           => 'required|numeric|min:1',
            ], [
                'property_size_psf.required' => 'Property size is required before saving preview pricing.',
                'minimum_required_hours.required' => 'Minimum required hours is required before saving preview pricing.',
                'tus_score.required' => 'Tenant Underwriting Score (TUS) is required before saving preview pricing.',
                'bdc_distance_km.required' => 'BDC distance (km) is required before saving preview pricing.',
                'bdc_time_min.required' => 'BDC travel time (minutes) is required before saving preview pricing.',
            ]);
        }

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
            'findings.*.finding_type'           => 'nullable|in:stand_alone,cascading',
            'findings.*.impact_categories'      => 'nullable|array',
            'findings.*.impact_categories.*'    => 'nullable|string|max:80',
            'findings.*.requires_trade_pricing' => 'nullable|boolean',
            'findings.*.fulfillment_type'       => 'nullable|in:ETOGO_team,trade_partner,decide_later',
            'findings.*.trade_application_id'   => 'nullable|exists:trade_applications,id',
            'findings.*.trade_quantity'         => 'nullable|numeric|min:0',
            'findings.*.trade_unit'             => 'nullable|string|max:30',
            'findings.*.trade_scope_area'       => 'nullable|string|max:255',
            'findings.*.trade_duration_hours'   => 'nullable|numeric|min:0',
            'findings.*.trade_notes'            => 'nullable|string|max:1000',
            'findings.*.trade_materials_included' => 'nullable|boolean',

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
            $fulfillmentType = $phar['fulfillment_type'] ?? ($finding['fulfillment_type'] ?? data_get($finding, 'trade_pricing.fulfillment_type', 'decide_later'));
            $isTradePartnerFulfillment = $fulfillmentType === 'trade_partner';
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
                'finding_type' => $phar['finding_type'] ?? ($finding['finding_type'] ?? 'stand_alone'),
                'impact_categories' => array_values(array_filter((array) (
                    $phar['impact_categories'] ?? ($finding['impact_categories'] ?? [])
                ))),
                'requires_trade_pricing' => array_key_exists('requires_trade_pricing', $phar)
                    ? (bool) $phar['requires_trade_pricing']
                    : ($finding['requires_trade_pricing'] ?? null),
                'fulfillment_type' => $fulfillmentType,
                'trade_application_id' => $isTradePartnerFulfillment && !empty($phar['trade_application_id'])
                    ? (int) $phar['trade_application_id']
                    : ($isTradePartnerFulfillment ? ($finding['trade_application_id'] ?? data_get($finding, 'trade_pricing.trade_application_id')) : null),
                'trade_quantity' => $isTradePartnerFulfillment && isset($phar['trade_quantity'])
                    ? (float) $phar['trade_quantity']
                    : ($isTradePartnerFulfillment ? ($finding['trade_quantity'] ?? data_get($finding, 'trade_pricing.quantity', 1)) : 1),
                'trade_unit' => $isTradePartnerFulfillment ? trim((string) ($phar['trade_unit'] ?? ($finding['trade_unit'] ?? data_get($finding, 'trade_pricing.unit', '')))) : '',
                'trade_scope_area' => $isTradePartnerFulfillment ? trim((string) ($phar['trade_scope_area'] ?? ($finding['trade_scope_area'] ?? data_get($finding, 'trade_pricing.scope_area', '')))) : '',
                'trade_duration_hours' => $isTradePartnerFulfillment && isset($phar['trade_duration_hours'])
                    ? (float) $phar['trade_duration_hours']
                    : ($isTradePartnerFulfillment ? ($finding['trade_duration_hours'] ?? data_get($finding, 'trade_pricing.estimated_duration_hours')) : null),
                'trade_notes' => $isTradePartnerFulfillment ? trim((string) ($phar['trade_notes'] ?? ($finding['trade_notes'] ?? ''))) : '',
                'trade_materials_included' => $isTradePartnerFulfillment && array_key_exists('trade_materials_included', $phar)
                    ? (bool) $phar['trade_materials_included']
                    : ($isTradePartnerFulfillment ? (bool) ($finding['trade_materials_included'] ?? data_get($finding, 'trade_pricing.materials_included', false)) : false),
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

        $computedInspection = $inspection->fresh();
        $computedFindings = collect($computedInspection->findings ?? [])->values();
        $computedLabourHours = (float) $computedFindings->sum(fn(array $f) => (float) ($f['phar_labour_hours'] ?? 0));

        if ($computedFindings->isEmpty() ||
            $computedLabourHours <= 0 ||
            (float) ($computedInspection->bdc_annual ?? 0) <= 0 ||
            (float) ($computedInspection->trc_annual ?? 0) <= 0) {
            return redirect()->back()->withInput()->withErrors([
                'save_preview' => 'Work costing cannot be saved yet. Please complete all required PHAR inputs and ensure BDC, labour hours, and totals are fully computed.',
            ]);
        }

        // ==== FINAL SAVE: process into relational tables ====
        // Update phar_findings in place so their ids (and any client commitments
        // that cascade from them) survive, then rebuild the pricing children.
        $inspection->materials()->delete();
        $inspection->tradePricingItems()->delete();

        $pharFindingMap = $this->persistPharFindings($inspection, $mergedFindings, true);

        $tradePricingService = app(\App\Services\PharTradePricingService::class);

        foreach ($mergedFindings as $findingIndex => $findingData) {
            $pharFinding = $pharFindingMap[$findingIndex] ?? null;
            if (!$pharFinding) {
                continue;
            }

            $isTradePartnerFinding = $tradePricingService->shouldPriceFinding($findingData);
            $tradeMaterialsIncluded = $isTradePartnerFinding && (bool) ($findingData['trade_materials_included'] ?? false);
            $billableMaterials = $tradeMaterialsIncluded ? [] : ($findingData['phar_materials'] ?? []);

            if ($isTradePartnerFinding) {
                $tradePricing = $tradePricingService->priceFinding($inspection, $findingData, (int) $findingIndex);
                $tradePricing['phar_finding_id'] = $pharFinding->id;
                $tradeItem = \App\Models\InspectionTradePricingItem::create($tradePricing);

                $mergedFindings[$findingIndex]['trade_pricing'] = [
                    'trade_pricing_item_id' => $tradeItem->id,
                    'phar_finding_id' => $pharFinding->id,
                    'trade_application_id' => $tradeItem->trade_application_id,
                    'trade_company_name' => $tradeItem->trade_company_name,
                    'fulfillment_type' => $tradeItem->fulfillment_type,
                    'scope_area' => $tradeItem->scope_area,
                    'unit' => $tradeItem->unit,
                    'quantity' => (float) $tradeItem->quantity,
                    'estimated_duration_hours' => $tradeItem->estimated_duration_hours !== null ? (float) $tradeItem->estimated_duration_hours : null,
                    'trade_unit_cost' => (float) $tradeItem->trade_unit_cost,
                    'trade_total_cost' => (float) $tradeItem->trade_total_cost,
                    'ETOGO_client_price' => (float) $tradeItem->ETOGO_client_price,
                    'ETOGO_margin_amount' => (float) $tradeItem->ETOGO_margin_amount,
                    'materials_included' => $tradeMaterialsIncluded,
                    'pricing_source' => $tradeItem->pricing_source,
                ];
            } else {
                unset($mergedFindings[$findingIndex]['trade_pricing']);
            }

            // Per-finding materials → InspectionMaterial records
            foreach ($billableMaterials as $materialData) {
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

        $inspection->findings = array_values($mergedFindings);
        $inspection->save();

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
            ->with('success', 'Work assignment and costing saved successfully. Review the preview below, then share the quotation when ready.');
    }

    /**
     * Create or update phar_findings rows from the inspection's findings list,
     * matching existing rows by position so ids (and any client decisions that
     * cascade from them) are preserved. Used at capture time (no pricing) and
     * during estimation (with pricing).
     *
     * @param  array  $findings  Ordered, normalised/merged findings array.
     * @return array<int, \App\Models\PHARFinding|null>  Map of finding index → row (null = empty/skipped).
     */
    private function persistPharFindings(Inspection $inspection, array $findings, bool $withPricing): array
    {
        // phar_findings.severity is ENUM(low, moderate, high, critical); the findings
        // JSON uses a wider scale, so map it down to a valid enum value.
        $severityMap = [
            'critical'       => 'critical',
            'high'           => 'high',
            'noi_protection' => 'high',
            'medium'         => 'moderate',
            'moderate'       => 'moderate',
            'low'            => 'low',
        ];

        $existing = $inspection->pharFindings()->orderBy('id')->get()->values();
        $tradePricingService = $withPricing ? app(\App\Services\PharTradePricingService::class) : null;

        $map = [];
        $position = 0;

        foreach ($findings as $index => $finding) {
            $hasContent = trim((string) ($finding['issue'] ?? '')) !== ''
                || trim((string) ($finding['issue_description'] ?? '')) !== ''
                || trim((string) ($finding['risk_impact'] ?? '')) !== ''
                || trim((string) ($finding['recommendation_details'] ?? '')) !== ''
                || trim((string) ($finding['notes'] ?? '')) !== '';

            if (!$hasContent && empty($finding['phar_labour_hours'])) {
                $map[$index] = null;
                continue;
            }

            $rawSeverity = (string) ($finding['severity'] ?? 'moderate');

            $attrs = [
                'property_id'   => $inspection->property_id,
                'building_system_id'     => $finding['building_system_id'] ?? null,
                'building_subsystem_id'  => $finding['building_subsystem_id'] ?? null,
                'building_component_id'  => $finding['building_component_id'] ?? null,
                'finding_type'  => $finding['finding_type'] ?? 'stand_alone',
                'impact_categories' => !empty($finding['impact_categories']) ? array_values((array) $finding['impact_categories']) : null,
                'task_question' => $finding['issue'] ?? ($finding['task_question'] ?? ''),
                'category'      => $finding['phar_category'] ?? 'General',
                'severity'      => $severityMap[$rawSeverity] ?? 'moderate',
                'priority'      => $finding['priority'] ?? 3,
                'included_yn'   => $finding['phar_included_yn'] ?? true,
                'notes'         => $finding['phar_notes'] ?? ($finding['notes'] ?? null),
                'photo_ids'     => !empty($finding['finding_photos']) ? $finding['finding_photos'] : null,
                // ETOGO Client Understanding — derived from the inspector's natural fields so the
                // client report can answer the key questions without a rigid questionnaire on the form.
                'observed_condition'     => $finding['issue_description'] ?? null,        // What we found
                'consequence_if_ignored' => $finding['risk_impact'] ?? null,             // Why it matters / what is at risk
                'remediation_strategy'   => $finding['recommendation_details'] ?? null,  // Our recommendation
            ];

            if ($withPricing) {
                $isTradePartnerFinding = $tradePricingService->shouldPriceFinding($finding);
                $tradeMaterialsIncluded = $isTradePartnerFinding && (bool) ($finding['trade_materials_included'] ?? false);
                $billableMaterials = $tradeMaterialsIncluded ? [] : ($finding['phar_materials'] ?? []);
                $attrs['labour_hours']  = $finding['phar_labour_hours'] ?? 0;
                $attrs['material_cost'] = collect($billableMaterials)->sum(fn($m) => (float) ($m['line_total'] ?? 0));
            }

            $row = $existing->get($position);
            if ($row) {
                $row->update($attrs);
            } else {
                $row = \App\Models\PHARFinding::create(array_merge(['inspection_id' => $inspection->id], $attrs));
            }

            $this->syncFindingAffectedAreas($row, (array) ($finding['affected_areas'] ?? []));

            $map[$index] = $row;
            $position++;
        }

        // Remove any leftover phar_findings beyond the current set.
        foreach ($existing->slice($position) as $row) {
            $row->delete();
        }

        return $map;
    }

    /**
     * ETOGO Stage A (preview) — read-only preview of the captured findings,
     * rendered as the client will see them, with a button to share the report.
     * Reached right after the inspector saves the findings-capture form.
     */
    private function syncFindingAffectedAreas(PHARFinding $finding, array $affectedAreas): void
    {
        $existing = $finding->affectedAreas()->orderBy('id')->get()->values();

        foreach (array_values($affectedAreas) as $index => $area) {
            $attrs = [
                'building_system_id' => $area['building_system_id'] ?? null,
                'building_subsystem_id' => $area['building_subsystem_id'] ?? null,
                'building_component_id' => $area['building_component_id'] ?? null,
                'location' => $area['location'] ?? null,
                'impact_description' => $area['impact_description'] ?? null,
                'severity' => $area['severity'] ?? 'moderate',
                'sort_order' => ($index + 1) * 10,
            ];

            $row = $existing->get($index);
            if ($row) {
                $row->update($attrs);
            } else {
                $finding->affectedAreas()->create($attrs);
            }
        }

        foreach ($existing->slice(count($affectedAreas)) as $row) {
            $row->delete();
        }
    }

    public function findingsPreview(string $id)
    {
        $inspection = Inspection::with([
            'property',
            'activeMatterportModel',
            'activeSpatialModels.captureSession',
            'twinSourceFiles.childSourceFiles',
            'twinProcessingJobs.sourceFile',
            'issueMarkers.spatialModel',
            'issueMarkers.captureSession',
            'issueMarkers.pharFinding',
        ])->findOrFail($id);

        $user = Auth::user();
        if (!$user->hasAnyRole(['Super Admin', 'Administrator', 'Project Manager', 'Inspector'])) {
            abort(403, 'Only staff may preview the findings report.');
        }

        $findings = PHARFinding::with('affectedAreas')
            ->where('inspection_id', $inspection->id)
            ->orderByRaw("
                CASE severity
                    WHEN 'critical' THEN 1
                    WHEN 'high' THEN 2
                    WHEN 'moderate' THEN 3
                    WHEN 'medium' THEN 3
                    WHEN 'low' THEN 4
                    ELSE 5
                END
            ")
            ->orderBy('id')
            ->get();

        return view('admin.inspections.findings-preview', [
            'inspection' => $inspection,
            'property'   => $inspection->property,
            'findings'   => $findings,
        ]);
    }

    /**
     * ETOGO Stage B — Share findings-only report with client.
     *
     * Marks the assessment as "findings_shared" so the client can review what
     * was discovered (in plain language, with no pricing) and decide which
     * items to commit to. Pricing/estimation only happens AFTER the client
     * commits in Stage C.
     */
    public function shareFindingsReport(Inspection $inspection)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['Super Admin', 'Administrator', 'Project Manager', 'Inspector'])) {
            abort(403, 'Only staff may share the findings report.');
        }

        $findings = PHARFinding::with('affectedAreas')
            ->where('inspection_id', $inspection->id)
            ->orderBy('id')
            ->get();

        if ($findings->isEmpty()) {
            return redirect()->route('inspections.show', $inspection->id)
                ->with('error', 'No findings captured yet. Save the diagnosis findings before sharing the report.');
        }

        if (in_array($inspection->status, ['completed', 'approved'], true)) {
            return redirect()->route('inspections.show', $inspection->id)
                ->with('info', 'This diagnosis is already completed.');
        }

        try {
            DB::transaction(function () use ($inspection) {
                $updates = [
                    'status' => 'findings_shared',
                    'findings_report_shared_at' => now(),
                ];
                $inspection->update($updates);
            });
        } catch (\Throwable $e) {
            Log::error('shareFindingsReport failed', [
                'inspection_id' => $inspection->id,
                'error' => $e->getMessage(),
            ]);
            return redirect()->route('inspections.show', $inspection->id)
                ->with('error', 'Could not share the findings report: ' . $e->getMessage());
        }

        return redirect()->route('inspections.show', $inspection->id)
            ->with('success', 'Findings report shared with the client. They can now review and commit to items for remediation.');
    }

    /**
     * Finalise the assessment — locks the captured findings from further edits and
     * produces the official PHAR assessment report. Reversible via reopenAssessment()
     * until the report has been shared with the client.
     */
    public function finaliseAssessment(Inspection $inspection)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['Super Admin', 'Administrator', 'Project Manager', 'Inspector'])) {
            abort(403, 'Only staff may finalise a diagnosis.');
        }

        $findingsCount = PHARFinding::where('inspection_id', $inspection->id)->count();
        if ($findingsCount === 0) {
            return redirect()->route('inspections.findings-preview', $inspection->id)
                ->with('error', 'Capture at least one finding before finalising the diagnosis.');
        }

        if (in_array($inspection->status, ['completed', 'approved'], true)) {
            return redirect()->route('inspections.assessment-report', $inspection->id)
                ->with('info', 'This diagnosis is already completed.');
        }

        try {
            DB::transaction(function () use ($inspection, $user) {
                $updates = [
                    'assessment_finalised_at' => now(),
                    'assessment_finalised_by' => $user->id,
                ];
                // Only advance the lifecycle if we are still in the capture phase.
                if (in_array($inspection->status, ['scheduled', 'in_progress'], true)) {
                    $updates['status'] = 'findings_captured';
                }
                $inspection->update($updates);
                $inspection->property()
                    ->where('status', '!=', 'archived')
                    ->update(['status' => 'assessed']);
            });
        } catch (\Throwable $e) {
            Log::error('finaliseAssessment failed', [
                'inspection_id' => $inspection->id,
                'error' => $e->getMessage(),
            ]);
            return redirect()->route('inspections.findings-preview', $inspection->id)
                ->with('error', 'Could not finalise the diagnosis: ' . $e->getMessage());
        }

        return redirect()->route('inspections.assessment-report', $inspection->id)
            ->with('success', 'Diagnosis finalised. The PHAR diagnosis report is ready to share with the client.');
    }

    /**
     * Official PHAR assessment report (staff view) — read-only, generated once the
     * assessment is finalised. Shows the full finding detail plus internal notes,
     * with actions to share the client-facing report or reopen for edits.
     */
    public function assessmentReport(string $id)
    {
        $inspection = Inspection::with([
            'property',
            'inspector',
            'finalisedBy',
            'activeMatterportModel',
            'activeSpatialModels.captureSession',
            'twinSourceFiles.childSourceFiles',
            'twinProcessingJobs.sourceFile',
            'issueMarkers.spatialModel',
            'issueMarkers.captureSession',
            'issueMarkers.pharFinding',
        ])->findOrFail($id);

        $user = Auth::user();
        if (!$user->hasAnyRole(['Super Admin', 'Administrator', 'Project Manager', 'Inspector'])) {
            abort(403, 'Only staff may view the diagnosis report.');
        }

        $findings = PHARFinding::with(['system', 'subsystem', 'affectedAreas'])
            ->where('inspection_id', $inspection->id)
            ->orderByRaw("
                CASE severity
                    WHEN 'critical' THEN 1
                    WHEN 'high' THEN 2
                    WHEN 'moderate' THEN 3
                    WHEN 'medium' THEN 3
                    WHEN 'low' THEN 4
                    ELSE 5
                END
            ")
            ->orderBy('id')
            ->get();

        return view('admin.inspections.assessment-report', [
            'inspection' => $inspection,
            'property'   => $inspection->property,
            'findings'   => $findings,
        ]);
    }

    /**
     * Reopen a finalised assessment for further edits (admins / managers only).
     * Clears the finalised lock and returns the inspection to the capture phase,
     * provided the findings have not already been shared with the client.
     */
    public function reopenAssessment(Inspection $inspection)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['Super Admin', 'Administrator', 'Project Manager'])) {
            abort(403, 'Only administrators or project managers may reopen a diagnosis.');
        }

        if ($inspection->findings_report_shared_at !== null) {
            return redirect()->route('inspections.assessment-report', $inspection->id)
                ->with('error', 'This report has already been shared with the client and can no longer be reopened.');
        }

        $inspection->update([
            'assessment_finalised_at' => null,
            'assessment_finalised_by' => null,
            'status' => 'in_progress',
        ]);
        $inspection->property()
            ->where('status', '!=', 'archived')
            ->update(['status' => 'in_assessment']);

        return redirect()->route('inspections.create', ['property_id' => $inspection->property_id])
            ->with('success', 'Diagnosis reopened. You can edit the findings and finalise again when ready.');
    }

    /**
     * ETOGO Stage D — Estimation form (admin pricing for committed findings only).
     *
     * Thin alias over pharData() that ensures staff only price findings the
     * client has committed to (Stage C). Deliverable costing is blocked until
     * the diagnosis report is shared and the client has made decisions.
     */
    public function estimation(string $id)
    {
        $inspection = Inspection::findOrFail($id);

        if (!$inspection->hasClientCommitted()) {
            return redirect()->route(
                $inspection->hasSharedFindingsReport() ? 'inspections.findings-preview' : 'inspections.assessment-report',
                $inspection->id
            )->with(
                $inspection->hasSharedFindingsReport() ? 'info' : 'error',
                $inspection->hasSharedFindingsReport()
                    ? 'The findings report has been shared. Wait for the client to choose which findings should be priced before opening work costing.'
                    : 'Share the diagnosis findings report with the client before starting deliverable costing.'
            );
        }

        // Bump estimation_started_at on first visit after client commits.
        if ($inspection->client_committed_at && !$inspection->estimation_started_at) {
            $inspection->update([
                'status' => 'estimation_in_progress',
                'estimation_started_at' => now(),
            ]);
        }

        return $this->pharData($id);
    }

    /**
     * ETOGO Stage D (save) — Persist pricing/estimation entered by admin.
     *
     * Delegates to storePharData() so the existing scientific BDC/FRLC/FMC
     * pipeline keeps working, then stamps the estimation_completed_at timestamp.
     */
    public function storeEstimation(Request $request, string $id)
    {
        $inspection = Inspection::findOrFail($id);

        if (!$inspection->hasClientCommitted()) {
            return redirect()->route(
                $inspection->hasSharedFindingsReport() ? 'inspections.findings-preview' : 'inspections.assessment-report',
                $inspection->id
            )->with(
                $inspection->hasSharedFindingsReport() ? 'info' : 'error',
                $inspection->hasSharedFindingsReport()
                    ? 'The findings report has been shared. Wait for the client to choose which findings should be priced before saving work costing.'
                    : 'Share the diagnosis findings report with the client before saving deliverable costing.'
            );
        }

        $response = $this->storePharData($request, $id);

        if ($request->input('action') === 'save_draft_back') {
            return $response;
        }

        if ($response instanceof \Illuminate\Http\RedirectResponse && $response->getSession()->has('errors')) {
            return $response;
        }

        try {
            $inspection = Inspection::find($id);
            if (
                $inspection
                && !in_array($inspection->status, ['completed', 'approved'], true)
                && ($inspection->quotation_status ?? null) !== 'approved'
                && (float) ($inspection->bdc_annual ?? 0) > 0
                && (float) ($inspection->trc_annual ?? 0) > 0
            ) {
                $updates = ['estimation_completed_at' => now()];
                // Only flip status if we are still in the assessment/estimation phase.
                if (in_array($inspection->status, ['findings_captured', 'findings_shared', 'client_committed', 'estimation_in_progress'], true)) {
                    $updates['status'] = 'estimation_completed';
                }
                $inspection->update($updates);
            }
        } catch (\Throwable $e) {
            Log::warning('storeEstimation timestamp update failed', [
                'inspection_id' => $id,
                'error' => $e->getMessage(),
            ]);
        }

        return $response;
    }

    /**
     * Finalise the assessment: mark as completed, sync schedule, generate invoice.
     * Called only when admin is satisfied with the pricing preview.
     */
    public function shareQuotation(Inspection $inspection)
    {
        if (!$inspection->hasClientCommitted()) {
            return redirect()->route(
                $inspection->hasSharedFindingsReport() ? 'inspections.findings-preview' : 'inspections.assessment-report',
                $inspection->id
            )->with(
                $inspection->hasSharedFindingsReport() ? 'info' : 'error',
                $inspection->hasSharedFindingsReport()
                    ? 'The findings report has been shared. Wait for the client to choose which findings should be priced before sharing a quotation.'
                    : 'Share the diagnosis findings report with the client before preparing a quotation.'
            );
        }

        if (($inspection->bdc_annual ?? 0) <= 0) {
            return redirect()->route('inspections.phar-data', $inspection->id)
                ->with('error', 'Please save and review work assignment and costing before sharing the quotation.');
        }

        if ($inspection->status === 'completed') {
            return redirect()->route('inspections.show', $inspection->id)
                ->with('info', 'This diagnosis is already completed and cannot be re-shared.');
        }

        $allFindings = PHARFinding::with('affectedAreas')
            ->where('inspection_id', $inspection->id)
            ->orderBy('id')
            ->get();

        if ($allFindings->isEmpty()) {
            return redirect()->route('inspections.phar-data', $inspection->id)
                ->with('error', 'No findings found. Please add findings before sharing the quotation.');
        }

        $committedFindingIds = FindingClientDecision::query()
            ->where('inspection_id', $inspection->id)
            ->whereNull('inspection_quotation_id')
            ->whereIn('decision', ['immediate_remediation', 'commit'])
            ->pluck('phar_finding_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($committedFindingIds->isEmpty()) {
            return redirect()->route('inspections.phar-data', $inspection->id)
                ->with('error', 'The client has not committed to any findings, so there is no remediation scope to quote.');
        }

        $findings = $allFindings
            ->filter(fn (PHARFinding $finding) => $committedFindingIds->contains((int) $finding->id))
            ->values();

        if ($findings->isEmpty()) {
            return redirect()->route('inspections.phar-data', $inspection->id)
                ->with('error', 'The committed findings could not be matched to the current PHAR findings.');
        }

        $hourlyRate = (float) ($inspection->labour_hourly_rate ?? 165);
        $inspectionFindings = collect($inspection->findings ?? [])->values();
        $findingIndexById = $allFindings
            ->values()
            ->mapWithKeys(fn (PHARFinding $finding, int $index) => [(int) $finding->id => $index]);

        $findingsSnapshot = $findings->values()->map(function (PHARFinding $finding) use ($hourlyRate, $inspectionFindings, $findingIndexById) {
            $index = (int) ($findingIndexById->get((int) $finding->id, 0));
            $labourHours = (float) ($finding->labour_hours ?? 0);
            $materialCost = (float) ($finding->material_cost ?? 0);
            $jsonFinding = $inspectionFindings->get($index, []);
            $tradePricing = is_array($jsonFinding['trade_pricing'] ?? null) ? $jsonFinding['trade_pricing'] : [];
            $issueText = trim((string) ($jsonFinding['issue'] ?? $finding->task_question ?? ''));
            $issueDescription = trim((string) ($jsonFinding['issue_description'] ?? ''));
            $recommendationDetails = trim((string) ($jsonFinding['recommendation_details'] ?? ''));
            $recommendationList = collect(is_array($jsonFinding['recommendations'] ?? null)
                ? ($jsonFinding['recommendations'] ?? [])
                : preg_split('/\r\n|\r|\n|\|/', (string) ($jsonFinding['recommendations'] ?? '')))
                ->map(fn ($item) => trim((string) $item))
                ->filter()
                ->values()
                ->all();

            $materials = collect($jsonFinding['phar_materials'] ?? [])
                ->map(function ($material) {
                    return [
                        'material_name' => trim((string) ($material['material_name'] ?? '')),
                        'quantity' => (float) ($material['quantity'] ?? 0),
                        'unit' => trim((string) ($material['unit'] ?? 'ea')),
                        'unit_cost' => round((float) ($material['unit_cost'] ?? 0), 2),
                        'line_total' => round((float) ($material['line_total'] ?? 0), 2),
                        'notes' => trim((string) ($material['notes'] ?? '')),
                    ];
                })
                ->filter(fn ($material) => $material['material_name'] !== '')
                ->values()
                ->all();

            // Backward-compatibility: for legacy rows where PHARFinding.material_cost was
            // persisted as 0, recover from inspection findings JSON materials by index.
            if ($materialCost <= 0) {
                $materialCost = (float) collect($materials)
                    ->sum(fn($m) => (float) ($m['line_total'] ?? 0));
            }

            $tradeClientPrice = round((float) ($tradePricing['ETOGO_client_price'] ?? 0), 2);
            $tradeMaterialsIncluded = (bool) ($tradePricing['materials_included'] ?? $jsonFinding['trade_materials_included'] ?? false);
            $clientLabourCost = $tradeClientPrice > 0
                ? 0.0
                : round($labourHours * $hourlyRate, 2);
            if ($tradeMaterialsIncluded) {
                $materialCost = 0.0;
                $materials = [];
            }

            return [
                'id' => (int) $finding->id,
                'task_question' => $issueText !== '' ? $issueText : $finding->task_question,
                'issue' => $issueText !== '' ? $issueText : $finding->task_question,
                'issue_description' => $issueDescription,
                'recommendation' => $finding->task_question,
                'recommendations' => $recommendationList,
                'recommendation_details' => $recommendationDetails,
                'category' => $finding->category,
                'priority' => $finding->priority,
                'included_yn' => (bool) $finding->included_yn,
                'labour_hours' => round($labourHours, 2),
                'labour_cost' => $clientLabourCost,
                'material_cost' => round($materialCost, 2),
                'trade_cost' => round((float) ($tradePricing['trade_total_cost'] ?? 0), 2),
                'trade_client_price' => $tradeClientPrice,
                'trade_margin' => round((float) ($tradePricing['ETOGO_margin_amount'] ?? 0), 2),
                'trade_pricing' => $tradePricing,
                'notes' => $finding->notes,
                'materials' => $materials,
                'photo_ids' => is_array($finding->photo_ids) ? array_values($finding->photo_ids) : [],
                'affected_areas' => $finding->affectedAreas
                    ->map(fn ($area) => [
                        'building_system_id' => $area->building_system_id,
                        'building_subsystem_id' => $area->building_subsystem_id,
                        'building_component_id' => $area->building_component_id,
                        'system' => $area->system?->name,
                        'subsystem' => $area->subsystem?->name,
                        'component' => $area->component?->name,
                        'location' => $area->location,
                        'impact_description' => $area->impact_description,
                        'severity' => $area->severity,
                    ])
                    ->values()
                    ->all(),
            ];
        })->values()->all();

        $quotation = $this->createSharedQuotation($inspection, $findingsSnapshot);
        $this->activateSharedQuotation($inspection, $quotation, resetApprovalAt: true);
        $this->notifyClientQuotationShared($inspection, $quotation);

        return redirect()->route('inspections.phar-data', $inspection->id)
            ->with('success', 'Quotation shared successfully. Waiting for client selection before completing diagnosis.');
    }

    /**
     * Create and share a follow-up quotation using only deferred findings
     * from the current active quotation.
     */
    public function shareFollowupQuotation(Inspection $inspection)
    {
        if ($inspection->status === 'completed') {
            return redirect()->route('inspections.show', $inspection->id)
                ->with('info', 'This diagnosis is already completed. Follow-up quotation cannot be created here.');
        }

        $activeQuotation = InspectionQuotation::query()
            ->where('id', $inspection->active_quotation_id)
            ->where('inspection_id', $inspection->id)
            ->first();

        if (!$activeQuotation) {
            return redirect()->route('inspections.phar-data', $inspection->id)
                ->with('error', 'No active quotation found to build follow-up from.');
        }

        if (($activeQuotation->status ?? null) !== 'approved') {
            return redirect()->route('inspections.phar-data', $inspection->id)
                ->with('error', 'Follow-up quotation is available only after quotation approval.');
        }

        $snapshot = collect($activeQuotation->findings_snapshot ?? [])->values();
        $deferredIds = collect($activeQuotation->deferred_finding_ids ?? [])->map(fn ($id) => (int) $id)->values();

        if ($deferredIds->isEmpty()) {
            return redirect()->route('inspections.phar-data', $inspection->id)
                ->with('error', 'There are no deferred findings to create a follow-up quotation.');
        }

        $followupSnapshot = $snapshot
            ->filter(fn ($f) => $deferredIds->contains((int) ($f['id'] ?? 0)))
            ->values()
            ->all();

        if (empty($followupSnapshot)) {
            return redirect()->route('inspections.phar-data', $inspection->id)
                ->with('error', 'Deferred findings could not be resolved from snapshot data.');
        }

        $quotation = $this->createSharedQuotation($inspection, $followupSnapshot);
        $this->activateSharedQuotation($inspection, $quotation, resetApprovalAt: true);
        $this->notifyClientQuotationShared($inspection, $quotation);

        return redirect()->route('inspections.phar-data', $inspection->id)
            ->with('success', 'Follow-up quotation shared from deferred findings.');
    }

    private function generateUniqueQuoteNumber(int $inspectionId): string
    {
        $quoteNumber = null;

        do {
            $candidate = 'IQ-' . now()->format('Ymd') . '-I' . $inspectionId . '-' . strtoupper(Str::random(4));
            $exists = InspectionQuotation::where('quote_number', $candidate)->exists();
            if (!$exists) {
                $quoteNumber = $candidate;
            }
        } while ($quoteNumber === null);

        return $quoteNumber;
    }

    private function seedFindingsFromPropertyKnownIssues(Property $property, $systems): array
    {
        $defaultSystemId = (int) (optional($systems->firstWhere('name', 'General'))->id
            ?? optional($systems->first())->id
            ?? 0);

        if ($defaultSystemId <= 0) {
            return [];
        }

        $details = collect($property->known_problem_details ?? [])
            ->filter(fn ($item) => is_array($item) && $this->sanitizeDiagnosisText($item['issue'] ?? '') !== '')
            ->map(fn ($item) => [
                'area' => trim((string) ($item['area'] ?? 'Unknown / not sure')),
                'issue' => $this->sanitizeDiagnosisText($item['issue'] ?? ''),
            ])
            ->values();

        if ($details->isEmpty()) {
            $details = collect($this->normalizeKnownIssueText($property->known_problems))
                ->map(fn ($issue) => [
                    'area' => 'Unknown / not sure',
                    'issue' => $issue,
                ]);
        }

        return $details
            ->map(function (array $item) use ($systems, $defaultSystemId) {
                $area = trim((string) ($item['area'] ?? 'Unknown / not sure'));
                $issue = $this->sanitizeDiagnosisText($item['issue'] ?? '');
                if ($issue === '') {
                    return null;
                }

                $areaLabel = $area !== '' ? $area : 'Unknown / not sure';

                return [
                    'building_system_id' => $this->resolveKnownIssueSystemId($areaLabel, $systems, $defaultSystemId),
                    'building_subsystem_id' => null,
                    'building_component_id' => null,
                    'issue' => $issue,
                    'issue_description' => 'Client reported under ' . $areaLabel . ': ' . $issue,
                    'location' => $areaLabel === 'Unknown / not sure' ? '' : $areaLabel,
                    'spot' => '',
                    'severity' => 'medium',
                    'notes' => 'Seeded from property known issues. Confirm system, subsystem, severity, labour, and materials on site.',
                    'recommendations' => [],
                    'risk_impact' => '',
                    'phar_labour_hours' => 0,
                    'phar_category' => '',
                    'phar_included_yn' => true,
                    'phar_notes' => '',
                    'materials' => [],
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function resolveKnownIssueSystemId(string $area, $systems, int $defaultSystemId): int
    {
        $normalizedArea = Str::of($area)->lower()->trim()->toString();
        if ($normalizedArea === '' || $normalizedArea === 'unknown / not sure') {
            return $defaultSystemId;
        }

        $exact = $systems->first(function ($system) use ($normalizedArea) {
            return Str::of((string) $system->name)->lower()->trim()->toString() === $normalizedArea;
        });

        if ($exact) {
            return (int) $exact->id;
        }

        $partial = $systems->first(function ($system) use ($normalizedArea) {
            $systemName = Str::of((string) $system->name)->lower()->trim()->toString();

            return Str::contains($systemName, $normalizedArea)
                || Str::contains($normalizedArea, $systemName);
        });

        return (int) ($partial->id ?? $defaultSystemId);
    }

    private function normalizeKnownIssueText($value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map(fn ($item) => $this->sanitizeDiagnosisText($item), $value), fn ($item) => $item !== ''));
        }

        $raw = $this->sanitizeDiagnosisText($value);
        if ($raw === '' || strtolower($raw) === 'null') {
            return [];
        }

        return array_values(array_filter(array_map(fn ($item) => $this->sanitizeDiagnosisText($item), preg_split('/[,\n]+/', $raw)), fn ($item) => $item !== ''));
    }

    private function sanitizeDiagnosisFindingsForForm(array $findings): array
    {
        return collect($findings)
            ->map(function ($finding) {
                if (!is_array($finding)) {
                    return null;
                }

                foreach (['issue', 'issue_description', 'risk_impact', 'location', 'spot', 'notes', 'recommendation_details'] as $field) {
                    if (array_key_exists($field, $finding)) {
                        $finding[$field] = $this->sanitizeDiagnosisText($finding[$field]);
                    }
                }

                if (isset($finding['recommendations']) && is_array($finding['recommendations'])) {
                    $finding['recommendations'] = array_values(array_filter(
                        array_map(fn ($item) => $this->sanitizeDiagnosisText($item), $finding['recommendations']),
                        fn ($item) => $item !== ''
                    ));
                }

                return $this->hasDiagnosisFindingContent($finding) ? $finding : null;
            })
            ->filter()
            ->values()
            ->all();
    }

    private function hasDiagnosisFindingContent(array $finding): bool
    {
        foreach (['issue', 'issue_description', 'risk_impact', 'location', 'notes', 'recommendation_details'] as $field) {
            if (trim((string) ($finding[$field] ?? '')) !== '') {
                return true;
            }
        }

        return !empty($finding['recommendations']);
    }

    private function sanitizeDiagnosisText($value): string
    {
        $text = preg_replace('/\s+/', ' ', trim((string) $value)) ?? '';
        if ($text === '') {
            return '';
        }

        $blockedFragments = [
            'an error occurred while processing your inspection request',
            'an error occurred while processing your diagnosis request',
            'please try again',
            'an unexpected error occurred',
        ];

        foreach ($blockedFragments as $fragment) {
            $text = preg_replace('/' . preg_quote($fragment, '/') . '[\.\s]*/i', '', $text) ?? '';
        }

        return trim(preg_replace('/\s+/', ' ', $text) ?? '');
    }

    /**
     * Create a shared quotation record with default totals and validity.
     *
     * @param  array<int, array<string, mixed>>  $findingsSnapshot
     */
    private function createSharedQuotation(Inspection $inspection, array $findingsSnapshot): InspectionQuotation
    {
        $quoteNumber = $this->generateUniqueQuoteNumber($inspection->id);

        return DB::transaction(function () use ($inspection, $findingsSnapshot, $quoteNumber) {
            $now = now();
            $expiresAt = $now->copy()->addDays(14);

            return InspectionQuotation::create([
                'inspection_id' => $inspection->id,
                'property_id' => $inspection->property_id,
                'project_id' => $inspection->project_id,
                'created_by' => Auth::id(),
                'quote_number' => $quoteNumber,
                'status' => 'shared',
                'findings_snapshot' => $findingsSnapshot,
                'approved_finding_ids' => [],
                'deferred_finding_ids' => [],
                'approved_labour_cost' => 0,
                'approved_material_cost' => 0,
                'approved_trade_cost' => 0,
                'approved_trade_client_price' => 0,
                'approved_trade_margin' => 0,
                'approved_bdc_cost' => 0,
                'approved_total' => 0,
                'shared_at' => $now,
                'expires_at' => $expiresAt,
                'valid_until' => $expiresAt->toDateString(),
            ]);
        });
    }

    private function activateSharedQuotation(Inspection $inspection, InspectionQuotation $quotation, bool $resetApprovalAt = false): void
    {
        $previousActiveQuotationId = (int) ($inspection->active_quotation_id ?? 0);

        if ($previousActiveQuotationId > 0 && $previousActiveQuotationId !== (int) $quotation->id) {
            InspectionQuotation::query()
                ->where('id', $previousActiveQuotationId)
                ->where('inspection_id', $inspection->id)
                ->whereIn('status', ['shared', 'client_reviewing', 'client_responded'])
                ->update([
                    'status' => 'expired',
                    'expires_at' => now(),
                    'updated_at' => now(),
                ]);
        }

        $updates = [
            'active_quotation_id' => $quotation->id,
            'status' => 'quotation_shared',
            'quotation_status' => 'shared',
            'quotation_shared_at' => now(),
        ];

        if ($resetApprovalAt) {
            $updates['quotation_approved_at'] = null;
        }

        $inspection->update($updates);
    }

    private function notifyClientQuotationShared(Inspection $inspection, InspectionQuotation $quotation): void
    {
        $clientUser = $this->resolveInspectionClientUser($inspection);

        if (!$clientUser) {
            return;
        }

        $clientUser->notify(new QuotationSharedNotification(
            inspectionId: (int) $inspection->id,
            propertyId: $inspection->property_id ? (int) $inspection->property_id : null,
            propertyName: (string) ($inspection->property?->property_name ?? 'your property'),
            quoteNumber: (string) ($quotation->quote_number ?? 'N/A'),
        ));
    }

    /**
     * @param  array<int, string>  $visitDates
     */
    private function notifyClientWorkSchedulePublished(Inspection $inspection, array $visitDates): void
    {
        $clientUser = $this->resolveInspectionClientUser($inspection);

        if (!$clientUser || empty($visitDates)) {
            return;
        }

        $formattedDates = collect($visitDates)
            ->map(fn (string $date) => Carbon::parse($date)->format('M d, Y'))
            ->values()
            ->all();

        $clientUser->notify(new WorkSchedulePublishedNotification(
            inspectionId: (int) $inspection->id,
            propertyId: $inspection->property_id ? (int) $inspection->property_id : null,
            propertyName: (string) ($inspection->property?->property_name ?? 'your property'),
            visitDates: $formattedDates,
        ));
    }

    private function notifyClientAssessmentCompleted(Inspection $inspection): void
    {
        $clientUser = $this->resolveInspectionClientUser($inspection);

        if (!$clientUser) {
            return;
        }

        $clientUser->notify(new AssessmentCompletedNotification(
            inspectionId: (int) $inspection->id,
            propertyId: $inspection->property_id ? (int) $inspection->property_id : null,
            propertyName: (string) ($inspection->property?->property_name ?? 'your property'),
        ));
    }

    private function resolveInspectionClientUser(Inspection $inspection)
    {
        $inspection->loadMissing('property.user');

        return $inspection->property?->user;
    }

    public function completeAssessment(Inspection $inspection)
    {
        if (($inspection->bdc_annual ?? 0) <= 0) {
            return redirect()->route('inspections.phar-data', $inspection->id)
                ->with('error', 'Please save and review work assignment and costing before completing the diagnosis.');
        }

        if (($inspection->quotation_status ?? null) !== 'approved') {
            return redirect()->route('inspections.phar-data', $inspection->id)
                ->with('error', 'Please share the quotation and wait for client approval before completing the diagnosis.');
        }

        if ($inspection->status === 'completed') {
            return redirect()->route('inspections.show', $inspection->id)
                ->with('info', 'This diagnosis has already been completed.');
        }

        $inspection->update([
            'status'         => 'completed',
            'completed_date' => now(),
        ]);
        $inspection->property()
            ->where('status', '!=', 'archived')
            ->update(['status' => 'assessed']);

        // Re-lock pricing from approved quotation + authoritative material total.
        $inspection = $this->reconcileApprovedQuotationPricing($inspection->fresh());

        $inspection = $this->agreementScheduleService->refresh($inspection);
        $inspection = $inspection->fresh(['property.user', 'project']);
        $this->ensureClientInvoiceFromInspection($inspection);
        $this->notifyClientAssessmentCompleted($inspection);

        return redirect()->route('inspections.show', $inspection->id)
            ->with('success', 'Diagnosis completed successfully! The client has been notified.');
    }

    /**
     * Admin-facing finding photo upload (used from preview-report).
     */
    public function addFindingPhotos(Request $request, Inspection $inspection, int $findingIndex)
    {
        $validated = $request->validate([
            'finding_photos'   => 'required|array|min:1',
            'finding_photos.*' => 'required|file|mimetypes:image/jpeg,image/png,image/webp,image/gif,image/heic,image/heif,video/mp4,video/webm,video/quicktime,video/x-msvideo,video/x-matroska|max:51200',
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

        $jsonFinding = $findings[$findingIndex] ?? [];
        $pharFinding = $inspection->pharFindings()
            ->orderBy('id')
            ->get()
            ->first(function ($row, $idx) use ($findingIndex, $jsonFinding) {
                if ((int) $idx === (int) $findingIndex) {
                    return true;
                }

                $jsonTitle = trim((string) ($jsonFinding['issue'] ?? $jsonFinding['finding'] ?? $jsonFinding['task_question'] ?? ''));
                $rowTitle = trim((string) ($row->task_question ?? ''));

                return $jsonTitle !== '' && $jsonTitle === $rowTitle;
            });

        if ($pharFinding) {
            $pharFinding->photo_ids = array_values(array_unique(array_merge(
                (array) ($pharFinding->photo_ids ?? []),
                $findings[$findingIndex]['finding_photos']
            )));
            $pharFinding->save();
        }

        return back()->with('success', count($newPaths) . ' evidence file(s) uploaded successfully.');
    }

    /**
     * Admin preview of the client-facing inspection report (read-only, no auth check on ownership).
     */
    public function previewReport(Inspection $inspection)
    {
        $inspection = $this->agreementScheduleService->refresh($inspection);
        $inspection->loadMissing([
            'property',
            'activeMatterportModel',
            'activeSpatialModels.captureSession',
            'twinSourceFiles.childSourceFiles',
            'twinProcessingJobs.sourceFile',
            'issueMarkers.spatialModel',
            'issueMarkers.captureSession',
            'issueMarkers.pharFinding',
        ]);

        $activeQuotation = null;
        if (!empty($inspection->active_quotation_id)) {
            $activeQuotation = InspectionQuotation::query()
                ->where('id', $inspection->active_quotation_id)
                ->where('inspection_id', $inspection->id)
                ->first();
        }

        // If active quotation is not yet approved (e.g. a follow-up re-share pending),
        // fall back to the most recently approved quotation so the report scope filter
        // correctly shows only previously-approved findings instead of all findings.
        if (($activeQuotation?->status ?? null) !== 'approved') {
            $approvedQuotation = InspectionQuotation::query()
                ->where('inspection_id', $inspection->id)
                ->where('status', 'approved')
                ->orderBy('id', 'desc')
                ->first();
            if ($approvedQuotation) {
                $activeQuotation = $approvedQuotation;
            }
        }

        $findings = \App\Models\PHARFinding::with('affectedAreas')
            ->where('inspection_id', $inspection->id)
            ->orderBy('id')
            ->get();

        $materials = \App\Models\InspectionMaterial::where('inspection_id', $inspection->id)
            ->orderBy('id')
            ->get();

        $adminPreview = true;

        return view('client.inspections.report', compact('inspection', 'findings', 'materials', 'adminPreview', 'activeQuotation'));
    }

    /**
     * Admin preview of the client-facing agreement/contract (read-only, no auth check on ownership).
     */
    public function previewAgreement(Request $request, Inspection $inspection)
    {
        $inspection = $this->agreementScheduleService->refresh($inspection);
        $adminPreview = true;
        $forCountersign = (bool) $request->boolean('for_countersign', false);
        return view('client.inspections.agreement', compact('inspection', 'adminPreview', 'forCountersign'));
    }

    /**
     * Admin download of the client-facing agreement PDF.
     */
    public function downloadAgreementPdf(Inspection $inspection)
    {
        if (($inspection->status ?? null) !== 'completed') {
            return redirect()->route('inspections.index')
                ->with('error', 'Agreement PDF is available only after inspection completion.');
        }

        $inspection = $this->agreementScheduleService->refresh($inspection);

        $pdf = Pdf::loadView('client.inspections.agreement-pdf', compact('inspection'))
            ->setPaper('a4', 'portrait')
            ->setOption('margin-top', 12)
            ->setOption('margin-right', 10)
            ->setOption('margin-bottom', 12)
            ->setOption('margin-left', 10)
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true);

        $clientName = Str::slug((string) ($inspection->property?->user?->name ?? 'client'));
        $propertyName = Str::slug((string) ($inspection->property?->property_name ?? $inspection->property?->property_code ?? 'property'));
        $filename = 'Client_Agreement_' . $clientName . '_' . $propertyName . '.pdf';

        return $pdf->download($filename);
    }

    protected function ensureClientInvoiceFromInspection(Inspection $inspection): void
    {
        $this->inspectionInvoiceSyncService->syncProjectInvoice($inspection);
    }

    /**
     * Keep approved quotation pricing and inspection pricing fields in sync.
     *
     * Labour is derived from approved findings in quotation snapshot.
     * Material is derived from inspection_materials (authoritative source).
     */
    private function reconcileApprovedQuotationPricing(Inspection $inspection, ?InspectionQuotation $activeQuotation = null): Inspection
    {
        if (($inspection->quotation_status ?? null) !== 'approved') {
            return $inspection;
        }

        $quotation = $activeQuotation;
        if (!$quotation && !empty($inspection->active_quotation_id)) {
            $quotation = InspectionQuotation::query()
                ->where('id', $inspection->active_quotation_id)
                ->where('inspection_id', $inspection->id)
                ->first();
        }

        if (!$quotation || ($quotation->status ?? null) !== 'approved') {
            return $inspection;
        }

        $approvedIds = collect($quotation->approved_finding_ids ?? [])
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->values();

        $snapshot = collect($quotation->findings_snapshot ?? [])->values();

        // Repair legacy snapshot material values (saved as 0 in older records)
        // so approved pricing remains scoped to approved findings.
        if ($snapshot->sum(fn($f) => (float) ($f['material_cost'] ?? 0)) <= 0) {
            $pharMaterialById = $inspection->pharFindings()
                ->get(['id', 'material_cost'])
                ->mapWithKeys(fn($f) => [(int) $f->id => (float) ($f->material_cost ?? 0)]);

            $inspectionFindings = collect($inspection->findings ?? [])->values();

            $snapshot = $snapshot->values()->map(function ($finding, $index) use ($pharMaterialById, $inspectionFindings) {
                $materialCost = (float) ($finding['material_cost'] ?? 0);
                $jsonFinding = $inspectionFindings->get($index, []);
                $tradePricing = is_array($finding['trade_pricing'] ?? null) ? $finding['trade_pricing'] : [];
                $tradeMaterialsIncluded = (bool) ($tradePricing['materials_included'] ?? $jsonFinding['trade_materials_included'] ?? false);

                if ($tradeMaterialsIncluded) {
                    $finding['material_cost'] = 0.0;
                    $finding['materials'] = [];
                    return $finding;
                }

                if ($materialCost <= 0) {
                    $findingId = (int) ($finding['id'] ?? 0);
                    $materialCost = (float) ($pharMaterialById->get($findingId, 0));
                }

                if ($materialCost <= 0) {
                    $materialCost = (float) collect($jsonFinding['phar_materials'] ?? [])
                        ->sum(fn($m) => (float) ($m['line_total'] ?? 0));
                }

                $finding['material_cost'] = round($materialCost, 2);
                return $finding;
            })->values();
        }

        $approvedFindings = $snapshot->filter(fn($f) => $approvedIds->contains((int) ($f['id'] ?? 0)))->values();
        $approvedLabour = round((float) $approvedFindings->sum(function ($f) {
            $labour = (float) ($f['labour_cost'] ?? 0);
            $tradeClientPrice = (float) ($f['trade_client_price'] ?? data_get($f, 'trade_pricing.ETOGO_client_price', 0));

            return $tradeClientPrice > 0 && abs($labour - $tradeClientPrice) < 0.01
                ? 0.0
                : $labour;
        }), 2);
        $approvedMaterial = round((float) $approvedFindings->sum(fn($f) => (float) ($f['material_cost'] ?? 0)), 2);
        $approvedTradeCost = round((float) $approvedFindings->sum(fn($f) => (float) ($f['trade_cost'] ?? data_get($f, 'trade_pricing.trade_total_cost', 0))), 2);
        $approvedTradeClientPrice = round((float) $approvedFindings->sum(fn($f) => (float) ($f['trade_client_price'] ?? data_get($f, 'trade_pricing.ETOGO_client_price', 0))), 2);
        $approvedTradeMargin = round((float) $approvedFindings->sum(fn($f) => (float) ($f['trade_margin'] ?? data_get($f, 'trade_pricing.ETOGO_margin_amount', 0))), 2);

        // Fallback for legacy quotations where snapshot values may be incomplete.
        if ($approvedLabour <= 0) {
            $approvedLabour = round((float) ($quotation->approved_labour_cost ?? 0), 2);
        }
        if ($approvedMaterial <= 0 && (float) ($quotation->approved_material_cost ?? 0) > 0) {
            $approvedMaterial = round((float) $quotation->approved_material_cost, 2);
        }
        if ($approvedTradeClientPrice <= 0 && (float) ($quotation->approved_trade_client_price ?? 0) > 0) {
            $approvedTradeCost = round((float) ($quotation->approved_trade_cost ?? 0), 2);
            $approvedTradeClientPrice = round((float) $quotation->approved_trade_client_price, 2);
            $approvedTradeMargin = round((float) ($quotation->approved_trade_margin ?? 0), 2);
        }

        // Recalculate visits and BDC from approved labour hours (1 visit = 11 working hours)
        // This ensures approved scope BDC matches the actual approved labour hours, not all-findings hours.
        $approvedLabourHours = round((float) $approvedFindings->sum(fn($f) => (float) ($f['labour_hours'] ?? 0)), 2);
        if ($approvedLabourHours <= 0) {
            $approvedLabourHours = round((float) ($approvedLabour / (float) ($inspection->labour_hourly_rate ?? 165)), 2);
        }
        $approvedVisits = max(1, (int) ceil($approvedLabourHours / 11));

        $bdcCalc = new BDCCalculator();
        $bdcResult = $bdcCalc->calculateWithParams([
            'travel_distance_km'  => (float) ($inspection->bdc_distance_km ?? null),
            'travel_time_minutes' => (float) ($inspection->bdc_time_minutes ?? null),
            'visits_per_year'     => (float) $approvedVisits,
            'rate_per_km'         => (float) ($inspection->bdc_rate_per_km ?? 1.50),
            'rate_per_minute'     => (float) ($inspection->bdc_rate_per_minute ?? 1.65),
        ]);
        $approvedBdc = round((float) ($bdcResult['bdc_annual'] ?? 0), 2);

        $approvedTotal = round($approvedLabour + $approvedMaterial + $approvedTradeClientPrice + $approvedBdc, 2);

        $quotation->update([
            'approved_labour_cost' => $approvedLabour,
            'approved_material_cost' => $approvedMaterial,
            'approved_trade_cost' => $approvedTradeCost,
            'approved_trade_client_price' => $approvedTradeClientPrice,
            'approved_trade_margin' => $approvedTradeMargin,
            'approved_bdc_cost' => $approvedBdc,
            'approved_total' => $approvedTotal,
        ]);

        $inspection->update([
            'frlc_annual' => $approvedLabour,
            'fmc_annual' => $approvedMaterial,
            'trade_cost_annual' => $approvedTradeCost,
            'trade_client_price_annual' => $approvedTradeClientPrice,
            'trade_margin_annual' => $approvedTradeMargin,
            'bdc_annual' => $approvedBdc,
            'bdc_visits_per_year' => $approvedVisits,
            'estimated_task_hours' => $approvedLabourHours,
            'trc_annual' => $approvedTotal,
            'trc_monthly' => $approvedTotal,
            'trc_per_visit' => round($approvedTotal / $approvedVisits, 2),
            'arp_monthly' => $approvedTotal,
            'scientific_final_monthly' => $approvedTotal,
            'scientific_final_annual' => $approvedTotal,
            'arp_equivalent_final' => $approvedTotal,
            'base_package_price_snapshot' => $approvedTotal,
        ]);

        return $inspection->fresh();
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
            // NOTE: hours_per_visit intentionally omitted so the BDCCalculator uses
            // the system-configured default (e.g. 4.5 h). estimated_task_hours is
            // the total remediation labour, NOT the duration of a single BDC visit.
            $result = $calc->calculateWithParams([
                'visits_per_year' => (float) ($inspection->bdc_visits_per_year ?? 1),
            ]);
        }

        $bdc    = $result['bdc_annual'];
        $visits = max(1.0, (float) ($inspection->bdc_visits_per_year ?? 1));
        $tradeItems = \App\Models\InspectionTradePricingItem::where('inspection_id', $inspection->id)->get();
        $tradeClientPrice = (float) $tradeItems->sum('ETOGO_client_price');
        $tradeCost = (float) $tradeItems->sum('trade_total_cost');
        $tradeMargin = (float) $tradeItems->sum('ETOGO_margin_amount');
        $tradePharFindingIds = $tradeItems->pluck('phar_finding_id')->filter()->map(fn ($id) => (int) $id)->unique();
        $hourlyRate = (float) ($inspection->labour_hourly_rate ?? \App\Models\BDCSetting::getValue('loaded_hourly_rate', 165) ?? 165);
        $nonTradeLabour = (float) \App\Models\PHARFinding::where('inspection_id', $inspection->id)
            ->when($tradePharFindingIds->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $tradePharFindingIds))
            ->get()
            ->sum(fn ($finding) => (float) ($finding->labour_hours ?? 0) * $hourlyRate);
        $frlc = $tradeItems->isNotEmpty()
            ? round($nonTradeLabour, 2)
            : (float) ($inspection->frlc_annual ?? 0);
        $trc = $bdc + $frlc + (float) ($inspection->fmc_annual ?? 0) + $tradeClientPrice;

        if ($onlyIfChanged && round((float) $inspection->bdc_annual, 2) === round($bdc, 2)) {
            return;
        }

        $inspection->update([
            'bdc_annual'                  => $bdc,
            'bdc_per_visit'               => round($bdc / $visits, 2),
            'trade_cost_annual'           => $tradeCost,
            'trade_client_price_annual'   => $tradeClientPrice,
            'trade_margin_annual'         => $tradeMargin,
            'frlc_annual'                 => $frlc,
            'frlc_monthly'                => $frlc,
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
