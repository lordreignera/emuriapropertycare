<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\Inspection;
use App\Models\InspectionQuotation;
use App\Models\User;
use App\Notifications\ClientQuotationApprovedNotification;
use App\Services\AgreementScheduleService;
use App\Services\BDCCalculator;
use App\Services\InspectionInvoiceSyncService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class InspectionController extends Controller
{
    // Inspection fee in cents (for Stripe)
    private const INSPECTION_FEE_CENTS = 29900; // $299.00
    private const INSPECTION_FEE_DOLLARS = 299;

    public function __construct(
        private readonly AgreementScheduleService $agreementScheduleService,
        private readonly InspectionInvoiceSyncService $inspectionInvoiceSyncService,
    )
    {
    }

    /**
     * Verify the inspection belongs to the authenticated user.
     * Aborts with 403 if it does not.
     */
    private function authorizeInspection(Inspection $inspection): void
    {
        $user = Auth::user();
        // Admins, inspectors, and project managers can access any inspection
        if ($user->hasRole(['Super Admin', 'Administrator', 'Inspector', 'Project Manager'])) {
            return;
        }
        if (!$inspection->property || (int) $inspection->property->user_id !== (int) $user->id) {
            abort(403, 'Unauthorized access to this inspection.');
        }
    }

    /**
     * List client's inspections (scheduled, in progress, completed).
     */
    public function index()
    {
        $propertyIds = Property::where('user_id', Auth::id())->pluck('id');

        $latestInspectionIds = Inspection::whereIn('property_id', $propertyIds)
            ->where('status', '!=', 'cancelled')
            ->selectRaw('MAX(id) as id')
            ->groupBy('property_id')
            ->pluck('id');

        $inspections = Inspection::with(['property', 'project'])
            ->whereIn('id', $latestInspectionIds)
            ->whereIn('property_id', $propertyIds)
            ->where('status', '!=', 'cancelled')
            ->orderByDesc('completed_date')
            ->orderByDesc('id')
            ->paginate(10);

        return view('client.inspections.index', compact('inspections'));
    }

    /**
     * Show client's inspection report & breakdown.
     */
    public function report(Inspection $inspection)
    {
        $this->authorizeInspection($inspection);
        $activeQuotation = null;

        if (!empty($inspection->active_quotation_id)) {
            $activeQuotation = InspectionQuotation::query()
                ->where('id', $inspection->active_quotation_id)
                ->where('inspection_id', $inspection->id)
                ->first();
        }

        if (($inspection->status ?? null) !== 'completed') {
            if ($activeQuotation) {
                return redirect()->route('client.inspections.quotation', $inspection->id)
                    ->with('info', 'Review the quotation first. The report and agreement become available after approval and assessment completion.');
            }

            return redirect()->route('client.inspections.index')
                ->with('info', 'The report is not available yet. It will appear after assessment completion.');
        }

        $inspection = $this->agreementScheduleService->refresh($inspection);

        $findings = \App\Models\PHARFinding::where('inspection_id', $inspection->id)
            ->orderBy('id')
            ->get();

        return view('client.inspections.report', compact('inspection', 'findings', 'activeQuotation'));
    }

    public function agreement(Inspection $inspection)
    {
        $this->authorizeInspection($inspection);

        if (($inspection->status ?? null) !== 'completed') {
            return redirect()->route('client.inspections.index')
                ->with('error', 'Agreement is available only after inspection completion.');
        }

        $inspection = $this->agreementScheduleService->refresh($inspection);

        return view('client.inspections.agreement', compact('inspection'));
    }

    public function signAgreement(Request $request, Inspection $inspection)
    {
        $this->authorizeInspection($inspection);

        if (($inspection->status ?? null) !== 'completed') {
            return redirect()->route('client.inspections.index')
                ->with('error', 'Agreement can only be signed after inspection completion.');
        }

        $validated = $request->validate([
            'client_full_name' => 'required|string|max:255',
            'client_acknowledgment' => 'required|accepted',
        ]);

        $fullName = trim((string) $validated['client_full_name']);

        $inspection->update([
            'approved_by_client' => true,
            'client_approved_at' => now(),
            'client_full_name' => $fullName,
            'client_signature' => 'typed:' . $fullName,
            'client_acknowledgment' => 'Client accepted Job Approval & Service Agreement online on ' . now()->toDateTimeString(),
        ]);

        $inspection = $this->agreementScheduleService->refresh($inspection);

        return redirect()->route('client.inspections.agreement', $inspection->id)
            ->with('success', 'Agreement signed successfully.');
    }

    public function downloadAgreementPdf(Inspection $inspection)
    {
        $this->authorizeInspection($inspection);

        if (($inspection->status ?? null) !== 'completed') {
            return redirect()->route('client.inspections.index')
                ->with('error', 'Agreement is available only after inspection completion.');
        }

        $inspection = $this->agreementScheduleService->refresh($inspection);

        $pdf = Pdf::loadView('client.inspections.agreement-pdf', compact('inspection'))
            ->setPaper('a4', 'portrait')
            ->setOption('margin-top', 12)
            ->setOption('margin-right', 10)
            ->setOption('margin-bottom', 12)
            ->setOption('margin-left', 10)
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true);

        $clientName = Str::slug((string) ($inspection->property?->user?->name ?? 'client'));
        $propertyName = Str::slug((string) ($inspection->property?->property_name ?? $inspection->property?->property_code ?? 'property'));
        $filename = 'Client_Agreement_' . $clientName . '_' . $propertyName . '.pdf';

        return $pdf->download($filename);
    }

    public function addFindingPhotos(Request $request, Inspection $inspection, int $findingIndex)
    {
        $this->authorizeInspection($inspection);

        $validated = $request->validate([
            'finding_photos' => 'required|array|min:1',
            'finding_photos.*' => 'required|image|max:10240',
        ]);

        $findings = is_array($inspection->findings)
            ? $inspection->findings
            : (json_decode($inspection->getRawOriginal('findings') ?? '[]', true) ?? []);

        if (!array_key_exists($findingIndex, $findings)) {
            return back()->with('error', 'Finding not found.');
        }

        $existingPhotos = is_array($findings[$findingIndex]['finding_photos'] ?? null)
            ? $findings[$findingIndex]['finding_photos']
            : [];

        $disk = config('filesystems.default', 's3');
        $newPaths = [];
        foreach ((array) ($validated['finding_photos'] ?? []) as $photo) {
            if ($photo && $photo->isValid()) {
                $newPaths[] = $photo->store('inspections/finding-photos', $disk);
            }
        }

        $findings[$findingIndex]['finding_photos'] = array_values(array_filter(array_merge($existingPhotos, $newPaths)));

        $inspection->findings = $findings;
        $inspection->save();

        return back()->with('success', 'Finding photos uploaded successfully.');
    }

    /**
     * Show client quotation review page (pre-completion flow).
     */
    public function quotation(Inspection $inspection)
    {
        $this->authorizeInspection($inspection);

        $quotation = InspectionQuotation::query()
            ->where('inspection_id', $inspection->id)
            ->where('id', $inspection->active_quotation_id)
            ->first();

        if (!$quotation) {
            if (($inspection->status ?? null) === 'completed') {
                return redirect()->route('client.inspections.report', $inspection->id)
                    ->with('error', 'No active quotation is available for this completed assessment.');
            }

            return redirect()->route('client.inspections.index')
                ->with('error', 'No active quotation is available yet.');
        }

        if (($quotation->status ?? null) === 'expired') {
            return redirect()->route('client.inspections.index')
                ->with('error', 'This quotation has expired. Please contact support.');
        }

        if (($quotation->status ?? null) === 'shared') {
            $quotation->update(['status' => 'client_reviewing']);
            $inspection->update(['quotation_status' => 'client_reviewing']);
        }

        $snapshotFindings = collect($quotation->findings_snapshot ?? [])->values();

        // Backward-compatibility for legacy quotations where snapshot material_cost was
        // saved as 0. Recover from PHARFinding first, then from inspection findings JSON.
        if ($snapshotFindings->sum(fn($f) => (float) ($f['material_cost'] ?? 0)) <= 0) {
            $pharMaterialById = $inspection->pharFindings()
                ->get(['id', 'material_cost'])
                ->mapWithKeys(fn($f) => [(int) $f->id => (float) ($f->material_cost ?? 0)]);

            $inspectionFindings = collect($inspection->findings ?? [])->values();

            $snapshotFindings = $snapshotFindings->values()->map(function ($finding, $index) use ($pharMaterialById, $inspectionFindings) {
                $materialCost = (float) ($finding['material_cost'] ?? 0);

                if ($materialCost <= 0) {
                    $findingId = (int) ($finding['id'] ?? 0);
                    $materialCost = (float) ($pharMaterialById->get($findingId, 0));
                }

                if ($materialCost <= 0) {
                    $jsonFinding = $inspectionFindings->get($index, []);
                    $materialCost = (float) collect($jsonFinding['phar_materials'] ?? [])
                        ->sum(fn($m) => (float) ($m['line_total'] ?? 0));
                }

                $finding['material_cost'] = round($materialCost, 2);
                return $finding;
            })->values();
        }

        $approvedIds = collect($quotation->approved_finding_ids ?? [])->map(fn ($id) => (int) $id)->all();
        $isLocked = ($quotation->fresh()->status ?? null) === 'approved';

        return view('client.inspections.quotation', [
            'inspection' => $inspection,
            'quotation' => $quotation->fresh(),
            'snapshotFindings' => $snapshotFindings,
            'approvedIds' => $approvedIds,
            'isLocked' => $isLocked,
        ]);
    }

    /**
     * Save client quotation response and recalculate inspection totals from selected findings.
     */
    public function respondQuotation(Request $request, Inspection $inspection)
    {
        $this->authorizeInspection($inspection);

        $quotation = InspectionQuotation::query()
            ->where('inspection_id', $inspection->id)
            ->where('id', $inspection->active_quotation_id)
            ->first();

        if (!$quotation) {
            return redirect()->route('client.inspections.index')
                ->with('error', 'No active quotation is available to respond to.');
        }

        if (($quotation->status ?? null) === 'approved') {
            return redirect()->route('client.inspections.index')
                ->with('info', 'This quotation has already been approved. Wait for the updated report and agreement from admin.');
        }

        $validated = $request->validate([
            'approved_finding_ids' => 'nullable|array',
            'approved_finding_ids.*' => 'integer',
            'client_notes' => 'nullable|string|max:3000',
        ]);

        $allFindings = collect($quotation->findings_snapshot ?? [])->values();

        // Repair legacy snapshot material values (saved as 0 in older records)
        // so approval math stays scoped to the selected findings.
        if ($allFindings->sum(fn ($f) => (float) ($f['material_cost'] ?? 0)) <= 0) {
            $pharMaterialById = $inspection->pharFindings()
                ->get(['id', 'material_cost'])
                ->mapWithKeys(fn ($f) => [(int) $f->id => (float) ($f->material_cost ?? 0)]);

            $inspectionFindings = collect($inspection->findings ?? [])->values();

            $allFindings = $allFindings->values()->map(function ($finding, $index) use ($pharMaterialById, $inspectionFindings) {
                $materialCost = (float) ($finding['material_cost'] ?? 0);

                if ($materialCost <= 0) {
                    $findingId = (int) ($finding['id'] ?? 0);
                    $materialCost = (float) ($pharMaterialById->get($findingId, 0));
                }

                if ($materialCost <= 0) {
                    $jsonFinding = $inspectionFindings->get($index, []);
                    $materialCost = (float) collect($jsonFinding['phar_materials'] ?? [])
                        ->sum(fn($m) => (float) ($m['line_total'] ?? 0));
                }

                $finding['material_cost'] = round($materialCost, 2);
                return $finding;
            })->values();
        }

        $snapshotIds = $allFindings
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values();

        $submittedApprovedIds = collect($validated['approved_finding_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $approvedIds = $submittedApprovedIds
            ->filter(fn ($id) => $snapshotIds->contains($id))
            ->values();

        if ($approvedIds->isEmpty()) {
            return redirect()->route('client.inspections.quotation', $inspection->id)
                ->with('error', 'Please select at least one finding. Unselected findings are stored as deferred when you submit.');
        }

        $deferredIds = $snapshotIds->diff($approvedIds)->values();
        $approvedFindings = $allFindings->filter(fn ($f) => $approvedIds->contains((int) ($f['id'] ?? 0)))->values();

        $approvedLabour = round((float) $approvedFindings->sum(fn ($f) => (float) ($f['labour_cost'] ?? 0)), 2);
        $approvedMaterial = round((float) $approvedFindings->sum(fn ($f) => (float) ($f['material_cost'] ?? 0)), 2);
        
        // Recalculate visits from approved labour hours (1 visit = 11 working hours)
        // This ensures BDC is derived from approved findings scope, not original all-findings scope.
        $approvedLabourHours = round((float) $approvedFindings->sum(fn ($f) => (float) ($f['labour_hours'] ?? 0)), 2);
        if ($approvedLabourHours <= 0) {
            $approvedLabourHours = round((float) ($approvedLabour / (float) ($inspection->labour_hourly_rate ?? 165)), 2);
        }
        $approvedVisits = max(1, (int) ceil($approvedLabourHours / 11));
        
        // Recalculate BDC using approved visits and stored travel parameters
        $bdcCalc = new BDCCalculator();
        $bdcResult = $bdcCalc->calculateWithParams([
            'travel_distance_km'  => (float) ($inspection->bdc_distance_km ?? null),
            'travel_time_minutes' => (float) ($inspection->bdc_time_minutes ?? null),
            'visits_per_year'     => (float) $approvedVisits,
            'rate_per_km'         => (float) ($inspection->bdc_rate_per_km ?? 1.50),
            'rate_per_minute'     => (float) ($inspection->bdc_rate_per_minute ?? 1.65),
        ]);
        $approvedBdc = round((float) ($bdcResult['bdc_annual'] ?? 0), 2);
        
        $approvedTotal = round($approvedLabour + $approvedMaterial + $approvedBdc, 2);

        $quotationStatus = 'approved';
        $inspectionQuotationStatus = 'approved';

        DB::transaction(function () use (
            $quotation,
            $validated,
            $approvedIds,
            $deferredIds,
            $approvedLabour,
            $approvedMaterial,
            $approvedBdc,
            $approvedTotal,
            $quotationStatus,
            $inspection,
            $inspectionQuotationStatus,
            $approvedVisits,
            $approvedLabourHours
        ) {
            $quotation->update([
                'status' => $quotationStatus,
                'approved_finding_ids' => $approvedIds->all(),
                'deferred_finding_ids' => $deferredIds->all(),
                'approved_labour_cost' => $approvedLabour,
                'approved_material_cost' => $approvedMaterial,
                'approved_bdc_cost' => $approvedBdc,
                'approved_total' => $approvedTotal,
                'client_notes' => $validated['client_notes'] ?? null,
                'client_responded_at' => now(),
            ]);

            $trcPerVisit = round($approvedTotal / $approvedVisits, 2);

            $inspection->update([
                'frlc_annual' => $approvedLabour,
                'fmc_annual' => $approvedMaterial,
                'bdc_annual' => $approvedBdc,
                'trc_annual' => $approvedTotal,
                'trc_monthly' => $approvedTotal,
                'trc_per_visit' => $trcPerVisit,
                'bdc_visits_per_year' => $approvedVisits,
                'estimated_task_hours' => $approvedLabourHours,
                'arp_monthly' => $approvedTotal,
                'scientific_final_monthly' => $approvedTotal,
                'scientific_final_annual' => $approvedTotal,
                'arp_equivalent_final' => $approvedTotal,
                'base_package_price_snapshot' => $approvedTotal,
                'quotation_status' => $inspectionQuotationStatus,
                'quotation_approved_at' => now(),
            ]);
        });

        $propertyName = (string) ($inspection->property?->property_name ?? 'Property');
        $adminRecipients = User::role(['Super Admin', 'Administrator'])
            ->get()
            ->unique('id')
            ->values();

        if ($adminRecipients->isNotEmpty()) {
            Notification::send($adminRecipients, new ClientQuotationApprovedNotification(
                inspectionId: (int) $inspection->id,
                propertyId: $inspection->property_id ? (int) $inspection->property_id : null,
                propertyName: $propertyName,
                quoteNumber: (string) ($quotation->quote_number ?? 'N/A'),
                approvedFindings: (int) $approvedIds->count(),
            ));
        }

        return redirect()->route('client.inspections.index')
            ->with('success', 'Quotation submitted successfully. Admin will now finalize the report and agreement based on your approved findings.');
    }

    /**
     * Show Stripe payment page for starting work.
     * plan=full        → charge full ARP total at once
     * plan=per_visit   → charge visit 1 amount (TRC / visits)
     * plan=installment → charge 50% deposit now, remaining 50% later
     */
    public function workPayment(Request $request, Inspection $inspection)
    {
        $this->authorizeInspection($inspection);

        if ($inspection->status !== 'completed') {
            return redirect()->route('client.inspections.index')
                ->with('error', 'Work payment is available only for completed inspections.');
        }

        if (($inspection->work_payment_status ?? null) === 'paid') {
            return redirect()->route('client.inspections.report', $inspection->id)
                ->with('info', 'Work payment has already been completed.');
        }

        $plan = $request->query('plan', 'full');
        if (!in_array($plan, ['full', 'per_visit', 'installment'], true)) {
            $plan = 'full';
        }

        $arpTotal    = (float) ($inspection->trc_annual ?? ($this->resolveMonthlyBase($inspection) * 12));
        $totalVisits = max(1, (int) ($inspection->bdc_visits_per_year ?? 1));
        $perVisit    = round($arpTotal / $totalVisits, 2);
        $depositAmount = round($arpTotal * 0.5, 2);
        $chargeAmount = match ($plan) {
            'per_visit' => $perVisit,
            'installment' => $depositAmount,
            default => round($arpTotal, 2),
        };

        if ($chargeAmount <= 0) {
            return redirect()->route('client.inspections.report', $inspection->id)
                ->with('error', 'Pricing has not been calculated yet for this inspection.');
        }

        $stripe = new \Stripe\StripeClient(config('cashier.secret'));
        $paymentIntent = $stripe->paymentIntents->create([
            'amount'   => (int) round($chargeAmount * 100),
            'currency' => 'usd',
            'metadata' => [
                'inspection_id' => $inspection->id,
                'property_id'   => $inspection->property_id,
                'project_id'    => $inspection->project_id,
                'payment_type'  => 'work_start',
                'plan'          => $plan,
                'client_user_id'=> Auth::id(),
            ],
        ]);

        return view('client.inspections.work-payment', [
            'inspection'     => $inspection,
            'plan'           => $plan,
            'arpTotal'       => round($arpTotal, 2),
            'totalVisits'    => $totalVisits,
            'perVisit'       => $perVisit,
            'depositAmount'  => $depositAmount,
            'chargeAmount'   => $chargeAmount,
            'clientSecret'   => $paymentIntent->client_secret,
            'stripeKey'      => config('cashier.key'),
        ]);
    }

    /**
     * Confirm first work payment and start work.
     */
    public function processWorkPayment(Request $request, Inspection $inspection)
    {
        $this->authorizeInspection($inspection);

        $validated = $request->validate([
            'payment_intent_id' => 'required|string',
            'plan'              => 'required|in:full,per_visit,installment',
        ]);

        DB::beginTransaction();
        try {
            $stripe = new \Stripe\StripeClient(config('cashier.secret'));
            $paymentIntent = $stripe->paymentIntents->retrieve($validated['payment_intent_id']);

            if (($paymentIntent->status ?? null) !== 'succeeded') {
                throw new \RuntimeException('Payment not completed.');
            }

            $plan        = $validated['plan'];
            $arpTotal    = (float) ($inspection->trc_annual ?? ($this->resolveMonthlyBase($inspection) * 12));
            $totalVisits = max(1, (int) ($inspection->bdc_visits_per_year ?? 1));
            $perVisit    = round($arpTotal / $totalVisits, 2);
            $depositAmount = round($arpTotal * 0.5, 2);

            $fields = [
                'work_payment_status'          => 'paid',
                'work_payment_paid_at'         => now(),
                'work_payment_amount'          => ((float) $paymentIntent->amount_received) / 100,
                'work_payment_cadence'         => $plan === 'installment' ? 'monthly' : $plan,
                'work_stripe_payment_intent_id'=> $paymentIntent->id,
                'payment_plan'                 => $plan,
                'installment_months'           => $plan === 'per_visit' ? $totalVisits : ($plan === 'installment' ? 2 : 1),
                'installments_paid'            => 1,
                'arp_total_locked'             => round($arpTotal, 2),
                'installment_amount'           => $plan === 'per_visit' ? $perVisit : ($plan === 'installment' ? $depositAmount : round($arpTotal, 2)),
                'arp_fully_paid_at'            => $plan === 'full' ? now() : null,
                'next_installment_due_date'    => null,
            ];

            $inspection->update($fields);
            $this->inspectionInvoiceSyncService->syncProjectInvoice($inspection->fresh(['property', 'project']));
            $inspection = $this->agreementScheduleService->refresh($inspection);

            if ($inspection->project) {
                $inspection->project->update([
                    'status'             => 'in_progress',
                    'actual_start_date'  => $inspection->project->actual_start_date ?: now()->toDateString(),
                ]);
            }

            DB::commit();

            return response()->json([
                'success'  => true,
                'redirect' => route('client.inspections.report', $inspection->id),
                'message'  => 'Payment successful. Work has started.',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Client work payment failed', [
                'inspection_id' => $inspection->id,
                'error'         => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment verification failed. Please try again.',
            ], 400);
        }
    }

    /**
     * Show Stripe payment page for the next per-visit payment.
     */
    public function payInstallment(Inspection $inspection)
    {
        $this->authorizeInspection($inspection);

        if (($inspection->work_payment_status ?? 'pending') !== 'paid') {
            return redirect()->route('client.inspections.report', $inspection->id)
                ->with('error', 'Work has not been started yet.');
        }

        $paymentPlan = (string) ($inspection->payment_plan ?? 'full');
        if (!in_array($paymentPlan, ['per_visit', 'installment'], true)) {
            return redirect()->route('client.inspections.report', $inspection->id)
                ->with('info', 'This inspection is on a full-payment plan.');
        }

        $paid  = (int) ($inspection->installments_paid ?? 0);
        $total = (int) ($inspection->installment_months ?? 1);

        if ($paid >= $total) {
            return redirect()->route('client.inspections.report', $inspection->id)
                ->with('success', 'All visits have been paid. Project cost fully settled.');
        }

        $installAmount    = (float) ($inspection->installment_amount ?? 0);
        $installmentNumber = $paid + 1;

        $stripe = new \Stripe\StripeClient(config('cashier.secret'));
        $paymentIntent = $stripe->paymentIntents->create([
            'amount'   => (int) round($installAmount * 100),
            'currency' => 'usd',
            'metadata' => [
                'inspection_id'      => $inspection->id,
                'property_id'        => $inspection->property_id,
                'payment_type'       => 'per_visit',
                'visit_number'       => $installmentNumber,
                'payment_plan'       => $paymentPlan,
                'client_user_id'     => Auth::id(),
            ],
        ]);

        return view('client.inspections.pay-installment', [
            'inspection'        => $inspection,
            'installAmount'     => $installAmount,
            'installmentNumber' => $installmentNumber,
            'totalInstallments' => $total,
            'arpTotal'          => (float) ($inspection->arp_total_locked ?? 0),
            'amountPaidSoFar'   => round($installAmount * $paid, 2),
            'paymentPlan'       => $paymentPlan,
            'clientSecret'      => $paymentIntent->client_secret,
            'stripeKey'         => config('cashier.key'),
        ]);
    }

    /**
     * Process a per-visit payment.
     */
    public function processInstallment(Request $request, Inspection $inspection)
    {
        $this->authorizeInspection($inspection);

        if (!in_array(($inspection->payment_plan ?? 'full'), ['per_visit', 'installment'], true)) {
            abort(403, 'This inspection is not on an installment-based payment plan.');
        }

        $validated = $request->validate([
            'payment_intent_id' => 'required|string',
        ]);

        DB::beginTransaction();
        try {
            $stripe = new \Stripe\StripeClient(config('cashier.secret'));
            $paymentIntent = $stripe->paymentIntents->retrieve($validated['payment_intent_id']);

            if (($paymentIntent->status ?? null) !== 'succeeded') {
                throw new \RuntimeException('Payment not completed.');
            }

            $paid  = (int) ($inspection->installments_paid ?? 0) + 1;
            $total = (int) ($inspection->installment_months ?? 1);

            $fields = [
                'installments_paid'          => $paid,
                'next_installment_due_date'  => null,
                'arp_fully_paid_at'          => $paid >= $total ? now() : null,
            ];

            $inspection->update($fields);
            $this->inspectionInvoiceSyncService->syncProjectInvoice($inspection->fresh(['property', 'project']));

            DB::commit();

            $remaining = $total - $paid;
            $isPerVisitPlan = ($inspection->payment_plan ?? 'full') === 'per_visit';
            $message   = $paid >= $total
                ? 'Final payment received — project cost fully settled!'
                : ($isPerVisitPlan
                    ? "Visit {$paid} of {$total} paid. {$remaining} visit(s) remaining."
                    : "Installment {$paid} of {$total} paid. {$remaining} installment(s) remaining.");

            return response()->json([
                'success'  => true,
                'redirect' => route('client.inspections.report', $inspection->id),
                'message'  => $message,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Per-visit payment failed', [
                'inspection_id' => $inspection->id,
                'error'         => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment verification failed. Please try again.',
            ], 400);
        }
    }

    /**
     * Resolve the base monthly ARP amount from whichever PHAR field is populated.
     */
    private function resolveMonthlyBase(Inspection $inspection): float
    {
        return (float) max(
            (float) ($inspection->scientific_final_monthly ?? 0),
            (float) ($inspection->arp_equivalent_final ?? 0),
            (float) ($inspection->base_package_price_snapshot ?? 0),
            (float) ($inspection->arp_monthly ?? 0),
            (float) ($inspection->trc_monthly ?? 0),
        );
    }

    /**
     * Show form to schedule inspection for a property
     * NEW FLOW: Client can schedule immediately after adding property (no approval needed)
     */
    public function scheduleCreate(Property $property)
    {
        // Verify property belongs to current user
        if ($property->user_id !== Auth::id()) {
            abort(403, 'Unauthorized: This property does not belong to you.');
        }

        // Check if inspection already scheduled and paid
        $existingInspection = Inspection::where('property_id', $property->id)
            ->where('inspection_fee_status', 'paid')
            ->where('status', '!=', 'cancelled')
            ->first();

        if ($existingInspection) {
            return redirect()->route('client.properties.index')
                ->with('info', 'An inspection has already been scheduled and paid for this property.');
        }

        // Create Stripe Payment Intent
        $stripe = new \Stripe\StripeClient(config('cashier.secret'));
        
        $paymentIntent = $stripe->paymentIntents->create([
            'amount' => self::INSPECTION_FEE_CENTS,
            'currency' => 'usd',
            'metadata' => [
                'property_id' => $property->id,
                'user_id' => Auth::id(),
                'property_name' => $property->property_name,
            ],
        ]);

        return view('client.inspections.schedule', [
            'property' => $property,
            'inspectionFee' => self::INSPECTION_FEE_DOLLARS,
            'clientSecret' => $paymentIntent->client_secret,
            'stripeKey' => config('cashier.key'),
        ]);
    }

    /**
     * Store inspection schedule and process payment
     */
    public function scheduleStore(Request $request, Property $property)
    {
        // Verify property belongs to current user
        if ($property->user_id !== Auth::id()) {
            abort(403, 'Unauthorized: This property does not belong to you.');
        }

        $validated = $request->validate([
            'preferred_date' => 'required|date|after_or_equal:today',
            'preferred_time' => 'nullable|date_format:H:i',
            'special_notes' => 'nullable|string|max:1000',
            'payment_intent_id' => 'required|string',
        ]);

        DB::beginTransaction();
        try {
            // Verify payment intent
            $stripe = new \Stripe\StripeClient(config('cashier.secret'));
            $paymentIntent = $stripe->paymentIntents->retrieve($validated['payment_intent_id']);

            if ($paymentIntent->status !== 'succeeded') {
                throw new \Exception('Payment not completed successfully.');
            }

            $project = \App\Models\Project::firstOrCreate(
                ['property_id' => $property->id],
                [
                    'title' => 'Property Inspection - ' . $property->property_name,
                    'description' => 'Client scheduled inspection for ' . $property->property_name,
                    'status' => 'pending',
                    'user_id' => $property->user_id,
                    'managed_by' => $property->project_manager_id,
                    'created_by' => Auth::id(),
                    'project_number' => 'PRJ-' . strtoupper(\Illuminate\Support\Str::random(8)),
                ]
            );

            // Create inspection record with paid status
            $inspection = Inspection::create([
                'property_id' => $property->id,
                'project_id' => $project->id,
                'scheduled_date' => $validated['preferred_date'] . ' ' . ($validated['preferred_time'] ?? '09:00'),
                'status' => 'scheduled',
                'summary' => $validated['special_notes'] ?? null,
                'inspection_fee_amount' => self::INSPECTION_FEE_DOLLARS,
                'inspection_fee_status' => 'paid',
                'inspection_fee_paid_at' => now(),
            ]);

            $this->ensureInspectionFeeInvoice($inspection->fresh(['property', 'project']));

            // Update property status to awaiting_inspection
            $property->update(['status' => 'awaiting_inspection']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Inspection scheduled successfully!',
                'redirect' => route('client.properties.index'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Inspection scheduling error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your inspection request. Please try again.',
            ], 400);
        }
    }

    /**
     * Handle successful payment redirect from Stripe (legacy Checkout Session flow).
     * Verifies the Stripe session before marking inspection as paid.
     */
    public function checkoutSuccess(Request $request)
    {
        $inspectionId = $request->get('inspection_id');
        $sessionId    = $request->get('session_id');

        if (!$inspectionId || !$sessionId) {
            return redirect()->route('client.properties.index')
                ->with('error', 'Invalid inspection request.');
        }

        $inspection = Inspection::find($inspectionId);

        if (!$inspection || (int) $inspection->property->user_id !== (int) Auth::id()) {
            abort(403, 'Unauthorized access.');
        }

        // Already paid — idempotent
        if ($inspection->inspection_fee_status === 'paid') {
            return redirect()->route('client.properties.index')
                ->with('success', 'Inspection already confirmed.');
        }

        // Verify the Stripe Checkout Session is actually paid
        try {
            $stripe          = new \Stripe\StripeClient(config('cashier.secret'));
            $checkoutSession = $stripe->checkout->sessions->retrieve($sessionId);

            if (($checkoutSession->payment_status ?? null) !== 'paid') {
                return redirect()->route('client.properties.index')
                    ->with('error', 'Payment has not been completed. Please try scheduling again.');
            }
        } catch (\Throwable $e) {
            \Log::error('Stripe checkout session verification failed', [
                'inspection_id' => $inspectionId,
                'session_id'    => $sessionId,
                'error'         => $e->getMessage(),
            ]);
            return redirect()->route('client.properties.index')
                ->with('error', 'Payment verification failed. Please contact support.');
        }

        // Safe to mark as paid now
        $inspection->update([
            'inspection_fee_status'  => 'paid',
            'inspection_fee_paid_at' => now(),
        ]);

        $inspection->property->update([
            'status' => 'awaiting_inspection',
        ]);

        $this->ensureInspectionFeeInvoice($inspection->fresh(['property', 'project']));

        return redirect()->route('client.properties.index')
            ->with('success', 'Inspection scheduled successfully! Your inspection fee of $' . number_format(self::INSPECTION_FEE_DOLLARS, 2) . ' has been processed. An inspector will be assigned to your property shortly.');
    }

    /**
     * Handle payment cancellation
     */
    public function checkoutCancel(Request $request)
    {
        $inspectionId = $request->get('inspection_id');

        if ($inspectionId) {
            $inspection = Inspection::find($inspectionId);
            if ($inspection) {
                // Mark inspection as cancelled since payment wasn't completed
                $inspection->update(['status' => 'cancelled']);
            }
        }

        return redirect()->route('client.properties.index')
            ->with('info', 'Inspection scheduling was cancelled. You can try again anytime.');
    }

    protected function ensureInspectionFeeInvoice(Inspection $inspection): void
    {
        if (!$inspection->project_id) {
            $project = \App\Models\Project::firstOrCreate(
                ['property_id' => $inspection->property_id],
                [
                    'title' => 'Property Inspection - ' . ($inspection->property->property_name ?? 'Property'),
                    'description' => 'Client scheduled inspection for ' . ($inspection->property->property_name ?? 'Property'),
                    'status' => 'pending',
                    'user_id' => $inspection->property->user_id,
                    'managed_by' => $inspection->property->project_manager_id,
                    'created_by' => Auth::id(),
                    'project_number' => 'PRJ-' . strtoupper(\Illuminate\Support\Str::random(8)),
                ]
            );

            $inspection->project_id = $project->id;
            $inspection->save();
            $inspection->refresh();
        }

        $this->inspectionInvoiceSyncService->syncInspectionFeeInvoice($inspection);
    }
}
