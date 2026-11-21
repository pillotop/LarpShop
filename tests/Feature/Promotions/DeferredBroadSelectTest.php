<?php

namespace Tests\Feature\Promotions;

use App\Services\Promotions\BroadSelectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeferredBroadSelectTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_defers_the_group_query_and_renders_a_loading_shell(): void
    {
        $this->mock(BroadSelectService::class)->shouldNotReceive('forHomepage');
        $this->get(route('home'))->assertOk()
            ->assertSee('data-deferred-broad-select', false)
            ->assertSee(route('home.broad-select'), false)
            ->assertSee('product-card-skeleton-a', false)
            ->assertSee('data-deferred-retry', false);
    }

    public function test_no_public_groups_returns_no_content_without_shared_caching(): void
    {
        $response = $this->get(route('home.broad-select'))->assertNoContent();
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
    }

    public function test_fragment_keeps_source_limits_and_localization_on_the_server(): void
    {
        $this->mock(BroadSelectService::class)->shouldReceive('forHomepage')->once()->withNoArgs()->andReturn(collect());
        $this->withSession(['locale' => 'vi'])
            ->get(route('home.broad-select', ['source' => 'unsafe', 'limit' => 999]))
            ->assertNoContent();
        $this->get(route('home'))->assertSee('Đang tải sản phẩm nổi bật…');
    }
}
