<?php

namespace Tests\Feature\Bookings;

use App\Enums\BookingStatus;
use App\Models\Area;
use App\Models\RoomBooking;
use App\Models\User;
use App\Notifications\BookingNotification;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Volt;
use Tests\TestCase;

class BookingApprovalTest extends TestCase
{
    use RefreshDatabase;

    private BookingService $bookings;

    private User $pic;

    private Area $meetingRoom;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        Carbon::setTestNow('2026-11-02 08:00');
        $this->bookings = app(BookingService::class);
        $this->pic = User::factory()->create(['name' => 'PIC Rapat']);
        $this->meetingRoom = Area::factory()->kolaborasi(requiresApproval: true)->create(['default_pic_id' => $this->pic->id]);
    }

    private function book(?Area $room = null, string $start = '2026-11-03 09:00', string $end = '2026-11-03 10:00', ?User $user = null): RoomBooking
    {
        return $this->bookings->book($user ?? User::factory()->create(), $room ?? $this->meetingRoom, Carbon::parse($start), Carbon::parse($end), 'Rapat', 4);
    }

    private function notified(User $user, string $event): void
    {
        Notification::assertSentTo($user, BookingNotification::class, fn (BookingNotification $n) => $n->event === $event);
    }

    public function test_pending_booking_notifies_the_room_pic_and_admins(): void
    {
        $admin = User::factory()->admin()->create();
        $otherStaff = User::factory()->create();

        $this->book();

        $this->notified($this->pic, 'submitted');
        $this->notified($admin, 'submitted');
        Notification::assertNotSentTo($otherStaff, BookingNotification::class);
    }

    public function test_instant_booking_sends_a_confirmation(): void
    {
        $booker = User::factory()->create();
        $this->book(Area::factory()->kolaborasi()->create(), user: $booker);

        $this->notified($booker, 'confirmed');
    }

    public function test_room_pic_approves_from_the_approvals_page(): void
    {
        $booking = $this->book();
        $this->actingAs($this->pic);

        $this->get('/booking/persetujuan')->assertOk()->assertSee('Rapat');

        Volt::test('bookings.approvals')->call('approve', $booking->id)->assertHasNoErrors();

        $booking->refresh();
        $this->assertSame(BookingStatus::Disetujui, $booking->approval_status);
        $this->assertTrue($booking->approver->is($this->pic));
        $this->notified($booking->requester, 'approved');
    }

    public function test_rejection_needs_a_reason_and_frees_the_slot(): void
    {
        $booking = $this->book();
        $this->actingAs($this->pic);

        Volt::test('bookings.approvals')
            ->call('startReject', $booking->id)
            ->call('reject')
            ->assertHasErrors(['reason' => 'required'])
            ->set('reason', 'Ruangan dipakai direksi')
            ->call('reject')
            ->assertHasNoErrors();

        $this->assertSame(BookingStatus::Ditolak, $booking->refresh()->approval_status);
        $this->notified($booking->requester, 'rejected');
        $this->book();
        $this->assertSame(2, RoomBooking::count());
    }

    public function test_pic_of_another_room_can_not_decide(): void
    {
        $booking = $this->book();
        $otherPic = User::factory()->create();
        Area::factory()->kolaborasi(requiresApproval: true)->create(['default_pic_id' => $otherPic->id]);
        $this->actingAs($otherPic);

        Volt::test('bookings.approvals')->assertDontSee($booking->purpose.' ')->call('approve', $booking->id)->assertForbidden();
        $this->assertSame(BookingStatus::MenungguPersetujuan, $booking->refresh()->approval_status);
    }

    public function test_approvals_page_is_only_for_admins_and_pics_of_rooms_that_need_approval(): void
    {
        $this->actingAs(User::factory()->create())->get('/booking/persetujuan')->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get('/booking/persetujuan')->assertOk();
        $this->actingAs($this->pic)->get('/booking/persetujuan')->assertOk();

        // PIC ruangan yang bisa langsung dipesan tidak punya apa pun untuk disetujui.
        $freeRoomPic = User::factory()->create();
        Area::factory()->kolaborasi()->create(['default_pic_id' => $freeRoomPic->id]);
        $this->actingAs($freeRoomPic)->get('/booking/persetujuan')->assertForbidden();
        $this->actingAs($freeRoomPic)->get('/dashboard')->assertDontSee('Persetujuan Booking');
    }

    public function test_room_pic_and_admin_bookings_in_an_approval_room_are_approved_immediately(): void
    {
        $mine = $this->book(user: $this->pic);
        $this->assertSame(BookingStatus::Disetujui, $mine->approval_status);
        $this->assertTrue($mine->approver->is($this->pic));
        $this->notified($this->pic, 'confirmed');

        $admin = User::factory()->admin()->create();
        $adminBooking = $this->book(start: '2026-11-03 13:00', end: '2026-11-03 14:00', user: $admin);
        $this->assertSame(BookingStatus::Disetujui, $adminBooking->approval_status);

        $staffBooking = $this->book(start: '2026-11-03 15:00', end: '2026-11-03 16:00');
        $this->assertSame(BookingStatus::MenungguPersetujuan, $staffBooking->approval_status);
    }

    public function test_time_options_are_every_15_minutes_within_opening_hours(): void
    {
        $this->actingAs(User::factory()->create());

        $html = $this->get('/booking')->assertOk()->getContent();

        $this->assertStringContainsString('value="06:00"', $html);
        $this->assertStringContainsString('value="09:45"', $html);
        $this->assertStringContainsString('value="22:00"', $html);
        $this->assertStringNotContainsString('value="09:50"', $html);
        $this->assertStringNotContainsString('value="05:45"', $html);
    }

    public function test_booker_cancels_an_upcoming_booking(): void
    {
        $booker = User::factory()->create();
        $booking = $this->book(Area::factory()->kolaborasi()->create(), user: $booker);
        $this->actingAs($booker);

        $this->get('/booking-saya')->assertOk()->assertSee('Batalkan');

        Volt::test('bookings.mine')->call('cancel', $booking->id)->assertHasNoErrors();

        $this->assertSame(BookingStatus::Dibatalkan, $booking->refresh()->approval_status);
    }

    public function test_others_can_not_cancel_and_started_bookings_can_not_be_cancelled(): void
    {
        $booking = $this->book(Area::factory()->kolaborasi()->create());

        $this->actingAs(User::factory()->create());
        Volt::test('bookings.mine')->call('cancel', $booking->id)->assertForbidden();

        Carbon::setTestNow('2026-11-03 09:30');
        $this->expectException(ValidationException::class);
        $this->bookings->cancel($booking->refresh());
    }

    public function test_stale_pending_bookings_expire_and_reminders_go_out(): void
    {
        $staleBooker = User::factory()->create();
        $stale = $this->book(user: $staleBooker, start: '2026-11-02 09:00', end: '2026-11-02 10:00');
        $tomorrowBooker = User::factory()->create();
        $tomorrow = $this->book(Area::factory()->kolaborasi()->create(), '2026-11-03 13:00', '2026-11-03 14:00', $tomorrowBooker);

        Carbon::setTestNow('2026-11-02 09:05');
        $this->artisan('bookings:check --remind')->assertSuccessful();

        $this->assertSame(BookingStatus::Kedaluwarsa, $stale->refresh()->approval_status);
        $this->assertSame(BookingStatus::Disetujui, $tomorrow->refresh()->approval_status);
        $this->notified($staleBooker, 'expired');
        $this->notified($tomorrowBooker, 'reminder');
    }

    public function test_expired_bookings_can_not_be_approved_late(): void
    {
        $booking = $this->book(start: '2026-11-02 09:00', end: '2026-11-02 10:00');
        Carbon::setTestNow('2026-11-02 09:30');

        $this->expectException(ValidationException::class);
        $this->bookings->approve($booking, $this->pic);
    }

    public function test_past_approved_bookings_show_as_finished_and_expired_ones_as_expired(): void
    {
        $booker = User::factory()->create();
        $used = $this->book(Area::factory()->kolaborasi()->create(), '2026-11-02 09:00', '2026-11-02 10:00', $booker);
        $expired = $this->book(start: '2026-11-02 13:00', end: '2026-11-02 14:00', user: $booker);

        Carbon::setTestNow('2026-11-02 15:00');
        $this->actingAs($booker);

        // Lewat HTTP supaya pemeriksaan kedaluwarsa (middleware RunDueChecks) ikut berjalan.
        $this->get('/booking-saya?tab=riwayat')
            ->assertOk()
            ->assertSee('Selesai Dipakai')
            ->assertSee('Kedaluwarsa');

        $this->assertSame(BookingStatus::Kedaluwarsa, $expired->refresh()->approval_status);
        $this->assertTrue($used->refresh()->isFinished());
    }

    public function test_stepper_reflects_the_booking_journey(): void
    {
        $booking = $this->book();
        $this->assertSame(['done', 'active', 'pending', 'pending'], array_column($booking->steps(), 'state'));

        $this->bookings->approve($booking, $this->pic);
        $this->assertSame(['done', 'done', 'done', 'active'], array_column($booking->refresh()->steps(), 'state'));

        Carbon::setTestNow('2026-11-03 11:00');
        $this->assertSame(['done', 'done', 'done', 'done'], array_column($booking->refresh()->steps(), 'state'));

        $instant = $this->book(Area::factory()->kolaborasi()->create(), '2026-11-04 09:00', '2026-11-04 10:00');
        $this->bookings->cancel($instant);
        $steps = $instant->refresh()->steps();
        $this->assertSame(['done', 'done', 'failed'], array_column($steps, 'state'));
        $this->assertSame('Dibatalkan', $steps[2]['label']);
    }
}
