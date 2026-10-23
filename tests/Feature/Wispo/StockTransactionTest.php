<?php

namespace Tests\Feature\Wispo;

use App\Enums\WispoTransactionType;
use App\Filament\Resources\WispoItems\Pages\ViewWispoItem;
use App\Filament\Resources\WispoItems\RelationManagers\TransactionsRelationManager;
use App\Filament\Resources\WispoTransactions\Pages\ListWispoTransactions;
use App\Models\User;
use App\Models\WispoItem;
use App\Notifications\LowStockNotification;
use App\Services\StockService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class StockTransactionTest extends TestCase
{
    use RefreshDatabase;

    private User $petugas;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
        Notification::fake();
        $this->petugas = User::factory()->petugasGudang()->create();
    }

    private function itemWithStock(int $stock, int $safety = 0): WispoItem
    {
        $item = WispoItem::factory()->create(['safety_stock_qty' => $safety]);
        if ($stock > 0) {
            app(StockService::class)->record($item, WispoTransactionType::Masuk, $stock, $this->petugas);
        }

        return $item;
    }

    public function test_masuk_keluar_reject_change_stock_and_are_logged(): void
    {
        $item = $this->itemWithStock(10);
        $stock = app(StockService::class);

        $stock->record($item, WispoTransactionType::Keluar, 3, $this->petugas, 'Tim Shift A');
        $stock->record($item, WispoTransactionType::Reject, 2, $this->petugas, notes: 'Pecah');
        $stock->record($item, WispoTransactionType::Masuk, 5, $this->petugas, 'Vendor');

        $this->assertSame(10, $item->refresh()->current_stock_qty);
        $this->assertSame([10, 7, 5, 10], $item->transactions()->orderBy('id')->pluck('stock_after')->all());
        $this->assertSame(4, $item->transactions()->count());
    }

    public function test_stock_can_not_go_negative(): void
    {
        $item = $this->itemWithStock(2);

        try {
            app(StockService::class)->record($item, WispoTransactionType::Keluar, 3, $this->petugas);
            $this->fail('Stok minus seharusnya ditolak.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('tidak cukup', $e->errors()['qty'][0]);
        }

        $this->assertSame(2, $item->refresh()->current_stock_qty);
        $this->assertSame(1, $item->transactions()->count());
    }

    public function test_petugas_records_a_transaction_from_the_transactions_page(): void
    {
        $item = $this->itemWithStock(10);
        $this->actingAs($this->petugas);

        Livewire::test(ListWispoTransactions::class)
            ->callAction('recordTransaction', [
                'item_id' => $item->id,
                'transaction_type' => 'keluar',
                'qty' => 4,
                'transaction_date' => today()->toDateString(),
                'source_or_destination' => 'Tim Maintenance',
            ])
            ->assertHasNoActionErrors();

        $this->assertSame(6, $item->refresh()->current_stock_qty);
        $this->assertSame('Tim Maintenance', $item->transactions()->latest()->first()->source_or_destination);
    }

    public function test_petugas_records_a_transaction_from_the_item_history(): void
    {
        $item = $this->itemWithStock(5);
        $this->actingAs($this->petugas);

        Livewire::test(TransactionsRelationManager::class, ['ownerRecord' => $item, 'pageClass' => ViewWispoItem::class])
            ->callTableAction('recordTransaction', data: [
                'transaction_type' => 'masuk',
                'qty' => 7,
                'transaction_date' => today()->toDateString(),
            ])
            ->assertHasNoTableActionErrors();

        $this->assertSame(12, $item->refresh()->current_stock_qty);
    }

    public function test_recording_more_than_available_shows_an_error(): void
    {
        $item = $this->itemWithStock(1);
        $this->actingAs($this->petugas);

        Livewire::test(ListWispoTransactions::class)
            ->callAction('recordTransaction', [
                'item_id' => $item->id,
                'transaction_type' => 'reject',
                'qty' => 5,
                'transaction_date' => today()->toDateString(),
            ])
            ->assertNotified('Transaksi tidak bisa disimpan');

        $this->assertSame(1, $item->refresh()->current_stock_qty);
    }

    public function test_management_sees_history_but_can_not_record(): void
    {
        $this->actingAs(User::factory()->management()->create());

        $this->get('/admin/transaksi-gudang')->assertOk();
        Livewire::test(ListWispoTransactions::class)->assertActionHidden('recordTransaction');
    }

    public function test_koordinator_and_staff_can_not_open_transactions(): void
    {
        $this->actingAs(User::factory()->koordinator()->create())->get('/admin/transaksi-gudang')->assertForbidden();
    }

    public function test_low_stock_notifies_warehouse_staff_and_admins_once(): void
    {
        $admin = User::factory()->admin()->create();
        $staff = User::factory()->create();
        $item = $this->itemWithStock(10, safety: 3);
        $stock = app(StockService::class);

        $stock->record($item, WispoTransactionType::Keluar, 5, $this->petugas);
        Notification::assertNothingSent();

        $stock->record($item, WispoTransactionType::Keluar, 2, $this->petugas);
        Notification::assertSentTo([$this->petugas, $admin], LowStockNotification::class);
        Notification::assertNotSentTo($staff, LowStockNotification::class);

        $stock->record($item, WispoTransactionType::Keluar, 1, $this->petugas);
        Notification::assertSentToTimes($this->petugas, LowStockNotification::class, 1);

        $stock->record($item, WispoTransactionType::Masuk, 10, $this->petugas);
        $stock->record($item, WispoTransactionType::Keluar, 9, $this->petugas);
        Notification::assertSentToTimes($this->petugas, LowStockNotification::class, 2);
    }
}
