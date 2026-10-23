<?php

use App\Models\RoomBooking;
use App\Services\BookingService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Title('Booking Saya')] class extends Component {
    use WithPagination;

    #[Url]
    public string $tab = 'mendatang';

    public ?string $message = null;

    public function updatedTab(): void
    {
        $this->resetPage();
    }

    public function cancel(string $id, BookingService $bookings): void
    {
        $booking = RoomBooking::findOrFail($id);
        $this->authorize('cancel', $booking);

        $bookings->cancel($booking);
        $this->message = 'Booking dibatalkan. Slot sudah terbuka untuk orang lain.';
    }

    public function with(): array
    {
        $query = RoomBooking::with(['area', 'approver'])->where('requester_user_id', Auth::id());

        $query = $this->tab === 'riwayat'
            ? $query->where(fn ($q) => $q
                ->where('end_time', '<', now())
                ->orWhereIn('approval_status', ['ditolak', 'dibatalkan', 'kedaluwarsa']))
                ->latest('start_time')
            : $query->where('end_time', '>=', now())
                ->whereIn('approval_status', ['menunggu_persetujuan', 'disetujui'])
                ->orderBy('start_time');

        return ['bookings' => $query->paginate(10)];
    }
}; ?>

<div class="mx-auto flex w-full max-w-2xl flex-col gap-6">
    <div class="flex items-end justify-between gap-3">
        <div>
            <flux:heading size="xl">Booking Saya</flux:heading>
            <flux:text class="mt-1">Booking ruangan yang Anda buat.</flux:text>
        </div>
        <flux:button size="sm" icon="plus" :href="route('bookings.index')">Booking baru</flux:button>
    </div>

    <div class="flex gap-2">
        @foreach (['mendatang' => 'Mendatang', 'riwayat' => 'Riwayat'] as $key => $label)
            <flux:button size="sm" :variant="$tab === $key ? 'primary' : 'ghost'" wire:click="$set('tab', '{{ $key }}')">{{ $label }}</flux:button>
        @endforeach
    </div>

    @if ($message)
        <div class="rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-200" role="status">{{ $message }}</div>
    @endif

    @forelse ($bookings as $booking)
        <div wire:key="{{ $booking->id }}" class="flex flex-col gap-4 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="font-semibold">{{ $booking->area->name }}</p>
                    <p class="text-sm">{{ $booking->start_time->translatedFormat('l, d M Y') }} · {{ $booking->start_time->format('H:i') }}–{{ $booking->end_time->format('H:i') }}</p>
                    <p class="text-sm text-zinc-500">{{ $booking->purpose }} · {{ $booking->participant_count }} orang</p>
                </div>
                <x-status-badge :status="$booking->approval_status" />
            </div>

            <x-status-stepper :steps="$booking->steps()" />

            @if ($booking->rejection_reason)
                <p class="text-sm text-red-700 dark:text-red-400"><span class="font-semibold">Alasan ditolak:</span> {{ $booking->rejection_reason }}</p>
            @endif
            @if ($booking->approver)
                <p class="text-xs text-zinc-400">Diproses oleh {{ $booking->approver->name }}</p>
            @endif

            @if ($booking->isCancellable())
                <div class="flex justify-end">
                    <flux:button size="sm" variant="danger" wire:click="cancel('{{ $booking->id }}')" wire:confirm="Batalkan booking {{ $booking->area->name }} {{ $booking->start_time->translatedFormat('d M, H:i') }}?">
                        Batalkan
                    </flux:button>
                </div>
            @endif
        </div>
    @empty
        <div class="rounded-xl border border-dashed border-zinc-300 p-8 text-center text-sm text-zinc-500 dark:border-zinc-600">
            {{ $tab === 'riwayat' ? 'Belum ada riwayat booking.' : 'Tidak ada booking mendatang.' }}
        </div>
    @endforelse

    {{ $bookings->links() }}
</div>
