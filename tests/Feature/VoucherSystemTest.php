<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vendor;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesCartFixtures;
use Tests\Support\CreatesCheckoutFixtures;
use Tests\TestCase;

class VoucherSystemTest extends TestCase
{
    use CreatesCartFixtures, CreatesCheckoutFixtures, RefreshDatabase;

    public function test_platform_percent_voucher_with_cap_applies_at_preview_checkout_and_releases_on_cancel(): void
    {
        $user = User::factory()->create();
        $address = $this->shippingAddress($user);
        $product = $this->product(name: 'Runed Plate Armor', price: '100.00', stock: 5);
        $this->cartWith($user, [[$product, 2]]); // $200.00 subtotal

        $voucher = Voucher::create([
            'code' => 'LARP20',
            'title' => '20% off up to $25',
            'type' => Voucher::TYPE_PERCENT,
            'value' => 20,
            'min_order_amount' => 50,
            'max_discount_amount' => 25,
            'usage_limit' => 5,
            'per_user_limit' => 1,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->postJson(route('vouchers.preview'), [
                'voucher_code' => ' larp20 ',
                'shipping_method' => 'home_delivery',
            ])
            ->assertOk()
            ->assertJsonPath('voucher.code', 'LARP20')
            ->assertJsonPath('voucher.discount_amount', '25.00')
            ->assertJsonPath('grand_total', '175.00');

        $orderResponse = $this->postJson(route('checkout.store'), [
            'address_id' => $address->getKey(),
            'voucher_code' => 'LARP20',
        ])->assertCreated()
            ->assertJsonPath('total_amount', '175.00');

        $orderId = $orderResponse->json('order_id');
        $this->assertDatabaseHas('orders', [
            'order_id' => $orderId,
            'subtotal_amount' => '200.00',
            'discount_amount' => '25.00',
            'voucher_id' => $voucher->getKey(),
            'voucher_code' => 'LARP20',
            'total_amount' => '175.00',
        ]);
        $this->assertDatabaseHas('voucher_usages', [
            'voucher_id' => $voucher->getKey(),
            'user_id' => $user->getKey(),
            'order_id' => $orderId,
            'discount_amount' => '25.00',
        ]);
        $this->assertSame(1, $voucher->fresh()->used_count);

        $this->get(route('checkout.show', $orderId))
            ->assertOk()
            ->assertSee('LARP20')
            ->assertSee('-$25.00');

        // Reusing on another cart while per_user_limit=1 is rejected.
        $this->cartWith($user, [[$product, 1]]);
        $this->postJson(route('vouchers.preview'), [
            'voucher_code' => 'LARP20',
        ])->assertUnprocessable()->assertJsonValidationErrors('voucher_code');

        // Cancelling the first order releases the voucher usage and count.
        $this->post(route('orders.cancel', $orderId))->assertRedirect();
        $this->assertSame(0, $voucher->fresh()->used_count);
        $this->assertDatabaseMissing('voucher_usages', ['order_id' => $orderId]);
    }

    public function test_vendor_voucher_only_discounts_its_own_store_items_and_rejects_unrelated_carts(): void
    {
        $user = User::factory()->create();
        $address = $this->shippingAddress($user);

        $productA = $this->product(name: 'Forge Hammer', price: '50.00', stock: 5);
        $productB = $this->product(name: 'Silk Cloak', price: '60.00', stock: 5);

        Voucher::create([
            'vendor_id' => $productA->vendor_id,
            'code' => 'FORGE15',
            'title' => '$15 off Forge items',
            'type' => Voucher::TYPE_FIXED,
            'value' => 15,
            'min_order_amount' => 40,
            'is_active' => true,
        ]);

        // Cart with ONLY productB fails vendor check.
        $this->cartWith($user, [[$productB, 1]]);
        $this->actingAs($user)
            ->postJson(route('vouchers.preview'), ['voucher_code' => 'FORGE15'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('voucher_code');

        // Adding productA makes it eligible ($50 + $60 - $15 = $95).
        $this->postJson(route('cart.add'), ['product_id' => $productA->getKey(), 'quantity' => 1])->assertOk();
        $this->postJson(route('checkout.store'), [
            'address_id' => $address->getKey(),
            'voucher_code' => 'FORGE15',
        ])->assertCreated()->assertJsonPath('total_amount', '95.00');
    }

    public function test_free_shipping_and_expired_or_exhausted_vouchers_behave_correctly(): void
    {
        $user = User::factory()->create();
        $address = $this->shippingAddress($user);
        $product = $this->product(price: '25.00', stock: 5);
        $this->cartWith($user, [[$product, 1]]);

        Voucher::create([
            'code' => 'FREESHIP',
            'title' => 'Free Express Delivery',
            'type' => Voucher::TYPE_FREE_SHIPPING,
            'value' => 0,
            'is_active' => true,
        ]);

        Voucher::create([
            'code' => 'OLDCODE',
            'title' => 'Expired',
            'type' => Voucher::TYPE_FIXED,
            'value' => 5,
            'expires_at' => now()->subDay(),
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->postJson(route('vouchers.preview'), ['voucher_code' => 'OLDCODE'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('voucher_code');

        $this->postJson(route('checkout.store'), [
            'address_id' => $address->getKey(),
            'shipping_method' => 'express',
            'voucher_code' => 'FREESHIP',
        ])->assertCreated()
            ->assertJsonPath('total_amount', '25.00');
    }

    public function test_vendor_and_admin_voucher_management_enforce_ownership_and_roles(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $vendorUserA = User::factory()->create();
        $vendorA = Vendor::create([
            'user_id' => $vendorUserA->getKey(),
            'store_name' => 'Alpha Forge',
            'status' => 'approved',
        ]);
        $vendorUserB = User::factory()->create();
        $vendorB = Vendor::create([
            'user_id' => $vendorUserB->getKey(),
            'store_name' => 'Beta Alchemy',
            'status' => 'approved',
        ]);

        // Vendor A creates a store voucher.
        $this->actingAs($vendorUserA)
            ->post(route('vendor.vouchers.store'), [
                'code' => 'ALPHA10',
                'title' => '10% off Alpha',
                'type' => 'percent',
                'value' => 10,
                'min_order_amount' => 0,
                'per_user_limit' => 2,
            ])->assertRedirect(route('vendor.vouchers.index'));

        $alphaVoucher = Voucher::where('code', 'ALPHA10')->firstOrFail();
        $this->assertSame($vendorA->getKey(), $alphaVoucher->vendor_id);

        // Vendor B cannot toggle or delete Vendor A's voucher.
        $this->actingAs($vendorUserB)
            ->patch(route('vendor.vouchers.toggle', $alphaVoucher))
            ->assertForbidden();
        $this->delete(route('vendor.vouchers.destroy', $alphaVoucher))
            ->assertForbidden();

        // Vendor cannot access Admin voucher page.
        $this->get(route('admin.vouchers.index'))->assertForbidden();

        // Admin creates a platform voucher.
        $this->actingAs($admin)
            ->post(route('admin.vouchers.store'), [
                'code' => 'PLATFORM5',
                'title' => '$5 Platform Bonus',
                'type' => 'fixed',
                'value' => 5,
            ])->assertRedirect(route('admin.vouchers.index'));

        $this->assertDatabaseHas('vouchers', [
            'code' => 'PLATFORM5',
            'vendor_id' => null,
        ]);
    }
}
