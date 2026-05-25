<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class PaymentFailedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public int $inspectionId,
        public int $propertyId,
        public string $propertyName,
        public string $paymentType,
        public string $errorMessage,
        public float $amount,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'inspection_id' => $this->inspectionId,
            'property_id' => $this->propertyId,
            'property_name' => $this->propertyName,
            'payment_type' => $this->paymentType,
            'error_message' => $this->errorMessage,
            'amount' => $this->amount,
            'action_url' => route('admin.inspections.show', $this->inspectionId),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $paymentTypeLabel = match ($this->paymentType) {
            'inspection_fee' => 'Inspection Fee',
            'work_start' => 'Work Payment',
            'per_visit' => 'Per-Visit Payment',
            default => 'Payment',
        };

        return (new MailMessage)
            ->subject("⚠️ Payment Failed — {$this->propertyName}")
            ->line("A {$paymentTypeLabel} payment has failed.")
            ->line("**Property:** {$this->propertyName}")
            ->line("**Amount:** \${$this->amount}")
            ->line("**Error:** {$this->errorMessage}")
            ->line("**Inspection ID:** #{$this->inspectionId}")
            ->action('View Inspection', route('admin.inspections.show', $this->inspectionId))
            ->line('Please contact the client to retry payment or update their payment method.');
    }
}
