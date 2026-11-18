<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Support\CreatesCartFixtures;
use Tests\TestCase;

class CurrencyCheckoutTest extends TestCase
{
    use CreatesCartFixtures, RefreshDatabase;

    public function test_display_conversion_does_not_change_cart_order_or_payment_amounts(): void
    {
        $this->withoutVite();
        Http::preventStrayRequests();
        Http::fake(['open.er-api.com/*' => Http::response([
            'result' => 'success', 'base_code' => 'USD', 'rates' => ['VND' => 25000],
        ])]);
        config(['app.locale' => 'en']);
        $user = User::factory()->create();
        $product = $this->product(price: '12.50');
        $address = $user->addresses()->create([
            'recipient_name' => 'Currency Customer', 'address_line_1' => '123 Test Street',
            'city' => 'Test City', 'country_code' => 'VN',
        ]);

        $response = $this->actingAs($user)->withSession(['display_currency' => 'VND'])
            ->postJson(route('cart.add'), ['product_id' => $product->getKey(), 'quantity' => 2])
            ->assertOk()->assertJsonPath('cart.total_amount', '25.00')
            ->assertJsonPath('cart.formatted_total', '₫625,000');
        $this->assertStringContainsString('₫625,000', $response->json('cart_page_html'));

        $this->get(route('products.show', ['product' => $product, 'slug' => $product->slug]))
            ->assertOk()->assertSee('₫312,500')->assertSee(route('currency.update'));

        $created = $this->postJson(route('checkout.store'), ['address_id' => $address->getKey()])
            ->assertCreated()->assertJsonPath('total_amount', '25.00');
        $order = $user->orders()->findOrFail($created->json('order_id'));
        $this->get(route('checkout.show', $order))->assertOk()
            ->assertSee('₫625,000')->assertSee('25.00 USD');
        $this->post(route('payments.store', $order), ['payment_option' => 'fake_card'])
            ->assertRedirect();
        $payment = $order->payments()->sole();
        $this->assertSame('USD', $payment->currency);
        $this->assertSame('25.00', $payment->amount);
        $this->assertSame('12.50', $product->fresh()->price);
    }
}
