<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Area;
use App\Models\RoomBooking;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Pengaman di level database (PostgreSQL) yang tetap berlaku walaupun validasi aplikasi terlewati.
 */
class DatabaseConstraintsTest extends TestCase
{
    use RefreshDatabase;

    private function booking(Area $area, string $start, string $end, BookingStatus $status = BookingStatus::Disetujui): RoomBooking
    {
        return RoomBooking::create([
            'area_id' => $area->id,
            'requester_user_id' => User::factory()->create()->id,
            'purpose' => 'Rapat',
            'participant_count' => 5,
            'start_time' => Carbon::parse($start),
            'end_time' => Carbon::parse($end),
            'approval_status' => $status,
        ]);
    }

    public function test_overlapping_active_bookings_in_the_same_room_are_rejected(): void
    {
        $room = Area::factory()->kolaborasi()->create();
        $this->booking($room, '2026-11-02 09:00', '2026-11-02 10:00');

        $this->expectException(QueryException::class);
        $this->booking($room, '2026-11-02 09:30', '2026-11-02 11:00', BookingStatus::MenungguPersetujuan);
    }

    public function test_back_to_back_bookings_other_rooms_and_cancelled_bookings_are_allowed(): void
    {
        $room = Area::factory()->kolaborasi()->create();
        $otherRoom = Area::factory()->kolaborasi()->create();

        $this->booking($room, '2026-11-02 09:00', '2026-11-02 10:00');
        $this->booking($room, '2026-11-02 10:00', '2026-11-02 11:00');
        $this->booking($otherRoom, '2026-11-02 09:00', '2026-11-02 10:00');
        $this->booking($room, '2026-11-02 09:00', '2026-11-02 10:00', BookingStatus::Dibatalkan);

        $this->assertSame(4, RoomBooking::count());
    }

    public function test_booking_must_end_after_it_starts(): void
    {
        $room = Area::factory()->kolaborasi()->create();

        $this->expectException(QueryException::class);
        $this->booking($room, '2026-11-02 10:00', '2026-11-02 09:00');
    }
}
