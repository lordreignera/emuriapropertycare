<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class WorkPaymentReceivedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly int $inspectionId,
        private readonly int $propertyId,
        private readonly string $propertyName,
        private readonly string $propertyCode,
        private readonly float $amount,
        private readonly string $clientName,
        private readonly string $plan,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $planLabel = match ($this->plan) {
            'per_visit'   => 'Per Visit',
            'installment' => 'Installment (Deposit)',
            default       => 'Full Payment',
        };

        return (new MailMessage)
            ->subject('💰 Work Payment Received — ' . $this->propertyCode)
            ->greeting('Payment Received')
            ->line('Client **' . $this->clientName . '** has made a work payment.')
            ->line('**Property:** ' . $this->propertyCode . ' — ' . $this->propertyName)
            ->line('**Amount Paid:** $' . number_format($this->amount, 2))
            ->line('**Payment Plan:** ' . $planLabel)
            ->action('View Inspection', route('inspections.show', $this->inspectionId))
            ->line('Work can now begin on this property.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'          => 'work_payment_received',
            'title'         => 'Work Payment Received',
            'message'       => 'Client ' . $this->clientName . ' paid $' . number_format($this->amount, 2) . ' (work start) for ' . $this->propertyCode . ' (' . $this->propertyName . ').',
            'inspection_id' => $this->inspectionId,
            'property_id'   => $this->propertyId,
            'amount'        => round($this->amount, 2),
            'plan'          => $this->plan,
            'action_url'    => route('inspections.show', $this->inspectionId),
        ];
    }
}
