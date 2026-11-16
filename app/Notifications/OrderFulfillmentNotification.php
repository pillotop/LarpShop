<?php

namespace App\Notifications;

use App\Enums\FulfillmentStatus;
use App\Models\Order;
use Illuminate\Notifications\Notification;

class OrderFulfillmentNotification extends Notification
{
    public function __construct(
        public Order $order,
        public FulfillmentStatus $status,
        public ?string $storeName = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $icon = match ($this->status) {
            FulfillmentStatus::Processing => 'bi-box-seam',
            FulfillmentStatus::Shipped => 'bi-truck',
            FulfillmentStatus::Delivered => 'bi-check2-circle',
            default => 'bi-bell',
        };

        return [
            'icon' => $icon,
            'title' => __('notifications.fulfillment.title', [
                'id' => $this->order->getKey(),
                'status' => $this->status->label(),
            ]),
            'message' => __('notifications.fulfillment.body', [
                'id' => $this->order->getKey(),
                'status' => $this->status->label(),
                'store' => $this->storeName ?? __('products.detail.partner'),
            ]),
            'url' => route('orders.show', $this->order->getKey()),
        ];
    }
}
