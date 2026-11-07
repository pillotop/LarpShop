<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Tests\Support\CreatesCartFixtures;
use Tests\Support\CreatesCheckoutFixtures;
use Tests\TestCase;

class ProfileOrderLocalizationTest extends TestCase
{
    use CreatesCartFixtures, CreatesCheckoutFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        config(['app.locale' => 'en', 'app.fallback_locale' => 'en']);
    }

    public function test_profile_translates_forms_and_preserves_customer_data_and_tab_contracts(): void
    {
        $user = User::factory()->create(['username' => 'Original customer']);
        $user->vendors()->create(['store_name' => 'Original store', 'status' => 'approved']);
        $this->shippingAddress($user, ['recipient_name' => 'Original recipient']);
        $product = $this->product(name: 'Original product');
        $user->wishlistProducts()->attach($product);

        $this->actingAs($user)->withSession(['locale' => 'vi'])->get(route('profile'))
            ->assertOk()
            ->assertSee('<html lang="vi">', false)
            ->assertSee('Cập nhật thông tin cá nhân')
            ->assertSee('Đổi mật khẩu tài khoản')
            ->assertSee('Thanh toán và địa chỉ')
            ->assertSee('Danh sách yêu thích')
            ->assertSee('Original customer')
            ->assertSee('Original store')
            ->assertSee('Original recipient')
            ->assertSee('Original product')
            ->assertSee('Đã duyệt')
            ->assertSee('name="username"', false)
            ->assertSee('name="current_password"', false)
            ->assertSee('name="country_code"', false)
            ->assertSee('id="pane-billing"', false)
            ->assertSee('id="tab-wishlist"', false);

        $this->putJson(route('profile.update'), [
            'username' => 'Updated customer', 'email' => $user->email, 'phone_number' => '0901234567',
        ])->assertOk()
            ->assertJsonPath('message', 'Đã cập nhật hồ sơ.')
            ->assertJsonPath('user.role', 'customer');

        $this->assertSame('Updated customer', $user->fresh()->username);
    }

    public function test_localized_success_messages_keep_wishlist_state_marker(): void
    {
        $user = User::factory()->create();
        $product = $this->product();

        $this->actingAs($user)->withSession(['locale' => 'vi'])
            ->from(route('profile'))
            ->post(route('wishlist.store', $product))
            ->assertRedirect(route('profile'))
            ->assertSessionHas('status', 'wishlist-updated')
            ->assertSessionHas('success', 'Đã thêm sản phẩm vào danh sách yêu thích.');

        $this->post(route('addresses.store'), [
            'recipient_name' => 'Original recipient',
            'address_line_1' => '123 Original Street', 'city' => 'Original City', 'country_code' => 'VN',
        ])->assertRedirect(route('profile').'#pane-billing')
            ->assertSessionHas('success', 'Đã lưu địa chỉ.');

        $this->assertSame('Original recipient', $user->addresses()->sole()->recipient_name);
    }

    public function test_order_history_translates_pagination_and_paid_purchase_review_form(): void
    {
        $user = User::factory()->create();
        $product = $this->product(name: 'Purchased Sword');
        for ($index = 0; $index < 11; $index++) {
            $order = $user->orders()->create(['status' => OrderStatus::Paid, 'total_amount' => '12.50']);
            $order->forceFill(['created_at' => now()->subMinutes($index)])->save();
            if ($index === 0) {
                $order->details()->create([
                    'product_id' => $product->getKey(), 'quantity' => 1, 'unit_price' => '12.50',
                ]);
            }
        }

        $this->actingAs($user)->withSession(['locale' => 'vi'])->get(route('orders.index'))
            ->assertOk()
            ->assertSee('Lịch sử đơn hàng')
            ->assertSee('Đã thanh toán')
            ->assertSee('Viết đánh giá')
            ->assertSee('Purchased Sword')
            ->assertSee('Hiển thị 1–10 trong 11 kết quả')
            ->assertSee('Đến trang 2')
            ->assertSee('name="review_form"', false);

        $this->from(route('orders.index'))->post(route('reviews.store', $product), [
            'review_form' => 'review-form-order-1-detail-1',
            'rating' => 5, 'comment' => 'Original customer review.',
        ])->assertRedirect(route('orders.index').'#review-feedback')
            ->assertSessionHas('review_success', 'Cảm ơn bạn! Đánh giá đã được đăng.');

        $this->assertDatabaseHas('product_reviews', [
            'user_id' => $user->getKey(), 'product_id' => $product->getKey(),
            'comment' => 'Original customer review.',
        ]);
    }

    public function test_order_details_and_cancellation_translate_only_display_values(): void
    {
        $user = User::factory()->create();
        $address = $this->shippingAddress($user);
        $product = $this->product();
        $this->cartWith($user, [[$product, 2]]);

        $response = $this->actingAs($user)->withSession(['locale' => 'vi'])
            ->postJson(route('checkout.store'), ['address_id' => $address->getKey()])
            ->assertCreated();
        $order = $user->orders()->findOrFail($response->json('order_id'));

        $this->get(route('orders.show', $order))->assertOk()
            ->assertSee('Trạng thái đơn hàng: Chờ thanh toán')
            ->assertSee('Hủy đơn hàng')
            ->assertSee('$25.00');

        $this->post(route('orders.cancel', $order))
            ->assertRedirect(route('orders.show', $order))
            ->assertSessionHas('status', 'Đã hủy đơn hàng');

        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
        $this->assertSame('25.00', $order->fresh()->total_amount);
        $this->assertSame(10, $product->fresh()->stock);
    }

    public function test_profile_order_and_pagination_catalogs_have_matching_keys(): void
    {
        foreach (['profile', 'orders', 'wishlist', 'pagination', 'reviews'] as $domain) {
            $english = require lang_path("en/{$domain}.php");
            $vietnamese = require lang_path("vi/{$domain}.php");

            $this->assertEqualsCanonicalizing(array_keys(Arr::dot($english)), array_keys(Arr::dot($vietnamese)));
        }
    }
}
