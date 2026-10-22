<?php

namespace App\Policies;

use App\Models\Area;
use App\Models\User;

/**
 * Kelola area/zona: hanya Admin. Area tidak dihapus, hanya dinonaktifkan.
 */
class AreaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Area $area): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Area $area): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Area $area): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
