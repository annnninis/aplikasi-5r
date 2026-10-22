<?php

namespace Tests\Feature\Findings;

use App\Enums\FindingPhotoType;
use App\Enums\FindingPriority;
use App\Enums\FindingStatus;
use App\Models\Area;
use App\Models\Finding;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;
use Tests\TestCase;

class QuickReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_any_user_can_open_quick_report(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/lapor')
            ->assertOk()
            ->assertSee('Lapor Cepat');
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/lapor')->assertRedirect(route('login'));
    }

    public function test_staff_can_report_a_finding_with_a_photo(): void
    {
        $staff = User::factory()->create();
        $area = Area::factory()->create();
        $this->actingAs($staff);

        Volt::test('findings.quick-report')
            ->set('photo', UploadedFile::fake()->image('kardus.jpg'))
            ->set('area_id', $area->id)
            ->set('description', 'Kardus bekas menumpuk di samping lemari arsip.')
            ->set('priority', 'tinggi')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSet('description', '')
            ->assertNotSet('lastReportId', null);

        $finding = Finding::sole();
        $this->assertSame(FindingStatus::BelumDitugaskan, $finding->status);
        $this->assertSame(FindingPriority::Tinggi, $finding->priority);
        $this->assertTrue($finding->reporter->is($staff));
        $this->assertNull($finding->pic_id);

        $photo = $finding->photos()->sole();
        $this->assertSame(FindingPhotoType::Before, $photo->type);
        Storage::disk('public')->assertExists($photo->photo_path);
    }

    public function test_photo_area_and_description_are_required(): void
    {
        $this->actingAs(User::factory()->create());

        Volt::test('findings.quick-report')
            ->call('submit')
            ->assertHasErrors(['photo' => 'required', 'area_id' => 'required', 'description' => 'required']);

        $this->assertSame(0, Finding::count());
    }

    public function test_only_images_are_accepted(): void
    {
        $this->actingAs(User::factory()->create());

        Volt::test('findings.quick-report')
            ->set('photo', UploadedFile::fake()->create('laporan.pdf', 100, 'application/pdf'))
            ->set('area_id', Area::factory()->create()->id)
            ->set('description', 'Deskripsi yang cukup panjang.')
            ->call('submit')
            ->assertHasErrors(['photo' => 'image']);
    }

    public function test_inactive_areas_can_not_be_reported(): void
    {
        $this->actingAs(User::factory()->create());

        Volt::test('findings.quick-report')
            ->set('photo', UploadedFile::fake()->image('foto.jpg'))
            ->set('area_id', Area::factory()->create(['is_active' => false])->id)
            ->set('description', 'Deskripsi yang cukup panjang.')
            ->call('submit')
            ->assertHasErrors('area_id');

        $this->assertSame(0, Finding::count());
    }
}
