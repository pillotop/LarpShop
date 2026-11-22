<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Orders\OrderLifecycleService;
use App\Services\Payments\PaymentService;
use Database\Seeders\Support\DemoAssetManager;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('assets:prepare', function (DemoAssetManager $assets) {
    $source = base_path('database/seeders/assets/products');
    $root = config('filesystems.disks.public.root');
    $link = public_path('storage');

    if (! File::isDirectory($source) || File::files($source) === []) {
        $this->error('Bundled demo product images are missing.');

        return 1;
    }

    // Never replace an actual directory containing user uploads with a symlink.
    if (file_exists($link) && ! is_link($link)) {
        $this->error('public/storage is not a symbolic link; preserve its contents before continuing.');

        return 1;
    }

    File::ensureDirectoryExists($root);
    $assets->syncToPublicDisk();

    foreach (File::files($source) as $file) {
        $path = 'demo-products/'.$file->getFilename();
        if (! Storage::disk('public')->exists($path)
            || Storage::disk('public')->size($path) !== $file->getSize()) {
            $this->error('Could not publish bundled demo product images.');

            return 1;
        }
    }

    $this->call('storage:link', ['--force' => true]);

    if (! is_link($link) || realpath($link) !== realpath($root)) {
        $this->error('The public storage link does not point to the configured storage directory.');

        return 1;
    }

    $this->info('Public storage is linked and bundled product images are ready.');

    return 0;
})->purpose('Publish bundled product images and link public storage without seeding database records');

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
