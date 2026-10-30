<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\ProductReviewController;
use App\Models\Product;
use App\View\ProductController;
use Diglactic\Breadcrumbs\Breadcrumbs;
use Diglactic\Breadcrumbs\Generator as BreadcrumbTrails;
use Illuminate\Support\Facades\Route;

Route::post('cart/add', [CartController::class, 'add'])->name('cart.add');

Route::post('cart', [CartController::class, 'store'])
    ->name('cart.store');

Route::get('cart', [CartController::class, 'show'])
    ->name('cart.show');

Route::get('cart/detail', [CartController::class, 'index'])
    ->name('cart.index');

Route::get('/products/{product}/reviews', [ProductReviewController::class, 'index'])->name('reviews.index');
Route::post('/products/{product}/reviews', [ProductReviewController::class, 'store'])
    ->middleware('auth')->name('reviews.store');

Route::get('/products/{product}/{slug?}', [ProductController::class, 'show'])
    ->name('products.show');

Breadcrumbs::for('product', function (BreadcrumbTrails $trail, Product $product) {
    $trail->parent('category', $product->category);
    $trail->push($product->name, route('products.show', [
        'product' => $product->product_id,
        'slug' => $product->slug,
    ]));
});
