<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class QuotationSharedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly int $inspectionId,
        private readonly ?int $propertyId,
        private readonly string $propertyName,
        private readonly string $quoteNumber,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'quotation_shared',
            'title' => 'Quotation Ready For Review',
            'message' => 'A quotation for ' . $this->propertyName . ' has been shared. Review and approve the findings to continue.',
            'inspection_id' => $this->inspectionId,
            'property_id' => $this->propertyId,
            'quote_number' => $this->quoteNumber,
            'action_url' => route('client.inspections.quotation', $this->inspectionId),
        ];
    }
}