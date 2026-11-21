<?php

namespace Tests\Feature;

use App\Models\ProductCollection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Vite;
use Tests\Support\CreatesCartFixtures;
use Tests\TestCase;

class InitialStylesheetPlacementTest extends TestCase
{
    use CreatesCartFixtures, RefreshDatabase;

    private function assertStylesInHead(string $html, array $assets): void
    {
        [$head, $body] = explode('</head>', $html, 2);
        $this->assertDoesNotMatchRegularExpression('/<link\b[^>]*rel=["\']stylesheet["\']/i', $body);
        foreach ($assets as $asset) {
            $url = app(Vite::class)->asset($asset);
            $this->assertStringContainsString('href="'.$url.'"', $head);
        }
    }

    public function test_catalog_pages_deliver_navigation_styles_before_the_body(): void
    {
        $product = $this->product();
        foreach ([route('home'), route('products.index'), route('products.show', [$product, $product->slug])] as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            $this->assertStylesInHead($html, [
                'resources/css/top_header.css',
                'resources/css/components/navigation_bar.css',
                'resources/css/components/two_arrow_scroller.css',
            ]);
        }
    }

    public function test_storefront_component_styles_are_in_head_and_only_registered_once(): void
    {
        $product = $this->product();
        $html = $this->actingAs($product->vendor->user)->get(route('vendors.show', $product->vendor))->assertOk()->getContent();
        $this->assertStylesInHead($html, [
            'resources/css/components/profile_container.css',
            'resources/css/components/image_cropper.css',
        ]);
        $url = app(Vite::class)->asset('resources/css/components/profile_container.css');
        $this->assertSame(1, substr_count($html, 'rel="stylesheet" href="'.$url.'"'));
    }

    public function test_admin_row_preview_keeps_styles_in_head_and_scripts_at_the_end(): void
    {
        $collection = ProductCollection::factory()->create();
        $collection->products()->attach($this->product());
        $html = $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.promotions.preview', ['collection' => $collection->getKey(), 'layout' => 'row']))
            ->assertOk()->getContent();
        $this->assertStylesInHead($html, ['resources/css/components/two_arrow_scroller.css']);
        $script = app(Vite::class)->asset('resources/js/components/two_arrow_scroller.js');
        $this->assertGreaterThan(strpos($html, '</main>'), strpos($html, 'src="'.$script.'"'));
    }
}
