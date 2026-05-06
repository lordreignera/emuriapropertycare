<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Property;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Notifications\ServiceRequestSubmittedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class ServiceRequestController extends Controller
{
    public function index()
    {
        $serviceRequests = ServiceRequest::query()
            ->with(['property', 'assignedTo'])
            ->where('user_id', auth()->id())
            ->latest('id')
            ->paginate(10);

        return view('client.service-requests.index', compact('serviceRequests'));
    }

    public function create()
    {
        $properties = Property::query()
            ->where('user_id', auth()->id())
            ->orderBy('property_name')
            ->get(['id', 'property_name', 'property_code', 'property_address', 'city']);

        return view('client.service-requests.create', compact('properties'));
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
            'photos' => 'nullable|array',
            'photos.*' => 'nullable|image|max:10240',
        ]);

        $property = Property::query()
            ->where('id', $validated['property_id'])
            ->where('user_id', auth()->id())
            ->firstOrFail();

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

        $photoPaths = [];
        $disk = config('filesystems.default', 's3');
        foreach ((array) $request->file('photos', []) as $photo) {
            if ($photo && $photo->isValid()) {
                $photoPaths[] = $photo->store('service-requests/photos', $disk);
            }
        }

        $latestProject = Project::query()
            ->where('property_id', $property->id)
            ->latest('id')
            ->first();

        $serviceRequest = ServiceRequest::create([
            'user_id' => auth()->id(),
            'property_id' => $property->id,
            'project_id' => $latestProject?->id,
            'source' => 'client_dashboard',
            'request_type' => $validated['request_type'],
            'urgency' => $validated['urgency'],
            'title' => trim((string) $validated['title']),
            'description' => trim((string) $validated['description']),
            'requested_location' => $validated['requested_location'] ?? null,
            'items_reported' => $itemsReported,
            'photos' => empty($photoPaths) ? null : $photoPaths,
            'preferred_visit_window' => $validated['preferred_visit_window'] ?? null,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $adminRecipients = User::role(['Super Admin', 'Administrator'])
            ->get()
            ->unique('id')
            ->values();

        if ($adminRecipients->isNotEmpty()) {
            Notification::send($adminRecipients, new ServiceRequestSubmittedNotification(
                serviceRequestId: (int) $serviceRequest->id,
                requestNumber: (string) $serviceRequest->request_number,
                propertyName: (string) ($property->property_name ?? 'Property'),
                requestType: (string) $serviceRequest->request_type,
                urgency: (string) $serviceRequest->urgency,
            ));
        }

        return redirect()->route('client.service-requests.show', $serviceRequest)
            ->with('success', 'Service request submitted successfully. Our team will triage it shortly.');
    }

    public function show(ServiceRequest $serviceRequest)
    {
        if ((int) $serviceRequest->user_id !== (int) auth()->id()) {
            abort(403);
        }

        $serviceRequest->load(['property', 'assignedTo']);

        return view('client.service-requests.show', compact('serviceRequest'));
    }
}
