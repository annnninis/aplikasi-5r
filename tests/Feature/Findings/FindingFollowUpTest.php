<?php

namespace Tests\Feature\Findings;

use App\Enums\FindingPhotoType;
use App\Enums\FindingStatus;
use App\Filament\Resources\Findings\Pages\ListFindings;
use App\Filament\Resources\Findings\Pages\ViewFinding;
use App\Models\Finding;
use App\Models\User;
use App\Services\FindingService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Livewire\Volt\Volt;
use Tests\TestCase;

class FindingFollowUpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Filament::setCurrentPanel('admin');
    }

    public function test_pic_sees_assigned_findings_on_my_findings_page(): void
    {
        $pic = User::factory()->create();
        Finding::factory()->assignedTo($pic)->create(['description' => 'Kabel berserakan di bawah meja']);
        Finding::factory()->assignedTo(User::factory()->create())->create(['description' => 'Milik orang lain']);

        $this->actingAs($pic)
            ->get('/temuan-saya')
            ->assertOk()
            ->assertSee('Kabel berserakan di bawah meja')
            ->assertDontSee('Milik orang lain');
    }

    public function test_pic_submits_after_photo_and_note(): void
    {
        $pic = User::factory()->create();
        $finding = Finding::factory()->assignedTo($pic)->create();
        $this->actingAs($pic);

        Volt::test('findings.show', ['finding' => $finding])
            ->set('afterPhoto', UploadedFile::fake()->image('sesudah.jpg'))
            ->set('note', 'Kardus sudah dibuang.')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSet('justSubmitted', true);

        $finding->refresh();
        $this->assertSame(FindingStatus::MenungguVerifikasi, $finding->status);
        $this->assertSame('Kardus sudah dibuang.', $finding->resolution_note);
        $after = $finding->photos()->where('type', FindingPhotoType::After)->sole();
        Storage::disk('public')->assertExists($after->photo_path);
    }

    public function test_overdue_findings_can_still_be_submitted(): void
    {
        $pic = User::factory()->create();
        $finding = Finding::factory()->assignedTo($pic)->create(['status' => FindingStatus::Terlambat]);
        $this->actingAs($pic);

        Volt::test('findings.show', ['finding' => $finding])
            ->set('afterPhoto', UploadedFile::fake()->image('sesudah.jpg'))
            ->set('note', 'Sudah diperbaiki.')
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame(FindingStatus::MenungguVerifikasi, $finding->refresh()->status);
    }

    public function test_only_the_pic_can_submit_a_resolution(): void
    {
        $finding = Finding::factory()->assignedTo(User::factory()->create())->create();
        $reporter = $finding->reporter;
        $this->actingAs($reporter);

        Volt::test('findings.show', ['finding' => $finding])
            ->assertDontSee('Kirim tindak lanjut')
            ->set('afterPhoto', UploadedFile::fake()->image('sesudah.jpg'))
            ->set('note', 'Coba kirim.')
            ->call('submit')
            ->assertForbidden();
    }

    public function test_unrelated_staff_can_not_open_a_finding(): void
    {
        $finding = Finding::factory()->assignedTo(User::factory()->create())->create();

        $this->actingAs(User::factory()->create())
            ->get(route('findings.show', $finding))
            ->assertForbidden();
    }

    public function test_reporter_and_koordinator_can_open_a_finding(): void
    {
        $finding = Finding::factory()->create();

        $this->actingAs($finding->reporter)->get(route('findings.show', $finding))->assertOk();
        $this->actingAs(User::factory()->koordinator()->create())->get(route('findings.show', $finding))->assertOk();
    }

    public function test_koordinator_verifies_a_submitted_finding(): void
    {
        $koordinator = User::factory()->koordinator()->create();
        $finding = Finding::factory()->assignedTo(User::factory()->create())->create(['status' => FindingStatus::MenungguVerifikasi]);
        $this->actingAs($koordinator);

        Livewire::test(ViewFinding::class, ['record' => $finding->getRouteKey()])
            ->callAction('verify')
            ->assertHasNoActionErrors();

        $finding->refresh();
        $this->assertSame(FindingStatus::Selesai, $finding->status);
        $this->assertTrue($finding->verifier->is($koordinator));
        $this->assertNotNull($finding->resolved_at);
    }

    public function test_koordinator_requests_revision_with_a_note(): void
    {
        Carbon::setTestNow('2026-11-02 08:00');
        $finding = Finding::factory()->assignedTo(User::factory()->create())->create([
            'status' => FindingStatus::MenungguVerifikasi,
            'deadline' => '2026-11-05',
        ]);
        $this->actingAs(User::factory()->koordinator()->create());

        Livewire::test(ListFindings::class)
            ->set('activeTab', 'semua')
            ->callTableAction('requestRevision', $finding, ['note' => 'Masih ada kardus di pojok.'])
            ->assertHasNoTableActionErrors();

        $finding->refresh();
        $this->assertSame(FindingStatus::BelumSelesai, $finding->status);
        $this->assertSame('Masih ada kardus di pojok.', $finding->revision_note);
    }

    public function test_revision_after_the_deadline_goes_straight_to_overdue(): void
    {
        Carbon::setTestNow('2026-11-10 08:00');
        $finding = Finding::factory()->assignedTo(User::factory()->create())->create([
            'status' => FindingStatus::MenungguVerifikasi,
            'deadline' => '2026-11-05',
        ]);

        app(FindingService::class)->requestRevision($finding, 'Belum bersih.');

        $this->assertSame(FindingStatus::Terlambat, $finding->refresh()->status);
    }

    public function test_verify_and_revision_are_hidden_until_the_pic_submits(): void
    {
        $finding = Finding::factory()->assignedTo(User::factory()->create())->create();
        $this->actingAs(User::factory()->koordinator()->create());

        Livewire::test(ListFindings::class)
            ->set('activeTab', 'semua')
            ->assertTableActionHidden('verify', $finding)
            ->assertTableActionHidden('requestRevision', $finding);
    }
}
