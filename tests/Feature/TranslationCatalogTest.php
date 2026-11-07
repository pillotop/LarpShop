<?php

namespace Tests\Feature;

use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class TranslationCatalogTest extends TestCase
{
    public function test_application_catalogs_have_matching_keys_and_placeholders(): void
    {
        foreach (['common', 'products', 'cart', 'checkout', 'profile', 'orders', 'reviews', 'wishlist', 'footer', 'pagination', 'auth', 'passwords', 'management', 'notifications'] as $domain) {
            $english = Arr::dot(require lang_path("en/{$domain}.php"));
            $vietnamese = Arr::dot(require lang_path("vi/{$domain}.php"));
            $this->assertEqualsCanonicalizing(array_keys($english), array_keys($vietnamese), $domain);

            foreach ($english as $key => $line) {
                $this->assertSame(
                    $this->placeholders($line),
                    $this->placeholders($vietnamese[$key]),
                    "{$domain}.{$key}",
                );
            }
        }
    }

    public function test_vietnamese_validation_rules_use_known_keys_and_preserve_placeholders(): void
    {
        $english = Arr::dot(require lang_path('en/validation.php'));
        $vietnamese = Arr::dot(require lang_path('vi/validation.php'));

        foreach ($vietnamese as $key => $line) {
            // Laravel reserves the empty custom map for per-field overrides.
            if (is_array($line)) {
                continue;
            }
            $this->assertArrayHasKey($key, $english);
            $this->assertSame($this->placeholders($english[$key]), $this->placeholders($line), $key);
        }
    }

    public function test_literal_application_keys_resolve_in_both_languages(): void
    {
        $files = [...File::allFiles(app_path()), ...File::allFiles(resource_path('views'))];
        $keys = [];
        foreach ($files as $file) {
            preg_match_all('/(?:__|trans_choice)\(\s*[\'"]([a-z][a-z0-9_-]*\.[a-z0-9_.-]+)[\'"]\s*[,)]/', $file->getContents(), $matches);
            $keys = [...$keys, ...$matches[1]];
        }
        foreach (array_unique($keys) as $key) {
            $this->assertTrue(app('translator')->hasForLocale($key, 'en'), "Missing English key: {$key}");
            $this->assertTrue(app('translator')->hasForLocale($key, 'vi'), "Missing Vietnamese key: {$key}");
        }
    }

    public function test_all_order_and_payment_statuses_have_display_labels(): void
    {
        foreach (['en', 'vi'] as $locale) {
            foreach (FulfillmentStatus::cases() as $status) {
                $this->assertTrue(app('translator')->hasForLocale('management.fulfillment_labels.'.$status->value, $locale));
            }
            foreach (OrderStatus::cases() as $status) {
                $this->assertTrue(app('translator')->hasForLocale('checkout.order_status.'.$status->value, $locale));
            }
            foreach (PaymentStatus::cases() as $status) {
                $this->assertTrue(app('translator')->hasForLocale('checkout.payment_status.'.$status->value, $locale));
            }
        }
    }

    private function placeholders(string $line): array
    {
        preg_match_all('/:[a-z_]+/i', $line, $matches);
        $placeholders = array_values(array_unique($matches[0]));
        sort($placeholders);

        return $placeholders;
    }
}
