<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CreatesCartFixtures;
use Tests\TestCase;

class ProductReviewTest extends TestCase
{
    use CreatesCartFixtures, RefreshDatabase;

    private function purchase(User $user, Product $product, OrderStatus $status = OrderStatus::Paid): Order
    {
        $order = $user->orders()->create(['status' => $status, 'total_amount' => $product->price]);
        $order->details()->create([
            'product_id' => $product->getKey(),
            'quantity' => 1,
            'unit_price' => $product->price,
        ]);

        return $order;
    }

    private function payload(array $overrides = []): array
    {
        return array_replace([
            'rating' => 5,
            'comment' => 'A well-made sword.',
            'review_form' => 'review-form-product-1',
        ], $overrides);
    }

    public function test_paid_customer_can_review_and_client_cannot_override_author_or_product(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $product = $this->product();
        $otherProduct = $this->product(name: 'Other product');
        $this->purchase($user, $product);

        $this->actingAs($user)->from(route('orders.index'))
            ->post(route('reviews.store', $product), $this->payload([
                'user_id' => $other->getKey(),
                'product_id' => $otherProduct->getKey(),
            ]))->assertRedirect(route('orders.index').'#review-feedback');

        $this->assertDatabaseHas('product_reviews', [
            'user_id' => $user->getKey(), 'product_id' => $product->getKey(), 'rating' => 5,
        ]);
        $this->assertDatabaseCount('product_reviews', 1);
        $this->get(route('products.show', [$product, $product->slug]))->assertOk()
            ->assertSee('Verified purchase')->assertSee('A well-made sword.')
            ->assertDontSee('Write a review');
    }

    public function test_guests_and_non_buyers_cannot_review_even_when_another_customer_bought_it(): void
    {
        $product = $this->product();
        $this->purchase(User::factory()->create(), $product);
        $this->post(route('reviews.store', $product), $this->payload())
            ->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())
            ->post(route('reviews.store', $product), $this->payload())
            ->assertSessionHasErrors('eligibility', null, 'review');
        $this->assertDatabaseCount('product_reviews', 0);
    }

    #[DataProvider('unpaidStatuses')]
    public function test_unpaid_orders_do_not_grant_review_access(OrderStatus $status): void
    {
        $user = User::factory()->create();
        $product = $this->product();
        $this->purchase($user, $product, $status);
        $this->actingAs($user)->post(route('reviews.store', $product), $this->payload())
            ->assertSessionHasErrors('eligibility', null, 'review');
        $this->get(route('orders.index'))->assertOk()->assertDontSee('Write a review');
        $this->assertDatabaseCount('product_reviews', 0);
    }

    public static function unpaidStatuses(): array
    {
        return [[OrderStatus::Pending], [OrderStatus::Cancelled], [OrderStatus::Expired]];
    }

    public function test_repeated_purchases_and_submissions_still_allow_only_one_review(): void
    {
        $user = User::factory()->create();
        $product = $this->product();
        $this->purchase($user, $product);
        $this->purchase($user, $product);
        $this->actingAs($user)->post(route('reviews.store', $product), $this->payload())->assertRedirect();
        $this->post(route('reviews.store', $product), $this->payload(['rating' => 1]))
            ->assertSessionHasErrors('eligibility', null, 'review');
        $this->assertDatabaseCount('product_reviews', 1);
        $this->assertDatabaseHas('product_reviews', ['rating' => 5]);
    }

    public function test_both_pages_offer_the_shared_form_and_restore_only_the_failed_form(): void
    {
        $user = User::factory()->create();
        $product = $this->product();
        $order = $this->purchase($user, $product);
        $formId = 'review-form-order-'.$order->getKey().'-detail-'.$order->details()->first()->getKey();
        $this->actingAs($user)->get(route('products.show', [$product, $product->slug]))
            ->assertOk()->assertSee('Write a review');
        $this->get(route('orders.index'))->assertOk()->assertSee('Write a review');
        $this->from(route('orders.index'))->post(route('reviews.store', $product), $this->payload([
            'review_form' => $formId, 'rating' => 7, 'comment' => 'Keep my draft',
        ]))->assertSessionHasErrors('rating', null, 'review')
            ->assertRedirect(route('orders.index').'#'.$formId);
        $this->get(route('orders.index'))->assertOk()->assertSee('Keep my draft');
        $this->assertDatabaseCount('product_reviews', 0);
    }

    #[DataProvider('invalidReviews')]
    public function test_invalid_review_input_is_rejected(array $input, string $field): void
    {
        $user = User::factory()->create();
        $product = $this->product();
        $this->purchase($user, $product);
        $this->actingAs($user)->post(route('reviews.store', $product), $this->payload($input))
            ->assertSessionHasErrors($field, null, 'review');
        $this->assertDatabaseCount('product_reviews', 0);
    }

    public static function invalidReviews(): array
    {
        return [
            [['rating' => 0], 'rating'],
            [['rating' => 6], 'rating'],
            [['rating' => 2.5], 'rating'],
            [['rating' => null], 'rating'],
            [['comment' => str_repeat('x', 2001)], 'comment'],
            [['comment' => ['bad']], 'comment'],
        ];
    }

    public function test_public_reviews_are_paginated_escaped_and_do_not_claim_unverified_purchases(): void
    {
        $product = $this->product();
        for ($index = 0; $index < 6; $index++) {
            ProductReview::forceCreate([
                'product_id' => $product->getKey(),
                'user_id' => User::factory()->create()->getKey(),
                'rating' => 4,
                'comment' => '<script>review-'.$index.'</script>',
            ]);
        }

        $this->get(route('products.show', [$product, $product->slug]))->assertOk()
            ->assertSee('Show more reviews')->assertSee('<script>review-5</script>')
            ->assertDontSee('<script>review-5</script>', false)
            ->assertDontSee('Verified purchase');
        $this->get(route('reviews.index', [$product, 'reviews_page' => 2]))->assertOk()
            ->assertSee('<script>review-0</script>')
            ->assertDontSee('Show more reviews');
        $this->getJson(route('reviews.index', [$product, 'reviews_page' => 'bad']))
            ->assertUnprocessable();
    }

    public function test_hidden_and_missing_products_cannot_receive_reviews(): void
    {
        $user = User::factory()->create();
        $product = $this->product();
        $this->purchase($user, $product);
        $product->forceFill(['status' => Product::STATUS_REJECTED])->save();
        $this->actingAs($user)->post(route('reviews.store', $product), $this->payload())->assertNotFound();
        $this->post(route('reviews.store', 999999), $this->payload())->assertNotFound();
        $this->get(route('reviews.index', $product))->assertNotFound();
        $this->assertDatabaseCount('product_reviews', 0);
    }
}
