<?php

use App\Enums\FindingStatus;
use App\Models\Finding;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new #[Title('Temuan Saya')] class extends Component {
    #[Url]
    public string $tab = 'aktif';

    public function with(): array
    {
        $statuses = [
            'aktif' => [FindingStatus::Terlambat, FindingStatus::BelumSelesai],
            'verifikasi' => [FindingStatus::MenungguVerifikasi],
            'selesai' => [FindingStatus::Selesai],
        ];

        $base = Finding::where('pic_id', Auth::id());

        return [
            'counts' => collect($statuses)->map(fn ($s) => (clone $base)->whereIn('status', $s)->count()),
            'findings' => (clone $base)
                ->with(['area:id,name', 'photos'])
                ->whereIn('status', $statuses[$this->tab] ?? $statuses['aktif'])
                ->orderByRaw("case when status = 'terlambat' then 0 else 1 end")
                ->orderBy('deadline')
                ->latest('updated_at')
                ->get(),
        ];
    }
}; ?>

<div class="mx-auto flex w-full max-w-2xl flex-col gap-6">
    <div>
        <flux:heading size="xl">Temuan Saya</flux:heading>
        <flux:text class="mt-1">Temuan yang ditugaskan kepada Anda sebagai PIC.</flux:text>
    </div>

    <div class="flex gap-2 overflow-x-auto">
        @foreach (['aktif' => 'Perlu dikerjakan', 'verifikasi' => 'Menunggu verifikasi', 'selesai' => 'Selesai'] as $key => $label)
            <flux:button size="sm" :variant="$tab === $key ? 'primary' : 'ghost'" wire:click="$set('tab', '{{ $key }}')">
                {{ $label }}
                @if ($counts[$key] > 0)
                    <span class="ms-1 rounded-full bg-white/20 px-1.5 text-xs">{{ $counts[$key] }}</span>
                @endif
            </flux:button>
        @endforeach
    </div>

    @forelse ($findings as $finding)
        @php
            $before = $finding->photos->first(fn ($p) => $p->type->value === 'before');
            $days = $finding->daysLeft();
            $color = $finding->urgencyColor();
        @endphp
        <a href="{{ route('findings.show', $finding) }}" wire:navigate
           class="flex gap-3 rounded-xl border border-zinc-200 bg-white p-3 hover:border-accent dark:border-zinc-700 dark:bg-zinc-900">
            @if ($before)
                <img src="{{ Storage::disk('public')->url($before->photo_path) }}" alt="" class="size-20 shrink-0 rounded-lg object-cover">
            @endif
            <div class="flex min-w-0 flex-1 flex-col gap-1">
                <div class="flex items-start justify-between gap-2">
                    <p class="font-semibold">{{ $finding->area->name }}</p>
                    <x-status-badge :status="$finding->status" />
                </div>
                <p class="line-clamp-2 text-sm text-zinc-600 dark:text-zinc-300">{{ $finding->description }}</p>
                @if ($tab === 'aktif' && $finding->deadline)
                    <p @class([
                        'text-sm font-medium',
                        'text-red-600 dark:text-red-400' => $color === 'red',
                        'text-amber-600 dark:text-amber-400' => $color === 'amber',
                        'text-green-600 dark:text-green-400' => $color === 'green',
                    ])>
                        @if ($days < 0)
                            Terlambat {{ abs($days) }} hari
                        @elseif ($days === 0)
                            Deadline hari ini
                        @else
                            {{ $days }} hari lagi
                        @endif
                        · {{ $finding->deadline->translatedFormat('d M Y') }}
                    </p>
                @endif
                @if ($finding->revision_note && $finding->isOpenForPic())
                    <p class="text-xs text-amber-700 dark:text-amber-400">Perlu revisi: {{ $finding->revision_note }}</p>
                @endif
            </div>
        </a>
    @empty
        <div class="rounded-xl border border-dashed border-zinc-300 p-8 text-center text-sm text-zinc-500 dark:border-zinc-600">
            Tidak ada temuan di sini.
        </div>
    @endforelse
</div>
