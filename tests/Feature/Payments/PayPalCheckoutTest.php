<?php

namespace Tests\Feature\Payments;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PayPalCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private string $providerStatus = 'APPROVED';

    private ?string $captureStatus = null;

    private string $amount = '49.95';

    private bool $failCreate = false;

    private bool $failCapture = false;

    private int $captures = 0;

    private string $merchantReference = '';

    private array $creationKeys = [];

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        Http::preventStrayRequests();
        config([
            'services.paypal.base_url' => 'https://paypal.example.test',
            'services.paypal.client_id' => 'test', 'services.paypal.client_secret' => 'test',
        ]);
        Http::fake(function (Request $request) {
            if (str_ends_with($request->url(), '/token')) {
                return Http::response(['access_token' => 'token']);
            }
            if ($request->method() === 'POST' && str_ends_with($request->url(), '/orders')) {
                $this->merchantReference = $request['purchase_units'][0]['reference_id'];
                $this->creationKeys[] = $request->header('PayPal-Request-Id')[0];

                return $this->failCreate ? Http::response([], 503) : Http::response([
                    'id' => 'PP-1', 'links' => [['rel' => 'approve', 'href' => 'https://paypal.example.test/approve']],
                ]);
            }
            if (str_ends_with($request->url(), '/capture')) {
                $this->captures++;
                $this->providerStatus = 'COMPLETED';
                $this->captureStatus ??= 'COMPLETED';
                if ($this->failCapture) {
                    return Http::response([], 503);
                }
            }
            $unit = [
                'reference_id' => $this->merchantReference,
                'amount' => ['value' => $this->amount, 'currency_code' => 'USD'],
            ];
            if ($this->captureStatus !== null) {
                $unit['payments']['captures'] = [[
                    'id' => 'CAP-1', 'status' => $this->captureStatus,
                    'amount' => $unit['amount'],
                ]];
            }

            return Http::response(['id' => 'PP-1', 'status' => $this->providerStatus, 'purchase_units' => [$unit]]);
        });
    }

    private function order(): Order
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $order = Order::create(['user_id' => $user->getKey(), 'status' => 'pending', 'total_amount' => '49.95', 'expires_at' => now()->addMinutes(30)]);
        $order->addresses()->create([
            'type' => 'shipping', 'recipient_name' => 'Test Buyer',
            'address_line_1' => '123 Test Street', 'city' => 'Test City', 'country_code' => 'VN',
        ]);

        return $order;
    }

    private function payment(Order $order): Payment
    {
        $id = $this->postJson(route('payments.store', $order), ['payment_option' => 'paypal'])
            ->assertCreated()->assertJsonPath('checkout_url', 'https://paypal.example.test/approve')->json('payment_id');

        return Payment::findOrFail($id);
    }

    public function test_return_is_read_only_and_capture_confirms_order_once(): void
    {
        $order = $this->order();
        $payment = $this->payment($order);
        $this->get(route('payments.return', $payment).'?token=untrusted&status=succeeded')->assertOk()->assertSee('Confirm payment');
        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
        $this->assertSame(0, $this->captures);
        $this->postJson(route('payments.complete', $payment))->assertOk()->assertJsonPath('status', 'succeeded');
        $this->postJson(route('payments.complete', $payment))->assertOk()->assertJsonPath('status', 'succeeded');
        $this->assertSame(1, $this->captures);
        $this->assertSame(OrderStatus::Paid, $order->fresh()->status);
        $this->get(route('orders.show', $order))->assertOk()->assertSee('Your order is confirmed.');
        $this->get(route('orders.index'))->assertOk()->assertSee('Order #'.$order->getKey());
        $this->assertDatabaseCount('payment_events', 2);
    }

    public function test_setup_failure_can_retry_the_same_payment_and_request_key(): void
    {
        $order = $this->order();
        $this->failCreate = true;
        $this->postJson(route('payments.store', $order), ['payment_option' => 'paypal'])->assertUnprocessable();
        $payment = $order->payments()->sole();
        $this->assertNull($payment->provider_reference);
        $this->get(route('checkout.show', $order))->assertOk()->assertSee('Retry payment setup');
        $this->failCreate = false;
        $this->postJson(route('payments.retry', $payment))->assertCreated()->assertJsonPath('payment_id', $payment->getKey());
        $this->assertSame($this->creationKeys[0], $this->creationKeys[1]);
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_capture_response_failure_is_recovered_without_another_capture(): void
    {
        $order = $this->order();
        $payment = $this->payment($order);
        $this->failCapture = true;
        $this->postJson(route('payments.complete', $payment))->assertUnprocessable();
        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
        $this->artisan('payments:reconcile')->assertSuccessful();
        $this->assertSame(PaymentStatus::Succeeded, $payment->fresh()->status);
        $this->assertSame(1, $this->captures);
    }

    public function test_unapproved_or_mismatched_payments_are_not_captured(): void
    {
        $order = $this->order();
        $payment = $this->payment($order);
        $this->providerStatus = 'CREATED';
        $this->postJson(route('payments.complete', $payment))->assertUnprocessable();
        $this->providerStatus = 'APPROVED';
        $this->amount = '0.01';
        $this->postJson(route('payments.complete', $payment))->assertUnprocessable();
        $this->assertSame(0, $this->captures);
        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
    }

    public function test_pending_capture_can_be_reconciled_and_late_success_requires_review(): void
    {
        $order = $this->order();
        $payment = $this->payment($order);
        $this->captureStatus = 'PENDING';
        $this->postJson(route('payments.complete', $payment))->assertOk()->assertJsonPath('status', 'processing');
        $this->post(route('orders.cancel', $order))->assertRedirect();
        $this->captureStatus = 'COMPLETED';
        $this->artisan('payments:reconcile')->assertSuccessful();
        $this->assertSame(PaymentStatus::RefundRequired, $payment->fresh()->status);
        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
    }

    public function test_closed_order_is_not_captured_and_cancel_return_does_not_cancel_order(): void
    {
        $order = $this->order();
        $payment = $this->payment($order);
        $this->get(route('payments.cancel', $payment))->assertRedirect(route('checkout.show', $order));
        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
        $order->update(['expires_at' => now()->subMinute()]);
        $this->postJson(route('payments.complete', $payment))->assertUnprocessable();
        $this->assertSame(0, $this->captures);
    }

    public function test_other_users_cannot_operate_on_payments_or_orders(): void
    {
        $order = $this->order();
        $payment = $this->payment($order);
        $this->actingAs(User::factory()->create());
        foreach (['payments.retry', 'payments.complete'] as $route) {
            $this->postJson(route($route, $payment))->assertNotFound();
        }
        foreach (['payments.return', 'payments.cancel'] as $route) {
            $this->get(route($route, $payment))->assertNotFound();
        }
        $this->get(route('orders.show', $order))->assertNotFound();
        $this->postJson(route('payments.store', $order), ['payment_option' => 'paypal'])->assertNotFound();
        $this->assertSame(0, $this->captures);
    }

    public function test_fake_payment_uses_its_own_actions_without_a_redirect_url(): void
    {
        $order = $this->order();
        $id = $this->postJson(route('payments.store', $order), ['payment_option' => 'fake_card'])
            ->assertCreated()->assertJsonPath('checkout_url', null)->json('payment_id');
        $this->get(route('checkout.show', $order))->assertOk()->assertSee('Simulate payment');
        $this->post(route('test.payments.simulate', $id), ['outcome' => 'succeeded'])->assertRedirect(route('checkout.show', $order));
        $this->assertSame(OrderStatus::Paid, $order->fresh()->status);
        Http::assertNothingSent();
    }
}
