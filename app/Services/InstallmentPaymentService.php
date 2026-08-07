<?php

namespace App\Services;

use App\Models\Inspection;
use Illuminate\Support\Facades\DB;

class InstallmentPaymentService
{
    /**
     * @return array{inspection: Inspection, applied: bool, paid: int, total: int}
     */
    public function apply(Inspection $inspection, string $paymentIntentId): array
    {
        return DB::transaction(function () use ($inspection, $paymentIntentId) {
            $inspection = Inspection::whereKey($inspection->id)->lockForUpdate()->firstOrFail();

            $recordedIntentIds = array_values(array_filter((array) ($inspection->installment_payment_intent_ids ?? [])));
            $total = max(1, (int) ($inspection->installment_months ?? 1));
            $paid = min($total, (int) ($inspection->installments_paid ?? 0));

            if (in_array($paymentIntentId, $recordedIntentIds, true)) {
                return [
                    'inspection' => $inspection->fresh(['property.user', 'project']),
                    'applied' => false,
                    'paid' => $paid,
                    'total' => $total,
                ];
            }

            if ($paid >= $total) {
                return [
                    'inspection' => $inspection->fresh(['property.user', 'project']),
                    'applied' => false,
                    'paid' => $paid,
                    'total' => $total,
                ];
            }

            $paid++;
            $recordedIntentIds[] = $paymentIntentId;

            $inspection->update([
                'installments_paid' => $paid,
                'installment_payment_intent_ids' => array_values(array_unique($recordedIntentIds)),
                'next_installment_due_date' => null,
                'arp_fully_paid_at' => $paid >= $total ? now() : null,
            ]);

            return [
                'inspection' => $inspection->fresh(['property.user', 'project']),
                'applied' => true,
                'paid' => $paid,
                'total' => $total,
            ];
        });
    }

    public function hasRecorded(Inspection $inspection, string $paymentIntentId): bool
    {
        return in_array($paymentIntentId, (array) ($inspection->installment_payment_intent_ids ?? []), true);
    }
}
