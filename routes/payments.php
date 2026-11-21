<?php

use App\Http\Controllers\Payments\CheckoutPaymentController;
use App\Http\Controllers\Payments\FakePaymentController;
use App\Http\Controllers\Payments\SimulatedWalletController;
use App\Http\Controllers\Payments\StripeWebhookController;
use App\Http\Controllers\Payments\WalletWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/stripe', StripeWebhookController::class)->name('payments.stripe.webhook');
Route::post('/webhooks/momo', [WalletWebhookController::class, 'momo'])->name('payments.momo.webhook');
Route::post('/webhooks/zalopay', [WalletWebhookController::class, 'zalopay'])->name('payments.zalopay.webhook');
// VNPAY specifies a signed server-to-server GET IPN. Browser return routes remain read-only.
Route::get('/webhooks/vnpay', [WalletWebhookController::class, 'vnpay'])->name('payments.vnpay.webhook');

if (app()->environment(['local', 'testing'])) {
    Route::middleware('auth')
        ->prefix('test/payments')
        ->name('test.payments.')
        ->group(function () {

            Route::post('/orders/{order}', [FakePaymentController::class, 'store'])
                ->name('store');

            Route::post('/orders/{order}/pay', [FakePaymentController::class, 'pay'])
                ->name('pay');

            Route::post('/{payment}/simulate', [FakePaymentController::class, 'simulate'])
                ->name('simulate');

            Route::post('/{payment}/verify', [FakePaymentController::class, 'verify'])
                ->name('verify');
        });
}

Route::middleware('auth')->group(function () {
    Route::get('/payments/{payment}/wallet-demo', [SimulatedWalletController::class, 'show'])->name('payments.wallet-demo.show');
    Route::post('/payments/{payment}/wallet-demo', [SimulatedWalletController::class, 'store'])->name('payments.wallet-demo.store');
    Route::post('/checkout/{order}/payments', [CheckoutPaymentController::class, 'store'])->name('payments.store');
    Route::get('/payments/{payment}/return', [CheckoutPaymentController::class, 'returned'])->name('payments.return');
    Route::get('/payments/{payment}/cancel', [CheckoutPaymentController::class, 'cancelled'])->name('payments.cancel');
    Route::post('/payments/{payment}/retry', [CheckoutPaymentController::class, 'retry'])->name('payments.retry');
    Route::post('/payments/{payment}/complete', [CheckoutPaymentController::class, 'complete'])->name('payments.complete');
});
