<?php

namespace App\Http\Controllers;

use App\Models\Inspection;
use App\Models\User;
use App\Notifications\InspectionFeePaidNotification;
use App\Notifications\WorkPaymentReceivedNotification;
use App\Notifications\InstallmentPaymentReceivedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class StripeWebhookController extends Controller
{
    /**
     * Handle incoming Stripe webhook events.
     * 
     * This ensures every payment is verified server-side, regardless of frontend issues.
     * Supports: payment_intent.succeeded, payment_intent.payment_failed, charge.refunded
     */
    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $sig = $request->header('Stripe-Signature');
        $secret = config('cashier.webhook.secret');

        if (!$secret) {
            Log::error('Stripe webhook: STRIPE_WEBHOOK_SECRET not configured');
            return response('No webhook secret configured', 500);
        }

        // Verify webhook signature
        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sig, $secret);
        } catch (\UnexpectedValueException $e) {
            Log::warning('Stripe webhook: Invalid payload', ['error' => $e->getMessage()]);
            return response('Invalid payload', 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            Log::warning('Stripe webhook: Invalid signature', ['error' => $e->getMessage()]);
            return response('Invalid signature', 403);
        }

        Log::info('Stripe webhook received', [
            'type' => $event->type,
            'id' => $event->id,
        ]);

        // Route to appropriate handler
        switch ($event->type) {
            case 'payment_intent.succeeded':
                $this->handlePaymentIntentSucceeded($event->data->object);
                break;

            case 'payment_intent.payment_failed':
                $this->handlePaymentIntentFailed($event->data->object);
                break;

            case 'charge.refunded':
                $this->handleChargeRefunded($event->data->object);
                break;

            default:
                Log::info('Stripe webhook: Unhandled event type', ['type' => $event->type]);
        }

        return response('OK', 200);
    }

    /**
     * Handle payment_intent.succeeded webhook.
     * Confirms inspection/work/installment payments and sends notifications.
     */
    private function handlePaymentIntentSucceeded(\Stripe\PaymentIntent $intent)
    {
        try {
            $metadata = $intent->metadata->toArray();
            $paymentType = $metadata['payment_type'] ?? null;
            $amount = round($intent->amount_received / 100, 2);

            Log::info('Payment succeeded', [
                'intent_id' => $intent->id,
                'amount' => $amount,
                'payment_type' => $paymentType,
                'metadata' => $metadata,
            ]);

            // Determine the payment type and handle accordingly
            if ($paymentType === 'inspection_fee') {
                $this->confirmInspectionFeePayment($intent, $metadata, $amount);
            } elseif ($paymentType === 'work_start') {
                $this->confirmWorkPayment($intent, $metadata, $amount);
            } elseif ($paymentType === 'per_visit') {
                $this->confirmInstallmentPayment($intent, $metadata, $amount);
            } else {
                Log::warning('Webhook: Unknown payment_type', ['metadata' => $metadata]);
            }
        } catch (\Throwable $e) {
            Log::error('Webhook payment_intent.succeeded handler failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Handle payment_intent.payment_failed webhook.
     * Logs failures, marks inspection as failed, notifies admins.
     */
    private function handlePaymentIntentFailed(\Stripe\PaymentIntent $intent)
    {
        try {
            $metadata = $intent->metadata->toArray();
            $inspectionId = $metadata['inspection_id'] ?? null;
            $propertyId = $metadata['property_id'] ?? null;
            $paymentType = $metadata['payment_type'] ?? 'unknown';

            Log::error('Payment failed', [
                'intent_id' => $intent->id,
                'amount' => $intent->amount / 100,
                'payment_type' => $paymentType,
                'inspection_id' => $inspectionId,
                'last_error' => $intent->last_payment_error ? $intent->last_payment_error->message : 'Unknown error',
            ]);

            if ($inspectionId) {
                $inspection = Inspection::find($inspectionId);
                if ($inspection) {
                    // Update status based on payment type
                    if ($paymentType === 'inspection_fee') {
                        $inspection->update(['inspection_fee_status' => 'failed']);
                    } elseif ($paymentType === 'work_start') {
                        $inspection->update(['work_payment_status' => 'failed']);
                    }

                    // Notify admins of payment failure
                    $adminRecipients = User::role(['Super Admin', 'Administrator', 'Project Manager'])
                        ->get()->unique('id')->values();
                    if ($adminRecipients->isNotEmpty()) {
                        Notification::send($adminRecipients, new \App\Notifications\PaymentFailedNotification(
                            inspectionId: $inspection->id,
                            propertyId: $inspection->property_id,
                            propertyName: $inspection->property->property_name ?? 'Property',
                            paymentType: $paymentType,
                            errorMessage: $intent->last_payment_error->message ?? 'Payment declined',
                            amount: round($intent->amount / 100, 2),
                        ));
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::error('Webhook payment_intent.payment_failed handler failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle charge.refunded webhook.
     * Logs refunds for audit trail.
     */
    private function handleChargeRefunded(\Stripe\Charge $charge)
    {
        try {
            Log::info('Charge refunded', [
                'charge_id' => $charge->id,
                'amount_refunded' => round($charge->amount_refunded / 100, 2),
                'payment_intent_id' => $charge->payment_intent,
            ]);
        } catch (\Throwable $e) {
            Log::error('Webhook charge.refunded handler failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Confirm inspection fee payment from webhook.
     */
    private function confirmInspectionFeePayment(\Stripe\PaymentIntent $intent, array $metadata, float $amount)
    {
        DB::beginTransaction();
        try {
            $propertyId = $metadata['property_id'] ?? null;
            if (!$propertyId) {
                throw new \Exception('No property_id in metadata');
            }

            // The inspection should already be created by scheduleStore(),
            // so we just verify and mark as paid if needed
            $inspection = Inspection::where('property_id', $propertyId)
                ->where('inspection_fee_status', 'pending')
                ->where('status', 'scheduled')
                ->first();

            if ($inspection) {
                $inspection->update([
                    'inspection_fee_status' => 'paid',
                    'inspection_fee_paid_at' => now(),
                    'stripe_payment_intent_id' => $intent->id,
                    'inspection_fee_amount' => $amount,
                ]);

                // Notify admins
                $adminRecipients = User::role(['Super Admin', 'Administrator', 'Project Manager', 'Inspector', 'Technician', 'Store Manager'])
                    ->get()->unique('id')->values();
                if ($adminRecipients->isNotEmpty()) {
                    Notification::send($adminRecipients, new InspectionFeePaidNotification(
                        inspectionId: $inspection->id,
                        propertyId: $inspection->property_id,
                        propertyName: $inspection->property->property_name ?? 'Property',
                        propertyCode: $inspection->property->property_code ?? 'N/A',
                        amount: $amount,
                        clientName: $inspection->property->user->name ?? 'Client',
                    ));
                }

                Log::info('Inspection fee payment confirmed via webhook', [
                    'inspection_id' => $inspection->id,
                    'amount' => $amount,
                ]);
            } else {
                Log::warning('No pending inspection found for property', [
                    'property_id' => $propertyId,
                ]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Confirmation of inspection fee payment failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Confirm work payment from webhook.
     */
    private function confirmWorkPayment(\Stripe\PaymentIntent $intent, array $metadata, float $amount)
    {
        DB::beginTransaction();
        try {
            $inspectionId = $metadata['inspection_id'] ?? null;
            if (!$inspectionId) {
                throw new \Exception('No inspection_id in metadata');
            }

            $inspection = Inspection::find($inspectionId);
            if (!$inspection) {
                throw new \Exception("Inspection {$inspectionId} not found");
            }

            // Only mark as paid if currently pending
            if (($inspection->work_payment_status ?? 'pending') === 'pending') {
                $inspection->update([
                    'work_payment_status' => 'paid',
                    'work_payment_paid_at' => now(),
                    'work_stripe_payment_intent_id' => $intent->id,
                    'work_payment_amount' => $amount,
                ]);

                // Notify admins
                $adminRecipients = User::role(['Super Admin', 'Administrator', 'Project Manager'])
                    ->get()->unique('id')->values();
                if ($adminRecipients->isNotEmpty()) {
                    Notification::send($adminRecipients, new WorkPaymentReceivedNotification(
                        inspectionId: $inspection->id,
                        propertyId: $inspection->property_id,
                        propertyName: $inspection->property->property_name ?? 'Property',
                        propertyCode: $inspection->property->property_code ?? 'N/A',
                        amount: $amount,
                        clientName: $inspection->property->user->name ?? 'Client',
                        plan: $metadata['plan'] ?? 'full',
                    ));
                }

                Log::info('Work payment confirmed via webhook', [
                    'inspection_id' => $inspection->id,
                    'amount' => $amount,
                ]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Confirmation of work payment failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Confirm installment/per-visit payment from webhook.
     */
    private function confirmInstallmentPayment(\Stripe\PaymentIntent $intent, array $metadata, float $amount)
    {
        DB::beginTransaction();
        try {
            $inspectionId = $metadata['inspection_id'] ?? null;
            if (!$inspectionId) {
                throw new \Exception('No inspection_id in metadata');
            }

            $inspection = Inspection::find($inspectionId);
            if (!$inspection) {
                throw new \Exception("Inspection {$inspectionId} not found");
            }

            $paid = (int) ($inspection->installments_paid ?? 0) + 1;
            $total = (int) ($inspection->installment_months ?? 1);

            $inspection->update([
                'installments_paid' => $paid,
                'arp_fully_paid_at' => $paid >= $total ? now() : null,
            ]);

            // Notify admins
            $adminRecipients = User::role(['Super Admin', 'Administrator', 'Project Manager'])
                ->get()->unique('id')->values();
            if ($adminRecipients->isNotEmpty()) {
                Notification::send($adminRecipients, new InstallmentPaymentReceivedNotification(
                    inspectionId: $inspection->id,
                    propertyId: $inspection->property_id,
                    propertyName: $inspection->property->property_name ?? 'Property',
                    propertyCode: $inspection->property->property_code ?? 'N/A',
                    amount: $amount,
                    clientName: $inspection->property->user->name ?? 'Client',
                    installmentNumber: $paid,
                    totalInstallments: $total,
                    plan: $inspection->payment_plan ?? 'installment',
                ));
            }

            Log::info('Installment payment confirmed via webhook', [
                'inspection_id' => $inspection->id,
                'paid' => $paid,
                'total' => $total,
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Confirmation of installment payment failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
