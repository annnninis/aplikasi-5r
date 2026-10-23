<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Area;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SetupCommandsTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_admin_command(): void
    {
        $this->artisan('app:create-admin')
            ->expectsQuestion('Nama lengkap', 'Admin Departemen')
            ->expectsQuestion('Email', 'admin@kantor.test')
            ->expectsQuestion('Kata sandi (min. 8 karakter)', 'rahasia-kuat-1')
            ->assertSuccessful();

        $admin = User::where('email', 'admin@kantor.test')->sole();
        $this->assertSame(Role::Admin, $admin->role);
        $this->assertTrue(Hash::check('rahasia-kuat-1', $admin->password));
        $this->assertTrue($admin->canAccessWorkPanel());
    }

    public function test_production_seeding_creates_areas_but_no_demo_accounts(): void
    {
        $this->app['env'] = 'production';

        $this->artisan('db:seed', ['--force' => true])->assertSuccessful();

        $this->assertSame(15, Area::count());
        $this->assertSame(0, User::count());
    }
}
