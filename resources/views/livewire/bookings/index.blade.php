<?php

use App\Enums\BookingStatus;
use App\Models\Area;
use App\Models\RoomBooking;
use App\Services\BookingService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new #[Title('Booking Ruangan')] class extends Component {
    #[Url(as: 'ruang')]
    public ?string $room = null;

    public string $date = '';

    public string $start_time = '';

    public string $end_time = '';

    public string $purpose = '';

    public ?int $participant_count = null;

    public ?string $savedMessage = null;

    public function mount(): void
    {
        $rooms = $this->rooms();
        if (! $rooms->contains('id', $this->room)) {
            $this->room = $rooms->first()?->id;
        }
        $this->date = today()->toDateString();
    }

    public function selectRoom(string $id): void
    {
        $this->room = $id;
        $this->resetValidation();
        $this->savedMessage = null;
        $this->dispatch('room-changed');
    }

    /**
     * Data untuk kalender (dipanggil dari JavaScript).
     *
     * @return list<array<string, mixed>>
     */
    public function events(string $start, string $end): array
    {
        if (! $this->room) {
            return [];
        }

        return RoomBooking::with('requester:id,name')
            ->where('area_id', $this->room)
            ->whereIn('approval_status', BookingStatus::occupying())
            ->where('start_time', '<', Carbon::parse($end)->setTimezone(config('app.timezone')))
            ->where('end_time', '>', Carbon::parse($start)->setTimezone(config('app.timezone')))
            ->get()
            ->map(function (RoomBooking $b) {
                $mine = $b->requester_user_id === Auth::id();
                $pending = $b->approval_status === BookingStatus::MenungguPersetujuan;

                return [
                    'id' => $b->id,
                    'title' => ($mine ? 'Booking Anda: '.$b->purpose : $b->requester->name).($pending ? ' (menunggu)' : ''),
                    'start' => $b->start_time->toIso8601String(),
                    'end' => $b->end_time->toIso8601String(),
                    'backgroundColor' => $pending ? '#f59e0b' : ($mine ? '#16a34a' : '#2563eb'),
                    'borderColor' => 'transparent',
                ];
            })
            ->all();
    }

    /**
     * Slot yang dipilih di kalender mengisi form.
     */
    public function pickSlot(string $start, string $end): void
    {
        $s = Carbon::parse($start)->setTimezone(config('app.timezone'));
        $e = Carbon::parse($end)->setTimezone(config('app.timezone'));

        $this->date = $s->toDateString();
        $this->start_time = $s->format('H:i');
        $this->end_time = $e->format('H:i');
        $this->savedMessage = null;
        $this->resetValidation();
    }

    protected function rules(): array
    {
        return [
            'room' => ['required', 'exists:areas,id'],
            'date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'purpose' => ['required', 'string', 'min:3', 'max:500'],
            'participant_count' => ['required', 'integer', 'min:1'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'room' => 'ruangan',
            'date' => 'tanggal',
            'start_time' => 'jam mulai',
            'end_time' => 'jam selesai',
            'purpose' => 'tujuan rapat',
            'participant_count' => 'jumlah peserta',
        ];
    }

    public function book(BookingService $bookings): void
    {
        $this->authorize('create', RoomBooking::class);
        $this->validate();

        $tz = config('app.timezone');
        $booking = $bookings->book(
            Auth::user(),
            Area::findOrFail($this->room),
            Carbon::parse("{$this->date} {$this->start_time}", $tz),
            Carbon::parse("{$this->date} {$this->end_time}", $tz),
            $this->purpose,
            (int) $this->participant_count,
        );

        $this->savedMessage = $booking->approval_status === BookingStatus::Disetujui
            ? 'Booking terkonfirmasi.'
            : 'Booking terkirim dan menunggu persetujuan PIC ruangan.';
        $this->reset('start_time', 'end_time', 'purpose', 'participant_count');
        $this->dispatch('booking-saved');
    }

    protected function rooms()
    {
        return Area::active()->bookable()->orderBy('name')->get();
    }

    public function with(): array
    {
        $rooms = $this->rooms();

        return [
            'rooms' => $rooms,
            'selected' => $rooms->firstWhere('id', $this->room),
            'timeOptions' => $this->timeOptions(),
        ];
    }

    /**
     * Pilihan jam per 15 menit dalam jam operasional (06:00 … 22:00).
     *
     * @return list<string>
     */
    protected function timeOptions(): array
    {
        $times = [];
        for ($m = BookingService::OPEN_HOUR * 60; $m <= BookingService::CLOSE_HOUR * 60; $m += 15) {
            $times[] = sprintf('%02d:%02d', intdiv($m, 60), $m % 60);
        }

        return $times;
    }
}; ?>

