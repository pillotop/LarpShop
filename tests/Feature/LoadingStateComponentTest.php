<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class LoadingStateComponentTest extends TestCase
{
    public function test_spinner_has_a_localized_status_and_can_start_hidden(): void
    {
        app()->setLocale('vi');
        $html = Blade::render('<x-loading-state :hidden="true" class="mt-2" />');
        $this->assertStringContainsString('Đang tải…', $html);
        $this->assertStringContainsString('role="status"', $html);
        $this->assertStringContainsString('aria-live="polite"', $html);
        $this->assertStringContainsString('hidden', $html);
        $this->assertStringContainsString('spinner-border', $html);
        $this->assertStringContainsString('mt-2', $html);
    }

    public function test_skeleton_keeps_decorative_content_hidden_and_escapes_its_label(): void
    {
        $label = '<script>Loading</script>';
        $html = Blade::render('<x-loading-state variant="skeleton" :label="$label" />', compact('label'));
        $this->assertStringContainsString('&lt;script&gt;Loading&lt;/script&gt;', $html);
        $this->assertStringContainsString('aria-hidden="true"', $html);
        $this->assertStringContainsString('placeholder-glow', $html);
        $this->assertStringNotContainsString('spinner-border', $html);
        $custom = Blade::render('<x-loading-state variant="skeleton"><span class="placeholder col-4"></span></x-loading-state>');
        $this->assertStringContainsString('placeholder col-4', $custom);
        $this->assertStringNotContainsString('col-8', $custom);
    }

    public function test_product_skeleton_variants_are_decorative_and_share_one_loading_status(): void
    {
        $html = Blade::render(<<<'BLADE'
<x-loading-state variant="skeleton">
    <x-product-card-skeleton variant="a" />
    <x-product-card-skeleton />
</x-loading-state>
BLADE);
        $document = new \DOMDocument;
        $previous = libxml_use_internal_errors(true);
        try {
            $document->loadHTML($html);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        $xpath = new \DOMXPath($document);
        $this->assertSame(1, $xpath->query('//*[@role="status"]')->length);
        $this->assertSame(2, $xpath->query('//*[contains(concat(" ", @class, " "), " product-card-skeleton ") and @aria-hidden="true"]')->length);
        $this->assertSame(3, $xpath->query('//*[contains(@class, "product-card-skeleton-circle")]')->length);
        $this->assertSame(1, $xpath->query('//*[contains(@class, "product-card-skeleton-compare")]')->length);
        $this->assertSame(0, $xpath->query('//a|//button|//input|//img')->length);
    }
}
