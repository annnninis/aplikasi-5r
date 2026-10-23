<?php

namespace App\Policies;

use App\Models\RoomBooking;
use App\Models\User;

class RoomBookingPolicy
{
    /**
     * Daftar semua booking di Panel Kerja (Hari 12): Admin dan PIC ruangan.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->areasAsDefaultPic()->bookable()->exists();
    }

    public function view(User $user, RoomBooking $booking): bool
    {
        return $booking->requester_user_id === $user->id || $this->decide($user, $booking);
    }

    /**
     * Pesan ruangan: semua user aktif.
     */
    public function create(User $user): bool
    {
        return $user->is_active;
    }

    /**
     * Setujui/tolak: Admin atau PIC ruangan tersebut.
     */
    public function decide(User $user, RoomBooking $booking): bool
    {
        return $user->isAdmin() || $booking->area->default_pic_id === $user->id;
    }

    /**
     * Batalkan: pemesan sendiri.
     */
    public function cancel(User $user, RoomBooking $booking): bool
    {
        return $booking->requester_user_id === $user->id;
    }

    public function update(User $user, RoomBooking $booking): bool
    {
        return false;
    }

    public function delete(User $user, RoomBooking $booking): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