<div class="flex w-full flex-col gap-6">
    <div>
        <flux:heading size="xl">Booking Ruangan</flux:heading>
        <flux:text class="mt-1">Pilih ruangan, lalu tandai jam kosong di kalender (klik & geser, atau tekan lama di HP).</flux:text>
    </div>

    @if ($rooms->isEmpty())
        <div class="rounded-xl border border-dashed border-zinc-300 p-8 text-center text-sm text-zinc-500 dark:border-zinc-600">
            Belum ada ruangan yang bisa dipesan. Hubungi Admin.
        </div>
    @else
        <div class="flex gap-3 overflow-x-auto pb-1">
            @foreach ($rooms as $r)
                <button type="button" wire:click="selectRoom('{{ $r->id }}')" wire:key="room-{{ $r->id }}" @class([
                    'flex min-w-48 flex-col items-start gap-1 rounded-xl border p-3 text-left text-sm transition',
                    'border-accent bg-accent/5 ring-2 ring-accent/30' => $r->id === $room,
                    'border-zinc-200 bg-white hover:border-accent dark:border-zinc-700 dark:bg-zinc-900' => $r->id !== $room,
                ])>
                    <span class="font-semibold">{{ $r->name }}</span>
                    <span class="text-xs text-zinc-500">
                        {{ $r->capacity ? $r->capacity.' orang' : 'Kapasitas belum diisi' }}@if ($r->facilities) · {{ $r->facilities }}@endif
                    </span>
                    @if ($r->requires_approval)
                        <flux:badge size="sm" color="amber">Perlu persetujuan</flux:badge>
                    @endif
                </button>
            @endforeach
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="rounded-xl border border-zinc-200 bg-white p-3 lg:col-span-2 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="mb-2 flex flex-wrap gap-3 text-xs text-zinc-500">
                    <span class="flex items-center gap-1"><span class="size-3 rounded bg-blue-600"></span>Terpakai</span>
                    <span class="flex items-center gap-1"><span class="size-3 rounded bg-amber-500"></span>Menunggu persetujuan</span>
                    <span class="flex items-center gap-1"><span class="size-3 rounded bg-green-600"></span>Booking Anda</span>
                </div>
                <div
                    wire:ignore
                    x-data="roomCalendar({{ \App\Services\BookingService::OPEN_HOUR }}, {{ \App\Services\BookingService::CLOSE_HOUR }})"
                    x-on:room-changed.window="refresh()"
                    x-on:booking-saved.window="refresh()"
                    class="room-calendar text-sm"
                >
                    <div x-ref="calendar"></div>
                </div>
            </div>

            <form wire:submit="book" class="flex h-fit flex-col gap-4 rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <div>
                    <flux:heading size="lg">Pesan {{ $selected?->name }}</flux:heading>
                    @if ($selected?->capacity)
                        <flux:text class="text-xs">Maksimal {{ $selected->capacity }} orang.</flux:text>
                    @endif
                </div>

                @if ($savedMessage)
                    <div class="rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-200" role="status">
                        {{ $savedMessage }}
                    </div>
                @endif

                <flux:input wire:model="date" type="date" :min="today()->toDateString()" label="Tanggal" />
                <div class="grid grid-cols-2 gap-3">
                    <flux:select wire:model="start_time" label="Mulai" placeholder="--:--">
                        @foreach ($timeOptions as $time)
                            @continue($loop->last)
                            <flux:select.option value="{{ $time }}">{{ $time }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="end_time" label="Selesai" placeholder="--:--">
                        @foreach ($timeOptions as $time)
                            @continue($loop->first)
                            <flux:select.option value="{{ $time }}">{{ $time }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <flux:textarea wire:model="purpose" label="Tujuan rapat" rows="2" placeholder="Contoh: Rapat koordinasi shift" />
                <flux:input wire:model="participant_count" type="number" min="1" :max="$selected?->capacity" label="Jumlah peserta" />
                <flux:error name="room" />

                <flux:button type="submit" variant="primary" class="w-full" wire:loading.attr="disabled" wire:target="book">Pesan Ruangan</flux:button>
            </form>
        </div>
    @endif
</div>
