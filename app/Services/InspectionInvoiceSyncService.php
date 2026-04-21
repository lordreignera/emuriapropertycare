<?php

namespace App\Services;

use App\Models\Inspection;
use App\Models\Invoice;

class InspectionInvoiceSyncService
{
    public function syncProjectInvoice(Inspection $inspection): ?Invoice
    {
        if (! $inspection->project_id || ! $inspection->property || ! $inspection->property->user_id) {
            return null;
        }

        $total = $this->resolveProjectInvoiceTotal($inspection);
        if ($total <= 0) {
            return null;
        }

        $userId = (int) $inspection->property->user_id;
        $projectId = (int) $inspection->project_id;
        $existingInvoice = Invoice::where('user_id', $userId)
            ->where('project_id', $projectId)
            ->where('type', 'project')
            ->first();

        $paidAmount = $this->resolveProjectPaidAmount($inspection, $total);
        $balance = max(0, round($total - $paidAmount, 2));
        $status = $paidAmount <= 0 ? 'sent' : ($balance > 0 ? 'partial' : 'paid');
        $paidAt = $balance <= 0 ? ($inspection->arp_fully_paid_at ?? $inspection->work_payment_paid_at) : null;

        $attributes = [
            'project_id' => $projectId,
            'user_id' => $userId,
            'type' => 'project',
            'subtotal' => $total,
            'tax' => 0,
            'total' => $total,
            'paid_amount' => $paidAmount,
            'balance' => $balance,
            'status' => $status,
            'paid_at' => optional($paidAt)?->toDateString(),
            'issue_date' => optional($existingInvoice?->issue_date)->toDateString()
                ?? optional($inspection->completed_date)->toDateString()
                ?? now()->toDateString(),
            'due_date' => optional($existingInvoice?->due_date)->toDateString()
                ?? now()->addDays(14)->toDateString(),
            'line_items' => [
                [
                    'description' => 'Project Work - ' . ($inspection->property?->property_name ?? 'Property'),
                    'inspection_id' => $inspection->id,
                    'quantity' => 1,
                    'unit_price' => $total,
                    'total' => $total,
                ],
            ],
            'notes' => 'Auto-synced project invoice for inspection #' . $inspection->id,
        ];

        if ($existingInvoice) {
            $existingInvoice->update($attributes);

            return $existingInvoice->fresh();
        }

        $attributes['invoice_number'] = $this->nextInvoiceNumber('INV-' . now()->format('Ymd') . '-' . $inspection->id);

        return Invoice::create($attributes);
    }

    public function syncInspectionFeeInvoice(Inspection $inspection): ?Invoice
    {
        if (! $inspection->property || ! $inspection->property->user_id) {
            return null;
        }

        $amount = round((float) ($inspection->inspection_fee_amount ?? 0), 2);
        if ($amount <= 0) {
            return null;
        }

        $userId = (int) $inspection->property->user_id;
        $projectId = (int) ($inspection->project_id ?? 0);
        if ($projectId <= 0) {
            return null;
        }

        $existingInvoice = Invoice::where('user_id', $userId)
            ->where('project_id', $projectId)
            ->where('type', 'additional')
            ->get()
            ->first(function (Invoice $invoice) use ($inspection) {
                return (int) data_get($invoice->line_items, '0.inspection_id') === (int) $inspection->id;
            });

        $isPaid = ($inspection->inspection_fee_status ?? 'pending') === 'paid';
        $paidAt = $isPaid ? $inspection->inspection_fee_paid_at : null;

        $attributes = [
            'project_id' => $projectId,
            'user_id' => $userId,
            'type' => 'additional',
            'subtotal' => $amount,
            'tax' => 0,
            'total' => $amount,
            'paid_amount' => $isPaid ? $amount : 0,
            'balance' => $isPaid ? 0 : $amount,
            'status' => $isPaid ? 'paid' : 'sent',
            'paid_at' => optional($paidAt)?->toDateString(),
            'issue_date' => optional($paidAt)->toDateString()
                ?? optional($existingInvoice?->issue_date)->toDateString()
                ?? now()->toDateString(),
            'due_date' => $isPaid
                ? (optional($paidAt)->toDateString() ?? now()->toDateString())
                : (optional($existingInvoice?->due_date)->toDateString() ?? now()->addDays(14)->toDateString()),
            'line_items' => [
                [
                    'description' => 'Pre-Inspection Fee - ' . ($inspection->property?->property_name ?? 'Property'),
                    'inspection_id' => $inspection->id,
                    'quantity' => 1,
                    'unit_price' => $amount,
                    'total' => $amount,
                ],
            ],
            'notes' => 'Auto-synced pre-inspection fee invoice for inspection #' . $inspection->id,
        ];

        if ($existingInvoice) {
            $existingInvoice->update($attributes);

            return $existingInvoice->fresh();
        }

        $attributes['invoice_number'] = $this->nextInvoiceNumber('INV-INSP-' . now()->format('Ymd') . '-' . $inspection->id);

        return Invoice::create($attributes);
    }

    private function resolveProjectInvoiceTotal(Inspection $inspection): float
    {
        return round(max(
            (float) ($inspection->arp_total_locked ?? 0),
            (float) ($inspection->trc_annual ?? 0),
            (float) ($inspection->scientific_final_annual ?? 0),
            (float) ($inspection->trc_monthly ?? 0),
            (float) ($inspection->scientific_final_monthly ?? 0),
            (float) ($inspection->arp_equivalent_final ?? 0),
            (float) ($inspection->base_package_price_snapshot ?? 0),
            (float) ($inspection->work_payment_amount ?? 0),
        ), 2);
    }

    private function resolveProjectPaidAmount(Inspection $inspection, float $total): float
    {
        if (($inspection->work_payment_status ?? 'pending') !== 'paid') {
            return 0;
        }

        if (($inspection->payment_plan ?? null) === 'per_visit') {
            $installmentsPaid = max(1, (int) ($inspection->installments_paid ?? 1));
            $installmentAmount = (float) ($inspection->installment_amount ?? 0);
            $fallbackInstallment = $installmentAmount > 0
                ? $installmentAmount
                : ($total / max(1, (int) ($inspection->installment_months ?? 1)));

            return round(min($total, $fallbackInstallment * $installmentsPaid), 2);
        }

        if ($inspection->arp_fully_paid_at) {
            return round($total, 2);
        }

        return round(min($total, (float) ($inspection->work_payment_amount ?? 0)), 2);
    }

    private function nextInvoiceNumber(string $baseInvoiceNumber): string
    {
        $invoiceNumber = $baseInvoiceNumber;
        $counter = 1;

        while (Invoice::where('invoice_number', $invoiceNumber)->exists()) {
            $invoiceNumber = $baseInvoiceNumber . '-' . $counter;
            $counter++;
        }

        return $invoiceNumber;
    }
}