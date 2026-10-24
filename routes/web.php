<?php

use App\Http\Controllers\AccountVendorController;
use App\Http\Controllers\CustomerServicePageController;
use App\Http\Controllers\ProductImageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StaticPageController;
use App\Http\Controllers\VendorStorefrontController;
use App\Models\Category;
use App\Models\Vendor;
use App\View\CategoryController;
use App\View\HomeController;
use App\View\ProductSearchController;
use Diglactic\Breadcrumbs\Breadcrumbs;
use Diglactic\Breadcrumbs\Generator as BreadcrumbTrail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/account/vendors/{vendor}', [AccountVendorController::class, 'update'])->whereNumber('vendor')->name('profile.vendor.update');
    Route::post('/account/vendors', [AccountVendorController::class, 'store'])->name('profile.vendor.register');
    Route::post('/account/vendors/{vendor}/resubmit', [AccountVendorController::class, 'resubmit'])->whereNumber('vendor')->name('profile.vendor.resubmit');
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

Route::get('/products', [ProductSearchController::class, 'index'])
    ->name('products.index');

Route::get('/categories/{slug}', [CategoryController::class, 'show'])
    ->name('categories.show');
Breadcrumbs::for('category', function (BreadcrumbTrail $trail, Category $category) {
    if ($category->parent) {
        $trail->parent('category', $category->parent);
    } else {
        $trail->parent('home');
    }

    $trail->push($category->name, route('categories.show', ['slug' => $category->slug]));
});

Breadcrumbs::for('vendor', function (BreadcrumbTrail $trail, Vendor $vendor) {
    $trail->parent('home');
    $trail->push($vendor->store_name, route('vendors.show', ['vendor' => $vendor->vendor_id]));
});

Breadcrumbs::for('about', function (BreadcrumbTrail $trail) {
    $trail->parent('home');
    $trail->push('About Us', route('about'));
});

Breadcrumbs::for('contact', function (BreadcrumbTrail $trail) {
    $trail->parent('home');
    $trail->push('Contact Us', route('contact'));
});

Breadcrumbs::for('investors', function (BreadcrumbTrail $trail) {
    $trail->parent('home');
    $trail->push('Investors', route('investors'));
});

Breadcrumbs::for('blog', function (BreadcrumbTrail $trail) {
    $trail->parent('home');
    $trail->push('Blog', route('blog'));
});

Breadcrumbs::for('press', function (BreadcrumbTrail $trail) {
    $trail->parent('home');
    $trail->push('Press & Media', route('press'));
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

// Company Info Static Pages
Route::get('/about', [StaticPageController::class, 'about'])->name('about');
Route::get('/contact', [StaticPageController::class, 'contact'])->name('contact');
Route::post('/contact', [StaticPageController::class, 'submitContact'])->name('contact.submit');
Route::get('/investors', [StaticPageController::class, 'investors'])->name('investors');
Route::get('/blog', [StaticPageController::class, 'blog'])->name('blog');
Route::get('/press', [StaticPageController::class, 'press'])->name('press');

require __DIR__.'/products.php';
require __DIR__.'/auth.php';
require __DIR__.'/admin.php';

// Customer Service Static Pages
Breadcrumbs::for('help', function (BreadcrumbTrail $trail) {
    $trail->parent('home');
    $trail->push('Help Centre', route('help'));
});

Breadcrumbs::for('privacy', function (BreadcrumbTrail $trail) {
    $trail->parent('home');
    $trail->push('Privacy Policy', route('privacy'));
});

Breadcrumbs::for('community', function (BreadcrumbTrail $trail) {
    $trail->parent('home');
    $trail->push('Community Forum', route('community'));
});

Breadcrumbs::for('recalls', function (BreadcrumbTrail $trail) {
    $trail->parent('home');
    $trail->push('Product Recalls', route('recalls'));
});

Route::get('/help', [CustomerServicePageController::class, 'help'])->name('help');
Route::get('/privacy', [CustomerServicePageController::class, 'privacy'])->name('privacy');
Route::get('/community', [CustomerServicePageController::class, 'community'])->name('community');
Route::get('/recalls', [CustomerServicePageController::class, 'recalls'])->name('recalls');
