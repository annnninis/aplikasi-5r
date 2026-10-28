<?php

use App\Enums\BookingStatus;
use App\Models\RoomBooking;
use App\Services\BookingService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Title('Persetujuan Booking')] class extends Component {
    use WithPagination;

    #[Url]
    public string $tab = 'menunggu';

    public ?string $rejectingId = null;

    public string $reason = '';

    public ?string $message = null;

    public function mount(): void
    {
        $this->authorize('viewAny', RoomBooking::class);
    }

    public function updatedTab(): void
    {
        $this->resetPage();
    }

    public function approve(string $id, BookingService $bookings): void
    {
        $booking = RoomBooking::with('area')->findOrFail($id);
        $this->authorize('decide', $booking);

        $bookings->approve($booking, Auth::user());
        $this->message = "Booking {$booking->area->name} disetujui.";
    }

    public function startReject(string $id): void
    {
        $this->rejectingId = $id;
        $this->reason = '';
        $this->resetValidation();
    }

    public function reject(BookingService $bookings): void
    {
        $booking = RoomBooking::with('area')->findOrFail($this->rejectingId);
        $this->authorize('decide', $booking);
        $this->validate(['reason' => ['required', 'string', 'max:500']], [], ['reason' => 'alasan']);

        $bookings->reject($booking, Auth::user(), $this->reason);
        $this->message = "Booking {$booking->area->name} ditolak.";
        $this->rejectingId = null;
    }

    public function with(): array
    {
        $user = Auth::user();

        $query = RoomBooking::with(['area', 'requester'])
            // Admin melihat semua ruangan; PIC hanya ruangan yang ia pegang.
            ->when(! $user->isAdmin(), fn ($q) => $q->whereHas('area', fn ($a) => $a->where('default_pic_id', $user->id)));

        $query = match ($this->tab) {
            'mendatang' => $query->where('approval_status', BookingStatus::Disetujui)->where('end_time', '>=', now())->orderBy('start_time'),
            'riwayat' => $query->whereNotIn('approval_status', [BookingStatus::MenungguPersetujuan])
                ->where(fn ($q) => $q->where('end_time', '<', now())->orWhere('approval_status', '!=', BookingStatus::Disetujui))
                ->latest('start_time'),
            default => $query->where('approval_status', BookingStatus::MenungguPersetujuan)->orderBy('start_time'),
        };

        return ['bookings' => $query->paginate(15)];
    }
}; ?>

<div class="mx-auto flex w-full max-w-3xl flex-col gap-6">
    <div>
        <flux:heading size="xl">Persetujuan Booking</flux:heading>
        <flux:text class="mt-1">
            {{ auth()->user()->isAdmin() ? 'Semua ruang kolaborasi.' : 'Ruangan yang Anda pegang sebagai PIC.' }}
        </flux:text>
    </div>

    <div class="flex gap-2">
        @foreach (['menunggu' => 'Menunggu', 'mendatang' => 'Disetujui (mendatang)', 'riwayat' => 'Riwayat'] as $key => $label)
            <flux:button size="sm" :variant="$tab === $key ? 'primary' : 'ghost'" wire:click="$set('tab', '{{ $key }}')">{{ $label }}</flux:button>
        @endforeach
    </div>

    @if ($message)
        <div class="rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-200" role="status">{{ $message }}</div>
    @endif
    <flux:error name="status" />

    @forelse ($bookings as $booking)
        <div wire:key="{{ $booking->id }}" class="flex flex-col gap-3 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="font-semibold">{{ $booking->area->name }}</p>
                    <p class="text-sm">{{ $booking->start_time->translatedFormat('l, d M Y') }} · {{ $booking->start_time->format('H:i') }}–{{ $booking->end_time->format('H:i') }}</p>
                    <p class="text-sm text-zinc-500">{{ $booking->requester->name }}@if ($booking->requester->unit_kerja) ({{ $booking->requester->unit_kerja }})@endif · {{ $booking->participant_count }} orang</p>
                    <p class="text-sm">{{ $booking->purpose }}</p>
                    @if ($booking->rejection_reason)
                        <p class="text-sm text-red-700 dark:text-red-400">Alasan ditolak: {{ $booking->rejection_reason }}</p>
                    @endif
                </div>
                <x-booking-status-badge :booking="$booking" />
            </div>

            @if ($booking->approval_status === \App\Enums\BookingStatus::MenungguPersetujuan)
                @if ($rejectingId === $booking->id)
                    <form wire:submit="reject" class="flex flex-col gap-2">
                        <flux:textarea wire:model="reason" rows="2" label="Alasan penolakan" placeholder="mis. Ruangan dipakai acara direksi" />
                        <div class="flex justify-end gap-2">
                            <flux:button size="sm" variant="ghost" wire:click="$set('rejectingId', null)">Batal</flux:button>
                            <flux:button size="sm" variant="danger" type="submit">Tolak Booking</flux:button>
                        </div>
                    </form>
                @else
                    <div class="flex justify-end gap-2">
                        <flux:button size="sm" variant="ghost" wire:click="startReject('{{ $booking->id }}')">Tolak</flux:button>
                        <flux:button size="sm" variant="primary" wire:click="approve('{{ $booking->id }}')">Setujui</flux:button>
                    </div>
                @endif
            @endif
        </div>
    @empty
        <div class="rounded-xl border border-dashed border-zinc-300 p-8 text-center text-sm text-zinc-500 dark:border-zinc-600">
            Tidak ada booking di sini.
        </div>
    @endforelse

    {{ $bookings->links() }}
</div>
