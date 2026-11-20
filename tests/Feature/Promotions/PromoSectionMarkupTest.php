<?php

namespace Tests\Feature\Promotions;

use App\Models\ProductCollection;
use App\Queries\Promotions\PromoSectionQuery;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\Support\CreatesCartFixtures;
use Tests\TestCase;

class PromoSectionMarkupTest extends TestCase
{
    use CreatesCartFixtures, RefreshDatabase;

    public function test_repeated_sections_have_named_landmarks_unique_controls_and_lazy_responsive_artwork(): void
    {
        $this->withoutVite();
        $collection = ProductCollection::factory()->published()->create(['name' => 'Seasonal collection']);
        $collection->products()->attach($this->product());
        foreach (['row', 'split', 'row'] as $index => $layout) {
            $collection->sections()->create([
                'heading' => 'Promotion '.$index, 'layout' => $layout, 'is_active' => true,
                'image_path' => 'promotions/art.webp', 'image_width' => 960, 'image_height' => 1200,
                'alt_text' => 'Seasonal artwork',
                'image_variants' => [['path' => 'promotions/art-384.webp', 'width' => 384, 'height' => 480]],
            ]);
        }
        $prepared = app(PromoSectionQuery::class)->visibleForPlacement('homepage_main');
        $html = Blade::render('@foreach ($prepared as $data)<x-promo-section :data="$data" />@endforeach', compact('prepared'));
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        try {
            $document->loadHTML($html);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        $xpath = new DOMXPath($document);
        $ids = [];
        foreach ($xpath->query('//*[@id]') as $element) {
            $this->assertNotContains($element->getAttribute('id'), $ids);
            $ids[] = $element->getAttribute('id');
        }
        $this->assertStringContainsString(asset('images/product-placeholder.svg'), $html);
        $this->assertFileExists(public_path('images/product-placeholder.svg'));
        $this->assertSame(3, $xpath->query('//section[@aria-labelledby]')->length);
        foreach ($xpath->query('//section[@aria-labelledby]') as $section) {
            $this->assertNotNull($document->getElementById($section->getAttribute('aria-labelledby')));
        }
        $this->assertSame(4, $xpath->query('//button[@aria-controls]')->length);
        foreach ($xpath->query('//button[@aria-controls]') as $button) {
            $target = $document->getElementById($button->getAttribute('aria-controls'));
            $this->assertNotNull($target);
            $this->assertSame('ul', $target->nodeName);
            $this->assertSame('0', $target->getAttribute('tabindex'));
            $this->assertNotEmpty($button->getAttribute('aria-label'));
        }
        $artwork = $xpath->query('//img[@alt="Seasonal artwork"]')->item(0);
        $this->assertNotNull($artwork);
        $this->assertSame('lazy', $artwork->getAttribute('loading'));
        $this->assertSame('auto', $artwork->getAttribute('fetchpriority'));
        $this->assertSame('960', $artwork->getAttribute('width'));
        $this->assertSame('1200', $artwork->getAttribute('height'));
        $this->assertStringContainsString('384w', $artwork->getAttribute('srcset'));
        $this->assertStringContainsString('960w', $artwork->getAttribute('srcset'));
    }
}
