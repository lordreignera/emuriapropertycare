<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\Inspection;
use App\Services\AgreementScheduleService;
use App\Services\InspectionInvoiceSyncService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

        if (($inspection->status ?? null) === 'completed') {
            $inspection = $this->agreementScheduleService->refresh($inspection);
        }

        $findings = \App\Models\PHARFinding::where('inspection_id', $inspection->id)
            ->orderBy('id')
            ->get();

        return view('client.inspections.report', compact('inspection', 'findings'));
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
     * Show Stripe payment page for starting work.
     * plan=full  → charge full ARP total at once
     * plan=installment → charge ARP/12 monthly starting now
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
        if (!in_array($plan, ['full', 'per_visit'], true)) {
            $plan = 'full';
        }

        $arpTotal    = (float) ($inspection->trc_annual ?? ($this->resolveMonthlyBase($inspection) * 12));
        $totalVisits = max(1, (int) ($inspection->bdc_visits_per_year ?? 1));
        $perVisit    = round($arpTotal / $totalVisits, 2);
        $chargeAmount = $plan === 'full' ? round($arpTotal, 2) : $perVisit;

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
            'plan'              => 'required|in:full,per_visit',
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

            $fields = [
                'work_payment_status'          => 'paid',
                'work_payment_paid_at'         => now(),
                'work_payment_amount'          => ((float) $paymentIntent->amount_received) / 100,
                'work_payment_cadence'         => $plan,
                'work_stripe_payment_intent_id'=> $paymentIntent->id,
                'payment_plan'                 => $plan,
                'installment_months'           => $plan === 'per_visit' ? $totalVisits : 1,
                'installments_paid'            => 1,
                'arp_total_locked'             => round($arpTotal, 2),
                'installment_amount'           => $plan === 'per_visit' ? $perVisit : round($arpTotal, 2),
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

        if (($inspection->payment_plan ?? 'full') !== 'per_visit') {
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

        if (($inspection->payment_plan ?? 'full') !== 'per_visit') {
            abort(403, 'This inspection is not on a per-visit payment plan.');
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
            $message   = $paid >= $total
                ? 'Final visit payment received — project cost fully settled!'
                : "Visit {$paid} of {$total} paid. {$remaining} visit(s) remaining.";

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
