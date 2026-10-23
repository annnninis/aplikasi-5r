<?php

namespace App\Notifications;

use App\Models\WispoItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Stok barang baru saja turun sampai/di bawah safety stock (PRD MVP B6).
 */
class LowStockNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public WispoItem $item) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $item = $this->item;

        return (new MailMessage)
            ->subject("[5R] Stok menipis: {$item->name}")
            ->greeting("Halo, {$notifiable->name}")
            ->line("Stok {$item->name} ({$item->item_code}) tinggal {$item->current_stock_qty} {$item->unit}.")
            ->line("Batas minimum (safety stock): {$item->safety_stock_qty} {$item->unit}.")
            ->line('Segera lakukan restock sebelum barang habis.')
            ->action('Lihat Barang', $this->url())
            ->salutation('Salam, '.config('app.name'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'item_id' => $this->item->id,
            'event' => 'low_stock',
            'title' => "Stok menipis: {$this->item->name}",
            'message' => "Sisa {$this->item->current_stock_qty} {$this->item->unit} (minimum {$this->item->safety_stock_qty}).",
            'url' => $this->url(),
        ];
    }

    private function url(): string
    {
        return route('filament.admin.resources.barang.view', $this->item);
    }
}
