<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ServiceRequestSubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly int $serviceRequestId,
        private readonly string $requestNumber,
        private readonly string $propertyName,
        private readonly string $requestType,
        private readonly string $urgency,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'service_request_submitted',
            'title' => 'New Service Request Submitted',
            'message' => 'New ' . str_replace('_', ' ', $this->requestType) . ' request ' . $this->requestNumber . ' for ' . $this->propertyName . ' (' . ucfirst($this->urgency) . ' urgency).',
            'service_request_id' => $this->serviceRequestId,
            'request_number' => $this->requestNumber,
            'action_url' => route('admin.service-requests.show', $this->serviceRequestId),
        ];
    }
}
