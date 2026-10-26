<?php

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
