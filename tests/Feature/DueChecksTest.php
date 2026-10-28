<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\FindingStatus;
use App\Enums\WispoRequestStatus;
use App\Enums\WispoRequestType;
use App\Http\Middleware\RunDueChecks;
use App\Models\Area;
use App\Models\Finding;
use App\Models\RoomBooking;
use App\Models\User;
use App\Models\WispoItem;
use App\Models\WispoRequest;
use App\Notifications\FindingNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Status terlambat/kedaluwarsa ikut diperbarui saat website dibuka, tanpa menunggu scheduler.
 */
class DueChecksTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        Carbon::setTestNow('2026-10-28 19:00');
        Cache::forget(RunDueChecks::CACHE_KEY);
    }

    public function test_opening_the_home_page_marks_overdue_findings_and_shows_them(): void
    {
        $pic = User::factory()->create();
        $late = Finding::factory()->assignedTo($pic)->create(['deadline' => '2026-10-23']);
        $notYet = Finding::factory()->assignedTo($pic)->create(['deadline' => '2026-10-28']);
        $koordinator = User::factory()->koordinator()->create();

        $this->actingAs($koordinator)
            ->get('/dashboard')
            ->assertOk()
            ->assertSeeInOrder(['Temuan terlambat', '1']);

        $this->assertSame(FindingStatus::Terlambat, $late->refresh()->status);
        $this->assertSame(FindingStatus::BelumSelesai, $notYet->refresh()->status, 'Deadline hari ini belum terlambat');
        Notification::assertSentTo($pic, FindingNotification::class, fn ($n) => $n->event === 'overdue');
    }

    public function test_overdue_loans_and_stale_bookings_are_updated_too(): void
    {
        $item = WispoItem::factory()->create();
        $loan = WispoRequest::create([
            'item_id' => $item->id,
            'request_type' => WispoRequestType::Peminjaman,
            'usage_category' => 'rutin',
            'qty' => 1,
            'requester_user_id' => User::factory()->create()->id,
            'purpose' => 'Inspeksi',
            'expected_return_at' => '2026-10-27',
            'status' => WispoRequestStatus::Dipinjam,
        ]);
        $booking = RoomBooking::create([
            'area_id' => Area::factory()->kolaborasi(requiresApproval: true)->create()->id,
            'requester_user_id' => User::factory()->create()->id,
            'purpose' => 'Rapat',
            'participant_count' => 3,
            'start_time' => '2026-10-28 10:00',
            'end_time' => '2026-10-28 11:00',
            'approval_status' => BookingStatus::MenungguPersetujuan,
        ]);

        $this->actingAs(User::factory()->create())->get('/stok')->assertOk();

        $this->assertSame(WispoRequestStatus::Terlambat, $loan->refresh()->status);
        $this->assertSame(BookingStatus::Kedaluwarsa, $booking->refresh()->approval_status);
    }

    public function test_work_panel_pages_also_run_the_checks(): void
    {
        $late = Finding::factory()->assignedTo(User::factory()->create())->create(['deadline' => '2026-10-20']);

        $this->actingAs(User::factory()->koordinator()->create())->get('/admin/findings')->assertOk();

        $this->assertSame(FindingStatus::Terlambat, $late->refresh()->status);
    }

    public function test_checks_run_at_most_once_per_interval_and_never_notify_twice(): void
    {
        $pic = User::factory()->create();
        Finding::factory()->assignedTo($pic)->create(['deadline' => '2026-10-23']);
        $this->actingAs($pic);

        $this->get('/dashboard');
        $second = Finding::factory()->assignedTo($pic)->create(['deadline' => '2026-10-22']);
        $this->get('/dashboard');

        $this->assertSame(FindingStatus::BelumSelesai, $second->refresh()->status, 'Masih dalam jeda 5 menit');

        Carbon::setTestNow(now()->addMinutes(RunDueChecks::INTERVAL_MINUTES + 1));
        $this->get('/dashboard');

        $this->assertSame(FindingStatus::Terlambat, $second->refresh()->status);
        // Satu notifikasi "terlambat" per temuan, walau halaman dibuka berkali-kali.
        Notification::assertSentToTimes($pic, FindingNotification::class, 2);
    }
}
