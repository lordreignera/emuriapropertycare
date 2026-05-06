<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PropertyRegisteredNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly int $propertyId,
        private readonly string $propertyCode,
        private readonly string $propertyName,
        private readonly string $city,
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
            'type' => 'property_registered',
            'title' => 'New Property Registered',
            'message' => 'Client ' . $this->clientName . ' registered property ' . $this->propertyCode . ' (' . $this->propertyName . ', ' . $this->city . ').',
            'property_id' => $this->propertyId,
            'property_code' => $this->propertyCode,
            'action_url' => route('properties.show', $this->propertyId),
        ];
    }
}
