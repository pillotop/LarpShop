<?php

use App\Models\VendorApplication;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

// Each worker has its own PDO connection and operates only on the isolated test database.
require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$input = json_decode(trim(fgets(STDIN)), true, flags: JSON_THROW_ON_ERROR);
if (app()->environment() !== 'testing' || ($input['connection']['database'] ?? '') !== 'larpshop_testing') {
    throw new RuntimeException('Onboarding workers require larpshop_testing.');
}
config(['database.default' => 'mysql', 'database.connections.mysql' => $input['connection']]);
DB::purge('mysql');
// Hold the first writer's row lock briefly so the other process contends for it.
VendorApplication::saving(fn () => usleep(300000));
Auth::loginUsingId($input['user_id']);
fwrite(STDOUT, "ready\n");
fflush(STDOUT);
fgets(STDIN);
$request = Request::create($input['path'], 'POST', $input['payload'] ?? []);
$request->headers->set('Accept', 'application/json');
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request);
fwrite(STDOUT, json_encode([
    'status' => $response->getStatusCode(),
    'body' => json_decode($response->getContent(), true),
])."\n");
$kernel->terminate($request, $response);
