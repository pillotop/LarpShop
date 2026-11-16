<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesCartFixtures;
use Tests\Support\CreatesCheckoutFixtures;
use Tests\TestCase;

class VariantCartCheckoutTest extends TestCase
{
    use CreatesCartFixtures, CreatesCheckoutFixtures, RefreshDatabase;

    public function test_cart_tracks_base_product_and_variants_separately_with_variant_pricing_and_stock(): void
    {
        $user = User::factory()->create();
        $product = $this->product(name: 'Battle Tunic', price: '40.00', stock: 10);
        $variant = ProductVariant::create([
            'product_id' => $product->getKey(),
            'name' => 'Large (Reinforced)',
            'sku' => 'BT-L-01',
            'price' => '55.00',
            'stock' => 3,
            'is_active' => true,
        ]);

        $this->actingAs($user)->postJson(route('cart.add'), [
            'product_id' => $product->getKey(),
            'quantity' => 1,
        ])->assertOk();

        $this->postJson(route('cart.add'), [
            'product_id' => $product->getKey(),
            'variant_id' => $variant->getKey(),
            'quantity' => 2,
        ])->assertOk()
            ->assertJsonPath('cart.total_count', 3)
            ->assertJsonPath('cart.total_amount', '150.00');

        // Variant stock overflow should fail even though base product has 10 in stock.
        $this->postJson(route('cart.add'), [
            'product_id' => $product->getKey(),
            'variant_id' => $variant->getKey(),
            'quantity' => 2,
        ])->assertUnprocessable()->assertJsonValidationErrors('quantity');
    }

    public function test_cart_rejects_mismatched_or_inactive_variant(): void
    {
        $user = User::factory()->create();
        $productA = $this->product(name: 'Product A');
        $productB = $this->product(name: 'Product B');

        $variantB = ProductVariant::create([
            'product_id' => $productB->getKey(),
            'name' => 'Red',
            'price' => '20.00',
            'stock' => 5,
            'is_active' => true,
        ]);

        $this->actingAs($user)->postJson(route('cart.add'), [
            'product_id' => $productA->getKey(),
            'variant_id' => $variantB->getKey(),
            'quantity' => 1,
        ])->assertUnprocessable()->assertJsonValidationErrors('variant_id');
    }

    public function test_selective_checkout_with_variant_and_express_shipping_keeps_unselected_items_and_releases_variant_stock_on_cancel(): void
    {
        $user = User::factory()->create();
        $address = $this->shippingAddress($user);
        $product = $this->product(name: 'Greaves', price: '30.00', stock: 10);
        $variant = ProductVariant::create([
            'product_id' => $product->getKey(),
            'name' => 'Gilded Steel',
            'sku' => 'GRV-GS',
            'price' => '45.00',
            'stock' => 5,
            'is_active' => true,
        ]);

        $this->actingAs($user);
        $this->postJson(route('cart.add'), [
            'product_id' => $product->getKey(),
            'quantity' => 1,
        ])->assertOk();
        $this->postJson(route('cart.add'), [
            'product_id' => $product->getKey(),
            'variant_id' => $variant->getKey(),
            'quantity' => 2,
        ])->assertOk();

        $cart = Cart::query()->where('user_id', $user->getKey())->firstOrFail();
        $baseItem = $cart->items()->whereNull('variant_id')->firstOrFail();
        $variantItem = $cart->items()->where('variant_id', $variant->getKey())->firstOrFail();

        $response = $this->postJson(route('checkout.store'), [
            'address_id' => $address->getKey(),
            'selected_cart_item_ids' => [$variantItem->getKey()],
            'shipping_method' => 'express',
        ])->assertCreated()
            ->assertJsonPath('total_amount', '99.99');

        $orderId = $response->json('order_id');
        $this->assertDatabaseHas('orders', [
            'order_id' => $orderId,
            'shipping_method' => 'express',
            'shipping_fee' => '9.99',
            'total_amount' => '99.99',
        ]);
        $this->assertDatabaseHas('order_details', [
            'order_id' => $orderId,
            'product_id' => $product->getKey(),
            'variant_id' => $variant->getKey(),
            'variant_name' => 'Gilded Steel',
            'variant_sku' => 'GRV-GS',
            'unit_price' => '45.00',
            'quantity' => 2,
        ]);

        // Unselected base item stays in cart; variant stock drops 5 -> 3; base product stays 10.
        $this->assertDatabaseHas('cart_items', ['cart_item_id' => $baseItem->getKey()]);
        $this->assertDatabaseMissing('cart_items', ['cart_item_id' => $variantItem->getKey()]);
        $this->assertSame(3, $variant->fresh()->stock);
        $this->assertSame(10, $product->fresh()->stock);

        // Cancelling the order restores variant stock 3 -> 5.
        $this->post(route('orders.cancel', $orderId))->assertRedirect();
        $this->assertSame(5, $variant->fresh()->stock);
        $this->assertSame(10, $product->fresh()->stock);
    }

    public function test_inline_address_endpoint_returns_json_for_ajax_requests(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('addresses.store'), [
                'recipient_name' => 'Alex Mercer',
                'phone_number' => '0901234567',
                'address_line_1' => '123 Main Street',
                'city' => 'Ho Chi Minh City',
                'country_code' => 'vn',
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('address.is_default', true);

        $this->assertDatabaseHas('addresses', [
            'user_id' => $user->getKey(),
            'recipient_name' => 'Alex Mercer',
            'country_code' => 'VN',
        ]);
    }
}
