<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;
use App\Models\WispoItem;

/**
 * Master barang WISPO. Semua user bisa melihat stok lewat halaman /stok;
 * kebijakan ini untuk Panel Kerja: kelola = Petugas Gudang & Admin, Management hanya lihat.
 */
class WispoItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->manages($user) || $user->hasRole(Role::Management);
    }

    public function view(User $user, WispoItem $item): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->manages($user);
    }

    public function update(User $user, WispoItem $item): bool
    {
        return $this->manages($user);
    }

    public function delete(User $user, WispoItem $item): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    private function manages(User $user): bool
    {
        return $user->isAdmin() || $user->isPetugasGudang();
    }
}
