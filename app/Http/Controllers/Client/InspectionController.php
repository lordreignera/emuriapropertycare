<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\Inspection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InspectionController extends Controller
{
    // Inspection fee in cents (for Stripe)
    private const INSPECTION_FEE_CENTS = 29900; // $299.00
    private const INSPECTION_FEE_DOLLARS = 299;

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

            // Create inspection record with paid status
            $inspection = Inspection::create([
                'property_id' => $property->id,
                'project_id' => null, // Will be set when project is created later
                'scheduled_date' => $validated['preferred_date'] . ' ' . ($validated['preferred_time'] ?? '09:00'),
                'status' => 'scheduled',
                'notes' => $validated['special_notes'] ?? null,
                'inspection_fee_amount' => self::INSPECTION_FEE_DOLLARS,
                'inspection_fee_status' => 'paid',
                'inspection_fee_paid_at' => now(),
            ]);

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
}
