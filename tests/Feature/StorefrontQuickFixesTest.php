<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CreatesCartFixtures;
use Tests\TestCase;

class StorefrontQuickFixesTest extends TestCase
{
    use CreatesCartFixtures, RefreshDatabase;

    public function test_unauthenticated_and_non_owner_users_cannot_upload_product_images(): void
    {
        Storage::fake('public');
        $product = $this->product();
        $otherUser = User::factory()->create();
        Vendor::create([
            'user_id' => $otherUser->getKey(),
            'store_name' => 'Other Store',
            'status' => 'approved',
        ]);
        $file = UploadedFile::fake()->create('hack.jpg', 100, 'image/jpeg');

        $this->post(route('products.images.store', $product), ['image' => $file])
            ->assertRedirect(route('login'));

        $this->actingAs($otherUser)
            ->post(route('products.images.store', $product), ['image' => $file])
            ->assertForbidden();
    }

    public function test_cart_summary_displays_delivery_total_and_privacy_link(): void
    {
        $user = User::factory()->create();
        $product = $this->product(price: '25.50', stock: 5);
        $this->cartWith($user, [[$product, 2]]);

        $this->actingAs($user)
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee('$51.00')
            ->assertSee('Free')
            ->assertSee(route('privacy'), false);
    }

    public function test_homepage_uses_valid_home_and_living_category_slug(): void
    {
        $this->withoutVite();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('/categories/home-and-living', false)
            ->assertDontSee('/categories/home-living', false);
    }
}
