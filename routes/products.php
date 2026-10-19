<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\View\ProductController;
use App\Http\Controllers\CartController;
use App\Models\Product;
use Diglactic\Breadcrumbs\Breadcrumbs;
use Diglactic\Breadcrumbs\Generator as BreadcrumbTrails;

Route::post('/products/cart', [CartController::class, 'store'])
    ->name('cart.store');

Route::get('/products/cart', [CartController::class, 'index'])
    ->name('cart.index');

Route::get('/products/{product}/{slug?}', [ProductController::class, 'show'])
    ->name('products.show');

Breadcrumbs::for('product', function (BreadcrumbTrails $trail, Product $product) {
    $trail->parent('category', $product->category);
    $trail->push($product->name, route('products.show', [
        'product'   => $product->product_id,
        'slug'      => $product->slug,
    ]));
});
