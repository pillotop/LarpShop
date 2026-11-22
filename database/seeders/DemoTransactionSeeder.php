<?php

namespace Database\Seeders;

use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderAddress;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\Product;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\Support\DemoSeedContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Seeds realistic 6-month multi-vendor order, payment, event, address, and fulfillment scenarios idempotently.
 */
class DemoTransactionSeeder extends Seeder
{
    public function __construct(private DemoSeedContext $context) {}

    public function run(): void
    {
        $existingOrderRefs = OrderAddress::query()
            ->where('address_line_2', 'like', 'DEMO-ORD-%')
            ->pluck('address_line_2')
            ->flip()
            ->all();

        $customers = User::query()
            ->where('role', 'customer')
            ->whereDoesntHave('vendors')
            ->orderBy('user_id')
            ->get();

        // Only consume healthy-stock approved active items so low-stock/out-of-stock/moderation bands remain intact.
        $products = Product::query()
            ->approved()
            ->active()
            ->whereHas('vendor', fn ($q) => $q->active())
            ->where('stock', '>=', 25)
            ->orderBy('product_id')
            ->get();

        if ($customers->isEmpty() || $products->count() < 10) {
            return;
        }

        $anchor = $this->context->anchor();
        $john = $customers->firstWhere('username', 'johndoe') ?? $customers->first();
        $jane = $customers->firstWhere('username', 'janestewart') ?? $customers->last();

        DB::transaction(function () use ($existingOrderRefs, $customers, $products, $anchor, $john, $jane): void {
            // 1. Canonical Pending order required by OrderStatusConsistencyTest (expires in future, 0 payments).
            $this->createDemoOrder(
                orderRef: 'DEMO-ORD-0001',
                existingRefs: $existingOrderRefs,
                buyer: $john,
                status: OrderStatus::Pending,
                createdAt: CarbonImmutable::now()->subMinutes(45),
                expiresAt: CarbonImmutable::now()->addHours(2),
                stockReleasedAt: null,
                lines: [
                    ['product' => $products[0], 'qty' => 1, 'fulfillment' => FulfillmentStatus::Unfulfilled, 'shipped_at' => null, 'delivered_at' => null],
                ],
                reserveStock: true,
                paymentAttempts: [],
            );

            // 2. Canonical first Paid order required by OrderStatusConsistencyTest (exactly 1 succeeded payment).
            $paidCreated = $anchor->subHours(10);
            $this->createDemoOrder(
                orderRef: 'DEMO-ORD-0002',
                existingRefs: $existingOrderRefs,
                buyer: $jane,
                status: OrderStatus::Paid,
                createdAt: $paidCreated,
                expiresAt: null,
                stockReleasedAt: null,
                lines: [
                    ['product' => $products[1], 'qty' => 1, 'fulfillment' => FulfillmentStatus::Processing, 'shipped_at' => null, 'delivered_at' => null],
                ],
                reserveStock: true,
                paymentAttempts: [
                    [
                        'ref' => 'DEMO-PAY-0002-1',
                        'provider' => 'paypal',
                        'method' => PaymentMethod::EWallet,
                        'status' => PaymentStatus::Succeeded,
                        'created_at' => $paidCreated->addMinutes(1),
                        'completed_at' => $paidCreated->addMinutes(4),
                    ],
                ],
            );

            // 3. Generate 318 diverse orders spanning 180 days (6 months).
            for ($seq = 3; $seq <= 320; $seq++) {
                $orderRef = sprintf('DEMO-ORD-%04d', $seq);
                if (isset($existingOrderRefs[$orderRef])) {
                    continue;
                }

                $buyer = $this->pickBuyer($seq, $customers);
                $createdAt = $this->createdAtForSequence($seq, $anchor);
                $lines = $this->buildLines($seq, $products, $createdAt);
                $status = $this->statusForSequence($seq);
                $paymentAttempts = $this->buildPaymentAttempts($seq, $status, $createdAt);

                $expiresAt = match ($status) {
                    OrderStatus::Pending => CarbonImmutable::now()->addHours(4),
                    OrderStatus::Expired => $createdAt->addMinutes(30),
                    default => null,
                };
                $stockReleasedAt = in_array($status, [OrderStatus::Cancelled, OrderStatus::Expired], true)
                    ? $createdAt->addMinutes(40)
                    : null;

                $this->createDemoOrder(
                    orderRef: $orderRef,
                    existingRefs: $existingOrderRefs,
                    buyer: $buyer,
                    status: $status,
                    createdAt: $createdAt,
                    expiresAt: $expiresAt,
                    stockReleasedAt: $stockReleasedAt,
                    lines: $lines,
                    reserveStock: in_array($status, [OrderStatus::Pending, OrderStatus::Paid], true),
                    paymentAttempts: $paymentAttempts,
                );
            }
        });
    }

