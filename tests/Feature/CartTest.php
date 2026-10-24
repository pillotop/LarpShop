<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CreatesCartFixtures;
use Tests\TestCase;

class CartTest extends TestCase
{
    use CreatesCartFixtures, RefreshDatabase;

    public function test_add_accumulates_quantity_without_duplicate_items(): void
    {
        $user = User::factory()->create();
        $product = $this->product();
        $this->actingAs($user)->postJson(route('cart.add'), ['product_id' => $product->getKey(), 'quantity' => 2])->assertOk();
        $this->postJson(route('cart.add'), ['product_id' => $product->getKey(), 'quantity' => 3])
            ->assertOk()->assertJsonPath('cart.total_count', 5)->assertJsonPath('cart.total_amount', '62.50');

        $this->assertDatabaseCount('cart_items', 1);
        $this->assertDatabaseHas('cart_items', ['itemable_id' => $product->getKey(), 'quantity' => 5]);
        $this->assertEquals(10, $product->fresh()->stock);
    }

    public function test_store_replaces_quantity_and_zero_explicitly_removes_the_item(): void
    {
        $user = User::factory()->create();
        $product = $this->product();
        $this->cartWith($user, [[$product, 4]]);
        $this->actingAs($user)->postJson(route('cart.store'), ['product_id' => $product->getKey(), 'quantity' => 2])
            ->assertOk()->assertJsonPath('cart.total_count', 2);
        $response = $this->postJson(route('cart.store'), ['product_id' => $product->getKey(), 'quantity' => 0])
            ->assertOk()->assertJsonPath('cart.total_count', 0)->assertJsonPath('cart.total_amount', '0.00')
            ->assertJsonCount(0, 'cart.products');

        $this->assertDatabaseCount('cart_items', 0);
        $this->assertStringNotContainsString('data-cart-remove', $response->json('cart_page_html'));
        $this->assertEquals(10, $product->fresh()->stock);
    }

    public function test_read_returns_products_totals_and_both_html_fragments(): void
    {
        $user = User::factory()->create();
        $product = $this->product();
        $this->cartWith($user, [[$product, 2]]);

        $response = $this->actingAs($user)->getJson(route('cart.show'))->assertOk()
            ->assertJsonPath('cart.total_count', 2)->assertJsonPath('cart.total_amount', '25.00')
            ->assertJsonPath('cart.products.0.product.product_id', $product->getKey());
        foreach (['cart_html', 'cart_page_html'] as $fragment) {
            $this->assertStringContainsString($product->name, $response->json($fragment));
            $this->assertStringContainsString('data-cart-remove="'.$product->getKey().'"', $response->json($fragment));
        }
        $this->get(route('cart.index'))->assertOk()->assertSee($product->name)->assertSee('data-cart-page', false);
    }

    public function test_add_and_set_reject_stock_overflow_without_changing_cart(): void
    {
        $user = User::factory()->create();
        $product = $this->product();
        $cart = $this->cartWith($user, [[$product, 8]]);
        $before = $cart->items()->get()->toArray();
        $this->actingAs($user);

        foreach (['cart.add' => 3, 'cart.store' => 11] as $route => $quantity) {
            $this->postJson(route($route), ['product_id' => $product->getKey(), 'quantity' => $quantity])
                ->assertUnprocessable()->assertJsonValidationErrors('quantity');
            $this->assertSame($before, $cart->items()->get()->toArray());
        }
        $this->assertEquals(10, $product->fresh()->stock);
    }

    #[DataProvider('invalidQuantities')]
    public function test_invalid_quantities_do_not_create_items(string $route, mixed $quantity): void
    {
        $product = $this->product();
        $this->actingAs(User::factory()->create())->postJson(route($route), [
            'product_id' => $product->getKey(), 'quantity' => $quantity,
        ])->assertUnprocessable()->assertJsonValidationErrors('quantity');
        $this->assertDatabaseCount('cart_items', 0);
    }

    public static function invalidQuantities(): array
    {
        return [
            'zero cannot be added' => ['cart.add', 0],
            'negative add' => ['cart.add', -1],
            'negative set' => ['cart.store', -1],
            'fractional quantity' => ['cart.store', 1.5],
            'missing quantity' => ['cart.add', null],
            'non-numeric quantity' => ['cart.store', 'invalid'],
            'integer overflow' => ['cart.add', 2147483648],
        ];
    }

    public function test_unknown_product_is_rejected(): void
    {
        $this->actingAs(User::factory()->create())->postJson(route('cart.add'), [
            'product_id' => 999999, 'quantity' => 1,
        ])->assertUnprocessable()->assertJsonValidationErrors('product_id');
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_users_can_only_read_and_change_their_own_cart(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $product = $this->product();
        $cart = $this->cartWith($owner, [[$product, 3]]);

        $this->actingAs($other)->getJson(route('cart.show'))->assertOk()->assertJsonCount(0, 'cart.products');
        $this->postJson(route('cart.store'), [
            'product_id' => $product->getKey(), 'quantity' => 0, 'user_id' => $owner->getKey(), 'cart_id' => $cart->getKey(),
        ])->assertOk();
        $this->assertEquals(3, $cart->items()->sole()->quantity);
        $this->actingAs($owner)->getJson(route('cart.show'))->assertOk()->assertJsonPath('cart.total_count', 3);
    }

    public function test_guest_cart_persists_in_the_same_session(): void
    {
        $product = $this->product();
        $this->withSession(['cart_test' => true])->postJson(route('cart.add'), [
            'product_id' => $product->getKey(), 'quantity' => 2,
        ])->assertOk()->assertJsonPath('cart.total_count', 2);
        $this->withCredentials()->withCookie(config('session.cookie'), session()->getId())
            ->getJson(route('cart.show'))->assertOk()->assertJsonPath('cart.total_count', 2);
        $this->assertDatabaseHas('carts', ['user_id' => null, 'session_id' => session()->getId()]);
    }
}
