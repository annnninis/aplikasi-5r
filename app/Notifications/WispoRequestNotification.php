<?php

namespace App\Notifications;

use App\Models\WispoRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi permintaan/peminjaman barang (email + lonceng). Isi disusun oleh WispoRequestService.
 */
class WispoRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public WispoRequest $request,
        public string $event,
        public string $subject,
        public string $message,
        public string $url,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $request = $this->request->loadMissing(['item', 'requester']);

        return (new MailMessage)
            ->subject("[5R] {$this->subject}")
            ->greeting("Halo, {$notifiable->name}")
            ->line($this->message)
            ->line("Barang: {$request->item->name} ({$request->item->item_code})")
            ->line("Jumlah: {$request->qty} {$request->item->unit} · {$request->request_type->getLabel()}")
            ->when($request->expected_return_at, fn (MailMessage $mail) => $mail->line('Rencana kembali: '.$request->expected_return_at->translatedFormat('d M Y')))
            ->action('Buka Permintaan', $this->url)
            ->salutation('Salam, '.config('app.name'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'wispo_request_id' => $this->request->id,
            'event' => $this->event,
            'title' => $this->subject,
            'message' => $this->message,
            'url' => $this->url,
        ];
    }
}
