<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AssessmentCompletedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly int $inspectionId,
        private readonly ?int $propertyId,
        private readonly string $propertyName,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'assessment_completed',
            'title' => 'Assessment Report Ready',
            'message' => 'Your assessment report for ' . $this->propertyName . ' is now ready to review.',
            'inspection_id' => $this->inspectionId,
            'property_id' => $this->propertyId,
            'action_url' => route('client.inspections.report', $this->inspectionId),
        ];
    }
}