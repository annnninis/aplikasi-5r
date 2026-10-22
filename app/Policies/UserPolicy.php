<?php

namespace App\Policies;

use App\Models\User;

/**
 * Kelola pengguna: hanya Admin. Pengguna tidak pernah dihapus, hanya dinonaktifkan.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, User $model): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, User $model): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, User $model): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
