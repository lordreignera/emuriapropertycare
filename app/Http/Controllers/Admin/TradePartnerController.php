<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TradeApplication;
use App\Models\TradePartner;
use Illuminate\Http\Request;

class TradePartnerController extends Controller
{
    public function index(Request $request)
    {
        $this->activateMissingApprovedApplications();

        $status = $request->get('status', TradePartner::STATUS_ACTIVE);

        $query = TradePartner::with('application')->latest('approved_at')->latest('id');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $partners = $query->paginate(15)->withQueryString();

        return view('admin.trade-partners.index', [
            'partners' => $partners,
            'status' => $status,
            'activeCount' => TradePartner::where('status', TradePartner::STATUS_ACTIVE)->count(),
            'inactiveCount' => TradePartner::where('status', TradePartner::STATUS_INACTIVE)->count(),
            'suspendedCount' => TradePartner::where('status', TradePartner::STATUS_SUSPENDED)->count(),
        ]);
    }

    public function show(TradePartner $tradePartner)
    {
        $tradePartner->load('application', 'approver');
        $application = $tradePartner->application;

        return view('admin.trade-partners.show', [
            'partner' => $tradePartner,
            'application' => $application,
            'systems' => $tradePartner->selectedSystems(),
            'subsystems' => $tradePartner->selectedSubsystems(),
        ]);
    }

    private function activateMissingApprovedApplications(): void
    {
        TradeApplication::query()
            ->where('status', TradeApplication::STATUS_APPROVED)
            ->whereDoesntHave('tradePartner')
            ->get()
            ->each(function (TradeApplication $application) {
                TradePartner::updateOrCreate(
                    ['trade_application_id' => $application->id],
                    [
                        'company_name' => $application->company_name,
                        'contact_person' => $application->contact_person,
                        'phone' => $application->phone,
                        'email' => $application->email,
                        'service_area' => $application->service_area,
                        'system_ids' => $application->system_ids ?? [],
                        'subsystem_ids' => $application->subsystem_ids ?? [],
                        'agreed_subsystem_pricing' => $application->agreed_subsystem_pricing ?? [],
                        'agreed_custom_coverage' => $application->agreed_custom_coverage ?? [],
                        'status' => TradePartner::STATUS_ACTIVE,
                        'approved_by' => $application->reviewed_by,
                        'approved_at' => $application->reviewed_at ?? now(),
                    ]
                );
            });
    }
}
