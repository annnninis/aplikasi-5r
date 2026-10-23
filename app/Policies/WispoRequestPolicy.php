<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;
use App\Models\WispoRequest;

class WispoRequestPolicy
{
    /**
     * Antrean semua permintaan di Panel Kerja.
     */
    public function viewAny(User $user): bool
    {
        return $this->decides($user) || $user->hasRole(Role::Management);
    }

    public function view(User $user, WispoRequest $request): bool
    {
        return $this->viewAny($user) || $request->requester_user_id === $user->id;
    }

    /**
     * Ajukan permintaan/peminjaman: semua user aktif.
     */
    public function create(User $user): bool
    {
        return $user->is_active;
    }

    /**
     * Setujui, tolak, dan (Hari 10) konfirmasi pengembalian.
     */
    public function decide(User $user, WispoRequest $request): bool
    {
        return $this->decides($user);
    }

    public function update(User $user, WispoRequest $request): bool
    {
        return false;
    }

    public function delete(User $user, WispoRequest $request): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    private function decides(User $user): bool
    {
        return $user->isAdmin() || $user->isPetugasGudang();
    }
}
