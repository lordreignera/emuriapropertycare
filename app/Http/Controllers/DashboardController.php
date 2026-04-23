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
            
            // Get active subscription
            $subscription = Subscription::where('user_id', $user->id)
                ->where('status', 'active')
                ->with('tier')
                ->first();

            // Build recent activities from latest properties, inspections, and invoices.
            $propertyActivities = Property::query()
                ->latest('created_at')
                ->take(5)
                ->get()
                ->map(function (Property $property) {
                    return (object) [
                        'created_at' => $property->created_at,
                        'description' => 'Property registered',
                        'property' => $property,
                        'status' => ucfirst((string) ($property->status ?? 'submitted')),
                        'status_color' => match ((string) ($property->status ?? '')) {
                            'approved' => 'success',
                            'pending_approval' => 'warning',
                            'rejected' => 'danger',
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
                        'description' => 'Inspection ' . ucfirst(str_replace('_', ' ', (string) ($inspection->status ?? 'scheduled'))),
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

            $quotationReadyInspections = Inspection::with(['property', 'project'])
                ->whereIn('property_id', $propertyIds)
                ->where('status', '!=', 'completed')
                ->whereNotNull('active_quotation_id')
                ->whereIn('quotation_status', ['shared', 'client_reviewing', 'approved'])
                ->orderByDesc('quotation_shared_at')
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
                'completedInspections',
                'quotationReadyInspections'
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
