<?php

use App\Http\Controllers\AddressController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/account/addresses', [
        AddressController::class, 'index',
    ])->name('addresses.index');

    Route::post('/account/addresses', [
        AddressController::class, 'store',
    ])->name('addresses.store');

    Route::put('/account/addresses/{address}', [
        AddressController::class, 'update',
    ])->name('addresses.update');

    Route::patch('/account/addresses/{address}/default', [
        AddressController::class, 'setDefault',
    ])->name('addresses.default');

    Route::delete('/account/addresses/{address}', [
        AddressController::class, 'destroy',
    ])->name('addresses.destroy');
});
