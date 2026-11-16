<?php

namespace App\View\Composers;

use App\Models\Category;
use App\Services\Cart\CartRetrievalService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class ProductCapabilityComposer
{
    public function __construct(
        private CartRetrievalService $carts,
    ) {}

    public function compose(View $view): void
    {
        $view->with('cart', $this->carts->getCartData());
        $view->with('productCategories', Category::query()->active()->orderBy('name')->get());

        $unreadCount = 0;
        $recentNotifications = collect();
        if (Auth::check() && Schema::hasTable('notifications')) {
            $user = Auth::user();
            $unreadCount = $user->unreadNotifications()->count();
            $recentNotifications = $user->notifications()->latest()->limit(8)->get();
        }

        $view->with('unreadNotificationCount', $unreadCount);
        $view->with('recentNotifications', $recentNotifications);
        view()->share('unreadNotificationCount', $unreadCount);
        view()->share('recentNotifications', $recentNotifications);
    }
}
