<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\ProductImageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\VendorStorefrontController;
use App\View\HomeController;
use App\View\ProductController;
use App\View\CategoryController;
use App\Models\Product;
use App\Models\Category;
use App\Models\Vendor;
use Diglactic\Breadcrumbs\Breadcrumbs;
use Diglactic\Breadcrumbs\Generator as BreadcrumbTrail;

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/vendor', [ProfileController::class, 'updateVendor'])->name('profile.vendor.update');
    Route::post('/profile/vendor/register', [ProfileController::class, 'registerVendor'])->name('profile.vendor.register');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
});

Breadcrumbs::for('home', function (BreadcrumbTrail $trail) {
    $trail->push('Home', route('home'));
});

Breadcrumbs::for('profile', function (BreadcrumbTrail $trail) {
    $trail->parent('home');
    $trail->push('Hồ sơ tài khoản', route('profile'));
});
Route::get('/', [HomeController::class, 'index'])
    ->name('home');

// Route::get('/o', function () {
//     return view('homepage_o');
// });

Route::get('/products', function () {
    $products = Product::with('category', 'images')->get();
    return view('products.pages.index', ['products' => $products]);
})->name('products.index');


Route::get('/categoriess/{slug}', [CategoryController::class, 'show'])
    ->name('categories.show');
Route::get('/categories/{slug}', function (string $slug) {
    return view('categories.pages.category', ['category' => $slug]);
})->name('categories.show');
Breadcrumbs::for('category', function (BreadcrumbTrail $trail, Category $category) {
    if ($category->parent) {
        $trail->parent('category', $category->parent);
    } else {
        $trail->parent('home');
    }

    $trail->push($category->name, route('categories.show', [ 'slug' => $category->slug ]));
});

Breadcrumbs::for('vendor', function (BreadcrumbTrail $trail, Vendor $vendor) {
    $trail->parent('home');
    $trail->push($vendor->store_name, route('vendors.show', ['vendor' => $vendor->vendor_id]));
});

Route::get('/vendors/{vendor}', [VendorStorefrontController::class, 'show'])
    ->name('vendors.show');

Route::get('/vendor/products/create', function () {
    return view('vendor.pages.create_product');
})->name('vendor.product.create');

Route::post('/vendor/products', function (Request $request) {
    $productName = $request->input('name');
    $productPrice = $request->input('price');
    $categoryId = $request->input('category_id');

    return view('vendor.success', compact('productName', 'productPrice', 'categoryId'));
})->name('vendor.product.store');

Route::post('/products/{product}/images', [ProductImageController::class, 'store'])
    ->name('products.images.store');

Route::get('/supports', function () {
    return view('misc.pages.supports');
});

require __DIR__ . '/products.php';
require __DIR__ . '/auth.php';
