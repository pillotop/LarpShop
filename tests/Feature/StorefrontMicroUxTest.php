<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesCartFixtures;
use Tests\TestCase;

class StorefrontMicroUxTest extends TestCase
{
    use CreatesCartFixtures, RefreshDatabase;

    public function test_wishlist_endpoints_return_json_for_ajax_requests(): void
    {
        $user = User::factory()->create();
        $product = $this->product();

        $this->actingAs($user)
            ->postJson(route('wishlist.store', $product))
            ->assertOk()
            ->assertJson([
                'success' => true,
                'saved' => true,
                'action_url' => route('wishlist.destroy', $product),
            ]);

        $this->assertDatabaseHas('wishlist_items', [
            'user_id' => $user->getKey(),
            'product_id' => $product->getKey(),
        ]);

        $this->actingAs($user)
            ->deleteJson(route('wishlist.destroy', $product))
            ->assertOk()
            ->assertJson([
                'success' => true,
                'saved' => false,
                'action_url' => route('wishlist.store', $product),
            ]);

        $this->assertDatabaseCount('wishlist_items', 0);
    }

    public function test_product_search_suggestions_returns_matching_approved_active_products(): void
    {
        $matching = $this->product(name: 'Obsidian Longsword', price: '149.00');
        $hidden = $this->product(name: 'Obsidian Pending Dagger');
        $hidden->forceFill(['status' => Product::STATUS_PENDING])->save();
        $this->product(name: 'Leather Boots');

        $this->getJson(route('products.suggestions', ['q' => 'Obsidian']))
            ->assertOk()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.id', $matching->getKey())
            ->assertJsonPath('items.0.name', 'Obsidian Longsword')
            ->assertJsonPath('items.0.price', '$149.00');

        $this->getJson(route('products.suggestions', ['q' => 'O']))
            ->assertOk()
            ->assertJsonCount(0, 'items');
    }

    public function test_product_search_is_paginated_and_renders_quick_sort_and_filter_chips(): void
    {
        for ($i = 1; $i <= 14; $i++) {
            $this->product(name: sprintf('Tactical Item %02d', $i));
        }

        $this->get(route('products.index', [
            'q' => 'Tactical',
            'in_stock' => '1',
            'min_price' => '1',
            'sort' => 'price_asc',
        ]))
            ->assertOk()
            ->assertSee('14 products found')
            ->assertSee('page=2', false)
            ->assertSee('Active filters:')
            ->assertSee('In stock only');
    }

    public function test_product_detail_and_homepage_render_recently_viewed_and_ux_messages(): void
    {
        $product = $this->product(name: 'Steel Buckler', price: '79.50');

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('id="recently-viewed-section"', false)
            ->assertSee('id="storefront-ux-messages"', false);

        $this->get(route('products.show', [$product, $product->slug]))
            ->assertOk()
            ->assertSee('id="recently-viewed-product"', false)
            ->assertSee('id="recently-viewed-section"', false)
            ->assertSee('Steel Buckler');
    }
}
