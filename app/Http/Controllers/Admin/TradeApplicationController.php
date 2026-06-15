<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TradeApplication;
use Illuminate\Http\Request;

class TradeApplicationController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status', 'open');

        $query = TradeApplication::query()->latest('submitted_at')->latest('id');

        if ($status === 'open') {
            $query->whereIn('status', [
                TradeApplication::STATUS_SUBMITTED,
                TradeApplication::STATUS_READY_FOR_REVIEW,
                TradeApplication::STATUS_NEEDS_MORE_INFORMATION,
                TradeApplication::STATUS_CONDITIONALLY_APPROVED,
            ]);
        } elseif ($status !== 'all') {
            $query->where('status', $status);
        }

        $applications = $query->paginate(15)->withQueryString();

        $openCount = TradeApplication::whereIn('status', [
            TradeApplication::STATUS_SUBMITTED,
            TradeApplication::STATUS_READY_FOR_REVIEW,
            TradeApplication::STATUS_NEEDS_MORE_INFORMATION,
            TradeApplication::STATUS_CONDITIONALLY_APPROVED,
        ])->count();
        $approvedCount = TradeApplication::where('status', TradeApplication::STATUS_APPROVED)->count();
        $rejectedCount = TradeApplication::where('status', TradeApplication::STATUS_REJECTED)->count();

        return view('admin.trade-applications.index', compact(
            'applications',
            'status',
            'openCount',
            'approvedCount',
            'rejectedCount'
        ));
    }

    public function show(TradeApplication $tradeApplication)
    {
        return view('admin.trade-applications.show', [
            'application' => $tradeApplication,
            'systems' => $tradeApplication->selectedSystems(),
            'subsystems' => $tradeApplication->selectedSubsystems(),
        ]);
    }

    public function updateStatus(Request $request, TradeApplication $tradeApplication)
    {
        $validated = $request->validate([
            'status' => 'required|in:ready_for_review,needs_more_information,conditionally_approved,approved,rejected,suspended',
            'admin_notes' => 'nullable|string|max:5000',
        ]);

        $tradeApplication->update([
            'status' => $validated['status'],
            'admin_notes' => $validated['admin_notes'] ?? $tradeApplication->admin_notes,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return redirect()->route('admin.trade-applications.show', $tradeApplication)
            ->with('success', 'Trade application status updated.');
    }
}
