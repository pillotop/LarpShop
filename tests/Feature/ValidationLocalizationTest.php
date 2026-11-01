<?php

namespace Tests\Feature;

use App\Data\Payments\PaymentRequest;
use App\Enums\PaymentMethod;
use App\Models\User;
use App\Services\Payments\PayPalClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\Support\CreatesCartFixtures;
use Tests\Support\CreatesCheckoutFixtures;
use Tests\TestCase;

class ValidationLocalizationTest extends TestCase
{
    use CreatesCartFixtures, CreatesCheckoutFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        config(['app.locale' => 'en', 'app.fallback_locale' => 'en']);
    }

    public function test_locale_and_search_errors_use_translated_field_names_and_summary(): void
    {
        $this->withSession(['locale' => 'vi'])->postJson(route('locale.update'), ['locale' => 'fr'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.locale.0', 'Giá trị đã chọn cho ngôn ngữ không hợp lệ.');

        $response = $this->getJson(route('products.index', [
            'min_price' => 10, 'max_price' => 5, 'sort' => 'invalid',
        ]))->assertUnprocessable()
            ->assertJsonValidationErrors(['max_price', 'sort']);
        $this->assertStringContainsString('giá cao nhất', $response->json('errors.max_price.0'));
        $this->assertStringContainsString('10', $response->json('errors.max_price.0'));
        $this->assertStringContainsString('(và 1 lỗi khác)', $response->json('message'));
    }

    public function test_address_errors_keep_the_billing_bag_and_old_input(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->withSession(['locale' => 'vi'])
            ->post(route('addresses.store'), [
                'recipient_name' => 'Original recipient',
                'address_line_1' => '123 Test Street', 'city' => '', 'country_code' => 'VNN',
            ])->assertRedirect(route('profile').'#pane-billing')
            ->assertSessionHasErrors([
                'city' => 'Trường thành phố là bắt buộc.',
                'country_code' => 'Trường mã quốc gia phải có đúng 2 ký tự.',
            ], null, 'billing')
            ->assertSessionHas('_old_input.address_form', 'new');

        $this->withCookie(config('session.cookie'), session()->getId())->get(route('profile'))->assertOk()
            ->assertSee('Trường thành phố là bắt buộc.')
            ->assertSee('value="Original recipient"', false);
        $this->assertDatabaseCount('addresses', 0);
    }

    public function test_password_errors_are_translated_and_do_not_change_the_password(): void
    {
        $user = User::factory()->create(['password' => 'original-password']);

        $this->actingAs($user)->withSession(['locale' => 'vi'])
            ->putJson(route('password.change'), [
                'current_password' => 'incorrect',
                'password' => 'replacement-password', 'password_confirmation' => 'mismatch',
            ])->assertUnprocessable()
            ->assertJsonPath('errors.current_password.0', 'Mật khẩu hiện tại không chính xác.')
            ->assertJsonPath('errors.password.0', 'Xác nhận mật khẩu không khớp.');

        $this->assertTrue(Hash::check('original-password', $user->fresh()->password));
    }

    public function test_checkout_and_review_validation_preserve_error_keys(): void
    {
        $user = User::factory()->create();
        $product = $this->product();

        $this->actingAs($user)->withSession(['locale' => 'vi'])
            ->postJson(route('checkout.store'))
            ->assertUnprocessable()
            ->assertJsonPath('errors.address_id.0', 'Trường địa chỉ giao hàng là bắt buộc.');

        $this->postJson(route('reviews.store', $product), [
            'rating' => 6, 'review_form' => 'review-form-product-1',
        ])->assertUnprocessable()
            ->assertJsonPath('errors.rating.0', 'Trường điểm đánh giá phải nằm trong khoảng 1 đến 5.');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('product_reviews', 0);
    }

    public function test_stock_failure_names_the_original_product_in_vietnamese(): void
    {
        $user = User::factory()->create();
        $address = $this->shippingAddress($user);
        $product = $this->product(name: 'Original Sword', stock: 1);
        $cart = $this->cartWith($user, [[$product, 2]]);

        $this->actingAs($user)->withSession(['locale' => 'vi'])
            ->postJson(route('checkout.store'), ['address_id' => $address->getKey()])
            ->assertUnprocessable()
            ->assertJsonPath('errors.cart.0', 'Không đủ hàng cho Original Sword');

        $this->assertSame(2, $cart->items()->sole()->quantity);
        $this->assertSame(1, $product->fresh()->stock);
    }

    public function test_invalid_login_credentials_are_translated_without_logging_in(): void
    {
        User::factory()->create(['email' => 'buyer@example.test', 'password' => 'original-password']);

        $this->withSession(['locale' => 'vi'])->postJson(route('login.store'), [
            'email' => 'buyer@example.test', 'password' => 'incorrect',
        ])->assertUnauthorized()
            ->assertJsonPath('message', 'Thông tin đăng nhập không chính xác')
            ->assertJsonPath('errors.email.0', 'Thông tin đăng nhập không chính xác.');

        $this->assertGuest();
    }

    public function test_english_required_message_and_missing_vietnamese_rule_fallback(): void
    {
        $this->withSession(['locale' => 'en'])->postJson(route('locale.update'))
            ->assertUnprocessable()
            ->assertJsonPath('errors.locale.0', 'The language field is required.');

        app()->setLocale('vi');
        $validator = validator(['tracking_code' => 'invalid'], ['tracking_code' => 'uuid']);
        $this->assertSame('The tracking code field must be a valid UUID.', $validator->errors()->first());
    }

    public function test_selected_language_is_not_added_to_paypal_requests(): void
    {
        app()->setLocale('vi');
        config([
            'services.paypal.base_url' => 'https://paypal.example.test',
            'services.paypal.client_id' => 'test-client',
            'services.paypal.client_secret' => 'test-secret',
        ]);
        Http::preventStrayRequests();
        Http::fake([
            'https://paypal.example.test/v1/oauth2/token' => Http::response(['access_token' => 'test-token']),
            'https://paypal.example.test/v2/checkout/orders' => Http::response(['id' => 'TEST-ORDER']),
        ]);

        app(PayPalClient::class)->createOrder(new PaymentRequest(
            reference: 'ORDER-1', amount: '12.50', currency: 'USD',
            method: PaymentMethod::EWallet, requestId: 'test-request',
            returnUrl: 'https://shop.example.test/return', cancelUrl: 'https://shop.example.test/cancel',
        ));

        Http::assertSent(fn (Request $request) => $request->url() === 'https://paypal.example.test/v2/checkout/orders'
            && $request['payment_source']['paypal']['experience_context'] === [
                'return_url' => 'https://shop.example.test/return',
                'cancel_url' => 'https://shop.example.test/cancel',
                'user_action' => 'PAY_NOW',
            ]
            && ! $request->hasHeader('Accept-Language'));
    }

    public function test_unconfigured_paypal_error_uses_the_request_language(): void
    {
        app()->setLocale('vi');
        config(['services.paypal.client_id' => null]);

        try {
            app(PayPalClient::class)->accessToken();
            $this->fail('An unconfigured provider must reject payment setup.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                ['payment' => ['PayPal chưa được cấu hình. Vui lòng chọn phương thức thanh toán khác.']],
                $exception->errors(),
            );
        }
    }

    public function test_shared_footer_uses_vietnamese_labels_and_preserves_destinations(): void
    {
        app()->setLocale('vi');

        $this->blade('<x-company-footer />')
            ->assertSee('Thông tin công ty')
            ->assertSee('Trung tâm trợ giúp')
            ->assertSee('Điều khoản dịch vụ')
            ->assertSee('href="/about"', false)
            ->assertSee('href="/terms"', false)
            ->assertDontSee('footer.');
    }
}
