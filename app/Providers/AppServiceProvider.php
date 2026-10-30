<?php

namespace App\Providers;

use App\View\Composers\ProductCapabilityComposer;
use App\View\Composers\WishlistButtonComposer;
use Illuminate\Support\Facades;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void {}

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Facades\View::composer('components.wishlist.button', WishlistButtonComposer::class);

        Facades\View::composer([
            'homepage',
            'products.pages.index',
            'products.pages.show',
            'categories.pages.show',
            'vendor.pages.storefront',
            'profile.pages.show',
            'profile',
            'vendor.*',
            'static.*',
            'cart.pages.index',
        ], ProductCapabilityComposer::class);
    }
}
