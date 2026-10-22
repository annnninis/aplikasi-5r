<?php

namespace App\Notifications;

use App\Models\Finding;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi temuan (email + lonceng di aplikasi). Isi pesan disusun oleh FindingNotifier.
 */
class FindingNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Finding $finding,
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
        $finding = $this->finding->loadMissing('area');

        return (new MailMessage)
            ->subject("[5R] {$this->subject}")
            ->greeting("Halo, {$notifiable->name}")
            ->line($this->message)
            ->line("Area: {$finding->area->name}")
            ->line('Deskripsi: '.str($finding->description)->limit(150))
            ->when($finding->deadline, fn (MailMessage $mail) => $mail->line('Deadline: '.$finding->deadline->translatedFormat('d M Y')))
            ->action('Buka Temuan', $this->url)
            ->salutation('Salam, '.config('app.name'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'finding_id' => $this->finding->id,
            'event' => $this->event,
            'title' => $this->subject,
            'message' => $this->message,
            'url' => $this->url,
        ];
    }
}
