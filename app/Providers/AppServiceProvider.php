<?php

namespace App\Providers;

use App\Models\Banner;
use App\Models\Vendor;
use App\Observers\BannerObserver;
use App\Observers\VendorBannerObserver;
use App\Support\Money\MoneyDisplay;
use App\View\Composers\ProductCapabilityComposer;
use App\View\Composers\WishlistButtonComposer;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(MoneyDisplay::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Facades\View::composer('*', function ($view) {
            $view->with('money', app(MoneyDisplay::class));
        });

        Banner::observe(BannerObserver::class);
        Vendor::observe(VendorBannerObserver::class);

        Paginator::defaultView('shared.pagination');

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
