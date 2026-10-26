<?php

use App\Data\Payments\PaymentSession;
use App\Models\Order;
use App\Services\Payments\FakePaymentGateway;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

// Separate PHP process: no inherited PDO connection or uncommitted test fixtures.
require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$input = json_decode(trim(fgets(STDIN)), true, flags: JSON_THROW_ON_ERROR);
config(['database.default' => 'mysql', 'database.connections.mysql' => $input['connection']]);
DB::purge('mysql');
$app->bind(FakePaymentGateway::class, function () {
    return new class extends FakePaymentGateway
    {
        public function createPayment(Order $order): PaymentSession
        {
            // Keep the first transaction open while the other request contends for it.
            usleep(300000);

            return parent::createPayment($order);
        }
    };
});
Auth::loginUsingId($input['user_id']);
fwrite(STDOUT, "ready\n");
fflush(STDOUT);
fgets(STDIN);
$request = Request::create('/test/payments/orders/'.$input['order_id'], 'POST');
$request->headers->set('Accept', 'application/json');
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request);
fwrite(STDOUT, json_encode(['status' => $response->getStatusCode(), 'body' => json_decode($response->getContent(), true)])."\n");
$kernel->terminate($request, $response);
