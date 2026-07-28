<?php

namespace App\Services;

use App\Models\Inspection;
use App\Models\Project;
use App\Models\Property;
use App\Models\User;
use App\Notifications\InspectionFeePaidNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class InspectionFeeConfirmationService
{
    public function __construct(
        private readonly InspectionInvoiceSyncService $inspectionInvoiceSyncService,
    ) {
    }

    public function confirm(
        Property $property,
        string $paymentIntentId,
        float $amount,
        array $scheduleData = [],
        ?int $actorId = null
    ): Inspection {
        $confirmedNow = false;

        $inspection = DB::transaction(function () use ($property, $paymentIntentId, $amount, $scheduleData, $actorId, &$confirmedNow) {
            $property = Property::with('user')
                ->whereKey($property->id)
                ->lockForUpdate()
                ->firstOrFail();

            $existingByIntent = Inspection::where('property_id', $property->id)
                ->where('stripe_payment_intent_id', $paymentIntentId)
                ->where('inspection_fee_status', 'paid')
                ->where('status', '!=', 'cancelled')
                ->lockForUpdate()
                ->first();

            if ($existingByIntent) {
                return $existingByIntent->fresh(['property.user', 'project']);
            }

            $existingByProperty = Inspection::where('property_id', $property->id)
                ->where('inspection_fee_status', 'paid')
                ->where('status', '!=', 'cancelled')
                ->lockForUpdate()
                ->first();

            if ($existingByProperty) {
                return $existingByProperty->fresh(['property.user', 'project']);
            }

            $project = Project::firstOrCreate(
                ['property_id' => $property->id],
                [
                    'title' => 'Property Inspection - ' . $property->property_name,
                    'description' => 'Client scheduled inspection for ' . $property->property_name,
                    'status' => 'pending',
                    'user_id' => $property->user_id,
                    'managed_by' => $property->project_manager_id,
                    'created_by' => $actorId ?: $property->user_id,
                    'project_number' => 'PRJ-' . strtoupper(Str::random(8)),
                ]
            );

            $inspection = Inspection::create([
                'property_id' => $property->id,
                'project_id' => $project->id,
                'scheduled_date' => $this->scheduledAt($scheduleData),
                'status' => 'scheduled',
                'summary' => $scheduleData['special_notes'] ?? null,
                'inspection_fee_amount' => $amount,
                'inspection_fee_status' => 'paid',
                'inspection_fee_paid_at' => now(),
                'stripe_payment_intent_id' => $paymentIntentId,
                'property_code' => $property->property_code,
                'property_name' => $property->property_name,
                'property_address_snapshot' => trim(($property->property_address ?? '') . ', ' . ($property->city ?? '')),
                'property_type_snapshot' => $property->type,
                'residential_units_snapshot' => (int) ($property->number_of_units ?: $property->residential_units ?: 0),
                'commercial_sqft_snapshot' => $property->square_footage_interior,
                'mixed_use_weight_snapshot' => $property->mixed_use_commercial_weight,
                'specialist_assessment_breakdown' => null,
                'specialist_trade_cost' => 0,
                'specialist_client_price' => 0,
                'specialist_margin_amount' => 0,
                'specialist_pricing_currency' => null,
            ]);

            $property->update(['status' => 'awaiting_inspection']);
            $confirmedNow = true;

            return $inspection->fresh(['property.user', 'project']);
        });

        if ($confirmedNow) {
            $this->syncInvoice($inspection);
            $this->notifyStaff($inspection, $amount);
        }

        return $inspection;
    }

    private function scheduledAt(array $scheduleData): Carbon
    {
        $date = trim((string) ($scheduleData['preferred_date'] ?? ''));
        $time = trim((string) ($scheduleData['preferred_time'] ?? '09:00')) ?: '09:00';

        try {
            if ($date !== '') {
                return Carbon::parse($date . ' ' . $time);
            }
        } catch (\Throwable) {
            Log::warning('Invalid inspection schedule metadata, using fallback date.', [
                'preferred_date' => $date,
                'preferred_time' => $time,
            ]);
        }

        return now()->addDay()->setTime(9, 0);
    }

    private function syncInvoice(Inspection $inspection): void
    {
        try {
            $this->inspectionInvoiceSyncService->syncInspectionFeeInvoice($inspection->fresh(['property', 'project']));
        } catch (\Throwable $e) {
            Log::warning('Inspection fee invoice sync failed after payment confirmation.', [
                'inspection_id' => $inspection->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function notifyStaff(Inspection $inspection, float $amount): void
    {
        try {
            $inspection->loadMissing('property.user');
            $property = $inspection->property;

            $adminRecipients = User::role(['Super Admin', 'Administrator', 'Project Manager', 'Inspector', 'Technician', 'Store Manager'])
                ->get()
                ->unique('id')
                ->values();

            if ($adminRecipients->isEmpty() || !$property) {
                return;
            }

            Notification::send($adminRecipients, new InspectionFeePaidNotification(
                inspectionId: (int) $inspection->id,
                propertyId: (int) $property->id,
                propertyName: (string) ($property->property_name ?? 'Property'),
                propertyCode: (string) ($property->property_code ?? 'N/A'),
                amount: $amount,
                clientName: (string) ($property->user?->name ?? 'Client'),
            ));
        } catch (\Throwable $e) {
            Log::warning('Inspection fee paid notification failed after payment confirmation.', [
                'inspection_id' => $inspection->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
