@props(['steps'])

{{-- Stepper status (PRD §3.9.3): hijau = lewat, biru = aktif, merah = ditolak/terlambat, abu = belum. --}}
<ol {{ $attributes->class('flex items-start') }}>
    @foreach ($steps as $i => $step)
        @php
            $circle = match ($step['state']) {
                'done' => 'bg-green-600 text-white',
                'active' => 'bg-accent text-white ring-4 ring-accent/20',
                'failed' => 'bg-red-600 text-white',
                default => 'bg-zinc-200 text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400',
            };
            $text = match ($step['state']) {
                'done' => 'text-green-700 dark:text-green-400',
                'active' => 'text-accent-content font-semibold',
                'failed' => 'text-red-700 dark:text-red-400 font-semibold',
                default => 'text-zinc-400',
            };
            $lineDone = $step['state'] === 'done' && isset($steps[$i + 1]) && $steps[$i + 1]['state'] !== 'pending';
        @endphp
        <li class="relative flex flex-1 flex-col items-center gap-1 text-center">
            @if (! $loop->last)
                <span @class([
                    'absolute top-3 left-1/2 h-0.5 w-full',
                    'bg-green-600' => $lineDone,
                    'bg-zinc-200 dark:bg-zinc-700' => ! $lineDone,
                ])></span>
            @endif
            <span class="relative z-10 flex size-6 items-center justify-center rounded-full text-xs font-bold {{ $circle }}">
                @if ($step['state'] === 'done')
                    <flux:icon.check variant="micro" />
                @elseif ($step['state'] === 'failed')
                    <flux:icon.x-mark variant="micro" />
                @else
                    {{ $i + 1 }}
                @endif
            </span>
            <span class="px-1 text-[11px] leading-tight {{ $text }}">{{ $step['label'] }}</span>
        </li>
    @endforeach
</ol>
