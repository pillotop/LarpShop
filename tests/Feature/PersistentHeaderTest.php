<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class PersistentHeaderTest extends TestCase
{
    public function test_guest_without_vendor_can_render_settings_without_storefront_forms(): void
    {
        $this->blade('<x-persistent-header />')
            ->assertSee('id="language"', false)
            ->assertDontSee('id="storefront-settings"', false);
    }

    public function test_authenticated_user_without_vendor_does_not_get_storefront_forms(): void
    {
        $user = new User;
        $user->user_id = 10;
        $this->actingAs($user);

        $this->blade('<x-persistent-header />')
            ->assertDontSee('id="storefront-settings"', false);
    }

    public function test_guest_viewing_a_store_does_not_get_storefront_forms(): void
    {
        $vendor = new Vendor(['user_id' => 10]);

        $this->blade('<x-persistent-header :vendor="$vendor" />', ['vendor' => $vendor])
            ->assertDontSee('id="storefront-settings"', false);
    }

    public function test_another_customer_does_not_get_storefront_forms(): void
    {
        $user = new User;
        $user->user_id = 20;
        $this->actingAs($user);
        $vendor = new Vendor(['user_id' => 10]);

        $this->blade('<x-persistent-header :vendor="$vendor" />', ['vendor' => $vendor])
            ->assertDontSee('id="storefront-settings"', false);
    }

    public function test_owner_gets_forms_with_the_correct_vendor_route(): void
    {
        $user = new User;
        $user->user_id = 10;
        $this->actingAs($user);
        $vendor = new Vendor(['user_id' => 10, 'store_name' => 'Test Store']);
        $vendor->vendor_id = 42;
        $this->withoutVite();
        view()->share('errors', new ViewErrorBag);

        $this->blade('<x-persistent-header :vendor="$vendor" />', ['vendor' => $vendor])
            ->assertSee('id="storefront-settings"', false)
            ->assertSee(route('vendor.store.name.update', $vendor), false);
    }
}
