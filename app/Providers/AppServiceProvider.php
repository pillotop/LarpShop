<?php

namespace App\Providers;

use Illuminate\Support\Facades;
use Illuminate\Support\ServiceProvider;
use App\View\Composers\ProductCapabilityComposer;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Facades\View::composer([
            'homepage',
            'products.pages.show',
            'categories.pages.show',
            'vendor.pages.storefront',
            'profile.pages.show',
            'profile',
        ], ProductCapabilityComposer::class);
    }
}
