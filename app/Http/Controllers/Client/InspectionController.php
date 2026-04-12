<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\Inspection;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InspectionController extends Controller
{
    // Inspection fee in cents (for Stripe)
    private const INSPECTION_FEE_CENTS = 29900; // $299.00
    private const INSPECTION_FEE_DOLLARS = 299;

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
        if (!$inspection->property || (int) $inspection->property->user_id !== (int) Auth::id()) {
            abort(403, 'Unauthorized access to this inspection report.');
        }

        $findings = \App\Models\PHARFinding::where('inspection_id', $inspection->id)
            ->orderBy('id')
            ->get();

        $materials = \App\Models\InspectionMaterial::where('inspection_id', $inspection->id)
            ->orderBy('id')
            ->get();

        return view('client.inspections.report', compact('inspection', 'findings', 'materials'));
    }

    /**
     * Show Stripe payment page for starting work (monthly/annual).
     */
    public function workPayment(Request $request, Inspection $inspection)
    {
        if (!$inspection->property || (int) $inspection->property->user_id !== (int) Auth::id()) {
            abort(403, 'Unauthorized access to this payment.');
        }

        if ($inspection->status !== 'completed') {
            return redirect()->route('client.inspections.index')
                ->with('error', 'Work payment is available only for completed inspections.');
        }

        if (($inspection->work_payment_status ?? null) === 'paid') {
            return redirect()->route('client.inspections.report', $inspection->id)
                ->with('info', 'Work payment has already been completed.');
        }

        $cadence = $request->query('cadence', 'monthly');
        if (!in_array($cadence, ['monthly', 'annual'], true)) {
            $cadence = 'monthly';
        }

        $monthlyBase = (float) max(
            (float) ($inspection->scientific_final_monthly ?? 0),
            (float) ($inspection->arp_equivalent_final ?? 0),
            (float) ($inspection->base_package_price_snapshot ?? 0),
            (float) ($inspection->arp_monthly ?? 0),
            (float) ($inspection->trc_monthly ?? 0),
        );

        if ($monthlyBase <= 0) {
            return redirect()->route('client.inspections.report', $inspection->id)
                ->with('error', 'Pricing has not been calculated yet for this inspection.');
        }

        $amount = $cadence === 'annual' ? ($monthlyBase * 12) : $monthlyBase;

        $stripe = new \Stripe\StripeClient(config('cashier.secret'));
        $paymentIntent = $stripe->paymentIntents->create([
            'amount' => (int) round($amount * 100),
            'currency' => 'usd',
            'metadata' => [
                'inspection_id' => $inspection->id,
                'property_id' => $inspection->property_id,
                'project_id' => $inspection->project_id,
                'payment_type' => 'work_start',
                'cadence' => $cadence,
                'client_user_id' => Auth::id(),
            ],
        ]);

        return view('client.inspections.work-payment', [
            'inspection' => $inspection,
            'cadence' => $cadence,
            'amount' => $amount,
            'monthlyBase' => $monthlyBase,
            'clientSecret' => $paymentIntent->client_secret,
            'stripeKey' => config('cashier.key'),
        ]);
    }

    /**
     * Confirm Stripe payment and start work.
     */
    public function processWorkPayment(Request $request, Inspection $inspection)
    {
        if (!$inspection->property || (int) $inspection->property->user_id !== (int) Auth::id()) {
            abort(403, 'Unauthorized payment action.');
        }

        $validated = $request->validate([
            'payment_intent_id' => 'required|string',
            'cadence' => 'required|in:monthly,annual',
        ]);

        DB::beginTransaction();
        try {
            $stripe = new \Stripe\StripeClient(config('cashier.secret'));
            $paymentIntent = $stripe->paymentIntents->retrieve($validated['payment_intent_id']);

            if (($paymentIntent->status ?? null) !== 'succeeded') {
                throw new \RuntimeException('Payment not completed.');
            }

            $inspection->update([
                'work_payment_status' => 'paid',
                'work_payment_paid_at' => now(),
                'work_payment_amount' => ((float) $paymentIntent->amount_received) / 100,
                'work_payment_cadence' => $validated['cadence'],
                'work_stripe_payment_intent_id' => $paymentIntent->id,
            ]);

            if ($inspection->project) {
                $inspection->project->update([
                    'status' => 'in_progress',
                    'actual_start_date' => $inspection->project->actual_start_date ?: now()->toDateString(),
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'redirect' => route('client.inspections.report', $inspection->id),
                'message' => 'Payment successful. Work has started.',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Client work payment failed', [
                'inspection_id' => $inspection->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment verification failed. Please try again.',
            ], 400);
        }
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
     * Handle successful payment redirect from Stripe
     */
    public function checkoutSuccess(Request $request)
    {
        $inspectionId = $request->get('inspection_id');

        if (!$inspectionId) {
            return redirect()->route('client.properties.index')
                ->with('error', 'Invalid inspection request.');
        }

        $inspection = Inspection::find($inspectionId);

        if (!$inspection || $inspection->property->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access.');
        }

        // Update inspection fee status to paid
        $inspection->update([
            'inspection_fee_status' => 'paid',
            'inspection_fee_paid_at' => now(),
        ]);

        // Update property status to awaiting_inspection
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
        if (!$inspection->property || !$inspection->property->user_id) {
            return;
        }

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

        $amount = (float) ($inspection->inspection_fee_amount ?? 0);
        if ($amount <= 0) {
            return;
        }

        $userId = (int) $inspection->property->user_id;
        $projectId = (int) $inspection->project_id;

        $existingInvoice = Invoice::where('user_id', $userId)
            ->where('project_id', $projectId)
            ->where('type', 'additional')
            ->get()
            ->first(function (Invoice $invoice) use ($inspection) {
                return (int) data_get($invoice->line_items, '0.inspection_id') === (int) $inspection->id;
            });

        if ($existingInvoice) {
            return;
        }

        $invoiceNumber = 'INV-INSP-' . now()->format('Ymd') . '-' . $inspection->id;
        $counter = 1;
        while (Invoice::where('invoice_number', $invoiceNumber)->exists()) {
            $invoiceNumber = 'INV-INSP-' . now()->format('Ymd') . '-' . $inspection->id . '-' . $counter;
            $counter++;
        }

        $isPaid = ($inspection->inspection_fee_status ?? 'pending') === 'paid';

        Invoice::create([
            'invoice_number' => $invoiceNumber,
            'project_id' => $projectId,
            'user_id' => $userId,
            'type' => 'additional',
            'subtotal' => $amount,
            'tax' => 0,
            'total' => $amount,
            'paid_amount' => $isPaid ? $amount : 0,
            'balance' => $isPaid ? 0 : $amount,
            'status' => $isPaid ? 'paid' : 'sent',
            'issue_date' => optional($inspection->inspection_fee_paid_at)->toDateString() ?? now()->toDateString(),
            'due_date' => $isPaid
                ? (optional($inspection->inspection_fee_paid_at)->toDateString() ?? now()->toDateString())
                : now()->addDays(14)->toDateString(),
            'line_items' => [
                [
                    'description' => 'Pre-Inspection Fee - ' . ($inspection->property?->property_name ?? 'Property'),
                    'inspection_id' => $inspection->id,
                    'quantity' => 1,
                    'unit_price' => $amount,
                    'total' => $amount,
                ],
            ],
            'notes' => 'Auto-generated pre-inspection fee invoice for inspection #' . $inspection->id,
        ]);
    }
}
