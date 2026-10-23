<?php

namespace Tests\Feature\Bookings;

use App\Enums\BookingStatus;
use App\Models\Area;
use App\Models\RoomBooking;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Volt;
use Tests\TestCase;

class RoomBookingTest extends TestCase
{
    use RefreshDatabase;

    private BookingService $bookings;

    private Area $room;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-02 08:00');
        $this->bookings = app(BookingService::class);
        $this->room = Area::factory()->kolaborasi(capacity: 10)->create(['name' => 'Ruang Sharing']);
    }

    private function book(string $start, string $end, ?Area $room = null, int $participants = 5, ?User $user = null): RoomBooking
    {
        return $this->bookings->book(
            $user ?? User::factory()->create(),
            $room ?? $this->room,
            Carbon::parse($start),
            Carbon::parse($end),
            'Rapat koordinasi',
            $participants,
        );
    }

    private function assertRejected(callable $attempt, string $field, string $messagePart): void
    {
        try {
            $attempt();
            $this->fail("Booking seharusnya ditolak ({$messagePart}).");
        } catch (ValidationException $e) {
            $this->assertArrayHasKey($field, $e->errors());
            $this->assertStringContainsString($messagePart, $e->errors()[$field][0]);
        }
    }

    public function test_booking_a_free_slot_is_confirmed_immediately(): void
    {
        $booking = $this->book('2026-11-02 09:00', '2026-11-02 10:00');

        $this->assertSame(BookingStatus::Disetujui, $booking->approval_status);
    }

    public function test_rooms_that_require_approval_start_as_pending(): void
    {
        $room = Area::factory()->kolaborasi(requiresApproval: true)->create();

        $this->assertSame(BookingStatus::MenungguPersetujuan, $this->book('2026-11-02 09:00', '2026-11-02 10:00', $room)->approval_status);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function overlappingSlots(): array
    {
        return [
            'sama persis' => ['09:00', '10:00'],
            'mulai di tengah' => ['09:30', '10:30'],
            'selesai di tengah' => ['08:30', '09:15'],
            'di dalam' => ['09:15', '09:45'],
            'membungkus' => ['08:30', '10:30'],
        ];
    }

    /**
     * @dataProvider overlappingSlots
     */
    public function test_overlapping_bookings_in_the_same_room_are_rejected(string $start, string $end): void
    {
        $owner = User::factory()->create(['name' => 'Budi']);
        $this->book('2026-11-02 09:00', '2026-11-02 10:00', user: $owner);

        $this->assertRejected(fn () => $this->book("2026-11-02 {$start}", "2026-11-02 {$end}"), 'start_time', 'Bentrok dengan booking Budi, 09:00–10:00');
        $this->assertSame(1, RoomBooking::count());
    }

    public function test_pending_bookings_also_block_the_slot(): void
    {
        $room = Area::factory()->kolaborasi(requiresApproval: true)->create();
        $this->book('2026-11-02 13:00', '2026-11-02 14:00', $room);

        $this->assertRejected(fn () => $this->book('2026-11-02 13:30', '2026-11-02 14:30', $room), 'start_time', 'menunggu persetujuan');
    }

    public function test_back_to_back_other_rooms_and_cancelled_slots_are_allowed(): void
    {
        $this->book('2026-11-02 09:00', '2026-11-02 10:00');
        $this->book('2026-11-02 10:00', '2026-11-02 11:00');
        $this->book('2026-11-02 09:00', '2026-11-02 10:00', Area::factory()->kolaborasi()->create());

        $cancelled = $this->book('2026-11-02 14:00', '2026-11-02 15:00');
        $cancelled->update(['approval_status' => BookingStatus::Dibatalkan]);
        $this->book('2026-11-02 14:00', '2026-11-02 15:00');

        $this->assertSame(5, RoomBooking::count());
    }

    public function test_database_constraint_is_mapped_to_a_friendly_error(): void
    {
        $this->book('2026-11-02 09:00', '2026-11-02 10:00');

        // Simulasikan dua pemesanan bersamaan yang lolos pengecekan aplikasi.
        $service = new class extends BookingService
        {
            public function findClash(Area $area, Carbon $start, Carbon $end, ?RoomBooking $ignore = null): ?RoomBooking
            {
                return null;
            }
        };

        $this->assertRejected(
            fn () => $service->book(User::factory()->create(), $this->room, Carbon::parse('2026-11-02 09:30'), Carbon::parse('2026-11-02 10:30'), 'Rapat', 3),
            'start_time',
            'baru saja dipesan orang lain',
        );
    }

    public function test_slot_rules(): void
    {
        $this->assertRejected(fn () => $this->book('2026-11-02 10:00', '2026-11-02 09:00'), 'end_time', 'setelah jam mulai');
        $this->assertRejected(fn () => $this->book('2026-11-02 21:00', '2026-11-03 08:00'), 'end_time', 'hari yang sama');
        $this->assertRejected(fn () => $this->book('2026-11-03 05:00', '2026-11-03 07:00'), 'start_time', 'antara 06.00 dan 22.00');
        $this->assertRejected(fn () => $this->book('2026-11-01 09:00', '2026-11-01 10:00'), 'start_time', 'sudah lewat');
        $this->assertRejected(fn () => $this->book('2026-11-02 09:00', '2026-11-02 10:00', participants: 11), 'participant_count', 'hanya 10 orang');
    }

    public function test_only_active_collaboration_rooms_can_be_booked(): void
    {
        $office = Area::factory()->create();
        $closed = Area::factory()->kolaborasi()->create(['is_active' => false]);

        $this->assertRejected(fn () => $this->book('2026-11-02 09:00', '2026-11-02 10:00', $office), 'area_id', 'tidak bisa dipesan');
        $this->assertRejected(fn () => $this->book('2026-11-02 09:00', '2026-11-02 10:00', $closed), 'area_id', 'tidak bisa dipesan');
    }

    public function test_staff_books_from_the_page_and_sees_the_clash_message(): void
    {
        $staff = User::factory()->create();
        $this->actingAs($staff);

        $this->get('/booking')->assertOk()->assertSee('Ruang Sharing');

        Volt::test('bookings.index')
            ->assertSet('room', $this->room->id)
            ->call('pickSlot', '2026-11-02T09:00:00+07:00', '2026-11-02T10:30:00+07:00')
            ->assertSet('start_time', '09:00')
            ->assertSet('end_time', '10:30')
            ->set('purpose', 'Rapat shift')
            ->set('participant_count', 6)
            ->call('book')
            ->assertHasNoErrors()
            ->assertSet('savedMessage', 'Booking terkonfirmasi.')
            ->assertDispatched('booking-saved');

        $booking = RoomBooking::sole();
        $this->assertTrue($booking->requester->is($staff));
        $this->assertSame('2026-11-02 09:00', $booking->start_time->format('Y-m-d H:i'));

        Volt::test('bookings.index')
            ->set('date', '2026-11-02')
            ->set('start_time', '10:00')
            ->set('end_time', '11:00')
            ->set('purpose', 'Rapat lain')
            ->set('participant_count', 3)
            ->call('book')
            ->assertHasErrors('start_time');
    }

    public function test_calendar_events_show_the_selected_room_only(): void
    {
        $me = User::factory()->create();
        $this->book('2026-11-02 09:00', '2026-11-02 10:00', user: $me);
        $this->book('2026-11-02 11:00', '2026-11-02 12:00', user: User::factory()->create(['name' => 'Sari']));
        $this->book('2026-11-02 09:00', '2026-11-02 10:00', Area::factory()->kolaborasi()->create());
        $this->actingAs($me);

        $events = collect(Volt::test('bookings.index')
            ->set('room', $this->room->id)
            ->instance()
            ->events('2026-11-02T00:00:00+07:00', '2026-11-03T00:00:00+07:00'));

        $this->assertCount(2, $events);
        $this->assertSame('Booking Anda: Rapat koordinasi', $events->firstWhere('backgroundColor', '#16a34a')['title']);
        $this->assertSame('Sari', $events->firstWhere('backgroundColor', '#2563eb')['title']);
    }
}
