<?php

use App\Http\Controllers\Admin\AdminCategoryController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminVendorController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [AdminDashboardController::class, 'index']);

    // UC-39: User Management and Role Assignment
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::patch('/users/{user}/role', [AdminUserController::class, 'updateRole'])->whereNumber('user')->name('users.update-role');

    // UC-40: Vendor Management and Approval
    Route::get('/vendors', [AdminVendorController::class, 'index'])->name('vendors.index');
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
});
