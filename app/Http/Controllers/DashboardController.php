<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Property;
use App\Models\Inspection;
use App\Models\Project;
use App\Models\Invoice;
use App\Models\Subscription;

class DashboardController extends Controller
{
    /**
     * Display the dashboard
     */
    public function index()
    {
        $user = auth()->user();
        
        // Technician Dashboard
        if ($user->hasRole('Technician')) {
            // Get projects assigned to this technician
            $assignedProjects = Project::where('assigned_to', $user->id)
                ->with(['property', 'property.user'])
                ->get();
            
            // Count projects by status
            $activeProjectsCount = Project::where('assigned_to', $user->id)
                ->where('status', 'active')
                ->count();
            
            $completedProjectsCount = Project::where('assigned_to', $user->id)
                ->where('status', 'completed')
                ->count();
            
            $pendingProjectsCount = Project::where('assigned_to', $user->id)
                ->where('status', 'pending')
                ->count();
            
            $onHoldProjectsCount = Project::where('assigned_to', $user->id)
                ->where('status', 'on_hold')
                ->count();
            
            // Get work logs for today
            $todayWorkLogs = \App\Models\WorkLog::whereHas('project', function($query) use ($user) {
                $query->where('assigned_to', $user->id);
            })->whereDate('created_at', today())->count();
            
            // Get upcoming projects
            $upcomingProjects = Project::where('assigned_to', $user->id)
                ->where('status', 'pending')
                ->with(['property', 'property.user'])
                ->orderBy('start_date', 'asc')
                ->take(5)
                ->get();

            return view('admin.technician-dashboard', compact(
                'assignedProjects',
                'activeProjectsCount',
                'completedProjectsCount',
                'pendingProjectsCount',
                'onHoldProjectsCount',
                'todayWorkLogs',
                'upcomingProjects'
            ));
        }
        
        // Finance Dashboard
        if ($user->hasRole('Finance')) {
            // Get invoice statistics
            $totalInvoices = Invoice::count();
            $paidInvoices = Invoice::paid()->count();
            $pendingInvoices = Invoice::pending()->count();
            $overdueInvoices = Invoice::where('status', 'overdue')->count();
            
            // Calculate revenue
            $totalRevenue = Invoice::sum('paid_amount');
            $pendingRevenue = Invoice::pending()->sum('balance');
            $monthlyRevenue = Invoice::whereNotNull('paid_at')
                ->whereMonth('paid_at', now()->month)
                ->sum('paid_amount');
            
            // Get recent invoices
            $recentInvoices = Invoice::with(['user'])
                ->latest()
                ->take(10)
                ->get();
            
            // Get overdue invoices
            $overdueInvoicesList = Invoice::where('status', 'overdue')
                ->with(['user'])
                ->orderBy('due_date', 'asc')
                ->take(5)
                ->get();
            
            // Get active subscriptions
            $activeSubscriptions = Subscription::where('status', 'active')->count();
            
            // Get subscription revenue
            $subscriptionRevenue = Subscription::where('status', 'active')
                ->with('tier')
                ->get()
                ->sum(function($sub) {
                    return $sub->tier->price ?? 0;
                });

            return view('admin.finance-dashboard', compact(
                'totalInvoices',
                'paidInvoices',
                'pendingInvoices',
                'overdueInvoices',
                'totalRevenue',
                'pendingRevenue',
                'monthlyRevenue',
                'recentInvoices',
                'overdueInvoicesList',
                'activeSubscriptions',
                'subscriptionRevenue'
            ));
        }
        
        // Inspector Dashboard
        if ($user->hasRole('Inspector')) {
            // Get properties assigned to this inspector (supports both property-level and inspection-level assignment)
            $assignedProperties = Property::where('status', 'awaiting_inspection')
                ->where(function ($query) use ($user) {
                    $query->where('inspector_id', $user->id)
                        ->orWhereHas('inspections', function ($inspectionQuery) use ($user) {
                            $inspectionQuery->where('inspector_id', $user->id)
                                ->whereIn('status', ['scheduled', 'in_progress']);
                        });
                })
                ->with([
                    'user',
                    'projectManager',
                    'inspections' => function ($inspectionQuery) use ($user) {
                        $inspectionQuery->where('inspector_id', $user->id)
                            ->whereIn('status', ['scheduled', 'in_progress'])
                            ->orderByDesc('scheduled_date')
                            ->orderByDesc('id');
                    },
                ])
                ->get();

            $assignedProperties = $assignedProperties->map(function ($property) {
                $latestAssignedInspection = $property->inspections->first();

                if (!$property->inspection_scheduled_at && $latestAssignedInspection?->scheduled_date) {
                    $property->inspection_scheduled_at = $latestAssignedInspection->scheduled_date;
                }

                if (!$property->assigned_at) {
                    $property->assigned_at = $latestAssignedInspection?->created_at ?? $property->updated_at;
                }

                return $property;
            });
            
            // Count inspections assigned to this inspector
            $assignedCount = $assignedProperties->count();
            
            // Count scheduled inspections
            $scheduledCount = $assignedProperties->filter(function ($property) {
                return !is_null($property->inspection_scheduled_at);
            })->count();
            
            // Count unscheduled inspections
            $unscheduledCount = $assignedProperties->filter(function ($property) {
                return is_null($property->inspection_scheduled_at);
            })->count();
            
            // Count completed inspections
            $completedCount = Inspection::where('inspector_id', $user->id)
                ->where('status', 'completed')
                ->distinct('property_id')
                ->count('property_id');
            
            // Get upcoming inspections
            $upcomingInspections = $assignedProperties
                ->filter(function ($property) {
                    return $property->inspection_scheduled_at && $property->inspection_scheduled_at >= now();
                })
                ->sortBy('inspection_scheduled_at')
                ->take(5)
                ->values();

            return view('admin.inspector-dashboard', compact(
                'assignedProperties',
                'assignedCount',
                'scheduledCount',
                'unscheduledCount',
                'completedCount',
                'upcomingInspections'
            ));
        }
        
        // Project Manager Dashboard
        if ($user->hasRole('Project Manager')) {
            // Get properties assigned to this PM
            $assignedProperties = Property::where('project_manager_id', $user->id)
                ->where('status', 'awaiting_inspection')
                ->with(['user', 'inspector'])
                ->get();
            
            // Count properties assigned
            $assignedCount = $assignedProperties->count();
            
            // Count scheduled inspections
            $scheduledCount = Property::where('project_manager_id', $user->id)
                ->where('status', 'awaiting_inspection')
                ->whereNotNull('inspection_scheduled_at')
                ->count();
            
            // Count unscheduled inspections
            $unscheduledCount = Property::where('project_manager_id', $user->id)
                ->where('status', 'awaiting_inspection')
                ->whereNull('inspection_scheduled_at')
                ->count();
            
            // Count active projects
            $activeProjectsCount = Project::whereHas('property', function($query) use ($user) {
                $query->where('project_manager_id', $user->id);
            })->where('status', 'active')->count();
            
            // Get upcoming inspections
            $upcomingInspections = Property::where('project_manager_id', $user->id)
                ->where('status', 'awaiting_inspection')
                ->whereNotNull('inspection_scheduled_at')
                ->where('inspection_scheduled_at', '>=', now())
                ->orderBy('inspection_scheduled_at', 'asc')
                ->with(['user', 'inspector'])
                ->take(5)
                ->get();

            return view('admin.pm-dashboard', compact(
                'assignedProperties',
                'assignedCount',
                'scheduledCount',
                'unscheduledCount',
                'activeProjectsCount',
                'upcomingInspections'
            ));
        }
        
        // Check if user has Super Admin or Administrator role
        if ($user->hasRole(['Super Admin', 'Administrator'])) {
            // Admins see all data
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
            $projectsCount = Project::where('status', 'active')->count();
            $invoicesCount = Invoice::count();
            
            // Get active subscription
            $subscription = Subscription::where('user_id', $user->id)
                ->where('status', 'active')
                ->with('tier')
                ->first();

            // Get recent activities
            $recentActivities = collect();

            return view('admin.index', compact(
                'propertiesCount',
                'inspectionsCount',
                'paidInspectionsCount',
                'completedInspectionsCount',
                'projectsCount',
                'invoicesCount',
                'subscription',
                'recentActivities'
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
            
            // Count inspections as unique completed properties (latest completed report per property)
            $projectIds = Project::whereIn('property_id', $propertyIds)->pluck('id');
            $inspectionsCount = Inspection::whereIn('property_id', $propertyIds)
                ->where('status', 'completed')
                ->distinct('property_id')
                ->count('property_id');

            // Count properties with inspection fee paid
            $paidInspectionsCount = Inspection::whereIn('property_id', $propertyIds)
                ->where('inspection_fee_status', 'paid')
                ->distinct('property_id')
                ->count('property_id');

            // Count paid inspections that are not yet completed
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
                
            // Get pending inspections
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
                'completedInspections'
            ));
        }
        
        // Default for other roles
        $propertiesCount = 0;
        $inspectionsCount = 0;
        $paidInspectionsCount = 0;
        $completedInspectionsCount = 0;
        $projectsCount = 0;
        $invoicesCount = 0;
        $subscription = null;
        $recentActivities = collect();

        return view('admin.index', compact(
            'propertiesCount',
            'inspectionsCount',
            'paidInspectionsCount',
            'completedInspectionsCount',
            'projectsCount',
            'invoicesCount',
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
                        'description' => 'Inspection Service - ' . ($inspection->property?->property_name ?? 'Property'),
                        'inspection_id' => $inspection->id,
                        'quantity' => 1,
                        'unit_price' => $monthlyAmount,
                        'total' => $monthlyAmount,
                    ],
                ],
                'notes' => 'Auto-generated from completed inspection #' . $inspection->id,
            ]);
        }
    }
}
