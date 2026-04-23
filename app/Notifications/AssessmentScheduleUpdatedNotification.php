<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AssessmentScheduleUpdatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly int $inspectionId,
        private readonly int $propertyId,
        private readonly string $propertyName,
        private readonly string $scheduledAt,
        private readonly string $scheduledByName,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'assessment_schedule_updated',
            'title' => 'Assessment Date Updated',
            'message' => 'Your assessment for ' . $this->propertyName . ' is scheduled for ' . $this->scheduledAt . '.',
            'inspection_id' => $this->inspectionId,
            'property_id' => $this->propertyId,
            'scheduled_at' => $this->scheduledAt,
            'scheduled_by' => $this->scheduledByName,
            'action_url' => route('client.inspections.report', $this->inspectionId),
        ];
    }
}
