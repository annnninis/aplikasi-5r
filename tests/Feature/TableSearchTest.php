<?php

namespace Tests\Feature;

use App\Enums\FindingPriority;
use App\Enums\UsageCategory;
use App\Enums\WispoRequestStatus;
use App\Enums\WispoRequestType;
use App\Filament\Resources\Findings\Pages\ListFindings;
use App\Filament\Resources\WispoRequests\Pages\ListWispoRequests;
use App\Models\Area;
use App\Models\Finding;
use App\Models\User;
use App\Models\WispoItem;
use App\Models\WispoRequest;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Kotak "Cari" mencari teks yang tampil di tabel, termasuk label enum (prioritas/status).
 */
class TableSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
    }

    public function test_findings_search_matches_priority_status_area_pic_and_reporter(): void
    {
        $this->actingAs(User::factory()->koordinator()->create());
        $pic = User::factory()->create(['name' => 'Joko Susilo']);
        $high = Finding::factory()->assignedTo($pic)->create([
            'priority' => FindingPriority::Tinggi,
            'description' => 'Kabel terkelupas',
            'area_id' => Area::factory()->create(['name' => 'Ruang Panel Listrik'])->id,
        ]);
        $low = Finding::factory()->assignedTo(User::factory()->create(['name' => 'Rina']))->create([
            'priority' => FindingPriority::Rendah,
            'description' => 'Kertas berserakan',
            'reported_by' => User::factory()->create(['name' => 'Bambang Pelapor'])->id,
        ]);

        $list = fn (string $search) => Livewire::test(ListFindings::class)->set('activeTab', 'semua')->searchTable($search);

        $list('tinggi')->assertCanSeeTableRecords([$high])->assertCanNotSeeTableRecords([$low]);
        $list('TINGGI')->assertCanSeeTableRecords([$high]);
        $list('rendah')->assertCanSeeTableRecords([$low])->assertCanNotSeeTableRecords([$high]);
        $list('belum selesai')->assertCanSeeTableRecords([$high, $low]);
        $list('panel listrik')->assertCanSeeTableRecords([$high])->assertCanNotSeeTableRecords([$low]);
        $list('joko')->assertCanSeeTableRecords([$high])->assertCanNotSeeTableRecords([$low]);
        $list('bambang')->assertCanSeeTableRecords([$low])->assertCanNotSeeTableRecords([$high]);
        $list('terkelupas')->assertCanSeeTableRecords([$high])->assertCanNotSeeTableRecords([$low]);
        $list('tidak ada yang cocok')->assertCanNotSeeTableRecords([$high, $low]);
    }

    public function test_request_queue_search_matches_type_usage_status_and_unit(): void
    {
        $this->actingAs(User::factory()->petugasGudang()->create());
        $item = WispoItem::factory()->create(['name' => 'HT Motorola']);
        $make = fn (array $attrs) => WispoRequest::create(array_merge([
            'item_id' => $item->id,
            'request_type' => WispoRequestType::Permintaan,
            'usage_category' => UsageCategory::Rutin,
            'qty' => 1,
            'requester_user_id' => User::factory()->create()->id,
            'purpose' => 'Keperluan rutin',
            'status' => WispoRequestStatus::MenungguPersetujuan,
        ], $attrs));

        $loan = $make([
            'request_type' => WispoRequestType::Peminjaman,
            'usage_category' => UsageCategory::TurnAround,
            'expected_return_at' => now()->addWeek(),
            'requester_user_id' => User::factory()->create(['unit_kerja' => 'Maintenance Mekanik'])->id,
        ]);
        $plain = $make([]);

        $list = fn (string $search) => Livewire::test(ListWispoRequests::class)->set('activeTab', 'semua')->searchTable($search);

        $list('peminjaman')->assertCanSeeTableRecords([$loan])->assertCanNotSeeTableRecords([$plain]);
        $list('turn around')->assertCanSeeTableRecords([$loan])->assertCanNotSeeTableRecords([$plain]);
        $list('mekanik')->assertCanSeeTableRecords([$loan])->assertCanNotSeeTableRecords([$plain]);
        $list('menunggu')->assertCanSeeTableRecords([$loan, $plain]);
        $list('motorola')->assertCanSeeTableRecords([$loan, $plain]);
    }
}
