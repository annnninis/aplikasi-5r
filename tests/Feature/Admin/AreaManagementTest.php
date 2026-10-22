<?php

namespace Tests\Feature\Admin;

use App\Enums\ZoneCategory;
use App\Filament\Resources\Areas\Pages\CreateArea;
use App\Models\Area;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AreaManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
    }

    public function test_only_admin_can_open_area_management(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/areas')
            ->assertOk();

        $this->actingAs(User::factory()->koordinator()->create())
            ->get('/admin/areas')
            ->assertForbidden();
    }

    public function test_admin_can_create_a_meeting_room_with_booking_settings(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $pic = User::factory()->create();

        Livewire::test(CreateArea::class)
            ->fillForm([
                'name' => 'Ruang Rapat Baru',
                'zone_category' => ZoneCategory::Kolaborasi->value,
                'focus_5r' => 'Rapi, Rawat',
                'default_pic_id' => $pic->id,
                'capacity' => 20,
                'facilities' => 'TV',
                'requires_approval' => true,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $area = Area::where('name', 'Ruang Rapat Baru')->firstOrFail();
        $this->assertTrue($area->isBookable());
        $this->assertSame(20, $area->capacity);
        $this->assertTrue($area->defaultPic->is($pic));
    }

    public function test_meeting_room_requires_capacity(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(CreateArea::class)
            ->fillForm([
                'name' => 'Ruang Tanpa Kapasitas',
                'zone_category' => ZoneCategory::Kolaborasi->value,
                'capacity' => null,
            ])
            ->call('create')
            ->assertHasFormErrors(['capacity' => 'required']);
    }

    public function test_area_names_must_be_unique(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Area::factory()->create(['name' => 'Kantor SPO']);

        Livewire::test(CreateArea::class)
            ->fillForm([
                'name' => 'Kantor SPO',
                'zone_category' => ZoneCategory::Operasional->value,
            ])
            ->call('create')
            ->assertHasFormErrors(['name' => 'unique']);
    }
}
