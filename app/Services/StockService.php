<?php

namespace App\Services;

use App\Enums\Role;
use App\Enums\WispoTransactionType;
use App\Models\User;
use App\Models\WispoItem;
use App\Models\WispoRequest;
use App\Models\WispoTransaction;
use App\Notifications\LowStockNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

/**
 * SATU-SATUNYA tempat yang mengubah wispo_items.current_stock_qty (lihat CLAUDE.md).
 * Setiap perubahan dikunci per baris barang dan dicatat di wispo_transactions.
 */
class StockService
{
    public function record(
        WispoItem $item,
        WispoTransactionType $type,
        int $qty,
        User $recordedBy,
        ?string $sourceOrDestination = null,
        ?string $notes = null,
        ?WispoRequest $request = null,
        ?Carbon $date = null,
    ): WispoTransaction {
        if ($qty <= 0) {
            throw ValidationException::withMessages(['qty' => 'Jumlah harus lebih dari 0.']);
        }

        [$transaction, $becameLow] = DB::transaction(function () use ($item, $type, $qty, $recordedBy, $sourceOrDestination, $notes, $request, $date) {
            $locked = WispoItem::whereKey($item->getKey())->lockForUpdate()->firstOrFail();
            $wasLow = $locked->isLowStock();

            $newStock = $type->increasesStock()
                ? $locked->current_stock_qty + $qty
                : $locked->current_stock_qty - $qty;

            if ($newStock < 0) {
                throw ValidationException::withMessages([
                    'qty' => "Stok {$locked->name} tidak cukup. Tersedia {$locked->current_stock_qty} {$locked->unit}.",
                ]);
            }

            $locked->forceFill(['current_stock_qty' => $newStock])->save();

            $transaction = WispoTransaction::create([
                'item_id' => $locked->id,
                'transaction_type' => $type,
                'qty' => $qty,
                'stock_after' => $newStock,
                'transaction_date' => ($date ?? today())->toDateString(),
                'source_or_destination' => $sourceOrDestination,
                'request_id' => $request?->id,
                'notes' => $notes,
                'recorded_by' => $recordedBy->id,
            ]);

            $item->setRawAttributes($locked->getAttributes(), true);

            return [$transaction, ! $wasLow && $locked->isLowStock()];
        });

        if ($becameLow) {
            $this->notifyLowStock($item);
        }

        return $transaction;
    }

    /**
     * Kirim sekali saat stok baru melewati batas (bukan di setiap transaksi berikutnya).
     */
    private function notifyLowStock(WispoItem $item): void
    {
        $recipients = User::where('is_active', true)
            ->where(fn ($q) => $q->where('is_petugas_gudang', true)->orWhere('role', Role::Admin))
            ->get();

        Notification::send($recipients, new LowStockNotification($item));
    }
}
