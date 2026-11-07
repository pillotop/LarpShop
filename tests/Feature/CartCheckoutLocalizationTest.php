<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Tests\Support\CreatesCartFixtures;
use Tests\TestCase;

class CartCheckoutLocalizationTest extends TestCase
{
    use CreatesCartFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        config(['app.locale' => 'en', 'app.fallback_locale' => 'en']);
    }

    public function test_cart_ajax_fragments_and_errors_use_the_selected_language(): void
    {
        $product = $this->product(name: 'Original Sword', price: '12.50', stock: 3);

        $response = $this->actingAs(User::factory()->create())
            ->withSession(['locale' => 'vi'])->postJson(route('cart.add'), [
                'product_id' => $product->getKey(),
                'quantity' => 2,
            ])->assertOk()
            ->assertJsonPath('message', 'Đã cập nhật giỏ hàng.')
            ->assertJsonPath('quantity', 2);

        $this->assertStringContainsString('Giỏ hàng', $response->json('cart_html'));
        $this->assertStringContainsString('Original Sword', $response->json('cart_html'));
        $this->assertStringContainsString('Tổng cộng 1 sản phẩm', $response->json('cart_page_html'));
        $this->assertStringContainsString('12.50', $response->json('cart_page_html'));

        $this->postJson(route('cart.add'), ['product_id' => $product->getKey(), 'quantity' => 2])
            ->assertUnprocessable()
            ->assertJsonPath('errors.quantity.0', 'Số lượng yêu cầu vượt quá số hàng còn lại');

        $this->getJson(route('cart.show'))
            ->assertOk()
            ->assertJsonPath('cart.total_count', 2);

        $this->get(route('cart.index'))->assertOk()
            ->assertSee('id="cart-messages"', false)
            ->assertSee('Giỏ hàng của bạn')
            ->assertDontSee('cart.js.');
    }

    public function test_checkout_translates_forms_without_changing_payment_values_or_totals(): void
    {
        $user = User::factory()->create(['username' => 'Original customer']);
        $address = $user->addresses()->create([
            'recipient_name' => 'Original recipient',
            'address_line_1' => '123 Original Street',
            'city' => 'Original city',
            'country_code' => 'VN',
        ]);
        $product = $this->product(price: '12.50');
        $this->cartWith($user, [[$product, 2]]);

        $created = $this->actingAs($user)->withSession(['locale' => 'vi'])
            ->postJson(route('checkout.store'), ['address_id' => $address->getKey()])
            ->assertCreated()
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('total_amount', '25.00');

        $order = $user->orders()->findOrFail($created->json('order_id'));
        $this->get(route('checkout.show', $order))->assertOk()
            ->assertSee('Thông tin của bạn')
            ->assertSee('Địa chỉ giao hàng')
            ->assertSee('Thẻ tín dụng hoặc thẻ ghi nợ')
            ->assertSee('Tiếp tục đến PayPal')
            ->assertSee('Original recipient')
            ->assertSee('123 Original Street')
            ->assertSee('value="fake_card"', false)
            ->assertSee('value="paypal"', false)
            ->assertSee('$25.00');

        $this->post(route('payments.store', $order), ['payment_option' => 'fake_card'])
            ->assertRedirect(route('checkout.show', $order));
        $payment = $order->payments()->sole();

        $this->get(route('checkout.show', $order))->assertOk()
            ->assertSee('Kết quả mô phỏng')
            ->assertSee('value="succeeded"', false)
            ->assertSee('Thành công');

        $this->post(route('test.payments.simulate', $payment), ['outcome' => 'succeeded'])
            ->assertRedirect(route('checkout.show', $order))
            ->assertSessionHas('success', 'Trạng thái thanh toán mô phỏng: Thành công');

        $this->assertSame(OrderStatus::Paid, $order->fresh()->status);
        $this->assertSame(PaymentStatus::Succeeded, $payment->fresh()->status);
        $this->assertSame('25.00', $order->fresh()->total_amount);
        $this->assertSame('fake', $payment->fresh()->provider);
    }

    public function test_empty_checkout_returns_translated_business_error(): void
    {
        $user = User::factory()->create();
        $address = $user->addresses()->create([
            'recipient_name' => 'Buyer', 'address_line_1' => '123 Street',
            'city' => 'Test City', 'country_code' => 'VN',
        ]);

        $this->actingAs($user)->withSession(['locale' => 'vi'])
            ->postJson(route('checkout.store'), ['address_id' => $address->getKey()])
            ->assertUnprocessable()
            ->assertJsonPath('errors.cart.0', 'Giỏ hàng của bạn đang trống');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_cart_and_checkout_translation_catalogs_match(): void
    {
        foreach (['cart', 'checkout'] as $domain) {
            $english = require lang_path("en/{$domain}.php");
            $vietnamese = require lang_path("vi/{$domain}.php");

            $this->assertSame(array_keys(Arr::dot($english)), array_keys(Arr::dot($vietnamese)));
        }
    }
}
