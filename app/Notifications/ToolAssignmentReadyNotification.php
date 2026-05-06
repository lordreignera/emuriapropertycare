<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ToolAssignmentReadyNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly int $inspectionId,
        private readonly ?int $propertyId,
        private readonly string $propertyName,
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
            'type'          => 'tool_assignment_ready',
            'title'         => 'Tools Ready to Assign',
            'message'       => $this->clientName . ' has signed the agreement and paid the deposit for '
                             . $this->propertyName . '. Please assign the required tools.',
            'inspection_id' => $this->inspectionId,
            'property_id'   => $this->propertyId,
            'action_url'    => route('tool-assignments.index'),
        ];
    }
}
