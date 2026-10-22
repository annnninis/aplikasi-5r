<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Finding;
use App\Models\User;

class FindingPolicy
{
    /**
     * Daftar semua temuan di Panel Kerja.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(Role::Admin, Role::Koordinator5R, Role::Management);
    }

    public function view(User $user, Finding $finding): bool
    {
        return $this->viewAny($user)
            || $finding->reported_by === $user->id
            || $finding->pic_id === $user->id;
    }

    /**
     * Lapor Cepat: semua user aktif.
     */
    public function create(User $user): bool
    {
        return $user->is_active;
    }

    /**
     * Tunjuk/ganti PIC, tolak laporan, dan (Hari 6) verifikasi.
     */
    public function manage(User $user, Finding $finding): bool
    {
        return $user->hasRole(Role::Admin, Role::Koordinator5R);
    }

    /**
     * Kirim tindak lanjut (foto sesudah): hanya PIC temuan.
     */
    public function resolve(User $user, Finding $finding): bool
    {
        return $finding->pic_id === $user->id;
    }

    public function update(User $user, Finding $finding): bool
    {
        return false;
    }

    public function delete(User $user, Finding $finding): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
