<?php

namespace Tests\Feature\Findings;

use App\Enums\FindingPriority;
use App\Enums\FindingStatus;
use App\Models\Area;
use App\Models\Finding;
use App\Models\User;
use App\Notifications\FindingNotification;
use App\Services\FindingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FindingNotificationsTest extends TestCase
{
    use RefreshDatabase;

    private FindingService $findings;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        Storage::fake('public');
        $this->findings = app(FindingService::class);
    }

    private function assertNotified(User $user, string $event): void
    {
        Notification::assertSentTo($user, FindingNotification::class, fn (FindingNotification $n) => $n->event === $event);
    }

    private function assertNotNotified(User $user, string $event): void
    {
        Notification::assertNotSentTo($user, FindingNotification::class, fn (FindingNotification $n) => $n->event === $event);
    }

    public function test_new_report_notifies_koordinator_and_admin_only(): void
    {
        $koordinator = User::factory()->koordinator()->create();
        $admin = User::factory()->admin()->create();
        $inactiveKoordinator = User::factory()->koordinator()->inactive()->create();
        $staff = User::factory()->create();

        $this->findings->report($staff, Area::factory()->create(), 'Lantai licin', FindingPriority::Tinggi, UploadedFile::fake()->image('a.jpg'));

        $this->assertNotified($koordinator, 'reported');
        $this->assertNotified($admin, 'reported');
        $this->assertNotNotified($inactiveKoordinator, 'reported');
        $this->assertNotNotified($staff, 'reported');
    }

    public function test_assignment_notifies_the_pic(): void
    {
        $pic = User::factory()->create();

        $this->findings->assign(Finding::factory()->create(), $pic, FindingPriority::Sedang);

        $this->assertNotified($pic, 'assigned');
    }

    public function test_rejection_notifies_the_reporter(): void
    {
        $finding = Finding::factory()->create();

        $this->findings->reject($finding, 'Duplikat');

        $this->assertNotified($finding->reporter, 'rejected');
    }

    public function test_submission_verification_and_revision_notify_the_right_people(): void
    {
        $koordinator = User::factory()->koordinator()->create();
        $pic = User::factory()->create();
        $finding = Finding::factory()->assignedTo($pic)->create();

        $this->findings->submitResolution($finding, $pic, UploadedFile::fake()->image('b.jpg'), 'Beres');
        $this->assertNotified($koordinator, 'submitted');

        $this->findings->requestRevision($finding, 'Kurang bersih');
        $this->assertNotified($pic, 'revision');

        $this->findings->submitResolution($finding->refresh(), $pic, UploadedFile::fake()->image('c.jpg'), 'Sudah');
        $this->findings->verify($finding->refresh(), $koordinator);
        $this->assertNotified($pic, 'verified');
        $this->assertNotified($finding->reporter, 'verified');
    }

    public function test_daily_check_marks_overdue_escalates_and_reminds(): void
    {
        Carbon::setTestNow('2026-11-10 07:00');
        $management = User::factory()->management()->create();
        $latePic = User::factory()->create();
        $soonPic = User::factory()->create();

        $late = Finding::factory()->assignedTo($latePic)->create(['deadline' => '2026-11-09']);
        $dueToday = Finding::factory()->assignedTo(User::factory()->create())->create(['deadline' => '2026-11-10']);
        $dueTomorrow = Finding::factory()->assignedTo($soonPic)->create(['deadline' => '2026-11-11']);
        $waiting = Finding::factory()->assignedTo(User::factory()->create())->create([
            'deadline' => '2026-11-01',
            'status' => FindingStatus::MenungguVerifikasi,
        ]);

        $this->artisan('findings:daily-check')->assertSuccessful();

        $this->assertSame(FindingStatus::Terlambat, $late->refresh()->status);
        $this->assertSame(FindingStatus::BelumSelesai, $dueToday->refresh()->status);
        $this->assertSame(FindingStatus::BelumSelesai, $dueTomorrow->refresh()->status);
        $this->assertSame(FindingStatus::MenungguVerifikasi, $waiting->refresh()->status);

        $this->assertNotified($latePic, 'overdue');
        $this->assertNotified($management, 'overdue');
        $this->assertNotified($soonPic, 'due_tomorrow');
    }

    public function test_notification_is_stored_for_the_in_app_bell_and_emailed(): void
    {
        $pic = User::factory()->create();
        $finding = Finding::factory()->create();

        $this->findings->assign($finding, $pic, FindingPriority::Rendah);

        Notification::assertSentTo($pic, FindingNotification::class, function (FindingNotification $n) use ($pic, $finding) {
            $data = $n->toArray($pic);

            return $n->via($pic) === ['mail', 'database']
                && $data['finding_id'] === $finding->id
                && $data['url'] === route('findings.show', $finding)
                && str_contains($n->toMail($pic)->subject, 'PIC');
        });
    }
}
