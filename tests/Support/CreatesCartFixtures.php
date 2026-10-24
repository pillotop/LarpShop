<?php

namespace Tests\Support;

use App\Models\Cart;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

trait CreatesCartFixtures
{
    private function product(string $name = 'Test Sword', string $price = '12.50', int $stock = 10): Product
    {
        $owner = User::factory()->create();
        $vendor = $owner->vendors()->create(['store_name' => $name.' Store', 'status' => 'active']);
        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Equipment', 'slug' => 'equipment',
        ], 'category_id');

        return Product::forceCreate([
            'category_id' => $categoryId,
            'vendor_id' => $vendor->getKey(),
            'name' => $name,
            'slug' => str($name)->slug()->toString(),
            'price' => $price,
            'stock' => $stock,
        ]);
    }

    private function cartWith(User $user, array $entries): Cart
    {
        $cart = Cart::create(['user_id' => $user->getKey()]);
        foreach ($entries as [$product, $quantity]) {
            $cart->items()->create([
                'itemable_type' => $product->getMorphClass(),
                'itemable_id' => $product->getKey(),
                'quantity' => $quantity,
            ]);
        }

        return $cart;
    }
}
