<?php

namespace App\Services;

use App\Models\Inspection;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

class InvoicePaymentService
{
    public function apply(Invoice $invoice, float $amount, string $paymentIntentId): Invoice
    {
        return DB::transaction(function () use ($invoice, $amount, $paymentIntentId) {
            $invoice = Invoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();

            if ($invoice->stripe_invoice_id === $paymentIntentId) {
                return $invoice->fresh(['project.property']);
            }

            $total = round((float) ($invoice->total ?? 0), 2);
            $paidAmount = round((float) ($invoice->paid_amount ?? 0) + $amount, 2);
            $paidAmount = min($paidAmount, $total);
            $balance = max(0, round($total - $paidAmount, 2));

            $invoice->update([
                'paid_amount' => $paidAmount,
                'balance' => $balance,
                'status' => $balance <= 0 ? 'paid' : 'partial',
                'paid_at' => $balance <= 0 ? now()->toDateString() : $invoice->paid_at,
                'stripe_invoice_id' => $paymentIntentId,
            ]);

            if ($balance <= 0) {
                $inspectionIds = collect($invoice->line_items ?? [])
                    ->pluck('inspection_id')
                    ->filter()
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values();

                Inspection::whereIn('id', $inspectionIds)
                    ->where('inspection_fee_status', '!=', 'paid')
                    ->update([
                        'inspection_fee_status' => 'paid',
                        'inspection_fee_paid_at' => now(),
                    ]);
            }

            return $invoice->fresh(['project.property']);
        });
    }
}
