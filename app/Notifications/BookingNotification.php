<?php

namespace App\Notifications;

use App\Models\RoomBooking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi booking ruangan (email + lonceng). Isi disusun oleh BookingService.
 */
class BookingNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public RoomBooking $booking,
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
        $booking = $this->booking->loadMissing(['area', 'requester']);

        return (new MailMessage)
            ->subject("[5R] {$this->subject}")
            ->greeting("Halo, {$notifiable->name}")
            ->line($this->message)
            ->line("Ruangan: {$booking->area->name}")
            ->line('Waktu: '.$booking->start_time->translatedFormat('l, d M Y H:i').'–'.$booking->end_time->format('H:i'))
            ->line("Pemesan: {$booking->requester->name} · {$booking->participant_count} orang")
            ->line("Tujuan: {$booking->purpose}")
            ->action('Buka Booking', $this->url)
            ->salutation('Salam, '.config('app.name'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'room_booking_id' => $this->booking->id,
            'event' => $this->event,
            'title' => $this->subject,
            'message' => $this->message,
            'url' => $this->url,
        ];
    }
}
