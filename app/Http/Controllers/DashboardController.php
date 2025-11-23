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
            $paidInvoices = Invoice::where('status', 'paid')->count();
            $pendingInvoices = Invoice::where('status', 'pending')->count();
            $overdueInvoices = Invoice::where('status', 'overdue')->count();
            
            // Calculate revenue
            $totalRevenue = Invoice::where('status', 'paid')->sum('amount');
            $pendingRevenue = Invoice::where('status', 'pending')->sum('amount');
            $monthlyRevenue = Invoice::where('status', 'paid')
                ->whereMonth('created_at', now()->month)
                ->sum('amount');
            
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
            // Get properties assigned to this inspector
            $assignedProperties = Property::where('inspector_id', $user->id)
                ->where('status', 'awaiting_inspection')
                ->with(['user', 'projectManager'])
                ->get();
            
            // Count inspections assigned to this inspector
            $assignedCount = $assignedProperties->count();
            
            // Count scheduled inspections
            $scheduledCount = Property::where('inspector_id', $user->id)
                ->where('status', 'awaiting_inspection')
                ->whereNotNull('inspection_scheduled_at')
                ->count();
            
            // Count unscheduled inspections
            $unscheduledCount = Property::where('inspector_id', $user->id)
                ->where('status', 'awaiting_inspection')
                ->whereNull('inspection_scheduled_at')
                ->count();
            
            // Count completed inspections
            $completedCount = Inspection::whereHas('project.property', function($query) use ($user) {
                $query->where('inspector_id', $user->id);
            })->where('status', 'completed')->count();
            
            // Get upcoming inspections
            $upcomingInspections = Property::where('inspector_id', $user->id)
                ->where('status', 'awaiting_inspection')
                ->whereNotNull('inspection_scheduled_at')
                ->where('inspection_scheduled_at', '>=', now())
                ->orderBy('inspection_scheduled_at', 'asc')
                ->with(['user', 'projectManager'])
                ->take(5)
                ->get();

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
            $inspectionsCount = Inspection::count();
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
            
            // Count properties
            $propertiesCount = $propertyIds->count();
            
            // Count projects for user's properties
            $projectsCount = Project::whereIn('property_id', $propertyIds)
                ->where('status', 'active')
                ->count();
            
            // Count inspections via projects
            $projectIds = Project::whereIn('property_id', $propertyIds)->pluck('id');
            $inspectionsCount = Inspection::whereIn('project_id', $projectIds)->count();
            
            // Count invoices
            $invoicesCount = Invoice::where('user_id', $user->id)->count();
            
            // Get unpaid invoices
            $unpaidInvoices = Invoice::where('user_id', $user->id)
                ->where('status', 'pending')
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

            return view('client.dashboard', compact(
                'propertiesCount',
                'inspectionsCount',
                'projectsCount',
                'invoicesCount',
                'unpaidInvoices',
                'pendingInspections',
                'subscription',
                'recentProperties'
            ));
        }
        
        // Default for other roles
        $propertiesCount = 0;
        $inspectionsCount = 0;
        $projectsCount = 0;
        $invoicesCount = 0;
        $subscription = null;
        $recentActivities = collect();

        return view('admin.index', compact(
            'propertiesCount',
            'inspectionsCount',
            'projectsCount',
            'invoicesCount',
            'subscription',
            'recentActivities'
        ));
    }
}
