<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Services\Orders\OrderLifecycleService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderStatusConsistencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_orders_have_consistent_payment_and_inventory_state(): void
    {
        $this->seed(DatabaseSeeder::class);

        $pending = Order::where('status', OrderStatus::Pending)->firstOrFail();
        $this->assertNotNull($pending->expires_at);
        $this->assertTrue($pending->expires_at->isFuture());
        $this->assertSame(0, $pending->payments()->count());
        $details = $pending->details()->with('product')->get();
        $stocks = $details->mapWithKeys(fn ($detail) => [$detail->product_id => $detail->product->stock]);

        app(OrderLifecycleService::class)->cancel($pending);
        foreach ($details as $detail) {
            $this->assertSame($stocks[$detail->product_id] + $detail->quantity, $detail->product->fresh()->stock);
        }

        $paid = Order::where('status', OrderStatus::Paid)->firstOrFail();
        $payment = $paid->payments()->sole();
        $this->assertSame(PaymentStatus::Succeeded, $payment->status);
        $this->assertSame($paid->total_amount, $payment->amount);
    }

    public function test_order_detail_can_store_its_order_id_and_price_snapshot(): void
    {
        $this->seed(DatabaseSeeder::class);
        $order = Order::firstOrFail();
        $original = $order->details()->firstOrFail();
        $detail = OrderDetail::create([
            'order_id' => $order->getKey(),
            'product_id' => $original->product_id,
            'quantity' => 1,
            'unit_price' => '12.50',
        ]);

        $this->assertSame($order->getKey(), $detail->fresh()->order->getKey());
        $this->assertSame('12.50', $detail->fresh()->unit_price);
    }
}
