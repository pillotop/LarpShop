<?php

namespace Tests\Feature;

use App\Models\OrderDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\Support\CreatesCartFixtures;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use CreatesCartFixtures, RefreshDatabase;

    public function test_two_products_create_one_order_with_two_details(): void
    {
        $user = User::factory()->create();
        $first = $this->product();
        $second = $this->product('Shield', '20.00');
        $this->cartWith($user, [[$first, 2], [$second, 1]]);

        $response = $this->actingAs($user)->postJson(route('checkout.store'))
            ->assertCreated()->assertJsonPath('status', 'pending')
            ->assertJsonPath('total_amount', '45.00');
        $orderId = $response->json('order_id');

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_details', 2);
        $this->assertDatabaseHas('orders', ['order_id' => $orderId, 'user_id' => $user->getKey(), 'total_amount' => '45.00']);
        foreach ([[$first, 2, '12.50'], [$second, 1, '20.00']] as [$product, $quantity, $price]) {
            $this->assertDatabaseHas('order_details', [
                'order_id' => $orderId, 'product_id' => $product->getKey(),
                'quantity' => $quantity, 'unit_price' => $price,
            ]);
        }
    }

    public function test_missing_or_empty_cart_is_rejected(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->postJson(route('checkout.store'))
            ->assertUnprocessable()->assertJsonValidationErrors('cart');
        $this->cartWith($user, []);
        $this->postJson(route('checkout.store'))
            ->assertUnprocessable()->assertJsonValidationErrors('cart');
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_details', 0);
    }

    public function test_checkout_uses_the_latest_database_price(): void
    {
        $user = User::factory()->create();
        $product = $this->product();
        $this->cartWith($user, [[$product, 3]]);
        $product->forceFill(['price' => '15.25'])->save();

        $this->actingAs($user)->postJson(route('checkout.store'))
            ->assertCreated()->assertJsonPath('total_amount', '45.75');
        $this->assertDatabaseHas('order_details', ['product_id' => $product->getKey(), 'unit_price' => '15.25', 'quantity' => 3]);
    }

    public function test_insufficient_stock_leaves_the_entire_cart_unchanged(): void
    {
        $user = User::factory()->create();
        $first = $this->product();
        $second = $this->product('Shield');
        $cart = $this->cartWith($user, [[$first, 2], [$second, 3]]);
        $before = $cart->items()->get()->toArray();
        $second->forceFill(['stock' => 1])->save();

        $this->actingAs($user)->postJson(route('checkout.store'))
            ->assertUnprocessable()->assertJsonValidationErrors('cart');

        $this->assertSame($before, $cart->items()->get()->toArray());
        $this->assertEquals(10, $first->fresh()->stock);
        $this->assertEquals(1, $second->fresh()->stock);
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_details', 0);
    }

    public function test_success_decreases_stock_and_empties_only_the_buyers_cart(): void
    {
        $buyer = User::factory()->create();
        $other = User::factory()->create();
        $product = $this->product();
        $cart = $this->cartWith($buyer, [[$product, 3]]);
        $otherCart = $this->cartWith($other, [[$product, 2]]);

        $this->actingAs($buyer)->postJson(route('checkout.store'))->assertCreated();

        $this->assertEquals(7, $product->fresh()->stock);
        $this->assertSame(0, $cart->items()->count());
        $this->assertEquals(2, $otherCart->items()->sole()->quantity);
    }

    public function test_failure_after_the_first_stock_change_rolls_everything_back(): void
    {
        $user = User::factory()->create();
        $first = $this->product();
        $second = $this->product('Shield');
        $cart = $this->cartWith($user, [[$first, 2], [$second, 1]]);
        $before = $cart->items()->get()->toArray();
        $reachedSecondDetail = false;
        // Use a cloned dispatcher so the injected failure cannot leak into other tests.
        $dispatcher = OrderDetail::getEventDispatcher();
        OrderDetail::setEventDispatcher(clone $dispatcher);
        OrderDetail::creating(function (OrderDetail $detail) use ($first, $second, &$reachedSecondDetail) {
            if ($detail->product_id === $second->getKey()) {
                $this->assertEquals(8, $first->fresh()->stock);
                $this->assertSame(1, DB::table('order_details')->count());
                $reachedSecondDetail = true;
                throw new RuntimeException('Simulated checkout failure');
            }
        });

        try {
            $this->withoutExceptionHandling()->actingAs($user)->postJson(route('checkout.store'));
            $this->fail('Checkout should have thrown the injected exception.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated checkout failure', $exception->getMessage());
        } finally {
            OrderDetail::setEventDispatcher($dispatcher);
        }

        $this->assertTrue($reachedSecondDetail);
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_details', 0);
        $this->assertEquals(10, $first->fresh()->stock);
        $this->assertEquals(10, $second->fresh()->stock);
        $this->assertSame($before, $cart->items()->get()->toArray());
    }

    public function test_guest_cannot_checkout(): void
    {
        $this->postJson(route('checkout.store'))->assertUnauthorized();
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_details', 0);
    }

    public function test_second_sequential_checkout_cannot_consume_the_cart_again(): void
    {
        $user = User::factory()->create();
        $product = $this->product();
        $this->cartWith($user, [[$product, 2]]);

        $this->actingAs($user)->postJson(route('checkout.store'))->assertCreated();
        $this->postJson(route('checkout.store'))->assertUnprocessable()->assertJsonValidationErrors('cart');

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_details', 1);
        $this->assertEquals(8, $product->fresh()->stock);
        $this->assertDatabaseCount('cart_items', 0);
    }
}
