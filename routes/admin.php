<?php

use App\Http\Controllers\Admin\AdminCategoryController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\AdminProductCollectionController;
use App\Http\Controllers\Admin\AdminProductModerationController;
use App\Http\Controllers\Admin\AdminPromoSectionController;
use App\Http\Controllers\Admin\AdminReportController;
use App\Http\Controllers\Admin\AdminReviewModerationController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminVendorController;
use App\Http\Controllers\Admin\CollectionProductController;
use App\Http\Controllers\Admin\PromoSectionPreviewController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::prefix('collections')->name('collections.')->group(function () {
        Route::get('/', [AdminProductCollectionController::class, 'index'])->name('index');
        Route::get('/create', [AdminProductCollectionController::class, 'create'])->name('create');
        Route::post('/', [AdminProductCollectionController::class, 'store'])->name('store');
        Route::get('/{collection}/edit', [AdminProductCollectionController::class, 'edit'])->whereNumber('collection')->name('edit');
        Route::put('/{collection}', [AdminProductCollectionController::class, 'update'])->whereNumber('collection')->name('update');
        Route::delete('/{collection}', [AdminProductCollectionController::class, 'destroy'])->whereNumber('collection')->name('destroy');
        Route::post('/{collection}/products', [CollectionProductController::class, 'store'])->whereNumber('collection')->name('products.store');
        Route::patch('/{collection}/products/{product}/move', [CollectionProductController::class, 'move'])
            ->whereNumber(['collection', 'product'])->name('products.move');
        Route::delete('/{collection}/products/{product}', [CollectionProductController::class, 'destroy'])
            ->whereNumber(['collection', 'product'])->name('products.destroy');
    });

    Route::prefix('promo-sections')->name('sections.')->group(function () {
        Route::get('/', [AdminPromoSectionController::class, 'index'])->name('index');
        Route::get('/create', [AdminPromoSectionController::class, 'create'])->name('create');
        Route::post('/', [AdminPromoSectionController::class, 'store'])->name('store');
        Route::get('/{section}/edit', [AdminPromoSectionController::class, 'edit'])->whereNumber('section')->name('edit');
        Route::put('/{section}', [AdminPromoSectionController::class, 'update'])->whereNumber('section')->name('update');
        Route::patch('/{section}/active', [AdminPromoSectionController::class, 'toggle'])->whereNumber('section')->name('toggle');
        Route::patch('/{section}/move', [AdminPromoSectionController::class, 'move'])->whereNumber('section')->name('move');
        Route::delete('/{section}', [AdminPromoSectionController::class, 'destroy'])->whereNumber('section')->name('destroy');
    });

    Route::get('/promotions/preview', PromoSectionPreviewController::class)
        ->name('promotions.preview');

    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [AdminDashboardController::class, 'index']);

    // UC-39: User Management and Role Assignment
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::patch('/users/{user}/role', [AdminUserController::class, 'updateRole'])->whereNumber('user')->name('users.update-role');

    // UC-40 & UC-29: Vendor Management and Store Configuration
    Route::get('/vendors', [AdminVendorController::class, 'index'])->name('vendors.index');
    Route::get('/vendors/{vendor}/edit', [AdminVendorController::class, 'edit'])->name('vendors.edit');
    Route::put('/vendors/{vendor}', [AdminVendorController::class, 'update'])->name('vendors.update');
    Route::post('/vendors/{vendor}/approve', [AdminVendorController::class, 'approve'])->name('vendors.approve');
    Route::post('/vendors/{vendor}/reject', [AdminVendorController::class, 'reject'])->name('vendors.reject');
    Route::post('/vendors/{vendor}/suspend', [AdminVendorController::class, 'suspend'])->name('vendors.suspend');
    Route::post('/vendors/{vendor}/reinstate', [AdminVendorController::class, 'reinstate'])->name('vendors.reinstate');

    // UC-41: Category Management
    Route::get('/categories', [AdminCategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories', [AdminCategoryController::class, 'store'])->name('categories.store');
    Route::put('/categories/{category}', [AdminCategoryController::class, 'update'])->whereNumber('category')->name('categories.update');
    Route::delete('/categories/{category}', [AdminCategoryController::class, 'destroy'])->whereNumber('category')->name('categories.destroy');

    // UC-42: Product Moderation
    Route::get('/products/moderation', [AdminProductModerationController::class, 'index'])->name('products.moderation.index');
    Route::patch('/products/{product}/approve', [AdminProductModerationController::class, 'approve'])->whereNumber('product')->name('products.moderation.approve');
    Route::patch('/products/{product}/reject', [AdminProductModerationController::class, 'reject'])->whereNumber('product')->name('products.moderation.reject');

    // UC-43: Review Moderation
    Route::get('/reviews', [AdminReviewModerationController::class, 'index'])->name('reviews.index');
    Route::delete('/reviews/{review}', [AdminReviewModerationController::class, 'destroy'])->whereNumber('review')->name('reviews.destroy');

    // UC-44: Order and Dispute Investigation
    Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->whereNumber('order')->name('orders.show');
    Route::patch('/orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->whereNumber('order')->name('orders.update-status');

    // UC-45: Platform Overview Analytics Reports
    Route::get('/reports', [AdminReportController::class, 'index'])->name('reports.index');
});
