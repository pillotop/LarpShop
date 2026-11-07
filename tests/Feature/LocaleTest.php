<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.locale' => 'en',
            'app.fallback_locale' => 'en',
            'app.supported_locales' => ['en' => 'English', 'vi' => 'Tiếng Việt'],
        ]);

        Route::middleware('web')->get('/_test/locale', fn () => response()->json([
            'locale' => app()->getLocale(),
            'translation_locale' => app('translator')->getLocale(),
        ]));
    }

    public function test_switcher_renders_languages_selected_value_and_csrf_field(): void
    {
        app()->setLocale('vi');

        $this->blade('<x-locale-switcher class="test-switcher" />')
            ->assertSee('Ngôn ngữ')
            ->assertSee('Áp dụng')
            ->assertSee('English')
            ->assertSee('Tiếng Việt')
            ->assertSee('name="_token"', false)
            ->assertSee('method="POST"', false)
            ->assertSee(route('locale.update'))
            ->assertSee('test-switcher')
            ->assertSeeInOrder(['value="vi"', 'checked'], false);
    }

    public function test_switching_language_updates_layout_and_navigation(): void
    {
        $this->withoutVite();

        Route::middleware('web')->get('/_test/navigation', fn () => response(Blade::render(
            <<<'BLADE'
            @extends('layouts.app')
            @section('content')
                <x-nav-bar :product-categories="$categories" :cart="$cart" />
            @endsection
            BLADE,
            [
                'categories' => collect(),
                'cart' => ['total_count' => 0, 'total_amount' => '0.00', 'products' => collect()],
            ],
        )));

        $this->from('/_test/navigation')
            ->post(route('locale.update'), ['locale' => 'vi'])
            ->assertRedirect('/_test/navigation');

        $this->get('/_test/navigation')
            ->assertOk()
            ->assertSee('<html lang="vi">', false)
            ->assertSee('Danh mục')
            ->assertSee('Tìm kiếm sản phẩm...')
            ->assertSee('Đăng nhập')
            ->assertSee('Đăng ký')
            ->assertSee('data-cart-show-url=', false);

        $this->post(route('locale.update'), ['locale' => 'en']);

        $this->get('/_test/navigation')
            ->assertOk()
            ->assertSee('<html lang="en">', false)
            ->assertSee('Categories')
            ->assertSee('Search products...')
            ->assertSee('Sign in');
    }

    public function test_footer_and_settings_render_distinct_drawers_with_shared_language_form(): void
    {
        app()->setLocale('en');

        $view = $this->blade('<x-persistent-header /><x-company-footer />');

        $view->assertSee('Settings')
            ->assertSee('Language')
            ->assertSee('Choose your language')
            ->assertSee('aria-controls="language-panel"', false)
            ->assertSee('<table', false)
            ->assertSee('name="locale"', false);

        $html = (string) $view;
        $this->assertSame(1, substr_count($html, 'id="language-panel"'));
        $this->assertSame(1, substr_count($html, 'id="settings-panel"'));
        $this->assertSame(2, substr_count($html, '<dialog'));
        $this->assertStringContainsString('drawer-bottom', $html);
        $this->assertStringContainsString('drawer-end', $html);
        $this->assertStringContainsString('--drawer-backdrop-blur: 0px', $html);
        $this->assertStringContainsString('<details class="accordion-item" open>', $html);
        $this->assertStringNotContainsString('data-side-panel-target="#settings"', $html);
    }

    public function test_account_navigation_uses_translated_labels(): void
    {
        $user = new class extends User
        {
            public function isVendor(): bool
            {
                return true;
            }
        };
        $user->forceFill(['user_id' => 1, 'username' => 'Test customer', 'role' => 'admin']);

        $this->actingAs($user);
        app()->setLocale('vi');

        $this->blade(
            '<x-nav-bar :product-categories="$categories" :cart="$cart" />',
            [
                'categories' => collect(),
                'cart' => ['total_count' => 0, 'total_amount' => '0.00', 'products' => collect()],
            ],
        )
            ->assertSee('Test customer')
            ->assertSee('Bảng điều khiển quản trị')
            ->assertSee('Hồ sơ tài khoản')
            ->assertSee('Gian hàng của tôi')
            ->assertSee('Sản phẩm của tôi')
            ->assertSee('Đăng xuất');
    }

    public function test_common_translation_catalogs_have_matching_keys(): void
    {
        $english = require lang_path('en/common.php');
        $vietnamese = require lang_path('vi/common.php');

        $this->assertSame(array_keys($english), array_keys($vietnamese));
    }

    public function test_request_without_preference_uses_application_default(): void
    {
        app('translator')->setLocale('vi');

        $this->get('/_test/locale')
            ->assertOk()
            ->assertExactJson(['locale' => 'en', 'translation_locale' => 'en']);
    }

    public function test_guest_can_switch_language_and_keep_it_on_following_requests(): void
    {
        $this->from('/products?q=sword')->post(route('locale.update'), ['locale' => 'vi'])
            ->assertRedirect('/products?q=sword')
            ->assertSessionHas('locale', 'vi');

        $this->get('/_test/locale')
            ->assertExactJson(['locale' => 'vi', 'translation_locale' => 'vi']);

        $this->get('/_test/locale')
            ->assertJsonPath('locale', 'vi');
    }

    public function test_guest_can_switch_back_to_english(): void
    {
        $this->withSession(['locale' => 'vi'])
            ->post(route('locale.update'), ['locale' => 'en'])
            ->assertSessionHas('locale', 'en');

        $this->get('/_test/locale')->assertJsonPath('locale', 'en');
    }

    #[DataProvider('invalidLocales')]
    public function test_invalid_selection_does_not_replace_existing_preference(mixed $locale): void
    {
        $this->withSession(['locale' => 'vi'])
            ->postJson(route('locale.update'), ['locale' => $locale])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('locale')
            ->assertSessionHas('locale', 'vi');
    }

    public static function invalidLocales(): array
    {
        return [
            'unsupported' => ['fr'],
            'missing' => [null],
            'empty' => [''],
            'array' => [['vi']],
            'numeric' => [123],
        ];
    }

    #[DataProvider('invalidSessionLocales')]
    public function test_invalid_session_preference_uses_fallback(mixed $locale): void
    {
        $this->withSession(['locale' => $locale])
            ->get('/_test/locale')
            ->assertExactJson(['locale' => 'en', 'translation_locale' => 'en']);
    }

    public static function invalidSessionLocales(): array
    {
        return [
            'unsupported' => ['fr'],
            'array' => [['vi']],
            'numeric' => [123],
        ];
    }

    public function test_switcher_uses_configured_supported_languages(): void
    {
        config(['app.supported_locales' => ['en' => 'English']]);

        $this->postJson(route('locale.update'), ['locale' => 'vi'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('locale');
    }

    public function test_login_session_regeneration_preserves_language(): void
    {
        $guard = Auth::guard();
        Auth::shouldReceive('guard')->with(null)->andReturn($guard);
        Auth::shouldReceive('attempt')
            ->once()
            ->with(['email' => 'customer@example.test', 'password' => 'password'])
            ->andReturn(true);

        $this->withSession(['locale' => 'vi'])
            ->postJson(route('login.store'), [
                'email' => 'customer@example.test',
                'password' => 'password',
            ])
            ->assertOk()
            ->assertSessionHas('locale', 'vi');
    }

    public function test_switcher_rejects_requests_without_csrf_token(): void
    {
        $this->app->instance('env', 'production');

        $this->post(route('locale.update'), ['locale' => 'vi'])
            ->assertStatus(419)
            ->assertSessionMissing('locale');
    }
}
