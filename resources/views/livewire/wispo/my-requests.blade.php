<?php

use App\Models\WispoRequest;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Title('Permintaan Saya')] class extends Component {
    use WithPagination;

    public function with(): array
    {
        return [
            'requests' => WispoRequest::with(['item', 'approver'])
                ->where('requester_user_id', Auth::id())
                ->orderByRaw("case status when 'terlambat' then 0 when 'menunggu_persetujuan' then 1 when 'dipinjam' then 2 else 3 end")
                ->latest()
                ->paginate(15),
        ];
    }
}; ?>

<div class="mx-auto flex w-full max-w-2xl flex-col gap-6">
    <div class="flex items-end justify-between gap-3">
        <div>
            <flux:heading size="xl">Permintaan Saya</flux:heading>
            <flux:text class="mt-1">Status permintaan dan peminjaman barang gudang Anda.</flux:text>
        </div>
        <flux:button size="sm" icon="plus" :href="route('wispo.stock')" wire:navigate>Ajukan baru</flux:button>
    </div>

    @if (session('status'))
        <div class="rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-200" role="status">
            {{ session('status') }}
        </div>
    @endif

    @forelse ($requests as $request)
        <div wire:key="{{ $request->id }}" class="flex flex-col gap-4 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="font-semibold">{{ $request->item->name }}</p>
                    <p class="text-sm text-zinc-500">
                        {{ $request->request_type->getLabel() }} · {{ $request->qty }} {{ $request->item->unit }} · {{ $request->usage_category->getLabel() }}
                    </p>
                    <p class="text-xs text-zinc-400">Diajukan {{ $request->created_at->translatedFormat('d M Y, H:i') }}</p>
                </div>
                <x-status-badge :status="$request->status" />
            </div>

            <x-status-stepper :steps="$request->steps()" />

            <div class="flex flex-col gap-1 text-sm">
                <p><span class="text-zinc-500">Tujuan:</span> {{ $request->purpose }}</p>
                @if ($request->expected_return_at)
                    <p><span class="text-zinc-500">Rencana kembali:</span> {{ $request->expected_return_at->translatedFormat('d M Y') }}</p>
                @endif
                @if ($request->rejection_reason)
                    <p class="text-red-700 dark:text-red-400"><span class="font-semibold">Alasan ditolak:</span> {{ $request->rejection_reason }}</p>
                @endif
                @if ($request->approver && $request->decided_at)
                    <p class="text-xs text-zinc-400">Diproses oleh {{ $request->approver->name }}, {{ $request->decided_at->translatedFormat('d M Y, H:i') }}</p>
                @endif
            </div>
        </div>
    @empty
        <div class="rounded-xl border border-dashed border-zinc-300 p-8 text-center text-sm text-zinc-500 dark:border-zinc-600">
            Belum ada pengajuan. Buka <a href="{{ route('wispo.stock') }}" wire:navigate class="text-accent-content underline">Stok Barang</a> lalu tekan "Ajukan".
        </div>
    @endforelse

    {{ $requests->links() }}
</div>
