<?php

namespace App\Queries\Cart;

use App\Models\Cart;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class CartQuery
{
    public function getCurrentCart(): Cart
    {
        $userId = Auth::id();
        $sessionId = Session::id();

        return Cart::with(['items.itemable.images'])
            ->firstOrCreate($userId
                ? ['user_id' => $userId]
                : ['session_id' => $sessionId]);
    }
}
