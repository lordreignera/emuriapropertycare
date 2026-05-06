<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class InspectionFeePaidNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly int $inspectionId,
        private readonly int $propertyId,
        private readonly string $propertyName,
        private readonly string $propertyCode,
        private readonly float $amount,
        private readonly string $clientName,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'inspection_fee_paid',
            'title' => 'Inspection Fee Paid',
            'message' => 'Client ' . $this->clientName . ' paid inspection fee of $' . number_format($this->amount, 2) . ' for ' . $this->propertyCode . ' (' . $this->propertyName . ').',
            'inspection_id' => $this->inspectionId,
            'property_id' => $this->propertyId,
            'amount' => round($this->amount, 2),
            'action_url' => route('properties.show', $this->propertyId),
        ];
    }
}
