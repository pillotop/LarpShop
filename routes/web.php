<?php

use App\Http\Controllers\AccountVendorController;
use App\Http\Controllers\ProductImageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\VendorStorefrontController;
use App\Models\Category;
use App\Models\Vendor;
use App\View\CategoryController;
use App\View\HomeController;
use App\View\ProductSearchController;
use Diglactic\Breadcrumbs\Breadcrumbs;
use Diglactic\Breadcrumbs\Generator as BreadcrumbTrail;
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

Route::post('/products/{product}/images', [ProductImageController::class, 'store'])
    ->name('products.images.store');

require __DIR__.'/static.php';
require __DIR__.'/products.php';
require __DIR__.'/auth.php';
require __DIR__.'/checkout.php';
require __DIR__.'/payments.php';
require __DIR__.'/addresses.php';
require __DIR__.'/orders.php';
require __DIR__.'/wishlist.php';
require __DIR__.'/vendor.php';
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
