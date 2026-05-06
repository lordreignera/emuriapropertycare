<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Http\Request;

class ServiceRequestController extends Controller
{
    public function index(Request $request)
    {
        $status = trim((string) $request->input('status', 'open'));

        $query = ServiceRequest::query()->with(['user:id,name,email', 'property:id,property_name,property_code', 'assignedTo:id,name']);

        if ($status === 'open') {
            $query->whereIn('status', ['submitted', 'triaged', 'awaiting_assessment']);
        } elseif ($status !== 'all') {
            $query->where('status', $status);
        }

        $serviceRequests = $query->latest('id')->paginate(20)->withQueryString();

        $openCount = ServiceRequest::query()
            ->whereIn('status', ['submitted', 'triaged', 'awaiting_assessment'])
            ->count();

        $resolvedCount = ServiceRequest::query()
            ->whereIn('status', ['resolved', 'cancelled'])
            ->count();

        return view('admin.service-requests.index', compact(
            'serviceRequests',
            'status',
            'openCount',
            'resolvedCount'
        ));
    }

    public function show(ServiceRequest $serviceRequest)
    {
        $serviceRequest->load(['user', 'property', 'assignedTo', 'project']);

        $assignableStaff = User::query()
            ->role(['Project Manager', 'Inspector', 'Administrator', 'Super Admin'])
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.service-requests.show', compact('serviceRequest', 'assignableStaff'));
    }

    public function triage(Request $request, ServiceRequest $serviceRequest)
    {
        $validated = $request->validate([
            'status' => 'required|in:triaged,awaiting_assessment,cancelled',
            'assigned_to' => 'nullable|exists:users,id',
            'triage_notes' => 'nullable|string',
        ]);

        $serviceRequest->update([
            'status' => $validated['status'],
            'assigned_to' => $validated['assigned_to'] ?? null,
            'triage_notes' => $validated['triage_notes'] ?? null,
            'triaged_at' => $serviceRequest->triaged_at ?? now(),
        ]);

        return redirect()->route('admin.service-requests.show', $serviceRequest)
            ->with('success', 'Service request triage updated.');
    }

    public function assess(ServiceRequest $serviceRequest)
    {
        $updates = [
            'status' => 'awaiting_assessment',
        ];

        if (!$serviceRequest->triaged_at) {
            $updates['triaged_at'] = now();
        }

        if (!$serviceRequest->assigned_to && auth()->check()) {
            $updates['assigned_to'] = auth()->id();
        }

        $serviceRequest->update($updates);

        return redirect()->route('inspections.create', [
            'property_id' => $serviceRequest->property_id,
            'service_request_id' => $serviceRequest->id,
        ])->with('success', 'Service request marked for assessment. Start the inspection workflow.');
    }
}
