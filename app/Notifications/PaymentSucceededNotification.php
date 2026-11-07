<?php

namespace App\Notifications;

use App\Enums\PaymentMethod;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentSucceededNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Payment $payment,
    ) {
        $this->afterCommit();
        $this->locale(app()->getLocale());
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('notifications.payment.subject'))
            ->greeting(__('notifications.payment.greeting'))
            ->line(__('notifications.payment.order', ['id' => $this->payment->order_id]))
            ->line(__('notifications.payment.amount', ['amount' => $this->payment->amount, 'currency' => $this->payment->currency]))
            ->line(__('notifications.payment.method', ['method' => match ($this->payment->method) {
                PaymentMethod::Card => __('checkout.payment.card'),
                PaymentMethod::EWallet => __('checkout.payment.wallet'),
                default => __('checkout.details.not_provided'),
            }]))
            ->line(__('notifications.payment.success'))
            ->action(
                __('notifications.payment.action'),
                route('orders.index')
            );
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
