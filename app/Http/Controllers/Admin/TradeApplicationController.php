<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TradeApplication;
use App\Models\TradePartner;
use Illuminate\Http\Request;

class TradeApplicationController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status', 'open');

        $query = TradeApplication::query()->with('tradePartner')->latest('submitted_at')->latest('id');

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
        $approvedCount = TradePartner::where('status', TradePartner::STATUS_ACTIVE)->count();
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
        $tradeApplication->load('tradePartner');

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
            'agreed_subsystem_pricing' => 'nullable|array',
            'agreed_subsystem_pricing.*.pricing_unit' => 'nullable|in:sf,lf,ea,hr,day,ls,ton',
            'agreed_subsystem_pricing.*.typical_rate' => 'nullable|numeric|min:0|max:999999.99',
            'agreed_subsystem_pricing.*.maximum_charge' => 'nullable|numeric|min:0|max:999999.99',
            'agreed_subsystem_pricing.*.estimated_duration' => 'nullable|string|max:255',
            'agreed_subsystem_pricing.*.notes' => 'nullable|string|max:1000',
            'agreed_custom_coverage' => 'nullable|array',
            'agreed_custom_coverage.*.system_name' => 'nullable|string|max:255',
            'agreed_custom_coverage.*.subsystem_name' => 'nullable|string|max:255',
            'agreed_custom_coverage.*.pricing_unit' => 'nullable|in:sf,lf,ea,hr,day,ls,ton',
            'agreed_custom_coverage.*.typical_rate' => 'nullable|numeric|min:0|max:999999.99',
            'agreed_custom_coverage.*.maximum_charge' => 'nullable|numeric|min:0|max:999999.99',
            'agreed_custom_coverage.*.estimated_duration' => 'nullable|string|max:255',
            'agreed_custom_coverage.*.notes' => 'nullable|string|max:1000',
        ]);

        $agreedSubsystemPricing = $this->normalizeAgreedSubsystemPricing(
            $validated['agreed_subsystem_pricing'] ?? [],
            $tradeApplication->subsystem_ids ?? []
        );
        $agreedCustomCoverage = $this->normalizeAgreedCustomCoverage(
            $validated['agreed_custom_coverage'] ?? [],
            $tradeApplication->custom_coverage ?? []
        );
        $hasAgreedPricing = !empty($agreedSubsystemPricing) || !empty($agreedCustomCoverage);

        $update = [
            'status' => $validated['status'],
            'admin_notes' => $validated['admin_notes'] ?? $tradeApplication->admin_notes,
            'agreed_subsystem_pricing' => $agreedSubsystemPricing,
            'agreed_custom_coverage' => $agreedCustomCoverage,
            'pricing_agreed_at' => $hasAgreedPricing ? now() : $tradeApplication->pricing_agreed_at,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ];

        $tradeApplication->update($update);

        if ($tradeApplication->status === TradeApplication::STATUS_APPROVED) {
            $this->activateTradePartner($tradeApplication->fresh());
            return redirect()->route('admin.trade-partners.index', ['status' => TradePartner::STATUS_ACTIVE])
                ->with('success', 'Trade partner approved and added to the approved partners list.');
        } elseif ($tradeApplication->tradePartner) {
            $tradeApplication->tradePartner->update([
                'status' => $tradeApplication->status === TradeApplication::STATUS_SUSPENDED
                    ? TradePartner::STATUS_SUSPENDED
                    : TradePartner::STATUS_INACTIVE,
            ]);
        }

        return redirect()->route('admin.trade-applications.show', $tradeApplication)
            ->with('success', 'Trade application status updated.');
    }

    private function activateTradePartner(TradeApplication $application): TradePartner
    {
        return TradePartner::updateOrCreate(
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
                'approved_by' => auth()->id(),
                'approved_at' => $application->reviewed_at ?? now(),
            ]
        );
    }

    private function normalizeAgreedSubsystemPricing(array $pricingRows, array $subsystemIds): array
    {
        return collect($pricingRows)
            ->only(array_map('strval', $subsystemIds))
            ->map(function ($pricing) {
                return [
                    'pricing_unit' => $pricing['pricing_unit'] ?? null,
                    'typical_rate' => isset($pricing['typical_rate']) && $pricing['typical_rate'] !== '' ? (float) $pricing['typical_rate'] : null,
                    'maximum_charge' => isset($pricing['maximum_charge']) && $pricing['maximum_charge'] !== '' ? (float) $pricing['maximum_charge'] : null,
                    'estimated_duration' => trim((string) ($pricing['estimated_duration'] ?? '')),
                    'notes' => trim((string) ($pricing['notes'] ?? '')),
                ];
            })
            ->filter(fn ($pricing) => $pricing['pricing_unit'] !== null || $pricing['typical_rate'] !== null || $pricing['maximum_charge'] !== null || $pricing['estimated_duration'] !== '' || $pricing['notes'] !== '')
            ->all();
    }

    private function normalizeAgreedCustomCoverage(array $pricingRows, array $submittedCoverage): array
    {
        return collect($pricingRows)
            ->map(function ($coverage, $index) use ($submittedCoverage) {
                $submitted = $submittedCoverage[$index] ?? [];

                return [
                    'system_name' => trim((string) ($coverage['system_name'] ?? $submitted['system_name'] ?? '')),
                    'subsystem_name' => trim((string) ($coverage['subsystem_name'] ?? $submitted['subsystem_name'] ?? '')),
                    'pricing_unit' => $coverage['pricing_unit'] ?? null,
                    'typical_rate' => isset($coverage['typical_rate']) && $coverage['typical_rate'] !== '' ? (float) $coverage['typical_rate'] : null,
                    'maximum_charge' => isset($coverage['maximum_charge']) && $coverage['maximum_charge'] !== '' ? (float) $coverage['maximum_charge'] : null,
                    'estimated_duration' => trim((string) ($coverage['estimated_duration'] ?? '')),
                    'notes' => trim((string) ($coverage['notes'] ?? '')),
                ];
            })
            ->filter(fn ($coverage) => $coverage['system_name'] !== '' || $coverage['subsystem_name'] !== '' || $coverage['pricing_unit'] !== null || $coverage['typical_rate'] !== null || $coverage['maximum_charge'] !== null || $coverage['estimated_duration'] !== '' || $coverage['notes'] !== '')
            ->values()
            ->all();
    }
}
