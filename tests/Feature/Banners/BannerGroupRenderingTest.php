<?php

namespace Tests\Feature\Banners;

use App\Data\Banners\BannerPreviewData;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class BannerGroupRenderingTest extends TestCase
{
    private function render(string $layout = 'duo', string $presentation = 'top_copy', bool $preview = false): array
    {
        $this->withoutVite();
        $group = app(BannerPreviewData::class)->group($layout, $presentation, 'dark');
        $group->cards->first()->fill([
            'title' => '<script>alert(1)</script>', 'link_url' => '/products',
            'translations' => ['vi' => ['title' => 'Khám phá bộ sưu tập', 'alt_text' => 'Ảnh bộ sưu tập']],
            'focal_x' => 12, 'focal_y' => 90,
            'image_variants' => [['path' => 'small.webp', 'width' => 480]],
        ]);
        $html = Blade::render('<x-banner-group :group="$group" :preview="$preview" :priority="true" />', compact('group', 'preview'));
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        try {
            $document->loadHTML('<?xml encoding="UTF-8">'.$html);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        return [$html, new DOMXPath($document), $group];
    }

    public function test_all_layouts_render_their_cards_without_nested_links_and_escape_copy(): void
    {
        foreach (config('banners.layouts') as $layout => $definition) {
            [$html, $xpath] = $this->render($layout);
            $this->assertSame($definition['card_count'], $xpath->query('//section//a')->length);
            $this->assertSame(0, $xpath->query('//a//a')->length);
            $this->assertSame(0, $xpath->query('//script')->length);
            $this->assertStringContainsString('&lt;script&gt;', $html);
            $this->assertSame($layout, $xpath->query('//section')->item(0)->getAttribute('data-banner-layout'));
        }
    }

    public function test_priority_responsive_metadata_localization_and_focal_point_are_preserved(): void
    {
        app()->setLocale('vi');
        [$html, $xpath] = $this->render('mosaic_5', 'bottom_caption');
        $images = $xpath->query('//img');
        $this->assertSame('eager', $images->item(0)->getAttribute('loading'));
        $this->assertSame('high', $images->item(0)->getAttribute('fetchpriority'));
        $this->assertSame('lazy', $images->item(1)->getAttribute('loading'));
        $this->assertSame('auto', $images->item(1)->getAttribute('fetchpriority'));
        $this->assertStringContainsString('480w', $images->item(0)->getAttribute('srcset'));
        $this->assertStringContainsString('1920w', $images->item(0)->getAttribute('srcset'));
        $this->assertSame('Ảnh bộ sưu tập', $images->item(0)->getAttribute('alt'));
        $this->assertStringContainsString('Khám phá bộ sưu tập', $html);
        $this->assertStringContainsString('--banner-focal-x: 12%', $html);
        $this->assertStringContainsString('--banner-focal-y: 90%', $html);
        $this->assertSame(5, $xpath->query('//*[@data-presentation="bottom_caption"]')->length);
    }

    public function test_preview_and_unsafe_destinations_render_without_clickable_links(): void
    {
        [, $xpath] = $this->render(preview: true);
        $this->assertSame(0, $xpath->query('//a')->length);
        $this->assertSame(2, $xpath->query('//article')->length);
        $group = app(BannerPreviewData::class)->group('duo', 'top_copy', 'dark');
        foreach ($group->cards as $card) {
            $card->link_url = 'javascript:alert(1)';
        }
        $html = Blade::render('<x-banner-group :group="$group" />', compact('group'));
        $this->assertStringNotContainsString('href=', $html);
        $this->assertStringNotContainsString('javascript:', $html);
    }

    public function test_incomplete_and_unknown_layouts_do_not_render(): void
    {
        $this->withoutVite();
        $group = app(BannerPreviewData::class)->group('duo', 'top_copy', 'dark');
        $group->cards->first()->is_active = false;
        $this->assertSame('', trim(Blade::render('<x-banner-group :group="$group" />', compact('group'))));
        $group->layout = 'arbitrary-view';
        $this->assertSame('', trim(Blade::render('<x-banner-group :group="$group" />', compact('group'))));
    }
}
