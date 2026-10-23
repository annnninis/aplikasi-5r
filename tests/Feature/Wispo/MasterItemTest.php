<?php

namespace Tests\Feature\Wispo;

use App\Enums\WispoTransactionType;
use App\Filament\Resources\WispoItems\Pages\CreateWispoItem;
use App\Filament\Resources\WispoItems\Pages\EditWispoItem;
use App\Models\User;
use App\Models\WispoItem;
use App\Services\StockService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MasterItemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
    }

    public function test_master_items_page_access_per_role(): void
    {
        foreach ([User::factory()->petugasGudang()->create(), User::factory()->admin()->create(), User::factory()->management()->create()] as $user) {
            $this->actingAs($user)->get('/admin/barang')->assertOk();
        }

        $this->actingAs(User::factory()->koordinator()->create())->get('/admin/barang')->assertForbidden();
    }

    public function test_management_can_view_but_not_create_items(): void
    {
        $this->actingAs(User::factory()->management()->create())
            ->get('/admin/barang/create')
            ->assertForbidden();
    }

    public function test_petugas_gudang_creates_item_with_auto_code_and_initial_stock(): void
    {
        $petugas = User::factory()->petugasGudang()->create();
        $this->actingAs($petugas);

        Livewire::test(CreateWispoItem::class)
            ->fillForm([
                'name' => 'HT Motorola XiR',
                'category' => 'HT',
                'unit' => 'unit',
                'initial_stock' => 10,
                'safety_stock_qty' => 3,
                'storage_location' => 'Rak A-1',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $item = WispoItem::sole();
        $this->assertMatchesRegularExpression('/^SPO-\d{4}$/', $item->item_code);
        $this->assertSame(10, $item->current_stock_qty);
        $this->assertTrue($item->addedBy->is($petugas));

        $transaction = $item->transactions()->sole();
        $this->assertSame(WispoTransactionType::Masuk, $transaction->transaction_type);
        $this->assertSame(10, $transaction->qty);
        $this->assertSame(10, $transaction->stock_after);
        $this->assertSame('Stok awal', $transaction->notes);
    }

    public function test_item_codes_are_sequential_and_unique(): void
    {
        $codes = WispoItem::factory()->count(3)->create()->pluck('item_code')->map(fn ($c) => (int) substr($c, 4));

        $this->assertSame([$codes[0], $codes[0] + 1, $codes[0] + 2], $codes->all());
    }

    public function test_editing_an_item_can_not_change_its_stock_or_code(): void
    {
        $petugas = User::factory()->petugasGudang()->create();
        $item = WispoItem::factory()->create();
        app(StockService::class)->record($item, WispoTransactionType::Masuk, 5, $petugas);
        $code = $item->item_code;
        $this->actingAs($petugas);

        Livewire::test(EditWispoItem::class, ['record' => $item->getRouteKey()])
            ->fillForm(['name' => 'Nama Baru', 'safety_stock_qty' => 1])
            ->call('save')
            ->assertHasNoFormErrors();

        $item->refresh();
        $this->assertSame('Nama Baru', $item->name);
        $this->assertSame(5, $item->current_stock_qty);
        $this->assertSame($code, $item->item_code);

        $item->update(['current_stock_qty' => 999, 'item_code' => 'HACK']);
        $this->assertSame(5, $item->refresh()->current_stock_qty);
        $this->assertSame($code, $item->item_code);
    }

    public function test_low_stock_is_at_or_below_safety_stock(): void
    {
        $user = User::factory()->create();
        $item = WispoItem::factory()->create(['safety_stock_qty' => 3]);

        app(StockService::class)->record($item, WispoTransactionType::Masuk, 3, $user);
        $this->assertTrue($item->isLowStock());

        app(StockService::class)->record($item, WispoTransactionType::Masuk, 1, $user);
        $this->assertFalse($item->isLowStock());
    }
}
