<?php

namespace Tests\Feature\Wispo;

use App\Enums\UsageCategory;
use App\Enums\WispoRequestStatus;
use App\Enums\WispoRequestType;
use App\Enums\WispoTransactionType;
use App\Filament\Resources\WispoRequests\Pages\ListWispoRequests;
use App\Models\User;
use App\Models\WispoItem;
use App\Models\WispoRequest;
use App\Notifications\WispoRequestNotification;
use App\Services\StockService;
use App\Services\WispoRequestService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class LoanReturnTest extends TestCase
{
    use RefreshDatabase;

    private User $petugas;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
        Notification::fake();
        Carbon::setTestNow('2026-11-02 09:00');
        $this->petugas = User::factory()->petugasGudang()->create();
    }

    private function borrowed(int $stock = 5, int $qty = 2, string $returnDate = '2026-11-05', ?User $borrower = null): WispoRequest
    {
        $item = WispoItem::factory()->create(['safety_stock_qty' => 0]);
        app(StockService::class)->record($item, WispoTransactionType::Masuk, $stock, $this->petugas);

        $service = app(WispoRequestService::class);
        $request = $service->submit($borrower ?? User::factory()->create(), $item, WispoRequestType::Peminjaman,
            UsageCategory::Rutin, $qty, 'Inspeksi', Carbon::parse($returnDate));

        return $service->approve($request, $this->petugas);
    }

    public function test_petugas_confirms_a_return_and_stock_comes_back(): void
    {
        $request = $this->borrowed(stock: 5, qty: 2);
        $this->assertSame(3, $request->item->refresh()->current_stock_qty);
        $this->actingAs($this->petugas);

        Livewire::test(ListWispoRequests::class)
            ->set('activeTab', 'dipinjam')
            ->callTableAction('confirmReturn', $request, ['notes' => 'Baik, lengkap'])
            ->assertHasNoTableActionErrors();

        $request->refresh();
        $this->assertSame(WispoRequestStatus::Dikembalikan, $request->status);
        $this->assertNotNull($request->actual_return_at);
        $this->assertSame('Baik, lengkap', $request->return_notes);
        $this->assertSame(5, $request->item->refresh()->current_stock_qty);
        $this->assertSame(
            [WispoTransactionType::Pinjam, WispoTransactionType::Kembali],
            $request->transactions()->orderBy('id')->get()->pluck('transaction_type')->all(),
        );
        Notification::assertSentTo($request->requester, WispoRequestNotification::class, fn ($n) => $n->event === 'returned');
    }

    public function test_overdue_loans_can_still_be_returned(): void
    {
        $request = $this->borrowed();
        $request->update(['status' => WispoRequestStatus::Terlambat]);

        app(WispoRequestService::class)->confirmReturn($request, $this->petugas);

        $this->assertSame(WispoRequestStatus::Dikembalikan, $request->refresh()->status);
    }

    public function test_a_return_can_only_be_confirmed_once(): void
    {
        $request = $this->borrowed();
        app(WispoRequestService::class)->confirmReturn($request, $this->petugas);

        $this->expectException(ValidationException::class);
        app(WispoRequestService::class)->confirmReturn($request->refresh(), $this->petugas);
    }

    public function test_confirm_return_only_shows_for_borrowed_items_and_warehouse_staff(): void
    {
        $request = $this->borrowed();

        $this->actingAs(User::factory()->management()->create());
        Livewire::test(ListWispoRequests::class)
            ->set('activeTab', 'dipinjam')
            ->assertTableActionHidden('confirmReturn', $request);
    }

    public function test_daily_check_marks_late_loans_and_reminds_due_tomorrow(): void
    {
        $lateBorrower = User::factory()->create();
        $soonBorrower = User::factory()->create();
        $late = $this->borrowed(returnDate: '2026-11-03', borrower: $lateBorrower);
        $soon = $this->borrowed(returnDate: '2026-11-11', borrower: $soonBorrower);
        $today = $this->borrowed(returnDate: '2026-11-10');

        Carbon::setTestNow('2026-11-10 07:00');
        $this->artisan('wispo:daily-check')->assertSuccessful();

        $this->assertSame(WispoRequestStatus::Terlambat, $late->refresh()->status);
        $this->assertSame(WispoRequestStatus::Dipinjam, $soon->refresh()->status);
        $this->assertSame(WispoRequestStatus::Dipinjam, $today->refresh()->status);

        Notification::assertSentTo($lateBorrower, WispoRequestNotification::class, fn ($n) => $n->event === 'overdue');
        Notification::assertSentTo($this->petugas, WispoRequestNotification::class, fn ($n) => $n->event === 'overdue');
        Notification::assertSentTo($soonBorrower, WispoRequestNotification::class, fn ($n) => $n->event === 'due_tomorrow');
    }

    public function test_daily_jobs_are_scheduled(): void
    {
        $this->artisan('schedule:list')
            ->expectsOutputToContain('wispo:daily-check')
            ->expectsOutputToContain('findings:daily-check')
            ->assertSuccessful();
    }
}
