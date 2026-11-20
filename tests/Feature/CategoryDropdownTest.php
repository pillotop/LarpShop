<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CategoryDropdownTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function category(int $key, string $name, ?int $parent = null): Category
    {
        return (new Category)->forceFill([
            'category_id' => $key, 'parent_id' => $parent,
            'name' => $name, 'slug' => 'category-'.$key,
        ]);
    }

    public function test_grouped_navigation_uses_custom_keys_preserves_order_and_does_not_query(): void
    {
        $categories = collect([
            $this->category(42, 'Second child', 91),
            $this->category(91, 'Equipment'),
            $this->category(7, 'First child', 91),
            $this->category(23, 'Costumes'),
        ]);
        DB::enableQueryLog();
        DB::flushQueryLog();
        try {
            $html = Blade::render('<x-category-dropdown :categories="$categories" />', compact('categories'));
            $this->assertSame([], DB::getQueryLog());
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }
        $positions = array_map(fn ($name) => strpos($html, '>'.$name.'</a>'), ['Equipment', 'Second child', 'First child', 'Costumes']);
        $this->assertNotContains(false, $positions);
        $this->assertSame($positions, collect($positions)->sort()->values()->all());
        $this->assertSame(5, preg_match_all('/<a\b/', $html));
        $this->assertStringContainsString(route('categories.show', 'category-42'), $html);
        $this->assertStringContainsString('data-dropdown-trigger="categories"', $html);
        $this->assertStringContainsString('data-dropdown-template="categories"', $html);
        $this->assertStringContainsString('category-menu-group', $html);
        $this->assertStringContainsString('category-menu-children', $html);
        $this->assertStringContainsString('arrow-btn position-absolute start-0 h-100', $html);
        $this->assertStringContainsString('arrow-btn position-absolute end-0 h-100', $html);
    }

    public function test_child_only_input_keeps_flat_fallback_and_escapes_category_names(): void
    {
        $categories = [$this->category(8, '<script>unsafe</script>', 999)];
        $html = Blade::render('<x-category-dropdown :categories="$categories" />', compact('categories'));
        $this->assertStringContainsString('&lt;script&gt;unsafe&lt;/script&gt;', $html);
        $this->assertStringNotContainsString('<script>unsafe</script>', $html);
        $this->assertStringContainsString(route('categories.show', 'category-8'), $html);
    }

    public function test_empty_input_and_translated_trigger_render_without_database_data(): void
    {
        app()->setLocale('vi');
        $html = Blade::render('<x-category-dropdown :categories="[]" />');
        $this->assertStringContainsString(__('category_menu.all'), $html);
        $this->assertStringContainsString(__('common.select_category'), $html);
        $this->assertDoesNotMatchRegularExpression('/<a\b/', $html);
        $this->assertStringContainsString(__('category_menu.empty'), $html);
    }

    public function test_repeated_dropdown_components_have_separate_scroller_targets(): void
    {
        $categories = [$this->category(9, 'Equipment')];
        $html = Blade::render('<x-category-dropdown :categories="$categories" /><x-category-dropdown :categories="$categories" />', compact('categories'));
        preg_match_all('/id="(category-list-[^"]+)"\s+class="category-menu-groups"/', $html, $matches);
        $this->assertCount(2, array_unique($matches[1]));
        foreach ($matches[1] as $id) {
            $this->assertSame(2, substr_count($html, 'aria-controls="'.$id.'"'));
        }
        $this->assertStringContainsString('tabindex="0"', $html);
    }

    public function test_scope_is_restored_from_slug_or_custom_key_without_queries(): void
    {
        $categories = [$this->category(91, 'Equipment')];
        foreach (['category-91', '91'] as $scope) {
            request()->query->set('category', $scope);
            $html = Blade::render('<x-category-dropdown :categories="$categories" />', compact('categories'));
            $this->assertMatchesRegularExpression('/name="category"\s+value="category-91"\s+data-category-scope\s*>/', $html);
            $this->assertStringContainsString('data-category-label>Equipment</span>', $html);
        }
    }

    public function test_invalid_scope_is_disabled_and_preview_content_is_escaped(): void
    {
        $category = $this->category(91, '<b>Equipment</b>');
        $category->description = '<script>alert(1)</script>';
        foreach (['unknown', ['category-91']] as $scope) {
            request()->query->set('category', $scope);
            $html = Blade::render('<x-category-dropdown :categories="[$category]" />', compact('category'));
            $this->assertMatchesRegularExpression('/name="category"\s+value=""\s+data-category-scope\s+disabled/', $html);
            $this->assertStringContainsString('data-category-description="&lt;script&gt;alert(1)&lt;/script&gt;"', $html);
            $this->assertStringContainsString('data-category-select', $html);
            $this->assertStringContainsString('data-category-preview-select', $html);
            $this->assertStringContainsString('data-category-reset', $html);
        }
    }
}
