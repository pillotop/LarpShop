<?php

use App\Http\Controllers\OrderController;
use App\Http\Controllers\Orders\OrderHistoryController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')
    ->prefix('orders')
    ->name('orders.')
    ->group(function () {
        // add more in the future ples
        Route::post('/{order}/cancel', [OrderController::class, 'cancel'])
            ->name('cancel');

        Route::get('/{order}', [OrderController::class, 'show'])
            ->name('show');

        Route::get('/index', [OrderHistoryController::class, 'index'])
            ->name('index');
    });
