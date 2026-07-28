<?php

namespace App\Http\Controllers;

use App\Models\Inspection;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Property;
use App\Models\User;
use App\Services\DiagnosisPricingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PropertyController extends Controller
{
    /**
     * Display a listing of registered properties and assessment status.
     */
    public function index(Request $request, DiagnosisPricingService $diagnosisPricingService)
    {
        $user = Auth::user();
        $query = Property::with([
            'user',
            'inspections' => function ($q) {
                $q->latest('created_at');
            },
            'inspections.activeMatterportModel',
            'inspections.activeSpatialModels',
        ]);

        // Role-based filtering
        if ($user->hasRole('Inspector')) {
            // Inspectors only see properties assigned to them (property-level or inspection-level)
            $query->where(function ($q) use ($user) {
                $q->where('inspector_id', $user->id)
                  ->orWhereHas('inspections', function ($inspectionQuery) use ($user) {
                      $inspectionQuery->where('inspector_id', $user->id)
                          ->whereIn('status', ['scheduled', 'in_progress', 'completed']);
                  });
            });

            // Inspector status filtering
            if ($request->filled('status')) {
                $this->applyPropertyStatusFilter($query, (string) $request->status);
            }
        } elseif ($user->hasRole('Project Manager')) {
            // Project Managers see their assigned properties through the full assessment lifecycle.
            $query->where('project_manager_id', $user->id);

            if ($request->filled('status')) {
                $this->applyPropertyStatusFilter($query, (string) $request->status);
            } else {
                $query->where('status', '!=', 'archived');
            }
        } elseif ($user->hasRole('Technician')) {
            // Technicians only see properties with projects assigned to them
            $query->whereHas('projects', function($q) use ($user) {
                $q->whereHas('inspections', function ($inspectionQuery) use ($user) {
                    $inspectionQuery->where('technician_id', $user->id);
                });
            });
        } else {
            // Admins see all properties with status filter
            // Filter by status
            if ($request->filled('status')) {
                $this->applyPropertyStatusFilter($query, (string) $request->status);
            }
        }

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('property_name', 'like', "%{$search}%")
                  ->orWhere('property_code', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('property_address', 'like', "%{$search}%");
            });
        }

        $properties = $query->orderBy('created_at', 'desc')->paginate(15);
        $diagnosisPricingByPropertyId = $properties->getCollection()
            ->mapWithKeys(fn (Property $property) => [
                $property->id => $diagnosisPricingService->calculate($property),
            ])
            ->all();

        return view('admin.properties.index', compact('properties', 'diagnosisPricingByPropertyId'));
    }

    private function applyPropertyStatusFilter(Builder $query, string $status): void
    {
        if ($status === 'awaiting_inspection') {
            $query->where(function ($statusQuery) {
                $statusQuery->where('status', 'awaiting_inspection')
                ->orWhereHas('inspections', function ($inspectionQuery) {
                    $inspectionQuery->where('status', 'scheduled');
                });
            })->whereDoesntHave('inspections', function ($inspectionQuery) {
                $inspectionQuery->where('status', 'completed');
            });

            return;
        }

        if ($status === 'not_inspected' || $status === 'active') {
            // Backward compatible: old "active" maps to "not inspected".
            $query->whereDoesntHave('inspections', function ($inspectionQuery) {
                $inspectionQuery->where('status', 'completed');
            });

            return;
        }

        if ($status === 'inspected_completed') {
            $query->whereHas('inspections', function ($inspectionQuery) {
                $inspectionQuery->where('status', 'completed');
            });

            return;
        }

        $query->where('status', $status);
    }

    /**
     * Display the specified property.
     */
    public function show(Property $property)
    {
        $property->load('user', 'inspector', 'projectManager', 'projects');
        
        // Generate proper back URL based on user role
        $user = Auth::user();
        if ($user->hasRole('Inspector') || $user->hasRole('Project Manager') || $user->hasRole('Technician')) {
            $backUrl = route('properties.index');
        } else {
            $backUrl = url()->previous();
        }
        
        return view('admin.properties.show', compact('property', 'backUrl'));
    }

    /**
     * Show the form for editing the specified property.
     */
    public function edit(Property $property)
    {
        return view('admin.properties.edit', compact('property'));
    }

    /**
     * Update lightweight property lifecycle status.
     */
    public function update(Request $request, Property $property)
    {
        $validated = $request->validate([
            'status' => 'required|in:registered,awaiting_inspection,in_assessment,assessed,archived',
        ]);

        $property->status = $validated['status'];
        $property->save();

        $statusMessage = ucfirst(str_replace('_', ' ', $validated['status']));
        
        return redirect()->route('properties.index')
            ->with('success', "Property '{$property->property_name}' has been {$statusMessage}!");
    }

    /**
     * Assign project manager and inspector to a property diagnosis workflow.
     */
    public function assign(Request $request, Property $property)
    {
        $validated = $request->validate([
            'project_manager_id' => 'nullable|exists:users,id',
            'inspector_id'       => 'nullable|exists:users,id',
            'technician_id'      => 'nullable|exists:users,id',
        ]);

        $project = Project::firstOrCreate(
            ['property_id' => $property->id],
            [
                'title' => 'Property Facts & Diagnosis - ' . $property->property_name,
                'description' => 'Property facts and diagnosis workflow for ' . $property->property_name,
                'status' => 'pending',
                'user_id' => $property->user_id,
                'managed_by' => $property->project_manager_id,
                'created_by' => Auth::id(),
                'project_number' => 'PRJ-' . strtoupper(Str::random(8)),
            ]
        );

        // Find the most recent open diagnosis/inspection record for this property.
        $inspection = $property->inspections()
            ->whereIn('status', ['scheduled', 'in_progress', 'findings_captured', 'findings_shared', 'client_committed', 'estimation_in_progress', 'estimation_completed', 'quotation_shared', 'quotation_approved'])
            ->latest('id')
            ->first();

        if (!$inspection) {
            $inspection = Inspection::create([
                'property_id' => $property->id,
                'project_id' => $project->id,
                'status' => 'scheduled',
                'inspection_fee_status' => 'pending',
                'property_code' => $property->property_code,
                'property_name' => $property->property_name,
                'property_address_snapshot' => trim(($property->property_address ?? '') . ', ' . ($property->city ?? '')),
                'property_type_snapshot' => $property->type,
                'residential_units_snapshot' => (int) ($property->number_of_units ?: $property->residential_units ?: 0),
                'commercial_sqft_snapshot' => $property->square_footage_interior,
                'mixed_use_weight_snapshot' => $property->mixed_use_commercial_weight,
            ]);
        }

        // Resolve values: use submitted value if provided, otherwise keep existing
        $projectManagerId = !empty($validated['project_manager_id'])
            ? $validated['project_manager_id']
            : ($inspection->project?->managed_by ?? $property->project_manager_id);

        $inspectorId = !empty($validated['inspector_id'])
            ? $validated['inspector_id']
            : $inspection->inspector_id;

        $technicianId = array_key_exists('technician_id', $validated)
            ? ($validated['technician_id'] ?: null)
            : $inspection->technician_id;

        // Verify roles only for newly provided values
        $projectManager = null;
        if (!empty($validated['project_manager_id'])) {
            $projectManager = User::findOrFail($validated['project_manager_id']);
            if (!$projectManager->hasRole('Project Manager')) {
                return redirect()->back()->with('error', 'Selected user is not a project manager.');
            }
        }

        $inspector = null;
        if (!empty($validated['inspector_id'])) {
            $inspector = User::findOrFail($validated['inspector_id']);
            if (!$inspector->hasRole('Inspector')) {
                return redirect()->back()->with('error', 'Selected user is not an inspector.');
            }
        }

        $technician = null;
        if (!empty($technicianId)) {
            $technician = User::findOrFail($technicianId);
            if (!$technician->hasRole('Technician')) {
                return redirect()->back()->with('error', 'Selected user is not a technician.');
            }
        }

        // Assign inspection team
        $inspection->project_id = $inspection->project_id ?: $project->id;
        if ($inspectorId)  $inspection->inspector_id  = $inspectorId;
        if ($technicianId !== null || array_key_exists('technician_id', $validated)) {
            $inspection->technician_id = $technicianId;
        }
        $inspection->assigned_by = Auth::id();
        $inspection->save();

        // Also update the property
        if ($inspectorId)    $property->inspector_id       = $inspectorId;
        if ($projectManagerId) $property->project_manager_id = $projectManagerId;
        $property->assigned_at = $property->assigned_at ?: now();
        $property->inspection_scheduled_at = $property->inspection_scheduled_at ?: $inspection->scheduled_date;
        if (($property->status ?? null) === 'registered') {
            $property->status = 'awaiting_inspection';
        }
        $property->save();

        // Also update the project's manager if a project exists and PM changed
        if ($projectManagerId) {
            $project->update(['managed_by' => $projectManagerId]);
        }

        $parts = [];
        if ($projectManager) $parts[] = "Project Manager ({$projectManager->name})";
        if ($inspector)      $parts[] = "Inspector ({$inspector->name})";
        if ($technician)     $parts[] = "Technician ({$technician->name})";
        $successMsg = count($parts)
            ? implode(', ', $parts) . ' assigned successfully!'
            : 'Team updated (no changes made).';

        return redirect()->back()->with('success', $successMsg);
    }

    public function createDiagnosisInvoice(Request $request, Property $property, DiagnosisPricingService $diagnosisPricingService)
    {
        $pricing = $diagnosisPricingService->calculate($property);

        $validated = $request->validate([
            'property_facts_amount' => ['nullable', 'numeric', 'min:0'],
            'diagnosis_amount' => ['nullable', 'numeric', 'min:0'],
            'due_date' => ['nullable', 'date', 'after_or_equal:today'],
        ]);

        $propertyFactsAmount = round((float) ($validated['property_facts_amount'] ?? 0), 2);
        $diagnosisAmount = round((float) ($validated['diagnosis_amount'] ?? $pricing['invoice_dollars']), 2);
        $total = round($propertyFactsAmount + $diagnosisAmount, 2);

        if ($total <= 0) {
            return back()->with('error', 'Invoice total must be greater than zero.');
        }

        $invoice = DB::transaction(function () use ($property, $propertyFactsAmount, $diagnosisAmount, $total, $validated, $pricing) {
            $property->loadMissing('user');

            $project = Project::firstOrCreate(
                ['property_id' => $property->id],
                [
                    'title' => 'Property Facts & Diagnosis - ' . $property->property_name,
                    'description' => 'Property facts capture and diagnosis preparation for ' . $property->property_name,
                    'status' => 'pending',
                    'user_id' => $property->user_id,
                    'managed_by' => $property->project_manager_id,
                    'created_by' => Auth::id(),
                    'project_number' => 'PRJ-' . strtoupper(Str::random(8)),
                ]
            );

            $inspection = Inspection::where('property_id', $property->id)
                ->where('status', '!=', 'completed')
                ->latest('id')
                ->first();

            if (!$inspection) {
                $inspection = Inspection::create([
                    'property_id' => $property->id,
                    'project_id' => $project->id,
                    'inspector_id' => $property->inspector_id,
                    'assigned_by' => Auth::id(),
                    'status' => 'scheduled',
                    'inspection_fee_status' => 'pending',
                    'inspection_fee_amount' => $diagnosisAmount,
                    'property_code' => $property->property_code,
                    'property_name' => $property->property_name,
                    'property_address_snapshot' => trim(($property->property_address ?? '') . ', ' . ($property->city ?? '')),
                    'property_type_snapshot' => $property->type,
                    'residential_units_snapshot' => (int) ($property->number_of_units ?: $property->residential_units ?: 0),
                    'commercial_sqft_snapshot' => $property->square_footage_interior,
                    'mixed_use_weight_snapshot' => $property->mixed_use_commercial_weight,
                ]);
            } else {
                $inspection->update([
                    'project_id' => $inspection->project_id ?: $project->id,
                    'inspection_fee_status' => $inspection->inspection_fee_status === 'paid' ? 'paid' : 'pending',
                    'inspection_fee_amount' => $diagnosisAmount,
                ]);
            }

            $lineItems = [];

            if ($propertyFactsAmount > 0) {
                $lineItems[] = [
                    'description' => 'Property Facts - floor plan and digital twin capture',
                    'purpose' => 'property_facts',
                    'property_id' => $property->id,
                    'inspection_id' => $inspection->id,
                    'quantity' => 1,
                    'unit_price' => $propertyFactsAmount,
                    'total' => $propertyFactsAmount,
                ];
            }

            $lineItems[] = [
                'description' => 'Property Diagnosis Fee',
                'purpose' => 'property_diagnosis',
                'property_id' => $property->id,
                'inspection_id' => $inspection->id,
                'quantity' => 1,
                'unit_price' => $diagnosisAmount,
                'total' => $diagnosisAmount,
                'pricing_snapshot' => [
                    'units' => $pricing['units'],
                    'base_fee' => $pricing['base_fee'],
                    'roof_surcharge' => $pricing['roof_surcharge'],
                    'crawl_surcharge' => $pricing['crawl_surcharge'],
                ],
            ];

            $existingInvoice = Invoice::where('project_id', $project->id)
                ->where('user_id', $property->user_id)
                ->where('type', 'additional')
                ->whereIn('status', ['draft', 'sent', 'partial', 'overdue'])
                ->get()
                ->first(function (Invoice $invoice) use ($property) {
                    return collect($invoice->line_items ?? [])
                        ->contains(fn ($item) => (int) ($item['property_id'] ?? 0) === (int) $property->id
                            && in_array(($item['purpose'] ?? null), ['property_facts', 'property_diagnosis'], true));
                });

            $attributes = [
                'project_id' => $project->id,
                'user_id' => $property->user_id,
                'type' => 'additional',
                'subtotal' => $total,
                'tax' => 0,
                'total' => $total,
                'paid_amount' => (float) ($existingInvoice->paid_amount ?? 0),
                'balance' => max(0, round($total - (float) ($existingInvoice->paid_amount ?? 0), 2)),
                'status' => (float) ($existingInvoice->paid_amount ?? 0) > 0 ? 'partial' : 'sent',
                'issue_date' => optional($existingInvoice?->issue_date)->toDateString() ?? now()->toDateString(),
                'due_date' => $validated['due_date'] ?? optional($existingInvoice?->due_date)->toDateString() ?? now()->addDays(14)->toDateString(),
                'line_items' => $lineItems,
                'notes' => 'Property facts and diagnosis invoice for ' . ($property->property_name ?? 'property') . '.',
            ];

            if ($attributes['balance'] <= 0) {
                $attributes['status'] = 'paid';
                $attributes['paid_at'] = now()->toDateString();
            }

            if ($existingInvoice) {
                $existingInvoice->update($attributes);
                $invoice = $existingInvoice->fresh();
            } else {
                $attributes['invoice_number'] = $this->nextInvoiceNumber('INV-DIAG-' . now()->format('Ymd') . '-' . $property->id);
                $invoice = Invoice::create($attributes);
            }

            if (($property->status ?? null) === 'registered') {
                $property->update(['status' => 'awaiting_inspection']);
            }

            return $invoice;
        });

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('success', 'Property facts and diagnosis invoice has been prepared for the client.');
    }

    private function nextInvoiceNumber(string $baseInvoiceNumber): string
    {
        $invoiceNumber = $baseInvoiceNumber;
        $counter = 1;

        while (Invoice::where('invoice_number', $invoiceNumber)->exists()) {
            $invoiceNumber = $baseInvoiceNumber . '-' . $counter;
            $counter++;
        }

        return $invoiceNumber;
    }

    /**
     * Remove the specified property.
     */
    public function destroy(Property $property)
    {
        $propertyName = $property->property_name;
        $property->delete();

        return redirect()->route('properties.index')
            ->with('success', "Property '{$propertyName}' has been deleted.");
    }
}
