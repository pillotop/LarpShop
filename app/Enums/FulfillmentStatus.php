<?php

namespace App\Enums;

enum FulfillmentStatus: string
{
    case Unfulfilled = 'unfulfilled';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case Delivered = 'delivered';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Unfulfilled => __('management.fulfillment_labels.unfulfilled'),
            self::Processing => __('management.fulfillment_labels.processing'),
            self::Shipped => __('management.fulfillment_labels.shipped'),
            self::Delivered => __('management.fulfillment_labels.delivered'),
        };
    }

    public function rank(): int
    {
        return match ($this) {
            self::Unfulfilled => 0,
            self::Processing => 1,
            self::Shipped => 2,
            self::Delivered => 3,
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return $target->rank() >= $this->rank();
    }
}
