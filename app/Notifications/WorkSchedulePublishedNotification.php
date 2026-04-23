<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class WorkSchedulePublishedNotification extends Notification
{
    use Queueable;

    /**
     * @param  array<int, string>  $visitDates
     */
    public function __construct(
        private readonly int $inspectionId,
        private readonly ?int $propertyId,
        private readonly string $propertyName,
        private readonly array $visitDates,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $firstVisit = $this->visitDates[0] ?? null;
        $lastVisit = $this->visitDates[count($this->visitDates) - 1] ?? null;
        $scheduleSummary = $firstVisit && $lastVisit
            ? ($firstVisit === $lastVisit ? $firstVisit : $firstVisit . ' to ' . $lastVisit)
            : 'the scheduled dates';

        return [
            'type' => 'work_schedule_published',
            'title' => 'Project Schedule Updated',
            'message' => 'Work visits for ' . $this->propertyName . ' have been scheduled for ' . $scheduleSummary . '.',
            'inspection_id' => $this->inspectionId,
            'property_id' => $this->propertyId,
            'visit_dates' => $this->visitDates,
            'action_url' => route('client.inspections.report', $this->inspectionId),
        ];
    }
}