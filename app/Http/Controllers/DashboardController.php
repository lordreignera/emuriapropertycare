<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Property;
use App\Models\Inspection;
use App\Models\InspectionToolAssignment;
use App\Models\Project;
use App\Models\Invoice;
use App\Models\Subscription;
use App\Models\ToolSetting;
use App\Models\TradeApplication;
use App\Models\TradePartner;
use App\Models\User;

class DashboardController extends Controller
{
    /**
     * Display the dashboard
     */
    public function index()
    {
        $user = auth()->user();

        // ── Store Manager dashboard ──────────────────────────────────────────
        if ($user->hasRole('Store Manager')) {
            // KPI cards
            $totalTools       = ToolSetting::where('is_active', true)->count();
            $toolsInUse       = InspectionToolAssignment::whereNull('returned_at')
                                    ->where('quantity', '>', 0)
                                    ->sum('quantity');
            $toolsHired       = ToolSetting::where('is_active', true)
                                    ->where('ownership_status', 'hired')->count();
            $toolsOwned       = ToolSetting::where('is_active', true)
                                    ->where('ownership_status', 'owned')->count();

            // System overview stats
            $pendingToolAssignment = Inspection::whereNotNull('client_signature')
                ->where('work_payment_status', 'paid')
                ->whereNull('etogo_signed_at')
                ->whereDoesntHave('toolAssignments', function ($q) {
                    $q->whereNull('returned_at')->where('quantity', '>', 0);
                })->count();

            $unreturnedRecords = InspectionToolAssignment::whereNull('returned_at')
                ->where('quantity', '>', 0)
                ->count();

            $availableTools = ToolSetting::where('is_active', true)
                ->where('availability_status', 'available')->count();

            // Recent activity: latest 10 tool assignments
            $recentAssignments = InspectionToolAssignment::with([
                    'inspection.property:id,property_name,property_code',
                    'toolSetting:id,tool_name',
                ])
                ->latest()
                ->take(10)
                ->get();

            return view('admin.index', compact(
                'totalTools', 'toolsInUse', 'toolsHired', 'toolsOwned',
                'pendingToolAssignment', 'unreturnedRecords', 'availableTools',
                'recentAssignments'
            ));
        }

        // ── Generic staff dashboard ──────────────────────────────────────────
        if ($user->isStaff()) {
            $propertiesCount = Property::count();
            $inspectionsCount = Inspection::where('status', '!=', 'cancelled')
                ->distinct('property_id')
                ->count('property_id');
            $paidInspectionsCount = Inspection::where('inspection_fee_status', 'paid')
                ->where('status', '!=', 'cancelled')
                ->distinct('property_id')
                ->count('property_id');
            $completedInspectionsCount = Inspection::where('status', 'completed')
                ->distinct('property_id')
                ->count('property_id');

            // Active projects KPI should reflect ongoing maintenance work,
            // not only project.status flag values.
            $projectsCount = Inspection::query()
                ->whereNotNull('etogo_signed_at')
                ->whereNotNull('work_schedule')
                ->where('work_schedule', '!=', '[]')
                ->get()
                ->filter(function (Inspection $inspection) {
                    $schedule = collect($inspection->work_schedule ?? []);
                    $totalVisits = $schedule->count();
                    if ($totalVisits === 0) {
                        return false;
                    }

                    $doneVisits = $schedule->where('status', 'completed')->count();
                    $progressPct = (int) round(($doneVisits / $totalVisits) * 100);

                    return $progressPct < 100;
                })
                ->count();

            $invoicesCount = Invoice::count();
            $openTradeApplicationsCount = $user->hasRole(['Super Admin', 'Administrator'])
                ? TradeApplication::whereIn('status', ['submitted', 'ready_for_review', 'needs_more_information', 'conditionally_approved'])->count()
                : 0;
            $approvedTradeApplicationsCount = $user->hasRole(['Super Admin', 'Administrator'])
                ? TradePartner::where('status', TradePartner::STATUS_ACTIVE)->count()
                : 0;
            
            // Get active subscription
            $subscription = Subscription::where('user_id', $user->id)
                ->where('status', 'active')
                ->with('tier')
                ->first();

            // Build recent activities from latest properties, diagnosis records, and invoices.
            $propertyActivities = Property::query()
                ->latest('created_at')
                ->take(5)
                ->get()
                ->map(function (Property $property) {
                    return (object) [
                        'created_at' => $property->created_at,
                        'description' => 'Property registered',
                        'property' => $property,
                        'status' => ucfirst(str_replace('_', ' ', (string) ($property->status ?? 'registered'))),
                        'status_color' => match ((string) ($property->status ?? '')) {
                            'registered' => 'info',
                            'awaiting_inspection' => 'warning',
                            'in_assessment' => 'primary',
                            'assessed' => 'success',
                            'archived' => 'secondary',
                            default => 'secondary',
                        },
                    ];
                });

            $inspectionActivities = Inspection::query()
                ->with('property')
                ->latest('created_at')
                ->take(5)
                ->get()
                ->map(function (Inspection $inspection) {
                    return (object) [
                        'created_at' => $inspection->created_at,
                        'description' => 'Diagnosis ' . ucfirst(str_replace('_', ' ', (string) ($inspection->status ?? 'scheduled'))),
                        'property' => $inspection->property,
                        'status' => ucfirst(str_replace('_', ' ', (string) ($inspection->status ?? 'scheduled'))),
                        'status_color' => match ((string) ($inspection->status ?? '')) {
                            'completed' => 'success',
                            'in_progress' => 'info',
                            'scheduled' => 'warning',
                            'cancelled' => 'danger',
                            default => 'secondary',
                        },
                    ];
                });

            $invoiceActivities = Invoice::query()
                ->with(['project.property'])
                ->latest('created_at')
                ->take(5)
                ->get()
                ->map(function (Invoice $invoice) {
                    $property = $invoice->project?->property;
                    return (object) [
                        'created_at' => $invoice->created_at,
                        'description' => 'Invoice ' . strtoupper((string) ($invoice->invoice_number ?? ('#' . $invoice->id))),
                        'property' => $property,
                        'status' => ucfirst(str_replace('_', ' ', (string) ($invoice->status ?? 'sent'))),
                        'status_color' => match ((string) ($invoice->status ?? '')) {
                            'paid' => 'success',
                            'partial' => 'warning',
                            'sent' => 'info',
                            'pending', 'overdue' => 'danger',
                            default => 'secondary',
                        },
                    ];
                });

            $recentActivities = $propertyActivities
                ->concat($inspectionActivities)
                ->concat($invoiceActivities)
                ->sortByDesc('created_at')
                ->take(10)
                ->values();

            $recentProperties = Property::query()
                ->latest('created_at')
                ->take(5)
                ->get();

            $upcomingInspections = Inspection::query()
                ->with('property')
                ->where('status', 'scheduled')
                ->whereNotNull('scheduled_date')
                ->orderBy('scheduled_date')
                ->take(5)
                ->get();

            $newRegistrationsCount = Property::where('status', 'registered')->count();
            $pendingInvoicesCount = Invoice::pending()->count();
            $totalUsersCount = User::count();
            $awaitingContactCount = Property::where('status', 'registered')->count();
            $propertyFactsPendingCount = Property::query()
                ->whereIn('status', ['awaiting_inspection', 'in_assessment'])
                ->whereDoesntHave('spatialModels', function ($query) {
                    $query->where('status', 'active');
                })
                ->count();
            $invoiceNeededCount = Property::query()
                ->whereHas('spatialModels', function ($query) {
                    $query->where('status', 'active');
                })
                ->whereDoesntHave('projects.invoices', function ($query) {
                    $query->where(function ($invoiceQuery) {
                        $invoiceQuery
                            ->where('notes', 'like', '%Property facts and diagnosis%')
                            ->orWhere('invoice_number', 'like', 'INV-DIAG-%');
                    });
                })
                ->count();
            $diagnosisInProgressCount = Inspection::whereIn('status', [
                'scheduled',
                'in_progress',
                'findings_captured',
                'findings_shared',
                'client_committed',
                'estimation_in_progress',
                'estimation_completed',
                'quotation_shared',
                'quotation_approved',
            ])->count();

            return view('admin.index', compact(
                'propertiesCount',
                'inspectionsCount',
                'paidInspectionsCount',
                'completedInspectionsCount',
                'projectsCount',
                'invoicesCount',
                'openTradeApplicationsCount',
                'approvedTradeApplicationsCount',
                'subscription',
                'recentActivities',
                'recentProperties',
                'upcomingInspections',
                'newRegistrationsCount',
                'pendingInvoicesCount',
                'totalUsersCount',
                'awaitingContactCount',
                'propertyFactsPendingCount',
                'invoiceNeededCount',
                'diagnosisInProgressCount'
            ));
        }
        
        // Client Dashboard
        if ($user->hasRole('Client')) {
            // Get user's property IDs first
            $propertyIds = Property::where('user_id', $user->id)->pluck('id');

            $this->syncClientInspectionInvoices((int) $user->id, $propertyIds->all());
            
            // Count properties
            $propertiesCount = $propertyIds->count();
            
            // Count projects for user's properties
            $projectsCount = Project::whereIn('property_id', $propertyIds)
                ->where('status', 'active')
                ->count();
            
            // Count diagnoses as unique completed properties (latest completed report per property)
            $projectIds = Project::whereIn('property_id', $propertyIds)->pluck('id');
            $inspectionsCount = Inspection::whereIn('property_id', $propertyIds)
                ->where('status', 'completed')
                ->distinct('property_id')
                ->count('property_id');

            // Count properties with diagnosis fee paid
            $paidInspectionsCount = Inspection::whereIn('property_id', $propertyIds)
                ->where('inspection_fee_status', 'paid')
                ->distinct('property_id')
                ->count('property_id');

            // Count paid diagnoses that are not yet completed
            $paidPendingInspectionsCount = max($paidInspectionsCount - $inspectionsCount, 0);
            
            // Count unpaid invoices for KPI
            $unpaidInvoices = Invoice::where('user_id', $user->id)
                ->pending()
                ->count();

            // Keep total invoices for optional secondary display
            $invoicesCount = Invoice::where('user_id', $user->id)->count();

            // Invoice breakdown (inspection fee vs work payment) + paid/pending
            $inspectionInvoicesCount = Invoice::where('user_id', $user->id)
                ->where('type', 'additional')
                ->count();

            $inspectionInvoicesPaidCount = Invoice::where('user_id', $user->id)
                ->where('type', 'additional')
                ->paid()
                ->count();

            $inspectionInvoicesPendingCount = Invoice::where('user_id', $user->id)
                ->where('type', 'additional')
                ->pending()
                ->count();

            $workPaymentInvoicesCount = Invoice::where('user_id', $user->id)
                ->where('type', 'project')
                ->count();

            $workPaymentInvoicesPaidCount = Invoice::where('user_id', $user->id)
                ->where('type', 'project')
                ->paid()
                ->count();

            $workPaymentInvoicesPendingCount = Invoice::where('user_id', $user->id)
                ->where('type', 'project')
                ->pending()
                ->count();
                
            // Get pending diagnoses
            $pendingInspections = Inspection::whereIn('project_id', $projectIds)
                ->where('status', 'scheduled')
                ->count();
            
            // Get active subscription
            $subscription = Subscription::where('user_id', $user->id)
                ->where('status', 'active')
                ->first();
                
            // Get recent properties
            $recentProperties = Property::where('user_id', $user->id)
                ->latest()
                ->take(5)
                ->get();

            // Completed inspections with pricing breakdown visible to client
            $latestCompletedInspectionIds = Inspection::whereIn('property_id', $propertyIds)
                ->where('status', 'completed')
                ->selectRaw('MAX(id) as id')
                ->groupBy('property_id')
                ->pluck('id');

            $completedInspections = Inspection::with(['property', 'project'])
                ->whereIn('id', $latestCompletedInspectionIds)
                ->whereIn('property_id', $propertyIds)
                ->where('status', 'completed')
                ->orderByDesc('completed_date')
                ->orderByDesc('id')
                ->take(5)
                ->get();

            $quotationReadyInspections = Inspection::with(['property', 'project'])
                ->whereIn('property_id', $propertyIds)
                ->where('status', '!=', 'completed')
                ->whereNotNull('active_quotation_id')
                ->whereIn('quotation_status', ['shared', 'client_reviewing', 'approved'])
                ->orderByDesc('quotation_shared_at')
                ->orderByDesc('id')
                ->take(5)
                ->get();

            $findingsReadyInspections = Inspection::with(['property', 'project'])
                ->withCount('pharFindings')
                ->whereIn('property_id', $propertyIds)
                ->whereNotNull('findings_report_shared_at')
                ->whereNull('client_committed_at')
                ->where('status', '!=', 'completed')
                ->orderByDesc('findings_report_shared_at')
                ->orderByDesc('id')
                ->take(5)
                ->get();

            // Completed projects with outstanding balance (work done, payment pending)
            $completedWithBalance = Inspection::with('property')
                ->whereIn('property_id', $propertyIds)
                ->whereNotNull('completed_finding_ids')
                ->where('completed_finding_ids', '!=', '[]')
                ->where('work_payment_status', 'paid')
                ->whereNull('arp_fully_paid_at')
                ->get()
                ->filter(fn($i) => !empty($i->completed_finding_ids))
                ->values();

            $upcomingInspections = Inspection::with('property')
                ->whereIn('property_id', $propertyIds)
                ->where('status', 'scheduled')
                ->whereNotNull('scheduled_date')
                ->orderBy('scheduled_date')
                ->take(5)
                ->get();

            $invoiceActivities = Invoice::where('user_id', $user->id)
                ->latest('created_at')
                ->take(5)
                ->get()
                ->map(function (Invoice $invoice) {
                    return (object) [
                        'created_at' => $invoice->created_at,
                        'title' => 'Invoice ' . strtoupper((string) ($invoice->invoice_number ?? ('#' . $invoice->id))),
                        'description' => ucfirst((string) ($invoice->status ?? 'sent')) . ' invoice',
                        'icon' => 'mdi-file-document-outline',
                        'tone' => match ((string) ($invoice->status ?? '')) {
                            'paid' => 'success',
                            'partial' => 'warning',
                            'overdue' => 'danger',
                            default => 'info',
                        },
                    ];
                });

            $inspectionActivities = Inspection::whereIn('property_id', $propertyIds)
                ->latest('created_at')
                ->take(5)
                ->get()
                ->map(function (Inspection $inspection) {
                    return (object) [
                        'created_at' => $inspection->created_at,
                        'title' => 'Diagnosis ' . ucfirst(str_replace('_', ' ', (string) ($inspection->status ?? 'scheduled'))),
                        'description' => $inspection->property_name ?? $inspection->property?->property_name ?? 'Property diagnosis',
                        'icon' => 'mdi-clipboard-check-outline',
                        'tone' => match ((string) ($inspection->status ?? '')) {
                            'completed' => 'success',
                            'scheduled' => 'primary',
                            'in_progress' => 'info',
                            default => 'warning',
                        },
                    ];
                });

            $propertyActivities = $recentProperties
                ->map(function (Property $property) {
                    return (object) [
                        'created_at' => $property->created_at,
                        'title' => 'Property registered',
                        'description' => $property->property_name ?? $property->property_code ?? 'Property',
                        'icon' => 'mdi-home-city-outline',
                        'tone' => 'primary',
                    ];
                });

            $recentClientActivities = $propertyActivities
                ->concat($inspectionActivities)
                ->concat($invoiceActivities)
                ->sortByDesc('created_at')
                ->take(6)
                ->values();

            return view('client.dashboard', compact(
                'propertiesCount',
                'inspectionsCount',
                'paidInspectionsCount',
                'paidPendingInspectionsCount',
                'projectsCount',
                'invoicesCount',
                'unpaidInvoices',
                'inspectionInvoicesCount',
                'inspectionInvoicesPaidCount',
                'inspectionInvoicesPendingCount',
                'workPaymentInvoicesCount',
                'workPaymentInvoicesPaidCount',
                'workPaymentInvoicesPendingCount',
                'pendingInspections',
                'subscription',
                'recentProperties',
                'findingsReadyInspections',
                'completedInspections',
                'quotationReadyInspections',
                'completedWithBalance',
                'upcomingInspections',
                'recentClientActivities'
            ));
        }
        
        // Default for other roles
        $propertiesCount = 0;
        $inspectionsCount = 0;
        $paidInspectionsCount = 0;
        $completedInspectionsCount = 0;
        $projectsCount = 0;
        $invoicesCount = 0;
        $openTradeApplicationsCount = 0;
        $approvedTradeApplicationsCount = 0;
        $subscription = null;
        $recentActivities = collect();

        return view('admin.index', compact(
            'propertiesCount',
            'inspectionsCount',
            'paidInspectionsCount',
            'completedInspectionsCount',
            'projectsCount',
            'invoicesCount',
            'openTradeApplicationsCount',
            'approvedTradeApplicationsCount',
            'subscription',
            'recentActivities'
        ));
    }

