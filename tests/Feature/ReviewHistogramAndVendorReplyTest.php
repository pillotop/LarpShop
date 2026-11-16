<?php

namespace Tests\Feature;

use App\Models\ProductReview;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesCartFixtures;
use Tests\TestCase;

class ReviewHistogramAndVendorReplyTest extends TestCase
{
    use CreatesCartFixtures, RefreshDatabase;

    public function test_product_detail_renders_rating_histogram_and_filters_by_star_and_comment(): void
    {
        $this->withoutVite();

        $product = $this->product(name: 'Starlit Buckler');
        $u1 = User::factory()->create();
        $u2 = User::factory()->create();
        $u3 = User::factory()->create();

        ProductReview::create([
            'product_id' => $product->getKey(),
            'user_id' => $u1->getKey(),
            'rating' => 5,
            'comment' => 'Brilliant craftsmanship!',
        ]);
        ProductReview::create([
            'product_id' => $product->getKey(),
            'user_id' => $u2->getKey(),
            'rating' => 2,
            'comment' => 'Strap felt loose.',
        ]);
        ProductReview::create([
            'product_id' => $product->getKey(),
            'user_id' => $u3->getKey(),
            'rating' => 5,
            'comment' => null,
        ]);

        $this->get(route('products.show', [$product, $product->slug]))
            ->assertOk()
            ->assertSee('data-review-histogram', false)
            ->assertSee('5 ★ (2)')
            ->assertSee('2 ★ (1)')
            ->assertSee('Brilliant craftsmanship!')
            ->assertSee('Strap felt loose.');

        $this->get(route('reviews.index', [$product, 'reviews_rating' => 2]))
            ->assertOk()
            ->assertSee('Strap felt loose.')
            ->assertDontSee('Brilliant craftsmanship!');

        $this->get(route('reviews.index', [$product, 'reviews_commented' => 1]))
            ->assertOk()
            ->assertSee('Brilliant craftsmanship!')
            ->assertSee('Strap felt loose.')
            ->assertDontSee('without a comment');
    }

    public function test_only_owning_vendor_can_reply_to_product_review(): void
    {
        $this->withoutVite();

        $product = $this->product(name: 'Iron Greaves');
        $ownerUser = $product->vendor->user;

        $otherVendorUser = User::factory()->create();
        Vendor::create([
            'user_id' => $otherVendorUser->getKey(),
            'store_name' => 'Rival Forge',
            'status' => 'approved',
        ]);

        $customer = User::factory()->create();
        $review = ProductReview::create([
            'product_id' => $product->getKey(),
            'user_id' => $customer->getKey(),
            'rating' => 4,
            'comment' => 'Solid steel!',
        ]);

        // Another vendor cannot reply.
        $this->actingAs($otherVendorUser)
            ->post(route('vendor.reviews.reply', $review), [
                'vendor_reply' => 'Unauthorized reply',
            ])->assertForbidden();

        // Product owner vendor can reply.
        $this->actingAs($ownerUser)
            ->from(route('products.show', [$product, $product->slug]))
            ->post(route('vendor.reviews.reply', $review), [
                'vendor_reply' => 'Thank you for supporting our forge!',
            ])->assertRedirect(route('products.show', [$product, $product->slug]).'#product-reviews');

        $review->refresh();
        $this->assertSame('Thank you for supporting our forge!', $review->vendor_reply);
        $this->assertNotNull($review->vendor_replied_at);

        $this->get(route('products.show', [$product, $product->slug]))
            ->assertOk()
            ->assertSee('Seller response')
            ->assertSee('Thank you for supporting our forge!');
    }
}
