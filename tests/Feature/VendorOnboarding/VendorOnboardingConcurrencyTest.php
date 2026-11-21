<?php

namespace Tests\Feature\VendorOnboarding;

use App\Models\User;
use App\Models\VendorApplication;
use App\Services\Vendors\Onboarding\VendorOnboardingService;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class VendorOnboardingConcurrencyTest extends TestCase
{
    // Independent HTTP workers need committed fixtures rather than an outer test transaction.
    use DatabaseTruncation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->beforeApplicationDestroyed(fn () => $this->truncateTablesForAllConnections());
    }

    protected function beforeTruncatingDatabase(): void
    {
        $this->assertSame('testing', app()->environment());
        $this->assertSame('larpshop_testing', DB::connection()->getDatabaseName());
        if (DB::getDriverName() !== 'mysql' || ! function_exists('proc_open')) {
            $this->markTestSkipped('Requires MySQL row locks and proc_open.');
        }
    }

    public function test_two_submissions_create_one_vendor_and_return_the_same_application(): void
    {
        $application = VendorApplication::factory()->complete()->create();
        $request = $this->request($application->user_id, route('vendor-applications.submit', $application, false), ['confirmation' => true]);
        $results = $this->race([$request, $request]);
        $this->assertSame([200, 200], array_column($results, 'status'));
        $this->assertSame($results[0]['body']['application']['vendor_id'], $results[1]['body']['application']['vendor_id']);
        $this->assertDatabaseCount('vendors', 1);
        $this->assertSame('pending', $application->fresh()->vendor->status);
    }

    public function test_conflicting_review_decisions_allow_only_one_winner(): void
    {
        $application = VendorApplication::factory()->complete()->create();
        $application = app(VendorOnboardingService::class)->submit($application->user, $application->getKey());
        $admin = User::factory()->create(['role' => 'admin']);
        $results = $this->race([
            $this->request($admin->getKey(), route('admin.vendors.approve', $application->vendor_id, false)),
            $this->request($admin->getKey(), route('admin.vendors.reject', $application->vendor_id, false), ['rejection_reason' => 'Concurrent review feedback']),
        ]);
        $statuses = array_column($results, 'status');
        sort($statuses);
        $this->assertSame([302, 422], $statuses);
        $application->refresh();
        $this->assertSame($application->status->value, $application->vendor->status);
        $this->assertContains($application->status->value, ['approved', 'rejected']);
        $this->assertSame($admin->getKey(), $application->reviewed_by);
        $this->assertSame($application->status->value === 'rejected' ? 'Concurrent review feedback' : null, $application->rejection_reason);
    }

    public function test_repeated_submission_cannot_undo_a_concurrent_approval(): void
    {
        $application = VendorApplication::factory()->complete()->create();
        $application = app(VendorOnboardingService::class)->submit($application->user, $application->getKey());
        $admin = User::factory()->create(['role' => 'admin']);
        $results = $this->race([
            $this->request($application->user_id, route('vendor-applications.submit', $application, false), ['confirmation' => true]),
            $this->request($admin->getKey(), route('admin.vendors.approve', $application->vendor_id, false)),
        ]);
        $this->assertContains($results[0]['status'], [200, 422]);
        $this->assertSame(302, $results[1]['status']);
        $this->assertSame('approved', $application->fresh()->status->value);
        $this->assertSame('approved', $application->vendor->fresh()->status);
        $this->assertDatabaseCount('vendors', 1);
    }

    public function test_simultaneous_legacy_resume_requests_create_one_linked_application(): void
    {
        $owner = User::factory()->create();
        $vendor = $owner->vendors()->create(['store_name' => 'Concurrent legacy resume', 'status' => 'rejected']);
        $request = $this->request($owner->getKey(), route('profile.vendor.resubmit', $vendor, false));
        $results = $this->race([$request, $request]);
        $this->assertSame([200, 200], array_column($results, 'status'));
        $this->assertSame($results[0]['body']['application']['vendor_application_id'], $results[1]['body']['application']['vendor_application_id']);
        $this->assertDatabaseCount('vendor_applications', 1);
        $this->assertSame('rejected', $vendor->fresh()->status);
    }

    private function request(int $userId, string $path, array $payload = []): array
    {
        return ['user_id' => $userId, 'path' => $path, 'payload' => $payload];
    }

    private function race(array $requests): array
    {
        $workers = [];
        try {
            foreach ($requests as $request) {
                $process = proc_open(
                    [PHP_BINARY, base_path('tests/Support/vendor-onboarding-worker.php')],
                    [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                    $pipes, base_path(), array_merge(getenv(), ['APP_ENV' => 'testing']),
                );
                $this->assertIsResource($process);
                $workers[] = [$process, $pipes];
                stream_set_timeout($pipes[1], 15);
                fwrite($pipes[0], json_encode($request + ['connection' => config('database.connections.mysql')], JSON_THROW_ON_ERROR)."\n");
                fflush($pipes[0]);
            }
            foreach ($workers as [, $pipes]) {
                $this->assertSame("ready\n", fgets($pipes[1]), 'Onboarding worker did not become ready.');
            }
            foreach ($workers as [, $pipes]) {
                fwrite($pipes[0], "go\n");
                fflush($pipes[0]);
            }
            $results = [];
            foreach ($workers as [, $pipes]) {
                $line = fgets($pipes[1]);
                $this->assertIsString($line, 'Onboarding worker timed out.');
                $results[] = json_decode($line, true, flags: JSON_THROW_ON_ERROR);
            }

            return $results;
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
        }
    }
}
