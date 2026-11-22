<?php

namespace Tests\Feature\Banners;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BannerGroupPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admins_can_access_the_preview(): void
    {
        $url = route('admin.banner-groups.preview');
        $this->get($url)->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create(['role' => 'customer']))->get($url)->assertForbidden();
    }

    public function test_preview_uses_sample_data_without_writing_banner_records(): void
    {
        $this->withoutVite();
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.banner-groups.preview', ['layout' => 'mosaic_5', 'presentation' => 'top_copy', 'width' => 'mobile']))
            ->assertOk()->assertSee('data-banner-layout="mosaic_5"', false)
            ->assertSee('data-width="mobile"', false)->assertSee(__('banner_groups.readonly'));
        $this->assertDatabaseCount('banner_groups', 0);
        $this->assertDatabaseCount('banner_cards', 0);
        $this->assertDatabaseCount('banner_group_placements', 0);
    }

    public function test_preview_rejects_unknown_layouts_and_unexpected_option_types(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->getJson(route('admin.banner-groups.preview', ['layout' => '../template', 'presentation' => ['top_copy'], 'tone' => 'red', 'width' => 'huge']))
            ->assertUnprocessable()->assertJsonValidationErrors(['layout', 'presentation', 'tone', 'width']);
    }
}
