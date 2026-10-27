<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileAddressTest extends TestCase
{
    use RefreshDatabase;

    private function payload(string $name = 'Home'): array
    {
        return ['recipient_name' => $name, 'address_line_1' => '123 Test Street', 'city' => 'Test City', 'country_code' => 'vn'];
    }

    public function test_profile_manages_multiple_addresses_and_default_replacement(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('profile'))->assertOk()->assertSee('pane-billing')->assertSee('You have no saved addresses yet.');
        foreach (['Home', 'Office', 'Family'] as $name) {
            $this->post(route('addresses.store'), $this->payload($name))->assertRedirect(route('profile').'#pane-billing');
        }
        $addresses = $user->addresses()->orderBy('address_id')->get();
        $this->assertCount(3, $addresses);
        $this->assertTrue($addresses[0]->is_default);
        $this->assertSame('VN', $addresses[0]->country_code);
        $this->patch(route('addresses.default', $addresses[2]))->assertRedirect();
        $this->assertSame(1, $user->addresses()->where('is_default', true)->count());
        $this->assertTrue($addresses[2]->fresh()->is_default);
        $this->put(route('addresses.update', $addresses[1]), $this->payload('New office'))->assertRedirect();
        $this->get(route('profile'))->assertOk()->assertSee('New office')->assertSee('Family');
        $this->delete(route('addresses.destroy', $addresses[2]))->assertRedirect();
        $this->assertTrue($addresses[0]->fresh()->is_default);
        $this->assertSame(2, $user->addresses()->count());
    }

    public function test_validation_restores_only_the_failed_form_in_billing_tab(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('addresses.store'), $this->payload());
        $address = $user->addresses()->sole();
        $this->put(route('addresses.update', $address), [...$this->payload('Edited name'), 'city' => ''])
            ->assertRedirect(route('profile').'#pane-billing')
            ->assertSessionHasErrors(['city'], null, 'billing')
            ->assertSessionHas('_old_input.address_form', (string) $address->getKey());
        $this->withCookie(config('session.cookie'), session()->getId())->get(route('profile'))->assertOk()->assertSee('value="Edited name"', false)->assertSee('The city field is required.');
        $this->assertSame('Home', $address->fresh()->recipient_name);
    }

    public function test_another_users_addresses_are_hidden_and_cannot_be_changed(): void
    {
        $owner = User::factory()->create();
        $this->actingAs($owner)->post(route('addresses.store'), $this->payload('Private recipient'));
        $address = $owner->addresses()->sole();
        $this->actingAs(User::factory()->create())->get(route('profile'))->assertOk()->assertDontSee('Private recipient');
        $this->put(route('addresses.update', $address), $this->payload('Stolen'))->assertNotFound();
        $this->patch(route('addresses.default', $address))->assertNotFound();
        $this->delete(route('addresses.destroy', $address))->assertNotFound();
        $this->assertSame('Private recipient', $address->fresh()->recipient_name);
        $this->assertTrue($address->fresh()->is_default);
    }

    public function test_guest_cannot_manage_addresses(): void
    {
        $this->post(route('addresses.store'), $this->payload())->assertRedirect(route('login'));
        $this->assertDatabaseCount('addresses', 0);
    }
}
