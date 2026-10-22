<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class WorkPanelAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_sent_to_the_single_login_page(): void
    {
        $this->get('/admin')->assertRedirect(route('login'));
        $this->get('/admin/login')->assertRedirect('/login');
    }

    public function test_staff_logging_in_after_visiting_the_panel_lands_on_the_dashboard(): void
    {
        $staff = User::factory()->create();
        $this->get('/admin');

        Volt::test('auth.login')
            ->set('email', $staff->email)
            ->set('password', 'password')
            ->call('login')
            ->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_staff_can_not_open_the_work_panel(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_managing_roles_and_warehouse_staff_can_open_the_work_panel(): void
    {
        $users = [
            User::factory()->admin()->create(),
            User::factory()->koordinator()->create(),
            User::factory()->management()->create(),
            User::factory()->petugasGudang()->create(),
        ];

        foreach ($users as $user) {
            $this->actingAs($user)->get('/admin')->assertOk();
        }
    }

    public function test_work_panel_link_is_only_shown_to_users_who_can_open_it(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertDontSee('Panel Kerja');

        $this->actingAs(User::factory()->admin()->create())
            ->get('/dashboard')
            ->assertSee('Panel Kerja');
    }
}
