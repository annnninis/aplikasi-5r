<?php

namespace Tests\Feature\Wispo;

use App\Enums\WispoTransactionType;
use App\Exports\WispoTransactionsExport;
use App\Filament\Pages\Wispo\Widgets\LowStockItems;
use App\Filament\Resources\WispoTransactions\Pages\ListWispoTransactions;
use App\Models\User;
use App\Models\WispoItem;
use App\Models\WispoTransaction;
use App\Services\StockService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class WarehouseSummaryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
    }

    public function test_summary_page_access(): void
    {
        foreach ([User::factory()->petugasGudang()->create(), User::factory()->admin()->create(), User::factory()->management()->create()] as $user) {
            $this->actingAs($user)->get('/admin/ringkasan-gudang')->assertOk();
        }

        $this->actingAs(User::factory()->koordinator()->create())->get('/admin/ringkasan-gudang')->assertForbidden();
    }

    public function test_summary_shows_low_stock_items(): void
    {
        $petugas = User::factory()->petugasGudang()->create();
        $low = WispoItem::factory()->create(['name' => 'Masker Kritis', 'safety_stock_qty' => 10]);
        app(StockService::class)->record($low, WispoTransactionType::Masuk, 2, $petugas);
        $ok = WispoItem::factory()->create(['name' => 'Baterai Banyak', 'safety_stock_qty' => 1]);
        app(StockService::class)->record($ok, WispoTransactionType::Masuk, 50, $petugas);

        Livewire::actingAs($petugas)
            ->test(LowStockItems::class)
            ->assertCanSeeTableRecords([$low])
            ->assertCanNotSeeTableRecords([$ok]);
    }

    public function test_export_downloads_the_filtered_transactions(): void
    {
        Excel::fake();
        $petugas = User::factory()->petugasGudang()->create();
        $a = WispoItem::factory()->create();
        $b = WispoItem::factory()->create();
        app(StockService::class)->record($a, WispoTransactionType::Masuk, 5, $petugas);
        app(StockService::class)->record($a, WispoTransactionType::Keluar, 2, $petugas);
        app(StockService::class)->record($b, WispoTransactionType::Masuk, 9, $petugas);
        $this->actingAs($petugas);

        Livewire::test(ListWispoTransactions::class)
            ->filterTable('item_id', $a->id)
            ->callAction('exportExcel');

        Excel::matchByRegex();
        Excel::assertDownloaded('/riwayat-transaksi-gudang-.*\.xlsx/', function (WispoTransactionsExport $export) use ($a) {
            $rows = $export->query()->get();

            return $rows->count() === 2
                && $rows->every(fn (WispoTransaction $t) => $t->item_id === $a->id)
                && $export->map($rows->firstWhere('transaction_type', WispoTransactionType::Keluar))[6] === -2;
        });
    }
}
