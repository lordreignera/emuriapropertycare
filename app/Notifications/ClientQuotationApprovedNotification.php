<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ClientQuotationApprovedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly int $inspectionId,
        private readonly ?int $propertyId,
        private readonly string $propertyName,
        private readonly string $quoteNumber,
        private readonly int $approvedFindings,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'client_quotation_approved',
            'title' => 'Quotation Approved By Client',
            'message' => 'Client approved quotation ' . $this->quoteNumber . ' for ' . $this->propertyName . ' (' . $this->approvedFindings . ' finding(s)).',
            'inspection_id' => $this->inspectionId,
            'property_id' => $this->propertyId,
            'quote_number' => $this->quoteNumber,
            'approved_findings' => $this->approvedFindings,
            'action_url' => route('inspections.phar-data', $this->inspectionId),
        ];
    }
}
