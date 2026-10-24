<?php

namespace App\Support\Carts;

use Closure;
use Illuminate\Support\Facades\Cache;

class CartLock
{
    public function forOwner(array $owner, Closure $callback): mixed
    {
        $key = 'cart-owner:'.hash('sha256', json_encode($owner));
        return Cache::lock($key, 30)->block(5, $callback);
    }
}
