<?php

use App\Http\Controllers\CheckoutController;
use Illuminate\Support\Facades\Route;

Route::post('/checkout', [CheckoutController::class, 'store'])
    ->middleware('auth')
    ->name('checkout.store');

Route::get('/checkout/{order}', [CheckoutController::class, 'show'])
    ->middleware('auth')
    ->name('checkout.show');

Route::put('/checkout/{order}/address', [CheckoutController::class, 'updateAddress'])
    ->middleware('auth')
    ->name('checkout.address.update');
