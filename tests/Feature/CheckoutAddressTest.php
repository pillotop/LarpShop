<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CreatesCartFixtures;
use Tests\Support\CreatesCheckoutFixtures;
use Tests\TestCase;

class CheckoutAddressTest extends TestCase
{
    use CreatesCartFixtures, CreatesCheckoutFixtures, RefreshDatabase;

    #[DataProvider('invalidAddressPayloads')]
    public function test_invalid_address_preserves_cart_and_stock(array $payload): void
    {
        $user = User::factory()->create();
        $this->shippingAddress($user, ['is_default' => true]);
        $product = $this->product();
        $cart = $this->cartWith($user, [[$product, 2]]);
        $before = $cart->items()->get()->toArray();

        $this->actingAs($user)->postJson(route('checkout.store'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('address_id');

        $this->assertSame($before, $cart->items()->get()->toArray());
        $this->assertSame(10, $product->fresh()->stock);
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_details', 0);
        $this->assertDatabaseCount('order_addresses', 0);
    }

    public static function invalidAddressPayloads(): array
    {
        return [
            'missing even with a default address' => [[]],
            'null' => [['address_id' => null]],
            'empty' => [['address_id' => '']],
            'array' => [['address_id' => [1]]],
            'not an integer' => [['address_id' => 'invalid']],
            'nonexistent' => [['address_id' => 2147483647]],
        ];
    }

    #[DataProvider('unavailableAddressTypes')]
    public function test_foreign_or_deleted_address_cannot_be_used(string $type): void
    {
        $user = User::factory()->create();
        $address = $this->shippingAddress($type === 'foreign' ? User::factory()->create() : $user);
        if ($type === 'deleted') {
            $address->delete();
        }
        $product = $this->product();
        $cart = $this->cartWith($user, [[$product, 2]]);

        $this->actingAs($user)->postJson(route('checkout.store'), ['address_id' => $address->getKey()])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('address_id');

        $this->assertSame(2, $cart->items()->sole()->quantity);
        $this->assertSame(10, $product->fresh()->stock);
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_details', 0);
        $this->assertDatabaseCount('order_addresses', 0);
    }

    public static function unavailableAddressTypes(): array
    {
        return [['foreign'], ['deleted']];
    }

    public function test_selected_address_is_snapshotted_instead_of_the_default(): void
    {
        $user = User::factory()->create();
        $default = $this->shippingAddress($user, ['is_default' => true]);
        $selected = $this->shippingAddress($user, [
            'recipient_name' => 'Office recipient',
            'address_line_1' => '456 Office Street',
        ]);
        $product = $this->product();
        $this->cartWith($user, [[$product, 2]]);

        $response = $this->actingAs($user)->postJson(route('checkout.store'), [
            'address_id' => $selected->getKey(),
            // Extra address text cannot replace the server-owned snapshot.
            'recipient_name' => 'Injected recipient',
            'address_line_1' => 'Injected street',
        ])->assertCreated();

        $order = $user->orders()->findOrFail($response->json('order_id'));
        $snapshot = $order->addresses()->sole();

        $this->assertSame('shipping', $snapshot->type);
        $this->assertSame($selected->snapshot(), $snapshot->only(array_keys($selected->snapshot())));
        $this->assertNotSame($default->address_line_1, $snapshot->address_line_1);
        $this->assertTrue($default->fresh()->is_default);
        $this->assertFalse($selected->fresh()->is_default);
        $this->assertSame('25.00', $order->total_amount);
    }

    public function test_editing_or_deleting_saved_address_does_not_change_order_snapshot(): void
    {
        $user = User::factory()->create();
        $address = $this->shippingAddress($user);
        $expected = $address->snapshot();
        $product = $this->product();
        $this->cartWith($user, [[$product, 1]]);

        $response = $this->actingAs($user)->postJson(route('checkout.store'), [
            'address_id' => $address->getKey(),
        ])->assertCreated();
        $order = $user->orders()->findOrFail($response->json('order_id'));

        $address->update(['recipient_name' => 'Edited recipient', 'city' => 'Edited city']);
        $this->assertSame($expected, $order->addresses()->sole()->only(array_keys($expected)));

        $address->delete();
        $this->assertSame($expected, $order->addresses()->sole()->only(array_keys($expected)));
        $this->assertSame('12.50', $order->fresh()->total_amount);
    }
}
