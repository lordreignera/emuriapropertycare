<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Property;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Http\Request;

class ServiceRequestController extends Controller
{
    public function index(Request $request)
    {
        $status = trim((string) $request->input('status', 'open'));
        $type = trim((string) $request->input('type', ''));

        $query = ServiceRequest::query()->with(['user:id,name,email', 'property:id,property_name,property_code', 'assignedTo:id,name']);

        if (in_array($type, ['addendum', 'change_request'], true)) {
            $query->where('request_type', 'change_request');
        }

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

        $addendumCount = ServiceRequest::query()
            ->where('request_type', 'change_request')
            ->whereIn('status', ['submitted', 'triaged', 'awaiting_assessment'])
            ->count();

        return view('admin.service-requests.index', compact(
            'serviceRequests',
            'status',
            'type',
            'openCount',
            'addendumCount',
            'resolvedCount'
        ));
    }

    public function create(Request $request)
    {
        $properties = Property::query()
            ->with('user:id,name,email')
            ->whereNotNull('user_id')
            ->orderBy('property_name')
            ->get(['id', 'user_id', 'property_name', 'property_code', 'property_address', 'city']);

        $isAddendum = in_array($request->query('type'), ['addendum', 'change_request'], true);

        return view('admin.service-requests.create', compact('properties', 'isAddendum'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'property_id' => 'required|exists:properties,id',
            'request_type' => 'required|in:emergency,repair,change_request',
            'urgency' => 'required|in:low,medium,high,critical',
            'title' => 'required|string|max:180',
            'description' => 'required|string',
            'requested_location' => 'nullable|string|max:180',
            'items_reported_text' => 'nullable|string',
            'preferred_visit_window' => 'nullable|string|max:180',
        ]);

        $property = Property::with('user:id,name,email')->findOrFail($validated['property_id']);

        if (!$property->user_id) {
            return back()->withInput()->with('error', 'This property has no client owner. Assign a client before logging a request.');
        }

        $itemsReported = collect(preg_split('/\r\n|\r|\n/', (string) ($validated['items_reported_text'] ?? '')))
            ->map(fn(string $line) => trim($line))
            ->filter()
            ->values()
            ->map(fn(string $issue) => ['issue' => $issue])
            ->all();

        if (empty($itemsReported)) {
            $itemsReported = [
                ['issue' => trim((string) $validated['title'])],
            ];
        }

        $latestProject = Project::query()
            ->where('property_id', $property->id)
            ->latest('id')
            ->first();

        $serviceRequest = ServiceRequest::create([
            'user_id' => $property->user_id,
            'property_id' => $property->id,
            'project_id' => $latestProject?->id,
            'source' => 'admin_dashboard',
            'request_type' => $validated['request_type'],
            'urgency' => $validated['urgency'],
            'title' => trim((string) $validated['title']),
            'description' => trim((string) $validated['description']),
            'requested_location' => $validated['requested_location'] ?? null,
            'items_reported' => $itemsReported,
            'preferred_visit_window' => $validated['preferred_visit_window'] ?? null,
            'status' => 'submitted',
            'assigned_to' => auth()->id(),
            'submitted_at' => now(),
        ]);

        return redirect()->route('admin.service-requests.show', $serviceRequest)
            ->with('success', 'Request logged. You can triage it or convert it into an assessment for quotation.');
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

        $message = $serviceRequest->request_type === 'change_request'
            ? 'Add-on request marked for assessment. Capture findings, finalise the PHAR report, then continue through quotation.'
            : 'Service request marked for assessment. Start the inspection workflow.';

        return redirect()->route('inspections.create', [
            'property_id' => $serviceRequest->property_id,
            'service_request_id' => $serviceRequest->id,
        ])->with('success', $message);
    }
}
