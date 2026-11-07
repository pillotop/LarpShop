<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\PaymentSucceededNotification;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RecoveryNotificationLocalizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_recovery_pages_render_in_both_languages_without_changing_reset_fields(): void
    {
        foreach (['en' => 'Send reset link', 'vi' => 'Gửi liên kết đặt lại'] as $locale => $label) {
            $this->withSession(['locale' => $locale])->get(route('password.request'))
                ->assertOk()->assertSee($label)->assertDontSee('Enter code');
            $this->get(route('password.reset', ['token' => 'sample-token', 'email' => 'buyer@example.test']))
                ->assertOk()->assertSee('<html lang="'.$locale.'">', false)
                ->assertSee('value="sample-token"', false)
                ->assertSee('value="buyer@example.test"', false)
                ->assertSee('name="password_confirmation"', false)
                ->assertDontSee('Enter code');
        }
    }

    public function test_locale_is_applied_before_route_model_binding_errors(): void
    {
        config(['app.debug' => false]);
        $this->withSession(['locale' => 'vi'])->get('/products/999999999')
            ->assertNotFound()->assertSee('Không tìm thấy');
    }

    public function test_password_reset_email_and_shared_mail_layout_are_translated(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->withSession(['locale' => 'vi'])->postJson(route('password.email'), ['email' => $user->email])
            ->assertOk();
        Notification::assertSentTo($user, ResetPassword::class);

        app()->setLocale('vi');
        $mail = (new ResetPassword('sample-token'))->toMail($user);
        $this->assertSame('Đặt lại mật khẩu', $mail->subject);
        $html = (string) $mail->render();
        $this->assertStringContainsString('Xin chào!', $html);
        $this->assertStringContainsString('Liên kết đặt lại mật khẩu này sẽ hết hạn sau', $html);
        $this->assertStringContainsString('Trân trọng,', $html);
        $this->assertStringContainsString('sample-token', $mail->actionUrl);
    }

    public function test_payment_notification_captures_locale_and_preserves_authoritative_payment_data(): void
    {
        app()->setLocale('vi');
        $payment = new Payment([
            'order_id' => 42, 'amount' => '125.50', 'currency' => 'USD', 'method' => PaymentMethod::EWallet,
        ]);
        $notification = new PaymentSucceededNotification($payment);
        $this->assertSame('vi', $notification->locale);
        $mail = $notification->toMail(new User);
        $this->assertSame('LarpShop - Xác nhận thanh toán', $mail->subject);
        $this->assertContains('Đơn hàng #42', $mail->introLines);
        $this->assertContains('Số tiền: 125.50 USD', $mail->introLines);
        $this->assertContains('Phương thức thanh toán: Ví điện tử', $mail->introLines);
        $this->assertSame(route('orders.index'), $mail->actionUrl);
        $this->assertSame('ewallet', $payment->method->value);
    }
}
