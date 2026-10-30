<?php

use App\Http\Controllers\Vendor\VendorCategoryController;
use App\Http\Controllers\Vendor\VendorProductController;
use App\Http\Controllers\Vendor\VendorProductImageController;
use App\Http\Controllers\Vendor\VendorStoreController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'vendor'])->prefix('vendor')->name('vendor.')->group(function () {
    // UC-29: Vendor Store Configuration & Multi-Store Switcher
    Route::get('/store/settings', [VendorStoreController::class, 'edit'])->name('store.settings');
    Route::put('/store/settings', [VendorStoreController::class, 'update'])->name('store.settings.update');
    Route::post('/store/switch', [VendorStoreController::class, 'switchStore'])->name('store.switch');

    // Category Proposals (Vendor can view and propose categories; only Admin can edit/disable shared categories)
    Route::get('/categories', [VendorCategoryController::class, 'index'])->name('categories.index');
    Route::get('/categories/create', [VendorCategoryController::class, 'create'])->name('categories.create');
    Route::post('/categories', [VendorCategoryController::class, 'store'])->name('categories.store');
    Route::get('/categories/{category}/edit', [VendorCategoryController::class, 'edit'])->name('categories.edit');
    Route::put('/categories/{category}', [VendorCategoryController::class, 'update'])->name('categories.update');
    Route::patch('/categories/{category}/toggle-status', [VendorCategoryController::class, 'toggleStatus'])->name('categories.toggle-status');

    // UC-30, UC-31 & UC-33: Vendor Product Listing & Inventory Management
    Route::get('/products', [VendorProductController::class, 'index'])->name('products.index');
    Route::get('/products/create', [VendorProductController::class, 'create'])->name('products.create');
    Route::post('/products', [VendorProductController::class, 'store'])->name('products.store');
    Route::get('/products/{product}/edit', [VendorProductController::class, 'edit'])->name('products.edit');
    Route::put('/products/{product}', [VendorProductController::class, 'update'])->name('products.update');
    Route::patch('/products/{product}/toggle-active', [VendorProductController::class, 'toggleActive'])->name('products.toggle-active');
    Route::patch('/products/{product}/stock', [VendorProductController::class, 'updateStock'])->name('products.update-stock');

    // UC-32: Vendor Product Image Management
    Route::get('/products/{product}/images', [VendorProductImageController::class, 'index'])->name('products.images.index');
    Route::post('/products/{product}/images', [VendorProductImageController::class, 'store'])->name('products.images.store');
    Route::delete('/products/{product}/images/{image}', [VendorProductImageController::class, 'destroy'])->name('products.images.destroy');
    Route::patch('/products/{product}/images/{image}/primary', [VendorProductImageController::class, 'setPrimary'])->name('products.images.primary');
});
