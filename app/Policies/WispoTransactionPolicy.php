<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;
use App\Models\WispoTransaction;

/**
 * Riwayat transaksi gudang. Transaksi tidak pernah diubah atau dihapus (jejak audit);
 * koreksi dilakukan dengan transaksi baru.
 */
class WispoTransactionPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->records($user) || $user->hasRole(Role::Management);
    }

    public function view(User $user, WispoTransaction $transaction): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->records($user);
    }

    public function update(User $user, WispoTransaction $transaction): bool
    {
        return false;
    }

    public function delete(User $user, WispoTransaction $transaction): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    private function records(User $user): bool
    {
        return $user->isAdmin() || $user->isPetugasGudang();
    }
}
