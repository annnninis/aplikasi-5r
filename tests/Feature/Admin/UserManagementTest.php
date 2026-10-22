<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
    }

    public function test_only_admin_can_open_user_management(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/users')
            ->assertOk();

        foreach ([User::factory()->koordinator()->create(), User::factory()->management()->create(), User::factory()->petugasGudang()->create()] as $user) {
            $this->actingAs($user)->get('/admin/users')->assertForbidden();
        }
    }

    public function test_admin_can_create_a_user(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Budi Santoso',
                'email' => 'budi@contoh.test',
                'unit_kerja' => 'Gudang SPO 3A',
                'password' => 'rahasia-123',
                'role' => Role::Staff->value,
                'is_petugas_gudang' => true,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::where('email', 'budi@contoh.test')->firstOrFail();
        $this->assertSame(Role::Staff, $user->role);
        $this->assertTrue($user->is_petugas_gudang);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Hash::check('rahasia-123', $user->password));
    }

    public function test_editing_without_password_keeps_the_old_password(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $user = User::factory()->create();
        $oldHash = $user->password;

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->fillForm(['name' => 'Nama Baru', 'password' => ''])
            ->call('save')
            ->assertHasNoFormErrors();

        $user->refresh();
        $this->assertSame('Nama Baru', $user->name);
        $this->assertSame($oldHash, $user->password);
    }

    public function test_admin_can_deactivate_another_user_but_not_themselves(): void
    {
        $admin = User::factory()->admin()->create();
        $staff = User::factory()->create();
        $this->actingAs($admin);

        Livewire::test(ListUsers::class)
            ->callTableAction('toggleActive', $staff)
            ->assertTableActionHidden('toggleActive', $admin);

        $this->assertFalse($staff->refresh()->is_active);
    }

    public function test_admin_can_not_change_own_role_or_status(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(EditUser::class, ['record' => $admin->getRouteKey()])
            ->assertFormFieldDisabled('role')
            ->assertFormFieldDisabled('is_active');
    }
}
