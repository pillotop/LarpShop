<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesCartFixtures;
use Tests\TestCase;

class WishlistTest extends TestCase
{
    use CreatesCartFixtures, RefreshDatabase;

    public function test_guests_cannot_add_or_remove_products(): void
    {
        $product = $this->product();

        $this->post(route('wishlist.store', $product))->assertRedirect(route('login'));
        $this->delete(route('wishlist.destroy', $product))->assertRedirect(route('login'));
        $this->assertDatabaseCount('wishlist_items', 0);
    }

    public function test_repeated_adds_save_once_and_ignore_client_user_id(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $product = $this->product(stock: 0);

        $this->actingAs($user);
        for ($attempt = 0; $attempt < 2; $attempt++) {
            $this->from(route('profile'))->post(route('wishlist.store', $product), [
                'user_id' => $other->getKey(),
            ])->assertRedirect(route('profile'));
        }

        $this->assertDatabaseCount('wishlist_items', 1);
        $this->assertDatabaseHas('wishlist_items', [
            'user_id' => $user->getKey(),
            'product_id' => $product->getKey(),
        ]);
        $this->assertDatabaseCount('cart_items', 0);
        $this->assertSame(0, (int) $product->fresh()->stock);
    }

    public function test_removing_a_product_only_changes_the_signed_in_users_wishlist(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $product = $this->product();
        $user->wishlistProducts()->attach($product);
        $other->wishlistProducts()->attach($product);

        $this->actingAs($user);
        for ($attempt = 0; $attempt < 2; $attempt++) {
            $this->delete(route('wishlist.destroy', $product))->assertRedirect();
        }

        $this->assertDatabaseCount('wishlist_items', 1);
        $this->assertDatabaseHas('wishlist_items', ['user_id' => $other->getKey()]);
    }

    public function test_profile_displays_only_owned_approved_products_and_escapes_names(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $saved = $this->product(name: '<script>saved</script>');
        $private = $this->product(name: 'Other wishlist product');
        $hidden = $this->product(name: 'Rejected product');
        $hidden->forceFill(['status' => Product::STATUS_REJECTED])->save();
        $user->wishlistProducts()->attach([$saved->getKey(), $hidden->getKey()]);
        $other->wishlistProducts()->attach($private);

        $this->actingAs($user)->get(route('profile'))->assertOk()
            ->assertSee('id="pane-wishlist"', false)
            ->assertSee($saved->name)
            ->assertDontSee($saved->name, false)
            ->assertSee('Remove from wishlist')
            ->assertSee('product-placeholder.jpg')
            ->assertDontSee($private->name)
            ->assertDontSee($hidden->name);
    }

    public function test_unknown_and_unapproved_products_cannot_be_saved(): void
    {
        $product = $this->product();
        $product->forceFill(['status' => Product::STATUS_PENDING])->save();
        $this->actingAs(User::factory()->create());

        $this->post(route('wishlist.store', $product))->assertNotFound();
        $this->post(route('wishlist.store', 999999))->assertNotFound();
        $this->assertDatabaseCount('wishlist_items', 0);
    }

    public function test_wishlist_is_paginated_and_links_restore_the_tab(): void
    {
        $user = User::factory()->create();
        for ($index = 0; $index < 13; $index++) {
            $user->wishlistProducts()->attach($this->product(name: 'Saved '.$index));
        }

        $this->actingAs($user)->get(route('profile'))->assertOk()
            ->assertViewHas('wishlistProducts', fn ($products) => $products->count() === 12)
            ->assertSee('wishlist_page=2#pane-wishlist', false);
        $this->get(route('profile', ['wishlist_page' => 2]))->assertOk()
            ->assertViewHas('wishlistProducts', fn ($products) => $products->count() === 1);
    }

    public function test_empty_wishlist_and_product_detail_button_render(): void
    {
        $user = User::factory()->create();
        $product = $this->product();
        $this->actingAs($user)->get(route('profile'))->assertOk()
            ->assertSee('No saved products are available.');
        $this->get(route('products.show', [$product, $product->slug]))->assertOk()
            ->assertSee(route('wishlist.store', $product), false);
    }
}
