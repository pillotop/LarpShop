<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesCartFixtures;
use Tests\TestCase;

class CheckoutPageTest extends TestCase
{
    use CreatesCartFixtures;
    use RefreshDatabase;

    public function test_cart_form_can_redirect_to_the_checkout_page_with_an_owned_address(): void
    {
        [$user, , $address] = $this->checkout();
        $product = $this->product();
        $this->cartWith($user, [[$product, 2]]);
        $this->get(route('cart.index'))->assertOk()
            ->assertSee('name="address_id"', false)->assertSee('name="_token"', false);
        $this->from(route('cart.index'))->post(route('checkout.store'))
            ->assertRedirect(route('cart.index'))->assertSessionHasErrors('address_id');
        $response = $this->post(route('checkout.store'), ['address_id' => $address->getKey()]);
        $created = $user->orders()->orderByDesc('order_id')->firstOrFail();
        $response->assertRedirect(route('checkout.show', $created));
        $this->get(route('checkout.show', $created))->assertOk()->assertSee('Shipping address');
    }

    private function checkout(): array
    {
        $user = User::factory()->create();
        $address = $user->addresses()->create(['recipient_name' => 'Buyer', 'address_line_1' => '123 First Street', 'city' => 'Test City', 'country_code' => 'VN']);
        $order = Order::create(['user_id' => $user->getKey(), 'status' => 'pending', 'total_amount' => '49.95', 'expires_at' => now()->addMinutes(30)]);
        $order->addresses()->create(['type' => 'shipping', ...$address->snapshot()]);
        $this->actingAs($user);

        return [$user, $order, $address];
    }

    public function test_checkout_renders_sections_and_switches_only_the_order_snapshot(): void
    {
        [$user, $order, $original] = $this->checkout();
        $replacement = $user->addresses()->create([...$original->snapshot(), 'address_line_1' => '456 Second Street']);
        $this->get(route('checkout.show', $order))->assertOk()->assertSee('Your details')->assertSee('Shipping address')->assertSee('Credit or Debit card')->assertSee('Fake gateway');
        $this->put(route('checkout.address.update', $order), ['address_id' => $replacement->getKey()])->assertRedirect(route('checkout.show', $order));
        $this->assertSame('456 Second Street', $order->addresses()->sole()->address_line_1);
        $this->assertSame('123 First Street', $original->fresh()->address_line_1);
        $this->assertSame('49.95', $order->fresh()->total_amount);
    }

    public function test_users_cannot_read_change_or_pay_another_users_order(): void
    {
        [, $order, $address] = $this->checkout();
        $this->actingAs(User::factory()->create());
        $this->get(route('checkout.show', $order))->assertNotFound();
        $this->putJson(route('checkout.address.update', $order), ['address_id' => $address->getKey()])->assertUnprocessable();
        $this->post(route('test.payments.pay', $order), ['terms' => 1])->assertNotFound();
        $this->assertSame('123 First Street', $order->addresses()->sole()->address_line_1);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_an_owned_order_cannot_use_another_users_address(): void
    {
        [, $order, $address] = $this->checkout();
        $other = User::factory()->create()->addresses()->create($address->snapshot());
        $this->putJson(route('checkout.address.update', $order), ['address_id' => $other->getKey()])->assertUnprocessable()->assertJsonValidationErrors('address_id');
        $this->assertSame('123 First Street', $order->addresses()->sole()->address_line_1);
    }

    public function test_address_is_locked_after_payment_starts_or_order_expires(): void
    {
        [, $order, $address] = $this->checkout();
        $this->postJson(route('test.payments.store', $order))->assertCreated();
        $this->putJson(route('checkout.address.update', $order), ['address_id' => $address->getKey()])->assertUnprocessable();
        [, $expired, $expiredAddress] = $this->checkout();
        $expired->update(['expires_at' => now()->subSecond()]);
        $this->putJson(route('checkout.address.update', $expired), ['address_id' => $expiredAddress->getKey()])->assertUnprocessable();
    }

    public function test_demo_pay_rejects_invalid_provider_and_method_values(): void
    {
        [, $order] = $this->checkout();

        foreach (['invalid', ['card'], '', null] as $method) {
            $this->postJson(route('test.payments.pay', $order), [
                'terms' => 1, 'method' => $method,
            ])->assertUnprocessable()->assertJsonValidationErrors('method');
        }

        foreach (['stripe', ['fake'], '', null] as $provider) {
            $this->postJson(route('test.payments.pay', $order), [
                'terms' => 1, 'provider' => $provider,
            ])->assertUnprocessable()->assertJsonValidationErrors('provider');
        }

        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('fake_payment_sessions', 0);
    }

    public function test_demo_pay_persists_the_selected_wallet_method(): void
    {
        [, $order] = $this->checkout();

        $this->post(route('test.payments.pay', $order), [
            'terms' => 1, 'provider' => 'fake', 'method' => 'ewallet',
        ])->assertRedirect(route('checkout.show', $order));

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->getKey(),
            'provider' => 'fake',
            'method' => 'ewallet',
            'status' => 'succeeded',
        ]);
    }

    public function test_demo_pay_requires_terms_and_uses_the_database_total_once(): void
    {
        [, $order] = $this->checkout();
        $this->postJson(route('test.payments.pay', $order))->assertUnprocessable()->assertJsonValidationErrors('terms');
        $this->assertDatabaseCount('payments', 0);
        $this->post(route('test.payments.pay', $order), ['terms' => 1, 'amount' => '0.01'])->assertRedirect(route('checkout.show', $order));
        $this->assertSame(OrderStatus::Paid, $order->fresh()->status);
        $this->assertDatabaseHas('payments', ['order_id' => $order->getKey(), 'amount' => '49.95', 'status' => 'succeeded', 'method' => 'card', 'provider' => 'fake']);
        $this->postJson(route('test.payments.pay', $order), ['terms' => 1])->assertUnprocessable();
        $this->assertDatabaseCount('payments', 1);
        $this->get(route('checkout.show', $order))->assertOk()->assertSee('This order has been paid.');
    }
}
