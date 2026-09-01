<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * This page is intentionally gated by exact email, not role - see
 * StoreMenuController. The other admin account on this system must be
 * refused exactly like anyone else.
 */
class StoreMenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/store-menu')->assertRedirect('/login');
    }

    public function test_the_owner_can_view_and_search_the_menu(): void
    {
        $owner = User::factory()->admin()->create(['email' => 'hjbalbiran@gmail.com']);

        $this->actingAs($owner)->get('/store-menu')
            ->assertOk()
            ->assertSee('Red Horse')
            ->assertSee('Gatorade');
    }

    public function test_another_admin_cannot_view_the_menu(): void
    {
        $otherAdmin = User::factory()->admin()->create(['email' => 'someoneelse@gmail.com']);

        $this->actingAs($otherAdmin)->get('/store-menu')->assertNotFound();
    }

    public function test_staff_and_customers_cannot_view_the_menu(): void
    {
        $this->actingAs(User::factory()->staff()->create())->get('/store-menu')->assertNotFound();
        $this->actingAs(User::factory()->customer()->create())->get('/store-menu')->assertNotFound();
    }
}
