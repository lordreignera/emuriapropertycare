<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TenantController extends Controller
{
    /**
     * Display a listing of the tenants.
     */
    public function index(Request $request)
    {
        $query = Tenant::with('property')
            ->where('client_id', Auth::id())
            ->orderBy('created_at', 'desc');

        // Filter by property if specified
        if ($request->filled('property_id')) {
            $query->where('property_id', $request->property_id);
        }

        // Filter by status if specified
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $tenants = $query->paginate(15);
        
        // Get user's approved properties with tenants enabled
        $properties = Property::where('user_id', Auth::id())
            ->where('status', 'approved')
            ->where('has_tenants', true)
            ->get();

        return view('client.tenants.index', compact('tenants', 'properties'));
    }

    /**
     * Show the form for creating a new tenant.
     */
    public function create(Request $request)
    {
        // Get approved properties with tenants enabled
        $properties = Property::where('user_id', Auth::id())
            ->where('status', 'approved')
            ->where('has_tenants', true)
            ->get();

        if ($properties->isEmpty()) {
            return redirect()->route('client.tenants.index')
                ->with('error', 'You need to have at least one approved property with multi-tenant enabled before adding tenants.');
        }

        // Pre-select property if passed in request
        $selectedPropertyId = $request->get('property_id');

        return view('client.tenants.create', compact('properties', 'selectedPropertyId'));
    }

    /**
     * Store a newly created tenant in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'property_id' => 'required|exists:properties,id',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'unit_number' => 'required|string|max:50',
            'move_in_date' => 'required|date',
            'move_out_date' => 'nullable|date|after:move_in_date',
            'can_report_emergency' => 'nullable|boolean',
        ]);

        // Verify property belongs to authenticated user
        $property = Property::where('id', $validated['property_id'])
            ->where('user_id', Auth::id())
            ->where('status', 'approved')
            ->where('has_tenants', true)
            ->firstOrFail();

        // Get next tenant number for this property
        $tenantNumber = Tenant::where('property_id', $property->id)->max('tenant_number') + 1;

        // Generate tenant login
        $tenantLogin = Tenant::generateTenantLogin($property->property_code, $tenantNumber);

        $validated['client_id'] = Auth::id();
        $validated['tenant_number'] = $tenantNumber;
        $validated['tenant_login'] = $tenantLogin;
        $validated['status'] = 'active';
        $validated['can_report_emergency'] = $request->has('can_report_emergency');

        $tenant = Tenant::create($validated);

        return redirect()->route('client.tenants.index')
            ->with('success', "Tenant {$tenant->full_name} added successfully! Login: {$tenantLogin}");
    }

    /**
     * Display the specified tenant.
     */
    public function show(Tenant $tenant)
    {
        // Verify tenant belongs to authenticated user
        if ($tenant->client_id !== Auth::id()) {
            abort(403, 'Unauthorized access to tenant information.');
        }

        $tenant->load('property', 'emergencyReports');

        return view('client.tenants.show', compact('tenant'));
    }

    /**
     * Show the form for editing the specified tenant.
     */
    public function edit(Tenant $tenant)
    {
        // Verify tenant belongs to authenticated user
        if ($tenant->client_id !== Auth::id()) {
            abort(403, 'Unauthorized access to tenant information.');
        }

        $properties = Property::where('user_id', Auth::id())
            ->where('status', 'approved')
            ->where('has_tenants', true)
            ->get();

        return view('client.tenants.edit', compact('tenant', 'properties'));
    }

    /**
     * Update the specified tenant in storage.
     */
    public function update(Request $request, Tenant $tenant)
    {
        // Verify tenant belongs to authenticated user
        if ($tenant->client_id !== Auth::id()) {
            abort(403, 'Unauthorized access to tenant information.');
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'unit_number' => 'required|string|max:50',
            'move_in_date' => 'required|date',
            'move_out_date' => 'nullable|date|after:move_in_date',
            'status' => 'required|in:active,inactive,moved_out',
            'can_report_emergency' => 'nullable|boolean',
        ]);

        $validated['can_report_emergency'] = $request->has('can_report_emergency');

        $tenant->update($validated);

        return redirect()->route('client.tenants.index')
            ->with('success', 'Tenant information updated successfully!');
    }

    /**
     * Remove the specified tenant from storage.
     */
    public function destroy(Tenant $tenant)
    {
        // Verify tenant belongs to authenticated user
        if ($tenant->client_id !== Auth::id()) {
            abort(403, 'Unauthorized access to tenant information.');
        }

        $tenantName = $tenant->full_name;
        $tenant->delete();

        return redirect()->route('client.tenants.index')
            ->with('success', "Tenant {$tenantName} has been removed successfully.");
    }

    /**
     * Export tenants to Excel.
     */
    public function export(Request $request)
    {
        $query = Tenant::with('property')
            ->where('client_id', Auth::id())
            ->orderBy('property_id')
            ->orderBy('tenant_number');

        // Filter by property if specified
        if ($request->filled('property_id')) {
            $query->where('property_id', $request->property_id);
        }

        // Filter by status if specified
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $tenants = $query->get();

        // Generate CSV content
        $filename = 'tenants_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($tenants) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for Excel UTF-8 support
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Add headers
            fputcsv($file, [
                'Tenant Login',
                'Tenant Number',
                'First Name',
                'Last Name',
                'Email',
                'Phone',
                'Property Name',
                'Property Code',
                'Unit Number',
                'Move-In Date',
                'Move-Out Date',
                'Status',
                'Can Report Emergency',
                'Last Login',
                'Created Date'
            ]);

            // Add data
            foreach ($tenants as $tenant) {
                fputcsv($file, [
                    $tenant->tenant_login,
                    $tenant->tenant_number,
                    $tenant->first_name,
                    $tenant->last_name,
                    $tenant->email ?? 'N/A',
                    $tenant->phone ?? 'N/A',
                    $tenant->property->property_name,
                    $tenant->property->property_code,
                    $tenant->unit_number,
                    $tenant->move_in_date ? $tenant->move_in_date->format('Y-m-d') : 'N/A',
                    $tenant->move_out_date ? $tenant->move_out_date->format('Y-m-d') : 'N/A',
                    ucfirst($tenant->status),
                    $tenant->can_report_emergency ? 'Yes' : 'No',
                    $tenant->last_login_at ? $tenant->last_login_at->format('Y-m-d H:i:s') : 'Never',
                    $tenant->created_at->format('Y-m-d H:i:s')
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get tenant password for a property (for display purposes).
     */
    public function getPropertyPassword(Property $property)
    {
        // Verify property belongs to authenticated user
        if ($property->user_id !== Auth::id()) {
            abort(403);
        }

        return response()->json([
            'property_code' => $property->property_code,
            'tenant_password' => $property->tenant_common_password,
            'property_name' => $property->property_name
        ]);
    }
}
