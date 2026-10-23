<?php

namespace Tests\Feature\Wispo;

use App\Enums\WispoTransactionType;
use App\Models\User;
use App\Models\WispoItem;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class StockPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_user_can_see_active_items_but_not_inactive_ones(): void
    {
        WispoItem::factory()->create(['name' => 'Proyektor Epson']);
        WispoItem::factory()->create(['name' => 'Barang Lama', 'is_active' => false]);

        $this->actingAs(User::factory()->create())
            ->get('/stok')
            ->assertOk()
            ->assertSee('Proyektor Epson')
            ->assertDontSee('Barang Lama');
    }

    public function test_search_category_and_low_stock_filters(): void
    {
        $user = User::factory()->create();
        $ht = WispoItem::factory()->create(['name' => 'HT Motorola', 'category' => 'HT', 'safety_stock_qty' => 1]);
        WispoItem::factory()->create(['name' => 'Kunci Pas 17', 'category' => 'Tools', 'safety_stock_qty' => 1]);
        app(StockService::class)->record($ht, WispoTransactionType::Masuk, 5, $user);
        $this->actingAs($user);

        Volt::test('wispo.stock')
            ->set('search', 'motor')
            ->assertSee('HT Motorola')
            ->assertDontSee('Kunci Pas 17')
            ->set('search', '')
            ->set('category', 'Tools')
            ->assertSee('Kunci Pas 17')
            ->assertDontSee('HT Motorola')
            ->set('category', '')
            ->set('lowOnly', true)
            ->assertSee('Kunci Pas 17')
            ->assertDontSee('HT Motorola');
    }

    public function test_stock_page_has_no_edit_controls(): void
    {
        WispoItem::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get('/stok')
            ->assertDontSee('Ubah')
            ->assertDontSee('/admin/barang');
    }
}
