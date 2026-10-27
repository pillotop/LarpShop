<?php

use App\Http\Controllers\Vendor\VendorCategoryController;
use App\Http\Controllers\Vendor\VendorOrderController;
use App\Http\Controllers\Vendor\VendorProductController;
use App\Http\Controllers\Vendor\VendorProductImageController;
use App\Http\Controllers\Vendor\VendorStoreController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'vendor'])->prefix('vendor')->name('vendor.')->group(function () {
    // UC-29: Vendor Store Configuration & Settings
    Route::get('/store/settings', [VendorStoreController::class, 'edit'])->name('store.settings');
    Route::put('/store/settings', [VendorStoreController::class, 'update'])->name('store.settings.update');

    // UC-30 & UC-31: Vendor Product Category Management
    Route::get('/categories', [VendorCategoryController::class, 'index'])->name('categories.index');
    Route::get('/categories/create', [VendorCategoryController::class, 'create'])->name('categories.create');
    Route::post('/categories', [VendorCategoryController::class, 'store'])->name('categories.store');
    Route::get('/categories/{category}/edit', [VendorCategoryController::class, 'edit'])->name('categories.edit');
    Route::put('/categories/{category}', [VendorCategoryController::class, 'update'])->name('categories.update');
    Route::patch('/categories/{category}/toggle-status', [VendorCategoryController::class, 'toggleStatus'])->name('categories.toggle-status');

    // Vendor Product Management List
    Route::get('/products', [VendorProductController::class, 'index'])->name('products.index');

    // UC-32: Vendor Product Image Management
    Route::get('/products/{product}/images', [VendorProductImageController::class, 'index'])->name('products.images.index');
    Route::post('/products/{product}/images', [VendorProductImageController::class, 'store'])->name('products.images.store');
    Route::delete('/products/{product}/images/{image}', [VendorProductImageController::class, 'destroy'])->name('products.images.destroy');
    Route::patch('/products/{product}/images/{image}/primary', [VendorProductImageController::class, 'setPrimary'])->name('products.images.primary');

    // UC-34 & UC-35: Vendor Order Viewing & Fulfillment Status Management
    Route::get('/orders', [VendorOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [VendorOrderController::class, 'show'])->whereNumber('order')->name('orders.show');
    Route::patch('/orders/{order}/fulfillment', [VendorOrderController::class, 'updateFulfillment'])->whereNumber('order')->name('orders.fulfillment.update');
});
