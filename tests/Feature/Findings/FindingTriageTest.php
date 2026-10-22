<?php

namespace Tests\Feature\Findings;

use App\Enums\FindingPriority;
use App\Enums\FindingStatus;
use App\Filament\Resources\Findings\Pages\ListFindings;
use App\Filament\Resources\Findings\Pages\ViewFinding;
use App\Models\Area;
use App\Models\Finding;
use App\Models\User;
use App\Services\FindingService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class FindingTriageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
    }

    public function test_findings_list_is_for_admin_koordinator_and_management_only(): void
    {
        foreach ([User::factory()->admin()->create(), User::factory()->koordinator()->create(), User::factory()->management()->create()] as $user) {
            $this->actingAs($user)->get('/admin/findings')->assertOk();
        }

        $this->actingAs(User::factory()->petugasGudang()->create())
            ->get('/admin/findings')
            ->assertForbidden();
    }

    public function test_koordinator_assigns_pic_and_deadline_follows_priority(): void
    {
        Carbon::setTestNow('2026-11-02 08:00');
        $koordinator = User::factory()->koordinator()->create();
        $pic = User::factory()->create();
        $finding = Finding::factory()->create();
        $this->actingAs($koordinator);

        Livewire::test(ListFindings::class)
            ->callTableAction('assign', $finding, ['pic_id' => $pic->id, 'priority' => FindingPriority::Tinggi->value])
            ->assertHasNoTableActionErrors();

        $finding->refresh();
        $this->assertSame(FindingStatus::BelumSelesai, $finding->status);
        $this->assertTrue($finding->pic->is($pic));
        $this->assertSame('2026-11-03', $finding->deadline->toDateString());
        $this->assertNotNull($finding->assigned_at);
    }

    public function test_assign_form_prefills_the_area_default_pic(): void
    {
        $defaultPic = User::factory()->create();
        $finding = Finding::factory()->for(Area::factory()->create(['default_pic_id' => $defaultPic->id]))->create();
        $this->actingAs(User::factory()->koordinator()->create());

        Livewire::test(ViewFinding::class, ['record' => $finding->getRouteKey()])
            ->mountAction('assign')
            ->assertActionDataSet(['pic_id' => $defaultPic->id]);
    }

    public function test_koordinator_can_reject_a_new_report_with_a_reason(): void
    {
        $finding = Finding::factory()->create();
        $this->actingAs(User::factory()->koordinator()->create());

        Livewire::test(ListFindings::class)
            ->callTableAction('reject', $finding, ['reason' => 'Duplikat laporan kemarin.'])
            ->assertHasNoTableActionErrors();

        $finding->refresh();
        $this->assertSame(FindingStatus::Ditolak, $finding->status);
        $this->assertSame('Duplikat laporan kemarin.', $finding->rejection_reason);
    }

    public function test_management_can_view_but_not_assign_or_reject(): void
    {
        $finding = Finding::factory()->create();
        $this->actingAs(User::factory()->management()->create());

        Livewire::test(ListFindings::class)
            ->assertTableActionHidden('assign', $finding)
            ->assertTableActionHidden('reject', $finding);
    }

    public function test_rejected_or_finished_findings_can_not_be_assigned(): void
    {
        $finding = Finding::factory()->create(['status' => FindingStatus::Ditolak]);

        $this->expectException(ValidationException::class);
        app(FindingService::class)->assign($finding, User::factory()->create(), FindingPriority::Sedang);
    }

    public function test_pic_must_be_an_active_user(): void
    {
        $finding = Finding::factory()->create();

        $this->expectException(ValidationException::class);
        app(FindingService::class)->assign($finding, User::factory()->inactive()->create(), FindingPriority::Sedang);
    }

    public function test_only_new_reports_can_be_rejected(): void
    {
        $finding = Finding::factory()->assignedTo(User::factory()->create())->create();

        $this->expectException(ValidationException::class);
        app(FindingService::class)->reject($finding, 'Tidak valid');
    }
}
