<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class InstallmentPaymentReceivedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly int $inspectionId,
        private readonly int $propertyId,
        private readonly string $propertyName,
        private readonly string $propertyCode,
        private readonly float $amount,
        private readonly string $clientName,
        private readonly int $installmentNumber,
        private readonly int $totalInstallments,
        private readonly string $plan,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $isPerVisit  = $this->plan === 'per_visit';
        $label       = $isPerVisit ? 'Visit' : 'Installment';
        $remaining   = $this->totalInstallments - $this->installmentNumber;
        $isFinal     = $remaining <= 0;

        $subject = $isFinal
            ? '✅ Final Payment Received — ' . $this->propertyCode
            : '💳 ' . $label . ' ' . $this->installmentNumber . '/' . $this->totalInstallments . ' Received — ' . $this->propertyCode;

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting('Payment Received')
            ->line('Client **' . $this->clientName . '** has made a payment.')
            ->line('**Property:** ' . $this->propertyCode . ' — ' . $this->propertyName)
            ->line('**Amount Paid:** $' . number_format($this->amount, 2))
            ->line('**' . $label . ':** ' . $this->installmentNumber . ' of ' . $this->totalInstallments);

        if ($isFinal) {
            $mail->line('**All payments have been received. The project is fully paid.**');
        } else {
            $mail->line('**Remaining:** ' . $remaining . ' ' . strtolower($label) . '(s) left.');
        }

        return $mail->action('View Inspection', route('inspections.show', $this->inspectionId));
    }

    public function toArray(object $notifiable): array
    {
        $isPerVisit = $this->plan === 'per_visit';
        $label      = $isPerVisit ? 'visit' : 'installment';
        $remaining  = $this->totalInstallments - $this->installmentNumber;
        $isFinal    = $remaining <= 0;

        $message = $isFinal
            ? 'Client ' . $this->clientName . ' made the final payment of $' . number_format($this->amount, 2) . ' for ' . $this->propertyCode . '. Project fully paid.'
            : 'Client ' . $this->clientName . ' paid ' . $label . ' ' . $this->installmentNumber . '/' . $this->totalInstallments . ' ($' . number_format($this->amount, 2) . ') for ' . $this->propertyCode . '.';

        return [
            'type'                 => 'installment_payment_received',
            'title'                => $isFinal ? 'Final Payment Received' : ucfirst($label) . ' Payment Received',
            'message'              => $message,
            'inspection_id'        => $this->inspectionId,
            'property_id'          => $this->propertyId,
            'amount'               => round($this->amount, 2),
            'installment_number'   => $this->installmentNumber,
            'total_installments'   => $this->totalInstallments,
            'action_url'           => route('inspections.show', $this->inspectionId),
        ];
    }
}
