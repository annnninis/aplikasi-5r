<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

/**
 * Lonceng notifikasi: membaca tabel notifications (diisi oleh semua notifikasi mail + database).
 */
new class extends Component {
    public string $align = 'start';

    public function open(string $id): void
    {
        $notification = Auth::user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        $this->redirect($notification->data['url'] ?? route('dashboard'));
    }

    public function markAllRead(): void
    {
        Auth::user()->unreadNotifications->markAsRead();
    }

    public function with(): array
    {
        $user = Auth::user();

        return [
            'unread' => $user->unreadNotifications()->count(),
            'items' => $user->notifications()->latest()->limit(15)->get(),
        ];
    }
}; ?>

{{-- Panel memakai position: fixed agar tidak terpotong oleh sidebar yang bisa di-scroll. --}}
<div
    wire:poll.60s
    class="relative"
    x-data="{
        open: false,
        pos: '',
        toggle() {
            const r = this.$refs.button.getBoundingClientRect();
            this.pos = @js($align) === 'end'
                ? `top:${r.bottom + 8}px; right:${Math.max(8, window.innerWidth - r.right)}px`
                : `top:${r.bottom + 8}px; left:${r.left}px`;
            this.open = ! this.open;
        },
    }"
    x-on:keydown.escape.window="open = false"
    x-on:resize.window="open = false"
>
    <button type="button" x-ref="button" x-on:click="toggle()" class="relative flex size-9 items-center justify-center rounded-lg text-zinc-600 hover:bg-zinc-200/60 dark:text-zinc-300 dark:hover:bg-zinc-700" aria-label="Notifikasi">
        <flux:icon.bell class="size-5" />
        @if ($unread > 0)
            <span class="absolute -right-0.5 -top-0.5 flex min-w-5 items-center justify-center rounded-full bg-red-600 px-1 text-[11px] font-bold leading-5 text-white">{{ $unread > 99 ? '99+' : $unread }}</span>
        @endif
    </button>

    <div
        x-cloak
        x-show="open"
        x-transition.origin.top
        x-on:click.outside="open = false"
        :style="pos"
        class="fixed z-50 w-80 max-w-[calc(100vw-1rem)] overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-900"
    >
        <div class="flex items-center justify-between border-b border-zinc-200 px-4 py-2 dark:border-zinc-700">
            <span class="text-sm font-semibold">Notifikasi</span>
            @if ($unread > 0)
                <button type="button" wire:click="markAllRead" class="text-xs text-accent-content hover:underline">Tandai semua dibaca</button>
            @endif
        </div>

        <ul class="max-h-96 divide-y divide-zinc-100 overflow-y-auto dark:divide-zinc-800">
            @forelse ($items as $item)
                <li wire:key="n-{{ $item->id }}">
                    <button type="button" wire:click="open('{{ $item->id }}')" class="flex w-full gap-3 px-4 py-3 text-left hover:bg-zinc-50 dark:hover:bg-zinc-800">
                        <span @class(['mt-1.5 size-2 shrink-0 rounded-full', 'bg-accent' => ! $item->read_at, 'bg-transparent' => $item->read_at])></span>
                        <span class="flex min-w-0 flex-col gap-0.5">
                            <span @class(['text-sm', 'font-semibold' => ! $item->read_at])>{{ $item->data['title'] ?? 'Notifikasi' }}</span>
                            <span class="line-clamp-2 text-xs text-zinc-500">{{ $item->data['message'] ?? '' }}</span>
                            <span class="text-[11px] text-zinc-400">{{ $item->created_at->diffForHumans() }}</span>
                        </span>
                    </button>
                </li>
            @empty
                <li class="px-4 py-8 text-center text-sm text-zinc-500">Belum ada notifikasi.</li>
            @endforelse
        </ul>
    </div>
</div>
