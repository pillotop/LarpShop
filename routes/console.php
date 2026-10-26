<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\Orders\OrderLifecycleService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Validation\ValidationException;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('orders:expire', function (OrderLifecycleService $lifecycle) {
    $expired = 0;
    Order::query()
        ->where('status', OrderStatus::Pending->value)
        ->where('expires_at', '<=', now())
        ->chunkById(100, function ($orders) use ($lifecycle, &$expired) {
            foreach ($orders as $order) {
                try {
                    $lifecycle->expire($order);
                    $expired++;
                } catch (ValidationException) {
                    // A concurrent payment or cancellation already transitioned this order.
                }
            }
        }, 'order_id');
    $this->info("Expired {$expired} order(s).");
})->purpose('Expire unpaid orders and release reserved inventory');

Schedule::command('orders:expire')->everyMinute()->withoutOverlapping();
