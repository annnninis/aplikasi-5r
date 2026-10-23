<?php

namespace App\Services;

use App\Enums\Role;
use App\Enums\UsageCategory;
use App\Enums\WispoRequestStatus;
use App\Enums\WispoRequestType;
use App\Enums\WispoTransactionType;
use App\Models\User;
use App\Models\WispoItem;
use App\Models\WispoRequest;
use App\Notifications\WispoRequestNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

/**
 * Satu-satunya tempat transisi status permintaan/peminjaman barang (PRD MVP §4.3).
 * Barang dianggap diserahkan saat disetujui, jadi stok berkurang saat itu (lewat StockService).
 */
class WispoRequestService
{
    public function __construct(private StockService $stock) {}

    public function submit(
        User $requester,
        WispoItem $item,
        WispoRequestType $type,
        UsageCategory $usage,
        int $qty,
        string $purpose,
        ?Carbon $expectedReturn = null,
    ): WispoRequest {
        if (! $item->is_active) {
            throw ValidationException::withMessages(['item' => 'Barang ini sudah tidak aktif.']);
        }

        if ($qty > $item->current_stock_qty) {
            throw ValidationException::withMessages(['qty' => "Stok tersedia hanya {$item->current_stock_qty} {$item->unit}."]);
        }

        if ($type === WispoRequestType::Peminjaman && ($expectedReturn === null || $expectedReturn->lt(today()))) {
            throw ValidationException::withMessages(['expected_return_at' => 'Tanggal rencana kembali wajib diisi dan tidak boleh lewat.']);
        }

        $request = WispoRequest::create([
            'item_id' => $item->id,
            'request_type' => $type,
            'usage_category' => $usage,
            'qty' => $qty,
            'requester_user_id' => $requester->id,
            'purpose' => $purpose,
            'expected_return_at' => $type === WispoRequestType::Peminjaman ? $expectedReturn?->toDateString() : null,
            'status' => WispoRequestStatus::MenungguPersetujuan,
        ]);

        $this->notify($this->warehouseStaff(), $request, 'submitted',
            "{$type->getLabel()} barang baru",
            "{$requester->name} mengajukan {$type->getLabel()} {$qty} {$item->unit} {$item->name}. Silakan setujui atau tolak.",
            $this->panelUrl());

        return $request;
    }

    /**
     * Setujui = barang diserahkan. Permintaan selesai di sini; peminjaman berstatus "Dipinjam".
     */
    public function approve(WispoRequest $request, User $approver): WispoRequest
    {
        DB::transaction(function () use ($request, $approver) {
            $locked = WispoRequest::whereKey($request->getKey())->lockForUpdate()->firstOrFail();
            $this->ensureStatus($locked, [WispoRequestStatus::MenungguPersetujuan]);

            $isLoan = $locked->request_type === WispoRequestType::Peminjaman;

            $this->stock->record(
                $locked->item,
                $isLoan ? WispoTransactionType::Pinjam : WispoTransactionType::Keluar,
                $locked->qty,
                $approver,
                $locked->requester->name,
                $locked->purpose,
                $locked,
            );

            $locked->update([
                'status' => $isLoan ? WispoRequestStatus::Dipinjam : WispoRequestStatus::Disetujui,
                'approved_by' => $approver->id,
                'decided_at' => now(),
            ]);

            $request->setRawAttributes($locked->getAttributes(), true);
        });

        $this->notify(collect([$request->requester]), $request, 'approved',
            "{$request->request_type->getLabel()} disetujui",
            $request->request_type === WispoRequestType::Peminjaman
                ? 'Peminjaman Anda disetujui. Ambil barang di Gudang SPO 3A dan kembalikan sesuai tanggal rencana.'
                : 'Permintaan Anda disetujui. Ambil barang di Gudang SPO 3A.',
            route('wispo.requests.mine'));

        return $request;
    }

    public function reject(WispoRequest $request, User $approver, string $reason): WispoRequest
    {
        $this->ensureStatus($request, [WispoRequestStatus::MenungguPersetujuan]);

        $request->update([
            'status' => WispoRequestStatus::Ditolak,
            'approved_by' => $approver->id,
            'decided_at' => now(),
            'rejection_reason' => $reason,
        ]);

        $this->notify(collect([$request->requester]), $request, 'rejected',
            "{$request->request_type->getLabel()} ditolak",
            "Alasan: {$reason}",
            route('wispo.requests.mine'));

        return $request;
    }

    /**
     * @param  list<WispoRequestStatus>  $allowed
     */
    private function ensureStatus(WispoRequest $request, array $allowed): void
    {
        if (! in_array($request->status, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => "Permintaan berstatus \"{$request->status->getLabel()}\" tidak bisa diproses dengan aksi ini.",
            ]);
        }
    }

    /**
     * @return Collection<int, User>
     */
    private function warehouseStaff(): Collection
    {
        return User::where('is_active', true)
            ->where(fn ($q) => $q->where('is_petugas_gudang', true)->orWhere('role', Role::Admin))
            ->get();
    }

    /**
     * @param  Collection<int, User|null>  $users
     */
    private function notify(Collection $users, WispoRequest $request, string $event, string $subject, string $message, string $url): void
    {
        $recipients = $users->filter(fn (?User $u) => $u?->is_active)->unique('id')->values();

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new WispoRequestNotification($request, $event, $subject, $message, $url));
        }
    }

    private function panelUrl(): string
    {
        return route('filament.admin.resources.permintaan-barang.index');
    }
}
