<?php

namespace Tests\Feature\Promotions;

use App\Data\Promotions\BroadSelectGroupData;
use App\Models\Product;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BroadSelectComponentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_rendering_loaded_groups_and_products_does_not_query_and_escapes_text(): void
    {
        $product = (new Product)->forceFill([
            'product_id' => 81, 'name' => '<b>Costume</b>', 'slug' => 'costume',
            'price' => 12, 'average_rating' => 4.5, 'total_reviews' => 9,
        ])->setRelation('images', collect());
        $groups = collect([new BroadSelectGroupData('rated', 'Top rated', 'top_rated', collect([$product]))]);
        $heading = '<script>unsafe</script>';
        DB::enableQueryLog();
        DB::flushQueryLog();
        try {
            $html = Blade::render('<x-broad-select :groups="$groups" :heading="$heading" />', compact('groups', 'heading'));
            $this->assertSame([], DB::getQueryLog());
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }
        $this->assertStringContainsString('&lt;script&gt;unsafe&lt;/script&gt;', $html);
        $this->assertStringContainsString('&lt;b&gt;Costume&lt;/b&gt;', $html);
        $this->assertStringContainsString(route('products.show', [$product, 'costume']), $html);
        $this->assertStringContainsString('data-add-to-cart', $html);
        $this->assertStringContainsString('data-carousel-autoplay-speed="60"', $html);
    }

    public function test_choice_count_is_data_driven_and_repeated_components_have_independent_markup(): void
    {
        $groups = collect(['First', '<b>Second</b>', 'Third'])->map(
            fn ($label, $index) => new BroadSelectGroupData('group-'.$index, $label, 'collection', collect()),
        );
        $html = Blade::render('<x-broad-select :groups="$groups" /><x-broad-select :groups="$groups" />', compact('groups'));
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        try {
            $document->loadHTML($html);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        $xpath = new DOMXPath($document);
        $this->assertSame(2, $xpath->query('//*[@data-broad-select]')->length);
        foreach ($xpath->query('//*[@data-broad-select]') as $section) {
            $this->assertSame(3, $xpath->query('.//button[@data-broad-select-mode]', $section)->length);
            $this->assertSame(1, $xpath->query('.//button[@role="tab" and @aria-selected="true"]', $section)->length);
            $this->assertSame(3, $xpath->query('.//*[@data-broad-select-panel]', $section)->length);
            $this->assertSame(2, $xpath->query('.//*[@data-broad-select-panel and @hidden]', $section)->length);
            foreach ($xpath->query('.//button[@aria-controls]', $section) as $button) {
                $this->assertSame(1, $xpath->query('//*[@id="'.$button->getAttribute('aria-controls').'"]')->length);
            }
        }
        $this->assertSame(0, $xpath->query('//*[@id="horizontal" or @id="vertical"]')->length);
        $this->assertStringContainsString('&lt;b&gt;Second&lt;/b&gt;', $html);
    }

    public function test_empty_groups_render_no_section(): void
    {
        $this->assertSame('', trim(Blade::render('<x-broad-select :groups="collect()" />')));
    }
}
