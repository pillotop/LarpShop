<?php

namespace Tests\Feature;

use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Tests\Support\CreatesCartFixtures;
use Tests\TestCase;

class CatalogLocalizationTest extends TestCase
{
    use CreatesCartFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        config(['app.locale' => 'en', 'app.fallback_locale' => 'en']);
    }

    public function test_vietnamese_catalog_keeps_filter_values_and_marketplace_data(): void
    {
        $product = $this->product(name: 'Vendor Authored Sword', price: '123.45');
        $product->update(['description' => 'Original vendor description.']);

        $this->withSession(['locale' => 'vi'])
            ->get(route('products.index', ['q' => 'Sword', 'sort' => 'price_asc']))
            ->assertOk()
            ->assertSee('Kết quả tìm kiếm')
            ->assertSee('Giá: thấp đến cao')
            ->assertSee('Tìm thấy 1 sản phẩm')
            ->assertSee('Vendor Authored Sword')
            ->assertSee('Equipment')
            ->assertSee('value="price_asc"', false)
            ->assertSee('name="sort"', false);

        $this->get(route('products.show', [$product, $product->slug]))
            ->assertOk()
            ->assertSee('<html lang="vi">', false)
            ->assertSee('Thêm vào giỏ hàng')
            ->assertSee('Sản phẩm tương tự')
            ->assertSee('Chưa có đánh giá.')
            ->assertSee('Original vendor description.')
            ->assertSee('123.45');

        $this->assertSame('123.45', $product->fresh()->price);
        $this->assertSame('Vendor Authored Sword', $product->fresh()->name);
    }

    public function test_home_category_and_empty_search_are_translated(): void
    {
        $product = $this->product();

        $this->withSession(['locale' => 'vi'])->get(route('home'))
            ->assertOk()
            ->assertSee('Mọi thứ tại một nơi')
            ->assertSee('Khám phá bộ sưu tập');

        $this->get(route('categories.show', $product->category->slug))
            ->assertOk()
            ->assertSee('Sản phẩm cùng loại')
            ->assertSee('Equipment')
            ->assertSee('Trang chủ');

        $this->get(route('products.index', ['q' => 'no-matching-product']))
            ->assertOk()
            ->assertSee('Không tìm thấy sản phẩm')
            ->assertSee('Tìm thấy 0 sản phẩm');
    }

    public function test_ajax_review_pages_receive_translated_labels_and_messages(): void
    {
        $product = $this->product();
        $customer = User::factory()->create(['username' => 'Original reviewer']);

        for ($index = 0; $index < 6; $index++) {
            ProductReview::forceCreate([
                'product_id' => $product->getKey(),
                'user_id' => $customer->getKey(),
                'rating' => 5,
                'comment' => 'Original customer comment.',
            ]);
        }

        $this->withSession(['locale' => 'vi'])
            ->get(route('reviews.index', $product))
            ->assertOk()
            ->assertSee('Original reviewer')
            ->assertSee('Original customer comment.')
            ->assertSee('Xem thêm đánh giá')
            ->assertSee('data-loading-message="Đang tải đánh giá…"', false)
            ->assertSee('data-error-message="Không thể tải đánh giá. Vui lòng thử lại."', false)
            ->assertDontSee('Show more reviews');

        $this->get(route('reviews.index', [$product, 'reviews_page' => 2]))
            ->assertOk()
            ->assertSee('Original customer comment.')
            ->assertDontSee('data-more-reviews', false);
    }

    public function test_localized_views_still_escape_vendor_content(): void
    {
        $name = '<script>alert("catalog")</script>';
        $product = $this->product(name: $name);

        $this->withSession(['locale' => 'vi'])
            ->get(route('products.show', [$product, $product->slug]))
            ->assertOk()
            ->assertSee($name)
            ->assertDontSee($name, false);
    }

    public function test_missing_vietnamese_key_falls_back_to_english(): void
    {
        app()->setLocale('vi');
        app('translator')->addLines(['localization_probe.message' => 'English fallback'], 'en');

        $this->assertSame('English fallback', __('localization_probe.message'));
        $this->assertSame('1 product found', trans_choice('products.search.found', 1, ['count' => 1], 'en'));
        $this->assertSame('2 products found', trans_choice('products.search.found', 2, ['count' => 2], 'en'));
    }

    public function test_catalog_translation_keys_match_between_languages(): void
    {
        foreach (['products', 'reviews', 'wishlist'] as $domain) {
            $english = require lang_path("en/{$domain}.php");
            $vietnamese = require lang_path("vi/{$domain}.php");

            $this->assertSame(
                array_keys(Arr::dot($english)),
                array_keys(Arr::dot($vietnamese)),
                "Translation keys differ for {$domain}.",
            );
        }
    }
}
