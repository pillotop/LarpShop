<?php

namespace Tests\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;

trait CreatesReportFixtures
{
    protected function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    protected function makeProduct(string $name, string $store, float $price = 10): int
    {
        $owner = User::factory()->create();
        $vendorId = DB::table('vendors')->insertGetId([
            'user_id' => $owner->user_id, 'store_name' => $store, 'status' => 'approved',
            'created_at' => now(), 'updated_at' => now(),
        ], 'vendor_id');
        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Cat '.uniqid(), 'slug' => 'cat-'.uniqid(), 'created_at' => now(), 'updated_at' => now(),
        ], 'category_id');

        return DB::table('products')->insertGetId([
            'category_id' => $categoryId, 'vendor_id' => $vendorId, 'name' => $name,
            'slug' => 'p-'.uniqid(), 'price' => $price, 'stock' => 5,
            'created_at' => now(), 'updated_at' => now(),
        ], 'product_id');
    }

    protected function makeOrder(int $productId, int $qty, float $unitPrice, string $status, $createdAt = null, ?string $paymentStatus = 'succeeded', string $provider = 'fake'): int
    {
        $buyer = User::factory()->create();
        $orderId = DB::table('orders')->insertGetId([
            'user_id' => $buyer->user_id, 'total_amount' => $qty * $unitPrice, 'status' => $status,
            'created_at' => $createdAt ?? now(), 'updated_at' => now(),
        ], 'order_id');
        DB::table('order_details')->insert([
            'order_id' => $orderId, 'product_id' => $productId, 'quantity' => $qty,
            'unit_price' => $unitPrice, 'created_at' => now(), 'updated_at' => now(),
        ]);
        if ($status === 'paid' && $paymentStatus !== null) {
            DB::table('payments')->insert([
                'order_id' => $orderId, 'provider' => $provider, 'method' => 'card',
                'amount' => $qty * $unitPrice, 'currency' => 'USD', 'status' => $paymentStatus,
                'completed_at' => $createdAt ?? now(),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        return $orderId;
    }
}