    private function pickBuyer(int $seq, Collection $customers): User
    {
        // Distribute across repeat-buyer cohort (first 32 customers) and occasional buyers (remaining).
        if ($seq % 6 !== 0) {
            $repeatPool = $customers->take(min(32, $customers->count()));

            return $repeatPool[($seq * 5) % $repeatPool->count()];
        }

        return $customers[($seq * 3) % $customers->count()];
    }

    private function createdAtForSequence(int $seq, CarbonImmutable $anchor): CarbonImmutable
    {
        $daysAgo = match (true) {
            $seq <= 55 => ($seq % 7),
            $seq <= 130 => 7 + ($seq % 23),
            $seq <= 195 => 30 + ($seq % 30),
            default => 60 + (($seq * 7) % 120),
        };

        $hourOffset = ($seq * 3) % 18 + 3;

        return $anchor->subDays($daysAgo)->subHours($hourOffset);
    }

    private function statusForSequence(int $seq): OrderStatus
    {
        return match (true) {
            $seq % 11 === 0 => OrderStatus::Cancelled,
            $seq % 13 === 0 => OrderStatus::Expired,
            $seq % 17 === 0 => OrderStatus::Pending,
            default => OrderStatus::Paid,
        };
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildLines(int $seq, Collection $products, CarbonImmutable $createdAt): array
    {
        $lineCount = ($seq % 3 === 0) ? 2 : 1;
        $lines = [];
        $baseIdx = ($seq * 7) % $products->count();
        $orderStatus = $this->statusForSequence($seq);
        $ageDays = $createdAt->diffInDays($this->context->anchor());

        for ($i = 0; $i < $lineCount; $i++) {
            $product = $products[($baseIdx + ($i * 11)) % $products->count()];
            $qty = ($seq % 4 === 0 && $i === 0) ? 2 : 1;

            [$fulfillment, $shippedAt, $deliveredAt] = $this->fulfillmentForLine($seq, $i, $orderStatus, $createdAt, $ageDays);

            $lines[] = [
                'product' => $product,
                'qty' => $qty,
                'fulfillment' => $fulfillment,
                'shipped_at' => $shippedAt,
                'delivered_at' => $deliveredAt,
            ];
        }

        return $lines;
    }

    /**
     * @return array{0: FulfillmentStatus, 1: ?CarbonImmutable, 2: ?CarbonImmutable}
     */
    private function fulfillmentForLine(
        int $seq,
        int $lineIdx,
        OrderStatus $orderStatus,
        CarbonImmutable $createdAt,
        int $ageDays,
    ): array {
        if ($orderStatus !== OrderStatus::Paid) {
            return [FulfillmentStatus::Unfulfilled, null, null];
        }

        // Keep an intentional backlog of older paid unfulfilled/processing lines for aging reports.
        if (($seq + $lineIdx) % 14 === 0) {
            $status = $seq % 2 === 0 ? FulfillmentStatus::Unfulfilled : FulfillmentStatus::Processing;

            return [$status, null, null];
        }

        if ($ageDays <= 3) {
            $pick = ($seq + $lineIdx) % 4;
            if ($pick === 0) {
                return [FulfillmentStatus::Unfulfilled, null, null];
            }
            if ($pick === 1) {
                return [FulfillmentStatus::Processing, null, null];
            }
            if ($pick === 2) {
                return [FulfillmentStatus::Shipped, $createdAt->addHours(14), null];
            }

            return [FulfillmentStatus::Delivered, $createdAt->addHours(12), $createdAt->addHours(36)];
        }

        if ($ageDays <= 20) {
            if (($seq + $lineIdx) % 3 === 0) {
                return [FulfillmentStatus::Shipped, $createdAt->addDays(1), null];
            }

            return [FulfillmentStatus::Delivered, $createdAt->addDays(1), $createdAt->addDays(4)];
        }

        return [FulfillmentStatus::Delivered, $createdAt->addDays(1), $createdAt->addDays(5)];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildPaymentAttempts(int $seq, OrderStatus $status, CarbonImmutable $createdAt): array
    {
        $realProviders = [
            ['paypal', PaymentMethod::EWallet],
            ['stripe', PaymentMethod::Card],
            ['vnpay', PaymentMethod::EWallet],
            ['momo', PaymentMethod::EWallet],
            ['zalopay', PaymentMethod::EWallet],
        ];
        $simProviders = [
            ['fake', PaymentMethod::Card],
            ['momo_demo', PaymentMethod::EWallet],
            ['zalopay_demo', PaymentMethod::EWallet],
            ['vnpay_demo', PaymentMethod::EWallet],
        ];

        $useProvider = ($seq % 9) < 5;
        [$provider, $method] = $useProvider
            ? $realProviders[$seq % count($realProviders)]
            : $simProviders[$seq % count($simProviders)];

        if ($status === OrderStatus::Paid) {
            $attempts = [];
            // ~18% of Paid orders demonstrate a failed initial attempt followed by a retry that succeeds.
            if ($seq % 6 === 0) {
                $attempts[] = [
                    'ref' => sprintf('DEMO-PAY-%04d-1', $seq),
                    'provider' => $provider,
                    'method' => $method,
                    'status' => PaymentStatus::Failed,
                    'created_at' => $createdAt->addMinutes(2),
                    'completed_at' => null,
                ];
            }
            $attempts[] = [
                'ref' => sprintf('DEMO-PAY-%04d-2', $seq),
                'provider' => $provider,
                'method' => $method,
                'status' => PaymentStatus::Succeeded,
                'created_at' => $createdAt->addMinutes(6),
                'completed_at' => $createdAt->addMinutes(11),
            ];

            return $attempts;
        }

        if ($status === OrderStatus::Pending) {
            if ($seq % 2 === 0) {
                return [];
            }
            $pendingStatus = match ($seq % 3) {
                0 => PaymentStatus::Pending,
                1 => PaymentStatus::Processing,
                default => PaymentStatus::Delayed,
            };

            return [[
                'ref' => sprintf('DEMO-PAY-%04d-1', $seq),
                'provider' => $provider,
                'method' => $method,
                'status' => $pendingStatus,
                'created_at' => $createdAt->addMinutes(2),
                'completed_at' => null,
            ]];
        }

        if ($status === OrderStatus::Cancelled) {
            $failStatus = $seq % 2 === 0 ? PaymentStatus::Cancelled : PaymentStatus::Failed;

            return [[
                'ref' => sprintf('DEMO-PAY-%04d-1', $seq),
                'provider' => $provider,
                'method' => $method,
                'status' => $failStatus,
                'created_at' => $createdAt->addMinutes(3),
                'completed_at' => null,
            ]];
        }

        return [[
            'ref' => sprintf('DEMO-PAY-%04d-1', $seq),
            'provider' => $provider,
            'method' => $method,
            'status' => PaymentStatus::Failed,
            'created_at' => $createdAt->addMinutes(3),
            'completed_at' => null,
        ]];
    }

    /**
     * @param  array<string, int>  $existingRefs
     * @param  array<int, array<string, mixed>>  $lines
     * @param  array<int, array<string, mixed>>  $paymentAttempts
     */
    private function createDemoOrder(
        string $orderRef,
        array $existingRefs,
        User $buyer,
        OrderStatus $status,
        CarbonImmutable $createdAt,
        ?CarbonImmutable $expiresAt,
        ?CarbonImmutable $stockReleasedAt,
        array $lines,
        bool $reserveStock,
        array $paymentAttempts,
    ): void {
        if (isset($existingRefs[$orderRef])) {
            return;
        }

        $totalAmount = 0.0;
        foreach ($lines as $line) {
            $totalAmount += ((float) $line['product']->price) * (int) $line['qty'];
        }
        $totalAmount = round($totalAmount, 2);

        $order = new Order([
            'user_id' => $buyer->user_id,
            'total_amount' => $totalAmount,
            'status' => $status,
            'expires_at' => $expiresAt,
            'stock_released_at' => $stockReleasedAt,
        ]);
        $order->created_at = $createdAt;
        $order->updated_at = $createdAt;
        $order->save();

        foreach ($lines as $line) {
            /** @var Product $product */
            $product = $line['product'];
            $detail = $order->details()->make([
                'product_id' => $product->product_id,
                'quantity' => $line['qty'],
                'unit_price' => $product->price,
                'fulfillment_status' => $line['fulfillment'],
                'shipped_at' => $line['shipped_at'],
                'delivered_at' => $line['delivered_at'],
            ]);
            $detail->created_at = $createdAt;
            $detail->updated_at = $line['delivered_at'] ?? $line['shipped_at'] ?? $createdAt;
            $detail->save();

            if ($reserveStock) {
                $product->decrement('stock', (int) $line['qty']);
            }
        }

        $address = $order->addresses()->make([
            'type' => 'shipping',
            'recipient_name' => ucwords(str_replace(['_', '.'], ' ', $buyer->username)),
            'phone_number' => $buyer->phone_number ?? '+15550199999',
            'address_line_1' => sprintf('%d Demo Commerce Blvd', 100 + ($buyer->user_id % 800)),
            'address_line_2' => $orderRef,
            'city' => 'Austin',
            'region' => 'TX',
            'postal_code' => '78701',
            'country_code' => 'US',
        ]);
        $address->created_at = $createdAt;
        $address->updated_at = $createdAt;
        $address->save();

        foreach ($paymentAttempts as $attempt) {
            $isVndProvider = in_array($attempt['provider'], ['vnpay', 'momo', 'zalopay', 'vnpay_demo', 'momo_demo', 'zalopay_demo'], true);

            $payment = new Payment([
                'order_id' => $order->order_id,
                'provider' => $attempt['provider'],
                'method' => $attempt['method'],
                'provider_reference' => $attempt['ref'],
                'amount' => $totalAmount,
                'currency' => config('payments.currency', 'USD'),
                'source_amount' => $isVndProvider ? round($totalAmount * 25000, 2) : $totalAmount,
                'source_currency' => $isVndProvider ? 'VND' : 'USD',
                'exchange_rate' => $isVndProvider ? 25000.00000000 : 1.00000000,
                'status' => $attempt['status'],
                'completed_at' => $attempt['completed_at'],
            ]);
            $payment->created_at = $attempt['created_at'];
            $payment->updated_at = $attempt['completed_at'] ?? $attempt['created_at'];
            $payment->save();

            $eventType = match ($attempt['status']) {
                PaymentStatus::Succeeded => 'payment.succeeded',
                PaymentStatus::Failed => 'payment.failed',
                PaymentStatus::Cancelled => 'payment.cancelled',
                default => 'payment.initiated',
            };

            $event = new PaymentEvent([
                'payment_id' => $payment->payment_id,
                'event_type' => $eventType,
                'status' => $attempt['status']->value,
                'provider_event_id' => 'EVT-'.$attempt['ref'],
                'metadata' => [
                    'synthetic' => true,
                    'order_ref' => $orderRef,
                ],
            ]);
            $event->created_at = $attempt['completed_at'] ?? $attempt['created_at'];
            $event->updated_at = $event->created_at;
            $event->save();
        }
    }
}
