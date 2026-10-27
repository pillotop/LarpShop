<?php

use App\Http\Controllers\Payments\CheckoutPaymentController;
use App\Http\Controllers\Payments\FakePaymentController;
use Illuminate\Support\Facades\Route;

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
    Route::post('/checkout/{order}/payments', [CheckoutPaymentController::class, 'store'])->name('payments.store');
    Route::get('/payments/{payment}/return', [CheckoutPaymentController::class, 'returned'])->name('payments.return');
    Route::get('/payments/{payment}/cancel', [CheckoutPaymentController::class, 'cancelled'])->name('payments.cancel');
    Route::post('/payments/{payment}/retry', [CheckoutPaymentController::class, 'retry'])->name('payments.retry');
    Route::post('/payments/{payment}/complete', [CheckoutPaymentController::class, 'complete'])->name('payments.complete');
});
