<?php

namespace Tests\Feature;

use App\Enums\FindingPriority;
use App\Enums\FindingStatus;
use App\Exports\FindingsExport;
use App\Filament\Resources\Findings\Pages\ListFindings;
use App\Models\Area;
use App\Models\Finding;
use App\Models\User;
use App\Services\FindingService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Livewire\Volt\Volt;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class HomeAndNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_home_shows_their_own_work_but_no_manager_cards(): void
    {
        $staff = User::factory()->create(['name' => 'Rina Wati']);
        Finding::factory()->assignedTo($staff)->create(['description' => 'Rak arsip berantakan']);

        $this->actingAs($staff)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Halo, Rina')
            ->assertSee('Rak arsip berantakan')
            ->assertDontSee('Laporan baru perlu ditugaskan')
            ->assertDontSee('Permintaan barang menunggu');
    }

    public function test_koordinator_and_warehouse_staff_see_their_task_cards(): void
    {
        Finding::factory()->count(2)->create();

        $this->actingAs(User::factory()->koordinator()->create())
            ->get('/dashboard')
            ->assertSee('Laporan baru perlu ditugaskan')
            ->assertSee('Temuan berjalan');

        $this->actingAs(User::factory()->petugasGudang()->create())
            ->get('/dashboard')
            ->assertSee('Permintaan barang menunggu')
            ->assertSee('Barang stok menipis')
            ->assertDontSee('Laporan baru perlu ditugaskan');
    }

    public function test_room_pics_see_pending_booking_card(): void
    {
        $pic = User::factory()->create();
        Area::factory()->kolaborasi(requiresApproval: true)->create(['default_pic_id' => $pic->id]);

        $this->actingAs($pic)->get('/dashboard')->assertSee('Booking menunggu persetujuan');
    }

    public function test_bell_lists_notifications_and_marks_them_read(): void
    {
        $pic = User::factory()->create();
        $finding = Finding::factory()->create();
        app(FindingService::class)->assign($finding, $pic, FindingPriority::Sedang);
        $this->actingAs($pic);

        $notification = $pic->notifications()->sole();

        Volt::test('notifications.bell')
            ->assertSee('Anda ditunjuk sebagai PIC')
            ->assertSee('1')
            ->call('open', $notification->id)
            ->assertRedirect(route('findings.show', $finding));

        $this->assertNotNull($notification->refresh()->read_at);
    }

    public function test_mark_all_read(): void
    {
        $user = User::factory()->create();
        foreach (Finding::factory()->count(3)->create() as $finding) {
            app(FindingService::class)->assign($finding, $user, FindingPriority::Rendah);
        }
        $this->actingAs($user);

        $this->assertSame(3, $user->unreadNotifications()->count());
        Volt::test('notifications.bell')->call('markAllRead');
        $this->assertSame(0, $user->unreadNotifications()->count());
    }

    public function test_users_can_not_open_someone_elses_notification(): void
    {
        $owner = User::factory()->create();
        app(FindingService::class)->assign(Finding::factory()->create(), $owner, FindingPriority::Rendah);
        $this->actingAs(User::factory()->create());

        $notification = $owner->notifications()->sole();

        Volt::test('notifications.bell')->call('open', $notification->id)->assertNotFound();
        $this->assertNull($notification->refresh()->read_at);
    }

    public function test_findings_export_follows_the_active_tab(): void
    {
        Excel::fake();
        Filament::setCurrentPanel('admin');
        Finding::factory()->count(2)->create();
        Finding::factory()->create(['status' => FindingStatus::Ditolak]);
        $this->actingAs(User::factory()->koordinator()->create());

        Livewire::test(ListFindings::class)->callAction('exportExcel');

        Excel::matchByRegex();
        Excel::assertDownloaded('/temuan-5r-.*\.xlsx/', fn (FindingsExport $export) => $export->query()->count() === 2
            && $export->query()->get()->every(fn (Finding $f) => $f->status === FindingStatus::BelumDitugaskan));
    }
}
