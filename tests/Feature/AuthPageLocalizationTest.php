<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthPageLocalizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        config(['app.locale' => 'en', 'app.fallback_locale' => 'en']);
    }

    public function test_login_page_defaults_to_english(): void
    {
        $this->get(route('login'))->assertOk()
            ->assertSee('<html lang="en">', false)
            ->assertSee('Login &amp; Register', false)
            ->assertSee('Sign in or create account')
            ->assertSee('Use another email');
    }

    public function test_vietnamese_login_page_provides_copy_for_every_dynamic_state(): void
    {
        $response = $this->withSession(['locale' => 'vi'])->get(route('login'))
            ->assertOk()
            ->assertSee('<html lang="vi">', false)
            ->assertSee('Đăng nhập và đăng ký')
            ->assertSee('Nền tảng thương mại điện tử thế hệ mới')
            ->assertSee('Mọi thứ đều có tại LarpShop')
            ->assertSee('Đăng nhập hoặc tạo tài khoản')
            ->assertSee('Địa chỉ email')
            ->assertSee('Xác nhận mật khẩu')
            ->assertSee('Dùng email khác')
            ->assertSee('data-form-state="email"', false)
            ->assertSee('data-login-url="'.route('login.store').'"', false)
            ->assertSee('data-register-url="'.route('register.store').'"', false)
            ->assertSee('name="_token"', false);

        $messages = $this->messages($response->getContent());
        $this->assertSame('Nhập mật khẩu của bạn', $messages['states']['login']['header']);
        $this->assertSame('Đang đăng nhập…', $messages['states']['login']['loading']);
        $this->assertSame('Tạo tài khoản', $messages['states']['register']['header']);
        $this->assertSame('Đang tạo tài khoản…', $messages['states']['register']['loading']);
        $this->assertSame('Đã xảy ra lỗi kết nối. Vui lòng thử lại.', $messages['network_error']);
    }

    public function test_localized_login_keeps_the_existing_email_and_login_contract(): void
    {
        $user = User::factory()->create(['email' => 'buyer@example.test', 'password' => 'test-password']);

        $this->withSession(['locale' => 'vi'])->postJson(route('auth.check-email'), [
            'email' => $user->email,
        ])->assertOk()->assertExactJson(['exists' => true]);

        $this->postJson(route('login.store'), [
            'email' => $user->email, 'password' => 'incorrect',
        ])->assertUnauthorized()
            ->assertJsonPath('errors.password.0', 'Thông tin đăng nhập không chính xác.');

        $this->postJson(route('login.store'), [
            'email' => $user->email, 'password' => 'test-password',
        ])->assertOk()->assertJsonPath('redirect', route('home'))
            ->assertSessionHas('locale', 'vi');

        $this->assertAuthenticatedAs($user);
    }

    public function test_registration_retains_validation_payload_and_language(): void
    {
        $this->withSession(['locale' => 'vi'])->postJson(route('auth.check-email'), [
            'email' => 'new-buyer@example.test',
        ])->assertOk()->assertExactJson(['exists' => false]);

        $this->postJson(route('register.store'), [
            'email' => 'new-buyer@example.test', 'username' => '',
            'password' => 'test-password', 'password_confirmation' => 'test-password',
        ])->assertUnprocessable()
            ->assertJsonPath('errors.username.0', 'Trường tên người dùng là bắt buộc.');

        $this->postJson(route('register.store'), [
            'email' => 'new-buyer@example.test', 'username' => 'Original buyer',
            'password' => 'test-password', 'password_confirmation' => 'test-password',
        ])->assertCreated()->assertJsonPath('redirect', route('home'))
            ->assertSessionHas('locale', 'vi');

        $user = User::where('email', 'new-buyer@example.test')->sole();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('customer', $user->role);
        $this->assertSame('Original buyer', $user->username);
        $this->assertTrue(Hash::check('test-password', $user->password));
    }

    public function test_other_users_of_the_shared_script_receive_its_message_dictionary(): void
    {
        $this->withSession(['locale' => 'vi']);

        foreach ([route('password.request'), route('password.reset', 'test-token')] as $url) {
            $response = $this->get($url)->assertOk();
            $messages = $this->messages($response->getContent());

            $this->assertSame('Quên mật khẩu?', $messages['states']['forgot-password']['header']);
            $this->assertSame('Đặt lại mật khẩu', $messages['states']['reset-password']['header']);
        }
    }

    private function messages(string $html): array
    {
        $this->assertSame(1, preg_match(
            '/<script type="application\/json" data-auth-messages>(.*?)<\/script>/s',
            $html,
            $matches,
        ));

        return json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR);
    }
}
