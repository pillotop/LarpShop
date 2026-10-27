<?php

namespace App\Providers;

use App\Contracts\Payment\PaymentGateway;
use App\Services\Payments\FakePaymentGateway;
use App\View\Composers\ProductCapabilityComposer;
use Illuminate\Support\Facades;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
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
