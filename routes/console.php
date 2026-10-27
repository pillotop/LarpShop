<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Orders\OrderLifecycleService;
use App\Services\Payments\PaymentService;
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

Artisan::command('payments:reconcile', function (PaymentService $payments) {
    Payment::query()
        ->whereIn('status', ['pending', 'processing'])
        ->whereNotNull('provider_reference')
        ->chunkById(100, function ($batch) use ($payments) {
            foreach ($batch as $payment) {
                try {
                    // Verify only: reconciliation must never initiate a new capture.
                    $payments->verify($payment);
                } catch (Throwable $exception) {
                    $this->warn("Could not reconcile payment #{$payment->getKey()}: ".class_basename($exception));
                }
            }
        }, 'payment_id');
})->purpose('Reconcile pending payments with their providers');

Schedule::command('payments:reconcile')->everyFiveMinutes()->withoutOverlapping();
