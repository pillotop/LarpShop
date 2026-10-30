<?php

use App\Http\Controllers\WishlistController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('wishlist')->name('wishlist.')->group(function () {
    Route::post('/{product}', [WishlistController::class, 'store'])->whereNumber('product')->name('store');
    Route::delete('/{product}', [WishlistController::class, 'destroy'])->whereNumber('product')->name('destroy');
});
