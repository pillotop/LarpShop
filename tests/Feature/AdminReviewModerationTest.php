<?php

namespace Tests\Feature;

use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminReviewModerationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function makeReview(string $productName, int $rating, string $comment, ?User $author = null): ProductReview
    {
        $owner = User::factory()->create();
        $vendorId = DB::table('vendors')->insertGetId([
            'user_id' => $owner->user_id, 'store_name' => 'Store '.uniqid(), 'status' => 'approved',
            'created_at' => now(), 'updated_at' => now(),
        ], 'vendor_id');
        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Cat '.uniqid(), 'slug' => 'cat-'.uniqid(), 'created_at' => now(), 'updated_at' => now(),
        ], 'category_id');
        $productId = DB::table('products')->insertGetId([
            'category_id' => $categoryId, 'vendor_id' => $vendorId, 'name' => $productName,
            'slug' => 'p-'.uniqid(), 'price' => 10, 'stock' => 5, 'created_at' => now(), 'updated_at' => now(),
        ], 'product_id');

        $author ??= User::factory()->create();
        $reviewId = DB::table('product_reviews')->insertGetId([
            'product_id' => $productId, 'user_id' => $author->user_id, 'rating' => $rating,
            'comment' => $comment, 'created_at' => now(), 'updated_at' => now(),
        ], 'review_id');

        return ProductReview::findOrFail($reviewId);
    }

    public function test_non_admins_cannot_access_review_moderation(): void
    {
        $review = $this->makeReview('Sword', 1, 'Spam link');

        $this->get('/admin/reviews')->assertRedirect('/login');

        $customer = User::factory()->create(['role' => 'customer']);
        $this->actingAs($customer)->get('/admin/reviews')->assertForbidden();
        $this->actingAs($customer)->delete("/admin/reviews/{$review->review_id}")->assertForbidden();

        $this->assertDatabaseHas('product_reviews', ['review_id' => $review->review_id]);
    }

    public function test_admin_can_list_reviews_with_author_and_product(): void
    {
        $author = User::factory()->create(['username' => 'angryreviewer']);
        $this->makeReview('Iron Sword', 1, 'Terrible scam', $author);

        $this->actingAs($this->admin())->get('/admin/reviews')
            ->assertOk()->assertSee('angryreviewer')->assertSee('Iron Sword')->assertSee('Terrible scam');
    }

    public function test_admin_can_filter_by_rating_and_search(): void
    {
        $this->makeReview('Item One', 5, 'Great stuff');
        $this->makeReview('Item Two', 1, 'Buy cheap pills here');

        $admin = $this->admin();

        $this->actingAs($admin)->get('/admin/reviews?rating=1')
            ->assertOk()->assertSee('Buy cheap pills here')->assertDontSee('Great stuff');

        $this->actingAs($admin)->get('/admin/reviews?q=Great')
            ->assertOk()->assertSee('Great stuff')->assertDontSee('Buy cheap pills here');

        $this->actingAs($admin)->get('/admin/reviews?q=Item Two')
            ->assertOk()->assertSee('Buy cheap pills here')->assertDontSee('Great stuff');
    }

    public function test_invalid_rating_filter_is_rejected(): void
    {
        $this->actingAs($this->admin())->get('/admin/reviews?rating=9')->assertSessionHasErrors('rating');
    }

    public function test_review_comment_is_escaped(): void
    {
        $this->makeReview('Sword', 3, '<script>alert(1)</script>');

        $this->actingAs($this->admin())->get('/admin/reviews')
            ->assertOk()->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_admin_can_delete_review(): void
    {
        $review = $this->makeReview('Sword', 1, 'Fraudulent review');

        $this->actingAs($this->admin())->delete("/admin/reviews/{$review->review_id}")->assertSessionHas('success');

        $this->assertDatabaseMissing('product_reviews', ['review_id' => $review->review_id]);
    }

    public function test_deleting_unknown_review_returns_404(): void
    {
        $this->actingAs($this->admin())->delete('/admin/reviews/99999')->assertNotFound();
    }
}
