<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMatterportModelRequest;
use App\Models\Inspection;
use App\Services\MatterportHostedTourService;
use Illuminate\Support\Facades\Auth;

class MatterportModelController extends Controller
{
    public function store(StoreMatterportModelRequest $request, Inspection $inspection, MatterportHostedTourService $hostedTours)
    {
        $inspection->loadMissing('property');

        if (!$inspection->property) {
            return back()->with('error', 'Attach a property to this inspection before adding a Matterport model.');
        }

        $validated = $request->validated();
        $hostedTours->createWithCaptureSession($inspection, [
            'model_sid' => $validated['model_sid'],
            'display_name' => $validated['model_name'] ?? null,
            'model_url' => $validated['model_url'] ?? null,
            'thumbnail_url' => $validated['thumbnail_url'] ?? null,
            'status' => $validated['status'] ?? 'active',
            'scanned_at' => $validated['scanned_at'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'created_by' => Auth::id(),
        ]);

        return redirect()
            ->route('inspections.digital-twin', $inspection)
            ->with('success', 'Matterport source added to the vendor-neutral digital twin.');
    }
}