    protected function syncClientInspectionInvoices(int $userId, array $propertyIds): void
    {
        if (empty($propertyIds)) {
            return;
        }

        $inspections = Inspection::with(['project', 'property'])
            ->whereIn('property_id', $propertyIds)
            ->where('status', 'completed')
            ->whereNotNull('project_id')
            ->orderByDesc('completed_date')
            ->orderByDesc('id')
            ->get();

        foreach ($inspections as $inspection) {
            $projectId = (int) ($inspection->project_id ?? 0);
            if ($projectId <= 0) {
                continue;
            }

            $existingInvoice = Invoice::where('user_id', $userId)
                ->where('project_id', $projectId)
                ->where('type', 'project')
                ->first();

            if ($existingInvoice) {
                continue;
            }

            $monthlyAmount = (float) max(
                (float) ($inspection->scientific_final_monthly ?? 0),
                (float) ($inspection->arp_equivalent_final ?? 0),
                (float) ($inspection->base_package_price_snapshot ?? 0),
                (float) ($inspection->trc_monthly ?? 0)
            );

            if ($monthlyAmount <= 0) {
                continue;
            }

            $invoiceNumber = 'INV-' . now()->format('Ymd') . '-' . $inspection->id;
            $counter = 1;
            while (Invoice::where('invoice_number', $invoiceNumber)->exists()) {
                $invoiceNumber = 'INV-' . now()->format('Ymd') . '-' . $inspection->id . '-' . $counter;
                $counter++;
            }

            Invoice::create([
                'invoice_number' => $invoiceNumber,
                'project_id' => $projectId,
                'user_id' => $userId,
                'type' => 'project',
                'subtotal' => $monthlyAmount,
                'tax' => 0,
                'total' => $monthlyAmount,
                'paid_amount' => 0,
                'balance' => $monthlyAmount,
                'status' => 'sent',
                'issue_date' => now()->toDateString(),
                'due_date' => now()->addDays(14)->toDateString(),
                'line_items' => [
                    [
                        'description' => 'Property Diagnosis Service - ' . ($inspection->property?->property_name ?? 'Property'),
                        'inspection_id' => $inspection->id,
                        'quantity' => 1,
                        'unit_price' => $monthlyAmount,
                        'total' => $monthlyAmount,
                    ],
                ],
                'notes' => 'Auto-generated from completed diagnosis #' . $inspection->id,
            ]);
        }
    }
}
