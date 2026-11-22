<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_history_requires_authentication_and_opens_profile_purchases(): void
    {
        $this->get(route('orders.index'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get(route('orders.index'))
            ->assertRedirect(route('profile').'#pane-purchases');
    }

    public function test_order_history_json_remains_paginated_and_scoped_to_the_owner(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $owned = $user->orders()->create(['status' => OrderStatus::Pending, 'total_amount' => '12.50']);
        $other->orders()->create(['status' => OrderStatus::Paid, 'total_amount' => '25.00']);

        $this->actingAs($user)->getJson(route('orders.index'))
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.order_id', $owned->getKey());
    }
}
