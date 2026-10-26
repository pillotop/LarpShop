<?php

namespace Tests\Feature\Payments;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PaymentConcurrencyTest extends TestCase
{
    // Fixtures must be committed so independent connections can see them.
    use DatabaseTruncation;

    protected function beforeTruncatingDatabase(): void
    {
        if (DB::getDriverName() !== 'mysql' || ! function_exists('proc_open')) {
            $this->markTestSkipped('Requires MySQL row locks and proc_open.');
        }

        $this->assertSame('testing', app()->environment());
        $this->assertStringEndsWith('_testing', DB::connection()->getDatabaseName());
    }

    public function test_simultaneous_requests_create_only_one_active_payment(): void
    {
        if (DB::getDriverName() !== 'mysql' || ! function_exists('proc_open')) {
            $this->markTestSkipped('Requires MySQL row locks and proc_open.');
        }

        $this->assertSame('testing', app()->environment());
        $this->assertStringEndsWith('_testing', DB::connection()->getDatabaseName());
        $user = User::factory()->create();
        $order = Order::create(['user_id' => $user->getKey(), 'status' => 'pending', 'total_amount' => '49.95']);
        $workers = [];
        try {
            for ($i = 0; $i < 2; $i++) {
                $process = proc_open(
                    [PHP_BINARY, base_path('tests/Support/payment-request-worker.php')],
                    [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                    $pipes,
                    base_path(),
                    array_merge(getenv(), ['APP_ENV' => 'testing'])
                );

                $this->assertIsResource($process);
                $workers[] = [$process, $pipes];

                stream_set_timeout($pipes[1], 15);

                fwrite($pipes[0], json_encode([
                    'connection' => config('database.connections.mysql'),
                    'user_id' => $user->getKey(), 'order_id' => $order->getKey(),
                ], JSON_THROW_ON_ERROR)."\n");

                fflush($pipes[0]);
            }

            foreach ($workers as [, $pipes]) {
                $this->assertSame("ready\n", fgets($pipes[1]), 'Payment worker did not become ready.');
            }

            foreach ($workers as [, $pipes]) {
                fwrite($pipes[0], "go\n");
                fflush($pipes[0]);
            }

            $statuses = [];

            foreach ($workers as [, $pipes]) {
                $line = fgets($pipes[1]);
                $this->assertIsString($line, 'Payment worker timed out.');
                $result = json_decode($line, true, flags: JSON_THROW_ON_ERROR);
                $statuses[] = $result['status'];
            }

            sort($statuses);

            $this->assertSame([201, 422], $statuses);
            $this->assertDatabaseCount('payments', 1);
            $this->assertDatabaseCount('fake_payment_sessions', 1);
            $this->assertDatabaseCount('payment_events', 1);
            $this->assertDatabaseHas('payments', ['order_id' => $order->getKey(), 'status' => 'pending', 'amount' => '49.95']);
        } finally {
            foreach ($workers as [$process, $pipes]) {
                foreach ($pipes as $pipe) {
                    fclose($pipe);
                }
                if (proc_get_status($process)['running']) {
                    proc_terminate($process);
                }
                proc_close($process);
            }
            $this->truncateTablesForAllConnections();
        }
    }
}
