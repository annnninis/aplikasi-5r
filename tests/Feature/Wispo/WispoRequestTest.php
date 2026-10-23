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
use Livewire\Volt\Volt;
use Tests\TestCase;

class WispoRequestTest extends TestCase
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

    private function item(int $stock = 10): WispoItem
    {
        $item = WispoItem::factory()->create(['safety_stock_qty' => 0]);
        app(StockService::class)->record($item, WispoTransactionType::Masuk, $stock, $this->petugas);

        return $item;
    }

    private function pending(WispoItem $item, WispoRequestType $type = WispoRequestType::Permintaan, int $qty = 2, ?User $requester = null): WispoRequest
    {
        return app(WispoRequestService::class)->submit(
            $requester ?? User::factory()->create(), $item, $type, UsageCategory::Rutin, $qty, 'Untuk inspeksi rutin',
            $type === WispoRequestType::Peminjaman ? Carbon::parse('2026-11-09') : null,
        );
    }

    public function test_staff_submits_a_loan_from_the_stock_page(): void
    {
        $staff = User::factory()->create();
        $item = $this->item(5);
        $this->actingAs($staff);

        $this->get('/stok')->assertSee(route('wispo.requests.create', $item));

        Volt::test('wispo.request-form', ['item' => $item])
            ->set('request_type', 'peminjaman')
            ->set('qty', 2)
            ->set('usage_category', 'turn_around')
            ->set('purpose', 'Komunikasi tim saat Turn Around')
            ->set('expected_return_at', '2026-11-05')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertRedirect(route('wispo.requests.mine'));

        $request = WispoRequest::sole();
        $this->assertSame(WispoRequestStatus::MenungguPersetujuan, $request->status);
        $this->assertSame(UsageCategory::TurnAround, $request->usage_category);
        $this->assertSame('2026-11-05', $request->expected_return_at->toDateString());
        $this->assertSame(5, $item->refresh()->current_stock_qty, 'Stok belum berkurang sebelum disetujui');

        Notification::assertSentTo($this->petugas, WispoRequestNotification::class, fn ($n) => $n->event === 'submitted');
        Notification::assertNotSentTo($staff, WispoRequestNotification::class);
    }

    public function test_form_validation(): void
    {
        $this->actingAs(User::factory()->create());
        $item = $this->item(3);

        Volt::test('wispo.request-form', ['item' => $item])
            ->set('qty', 4)
            ->set('purpose', '')
            ->set('request_type', 'peminjaman')
            ->set('expected_return_at', '2026-11-01')
            ->call('submit')
            ->assertHasErrors(['qty' => 'max', 'purpose' => 'required', 'expected_return_at' => 'after_or_equal']);

        $this->assertSame(0, WispoRequest::count());
    }

    public function test_approving_a_request_takes_stock_and_logs_a_keluar_transaction(): void
    {
        $item = $this->item(10);
        $request = $this->pending($item, qty: 3);
        $this->actingAs($this->petugas);

        Livewire::test(ListWispoRequests::class)
            ->callTableAction('approve', $request)
            ->assertHasNoTableActionErrors();

        $request->refresh();
        $this->assertSame(WispoRequestStatus::Disetujui, $request->status);
        $this->assertTrue($request->approver->is($this->petugas));
        $this->assertSame(7, $item->refresh()->current_stock_qty);

        $transaction = $request->transactions()->sole();
        $this->assertSame(WispoTransactionType::Keluar, $transaction->transaction_type);
        $this->assertSame(3, $transaction->qty);

        Notification::assertSentTo($request->requester, WispoRequestNotification::class, fn ($n) => $n->event === 'approved');
    }

    public function test_approving_a_loan_marks_it_borrowed(): void
    {
        $item = $this->item(4);
        $request = $this->pending($item, WispoRequestType::Peminjaman, 1);

        app(WispoRequestService::class)->approve($request, $this->petugas);

        $this->assertSame(WispoRequestStatus::Dipinjam, $request->refresh()->status);
        $this->assertSame(3, $item->refresh()->current_stock_qty);
        $this->assertSame(WispoTransactionType::Pinjam, $request->transactions()->sole()->transaction_type);
    }

    public function test_approval_fails_if_stock_ran_out_meanwhile(): void
    {
        $item = $this->item(3);
        $first = $this->pending($item, qty: 3);
        $second = $this->pending($item, qty: 2);
        $this->actingAs($this->petugas);

        app(WispoRequestService::class)->approve($first, $this->petugas);

        Livewire::test(ListWispoRequests::class)
            ->callTableAction('approve', $second)
            ->assertNotified('Tidak bisa disetujui');

        $this->assertSame(WispoRequestStatus::MenungguPersetujuan, $second->refresh()->status);
        $this->assertSame(0, $item->refresh()->current_stock_qty);
    }

    public function test_rejecting_keeps_stock_and_tells_the_requester(): void
    {
        $item = $this->item(5);
        $request = $this->pending($item);
        $this->actingAs($this->petugas);

        Livewire::test(ListWispoRequests::class)
            ->callTableAction('reject', $request, ['reason' => 'Stok dicadangkan untuk shutdown.'])
            ->assertHasNoTableActionErrors();

        $request->refresh();
        $this->assertSame(WispoRequestStatus::Ditolak, $request->status);
        $this->assertSame('Stok dicadangkan untuk shutdown.', $request->rejection_reason);
        $this->assertSame(5, $item->refresh()->current_stock_qty);
        Notification::assertSentTo($request->requester, WispoRequestNotification::class, fn ($n) => $n->event === 'rejected');
    }

    public function test_a_request_can_only_be_decided_once(): void
    {
        $request = $this->pending($this->item());
        app(WispoRequestService::class)->reject($request, $this->petugas, 'Tidak perlu');

        $this->expectException(ValidationException::class);
        app(WispoRequestService::class)->approve($request->refresh(), $this->petugas);
    }

    public function test_only_warehouse_staff_and_admin_can_decide(): void
    {
        $request = $this->pending($this->item());

        $this->actingAs(User::factory()->management()->create());
        $this->get('/admin/permintaan-barang')->assertOk();
        Livewire::test(ListWispoRequests::class)
            ->assertTableActionHidden('approve', $request)
            ->assertTableActionHidden('reject', $request);

        $this->actingAs(User::factory()->koordinator()->create())
            ->get('/admin/permintaan-barang')
            ->assertForbidden();
    }

    public function test_my_requests_shows_only_my_own_with_a_stepper(): void
    {
        $me = User::factory()->create();
        $item = $this->item();
        $mine = $this->pending($item, requester: $me);
        $this->pending($item, requester: User::factory()->create());
        app(WispoRequestService::class)->reject($mine, $this->petugas, 'Alasan khusus saya');

        $this->actingAs($me)
            ->get('/permintaan-saya')
            ->assertOk()
            ->assertSee($item->name)
            ->assertSee('Alasan khusus saya')
            ->assertSee('Ditolak');

        $this->assertSame(1, WispoRequest::where('requester_user_id', $me->id)->count());
    }

    public function test_stepper_states(): void
    {
        $loan = new WispoRequest(['request_type' => WispoRequestType::Peminjaman, 'status' => WispoRequestStatus::Dipinjam]);
        $this->assertSame(['done', 'done', 'active', 'pending'], array_column($loan->steps(), 'state'));

        $late = new WispoRequest(['request_type' => WispoRequestType::Peminjaman, 'status' => WispoRequestStatus::Terlambat]);
        $this->assertSame(['done', 'done', 'failed', 'pending'], array_column($late->steps(), 'state'));

        $rejected = new WispoRequest(['request_type' => WispoRequestType::Permintaan, 'status' => WispoRequestStatus::Ditolak]);
        $this->assertSame(['done', 'failed', 'pending'], array_column($rejected->steps(), 'state'));
        $this->assertSame('Ditolak', $rejected->steps()[1]['label']);

        $done = new WispoRequest(['request_type' => WispoRequestType::Permintaan, 'status' => WispoRequestStatus::Disetujui]);
        $this->assertSame(['done', 'done', 'done'], array_column($done->steps(), 'state'));
    }
}
