<?php

namespace Tests\Feature\Banners;

use App\Services\Banners\BannerGroupRules;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BannerGroupRulesTest extends TestCase
{
    private function payload(): array
    {
        $card = [
            'title' => 'Explore', 'alt_text' => 'Products', 'link_url' => '/products?q=home',
            'presentation' => 'top_copy', 'text_tone' => 'dark', 'focal_x' => 50,
            'focal_y' => 50, 'is_active' => true, 'sort_order' => 0,
        ];

        return [
            'slug' => 'home-picks', 'name' => 'Home picks', 'layout' => 'duo', 'is_active' => true,
            'cards' => [$card, array_merge($card, ['sort_order' => 1])],
            'placements' => ['homepage_main'],
        ];
    }

    private function validator(array $data): \Illuminate\Validation\Validator
    {
        return Validator::make($data, app(BannerGroupRules::class)->rules($data));
    }

    public function test_valid_payload_and_empty_draft_are_accepted(): void
    {
        $data = $this->payload();
        $data['cards'][0]['translations'] = ['vi' => ['title' => 'Khám phá']];
        $this->assertFalse($this->validator($data)->fails());
        $data['is_active'] = false;
        $data['cards'] = [];
        $this->assertFalse($this->validator($data)->fails());
    }

    public function test_invalid_layout_schedule_card_count_focus_and_metadata_are_rejected(): void
    {
        $data = $this->payload();
        $data['layout'] = 'user/template';
        $data['starts_at'] = '2026-11-22 12:00:00';
        $data['expires_at'] = '2026-11-22 12:00:00';
        $data['cards'][0]['focal_x'] = 101;
        $data['cards'][0]['image_path'] = 'untrusted.webp';
        $errors = $this->validator($data)->errors();
        foreach (['layout', 'expires_at', 'cards.0.focal_x', 'cards.0'] as $field) {
            $this->assertTrue($errors->has($field), $field);
        }
        $data = $this->payload();
        array_pop($data['cards']);
        $this->assertTrue($this->validator($data)->errors()->has('cards'));
        $data = $this->payload();
        $data['cards'][0]['is_active'] = false;
        $data['cards'][0]['sort_order'] = 1;
        $this->assertTrue($this->validator($data)->errors()->has('cards.0.is_active'));
        $this->assertTrue($this->validator($data)->errors()->has('cards.0.sort_order'));
    }

    #[DataProvider('links')]
    public function test_destination_allowlist(string $url, bool $valid): void
    {
        $data = $this->payload();
        $data['cards'][0]['link_url'] = $url;
        $this->assertSame(! $valid, $this->validator($data)->errors()->has('cards.0.link_url'));
    }

    public static function links(): array
    {
        return [
            ['/products', true], ['https://example.test/products', true],
            ['javascript:alert(1)', false], ['//example.test', false],
            ['/\\example.test', false], ["/\n/example.test", false],
            ['https://user:password@example.test', false], ['data:text/html,test', false],
        ];
    }
}
