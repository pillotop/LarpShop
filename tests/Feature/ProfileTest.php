<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_accessing_profile(): void
    {
        $response = $this->get('/profile');

        $response->assertRedirect('/login');
    }

    public function test_customer_can_view_profile_page(): void
    {
        $user = User::factory()->create([
            'username' => 'customerhero',
            'email' => 'hero@larpshop.test',
            'phone_number' => '0912345678',
            'role' => 'customer',
        ]);

        $response = $this->actingAs($user)->get('/profile');

        $response
            ->assertOk()
            ->assertSee('customerhero')
            ->assertSee('hero@larpshop.test')
            ->assertSee('0912345678')
            ->assertSee('Khách hàng (Customer)');
    }

    public function test_vendor_can_view_profile_page_with_store_details(): void
    {
        $user = User::factory()->vendor()->create([
            'username' => 'blacksmith',
            'email' => 'blacksmith@larpshop.test',
        ]);

        $vendor = Vendor::create([
            'user_id' => $user->user_id,
            'store_name' => 'Dragon Forge Armory',
            'description' => 'Handcrafted foam swords and plate armor.',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get('/profile');

        $response
            ->assertOk()
            ->assertSee('Dragon Forge Armory')
            ->assertSee('Handcrafted foam swords and plate armor.')
            ->assertSee('Người bán (Vendor)');
    }

    public function test_customer_can_update_profile_information(): void
    {
        $user = User::factory()->create([
            'username' => 'initialuser',
            'email' => 'initial@larpshop.test',
            'phone_number' => '0911111111',
        ]);

        $response = $this->actingAs($user)->put('/profile', [
            'username' => 'updateduser',
            'email' => 'updated@larpshop.test',
            'phone_number' => '0922222222',
        ]);

        $response
            ->assertRedirect(route('profile'))
            ->assertSessionHas('success');

        $user->refresh();

        $this->assertSame('updateduser', $user->username);
        $this->assertSame('updated@larpshop.test', $user->email);
        $this->assertSame('0922222222', $user->phone_number);
    }

    public function test_update_profile_validates_unique_username_and_email(): void
    {
        $userA = User::factory()->create([
            'username' => 'user_alpha',
            'email' => 'alpha@larpshop.test',
        ]);

        $userB = User::factory()->create([
            'username' => 'user_beta',
            'email' => 'beta@larpshop.test',
        ]);

        // Attempt to update userB with userA's username and email
        $response = $this->actingAs($userB)->put('/profile', [
            'username' => 'user_alpha',
            'email' => 'alpha@larpshop.test',
            'phone_number' => '0933333333',
        ]);

        $response->assertSessionHasErrors(['username', 'email']);

        $userB->refresh();
        $this->assertSame('user_beta', $userB->username);
        $this->assertSame('beta@larpshop.test', $userB->email);
    }

    public function test_vendor_can_update_store_information(): void
    {
        $user = User::factory()->vendor()->create();

        $vendor = Vendor::create([
            'user_id' => $user->user_id,
            'store_name' => 'Old Forge Name',
            'description' => 'Old description text.',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->put('/profile/vendor', [
            'store_name' => 'Mythic Armory Crafts',
            'description' => 'High-grade LARP battle shields and leather boots.',
        ]);

        $response
            ->assertRedirect(route('profile'))
            ->assertSessionHas('success');

        $vendor->refresh();

        $this->assertSame('Mythic Armory Crafts', $vendor->store_name);
        $this->assertSame('High-grade LARP battle shields and leather boots.', $vendor->description);
    }

    public function test_customer_can_register_as_vendor(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
        ]);

        $response = $this->actingAs($customer)->post('/profile/vendor/register', [
            'store_name' => 'Elven Woodwork Workshop',
            'description' => 'Bow staves and custom quivers.',
        ]);

        $response
            ->assertRedirect(route('profile'))
            ->assertSessionHas('success');

        $customer->refresh();

        $this->assertSame('customer', $customer->role);
        $this->assertNotNull($customer->vendor);
        $this->assertSame('Elven Woodwork Workshop', $customer->vendor->store_name);
        $this->assertSame('Bow staves and custom quivers.', $customer->vendor->description);
        $this->assertSame('pending', $customer->vendor->status);
    }

    public function test_customer_vendor_registration_validates_store_name(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $response = $this->actingAs($customer)->post('/profile/vendor/register', [
            'store_name' => '',
            'description' => 'Some description',
        ]);

        $response->assertSessionHasErrors('store_name');
    }

    public function test_customer_vendor_registration_requires_unique_store_name(): void
    {
        $existingVendorUser = User::factory()->vendor()->create();
        Vendor::create([
            'user_id' => $existingVendorUser->user_id,
            'store_name' => 'Existing Unique Armory',
            'status' => 'active',
        ]);

        $customer = User::factory()->create(['role' => 'customer']);

        $response = $this->actingAs($customer)->post('/profile/vendor/register', [
            'store_name' => 'Existing Unique Armory',
            'description' => 'Trying to use the same name',
        ]);

        $response->assertSessionHasErrors('store_name');
    }

    public function test_user_cannot_register_vendor_twice(): void
    {
        $user = User::factory()->vendor()->create();
        Vendor::create([
            'user_id' => $user->user_id,
            'store_name' => 'First Store',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->post('/profile/vendor/register', [
            'store_name' => 'Second Store Attempt',
        ]);

        $response
            ->assertRedirect(route('profile'))
            ->assertSessionHas('warning');
    }

    public function test_user_can_update_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('current-password-123'),
        ]);

        $response = $this->actingAs($user)->put('/profile/password', [
            'current_password' => 'current-password-123',
            'password' => 'brand-new-password-789',
            'password_confirmation' => 'brand-new-password-789',
        ]);

        $response
            ->assertRedirect(route('profile'))
            ->assertSessionHas('success');

        $this->assertTrue(Hash::check('brand-new-password-789', $user->refresh()->password));
    }

    public function test_password_update_fails_with_incorrect_current_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('correct-password-123'),
        ]);

        $response = $this->actingAs($user)->put('/profile/password', [
            'current_password' => 'wrong-pass',
            'password' => 'brand-new-password-789',
            'password_confirmation' => 'brand-new-password-789',
        ]);

        $response->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('correct-password-123', $user->refresh()->password));
    }
}
