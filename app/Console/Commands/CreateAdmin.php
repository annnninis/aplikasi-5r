<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/**
 * Membuat akun Admin pertama di server baru (registrasi publik dimatikan).
 */
class CreateAdmin extends Command
{
    protected $signature = 'app:create-admin';

    protected $description = 'Buat akun Admin (dipakai sekali saat setup server)';

    public function handle(): int
    {
        $name = text('Nama lengkap', required: true);
        $email = text('Email', required: true, validate: fn (string $v) => Validator::make(['email' => $v], ['email' => 'email|unique:users,email'])->errors()->first('email') ?: null);
        $password = password('Kata sandi (min. 8 karakter)', required: true, validate: fn (string $v) => strlen($v) < 8 ? 'Minimal 8 karakter.' : null);

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => Role::Admin,
            'is_active' => true,
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();

        $this->info("Admin {$user->email} dibuat. Login di ".url('/login'));

        return self::SUCCESS;
    }
}
