<?php

// Read-only HTTP acceptance checks for the isolated, seeded Docker demo.
use App\Models\Product;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

function check(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
    echo "PASS: {$message}\n";
}

function browser(): Client
{
    return new Client([
        'base_uri' => 'http://127.0.0.1:8000',
        'cookies' => new CookieJar,
        'http_errors' => false,
        'allow_redirects' => false,
        'timeout' => 30,
    ]);
}

function login(string $email): Client
{
    $client = browser();
    $html = (string) $client->get('/login')->getBody();
    preg_match('/name="csrf-token" content="([^"]+)"/', $html, $match);
    check(isset($match[1]), "Login page provides CSRF token for {$email}");
    $response = $client->post('/login', ['form_params' => [
        '_token' => html_entity_decode($match[1]),
        'email' => $email,
        'password' => 'password123',
    ]]);
    check($response->getStatusCode() === 302, "Login redirects for {$email}");
    check($client->get('/profile')->getStatusCode() === 200, "Authenticated profile for {$email}");

    return $client;
}

try {
    $client = browser();
    $home = '';
    foreach (['/up', '/', '/products?q=Speaker', '/cart', '/login', '/home/discovery', '/home/promo-sections', '/home/broad-select'] as $path) {
        $response = $client->get($path);
        check($response->getStatusCode() === 200, "HTTP 200 {$path}");
        if ($path === '/') {
            $home = (string) $response->getBody();
        }
    }
    check(! str_contains($home, ':5173') && ! str_contains($home, '@vite/client'), 'Built assets are used without a Vite dev server');

    $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true, flags: JSON_THROW_ON_ERROR);
    $builtFiles = [];
    foreach ($manifest as $entry) {
        foreach (array_merge([$entry['file']], $entry['css'] ?? [], $entry['assets'] ?? []) as $file) {
            $builtFiles[$file] = true;
            if (! is_file(public_path('build/'.$file))) {
                throw new RuntimeException("Missing built file: {$file}");
            }
        }
    }
    check(count($builtFiles) > 0, 'All '.count($builtFiles).' manifest assets exist');
    $asset = $manifest['resources/js/app.js']['file'];
    check($client->get('/build/'.$asset)->getStatusCode() === 200, 'Compiled JavaScript is served over HTTP');

    $product = Product::query()->approved()->active()->where('stock', '>', 0)
        ->whereHas('vendor', fn ($query) => $query->active())->with('images')->firstOrFail();
    check($client->get('/products/'.$product->product_id.'/'.$product->slug)->getStatusCode() === 200, 'Seeded product detail renders');
    $imagePath = $product->images->firstOrFail()->path;
    check(! str_starts_with($imagePath, 'http'), 'Seeded product image is bundled locally');
    $image = $client->get('/storage/'.preg_replace('#^storage/#', '', ltrim($imagePath, '/')));
    check($image->getStatusCode() === 200 && str_starts_with($image->getHeaderLine('Content-Type'), 'image/'), 'Seeded product image is served through public storage');

    $customer = login('customer@example.test');
    check($customer->get('/admin/reports')->getStatusCode() === 403, 'Customer cannot access admin reports');
    $admin = login('demo-admin@example.test');
    foreach (['/admin', '/admin/reports', '/admin/homepage-layout'] as $path) {
        check($admin->get($path)->getStatusCode() === 200, "Admin page renders: {$path}");
    }
    $vendor = login('vendor@example.test');
    foreach (['/vendor/products', '/vendor/orders', '/vendor/reports'] as $path) {
        check($vendor->get($path)->getStatusCode() === 200, "Vendor page renders: {$path}");
    }
    echo "Docker HTTP acceptance checks passed.\n";
} catch (Throwable $exception) {
    fwrite(STDERR, 'FAIL: '.$exception->getMessage()."\n");
    exit(1);
}
