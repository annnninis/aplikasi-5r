<?php

use App\Enums\BookingStatus;
use App\Enums\FindingStatus;
use App\Enums\Role;
use App\Enums\WispoRequestStatus;
use App\Models\Finding;
use App\Models\RoomBooking;
use App\Models\WispoItem;
use App\Models\WispoRequest;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Title('Beranda')] class extends Component {
    public function with(): array
    {
        $user = Auth::user();
        $managesFindings = $user->hasRole(Role::Admin, Role::Koordinator5R);
        $managesWarehouse = $user->isAdmin() || $user->isPetugasGudang();
        $seesOverview = $user->hasRole(Role::Admin, Role::Management, Role::Koordinator5R);

        // Kartu "tugas" yang menuntut tindakan dari user ini.
        $tasks = collect();

        if ($managesFindings) {
            $tasks->push(['label' => 'Laporan baru perlu ditugaskan', 'count' => Finding::where('status', FindingStatus::BelumDitugaskan)->count(),
                'url' => route('filament.admin.resources.findings.index', ['activeTab' => 'baru']), 'icon' => 'exclamation-triangle', 'tone' => 'amber']);
            $tasks->push(['label' => 'Tindak lanjut perlu diverifikasi', 'count' => Finding::where('status', FindingStatus::MenungguVerifikasi)->count(),
                'url' => route('filament.admin.resources.findings.index', ['activeTab' => 'verifikasi']), 'icon' => 'check-badge', 'tone' => 'blue']);
        }

        if ($managesWarehouse) {
            $tasks->push(['label' => 'Permintaan barang menunggu', 'count' => WispoRequest::where('status', WispoRequestStatus::MenungguPersetujuan)->count(),
                'url' => route('filament.admin.resources.permintaan-barang.index', ['activeTab' => 'menunggu']), 'icon' => 'inbox-stack', 'tone' => 'amber']);
            $tasks->push(['label' => 'Peminjaman terlambat', 'count' => WispoRequest::where('status', WispoRequestStatus::Terlambat)->count(),
                'url' => route('filament.admin.resources.permintaan-barang.index', ['activeTab' => 'dipinjam']), 'icon' => 'clock', 'tone' => 'red']);
            $tasks->push(['label' => 'Barang stok menipis', 'count' => WispoItem::active()->lowStock()->count(),
                'url' => route('filament.admin.pages.ringkasan-gudang'), 'icon' => 'cube', 'tone' => 'red']);
        }

        if ($user->canApproveBookings()) {
            $tasks->push(['label' => 'Booking menunggu persetujuan', 'count' => RoomBooking::where('approval_status', BookingStatus::MenungguPersetujuan)
                ->when(! $user->isAdmin(), fn ($q) => $q->whereHas('area', fn ($a) => $a->where('default_pic_id', $user->id)))->count(),
                'url' => route('bookings.approvals'), 'icon' => 'calendar-days', 'tone' => 'amber']);
        }

        return [
            'user' => $user,
            'tasks' => $tasks,
            'overview' => $seesOverview ? [
                'open' => Finding::whereIn('status', [FindingStatus::BelumDitugaskan, FindingStatus::BelumSelesai, FindingStatus::MenungguVerifikasi])->count(),
                'late' => Finding::where('status', FindingStatus::Terlambat)->count(),
                'doneThisMonth' => Finding::where('status', FindingStatus::Selesai)->where('resolved_at', '>=', now()->startOfMonth())->count(),
            ] : null,
            'myFindings' => Finding::with('area:id,name')
                ->where('pic_id', $user->id)
                ->whereIn('status', [FindingStatus::Terlambat, FindingStatus::BelumSelesai])
                ->orderByRaw("case when status = 'terlambat' then 0 else 1 end")
                ->orderBy('deadline')
                ->limit(5)
                ->get(),
            'myRequests' => WispoRequest::with('item:id,name,unit')
                ->where('requester_user_id', $user->id)
                ->whereIn('status', [WispoRequestStatus::MenungguPersetujuan, WispoRequestStatus::Dipinjam, WispoRequestStatus::Terlambat])
                ->latest()
                ->limit(5)
                ->get(),
            'myBookings' => RoomBooking::with('area:id,name')
                ->where('requester_user_id', $user->id)
                ->whereIn('approval_status', BookingStatus::occupying())
                ->where('end_time', '>=', now())
                ->orderBy('start_time')
                ->limit(3)
                ->get(),
        ];
    }
}; ?>

<div class="mx-auto flex w-full max-w-5xl flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <flux:heading size="xl">Halo, {{ str($user->name)->before(' ') }} 👋</flux:heading>
            <flux:text class="mt-1">{{ now()->translatedFormat('l, d F Y') }}</flux:text>
        </div>
        <flux:button variant="primary" icon="camera" :href="route('findings.report')" wire:navigate>Lapor Cepat</flux:button>
    </div>

    @if ($tasks->isNotEmpty())
        <section class="flex flex-col gap-3">
            <flux:heading size="lg">Perlu perhatian Anda</flux:heading>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($tasks as $task)
                    <a href="{{ $task['url'] }}" @class([
                        'flex items-center gap-3 rounded-xl border bg-white p-4 transition hover:shadow-sm dark:bg-zinc-900',
                        'border-zinc-200 dark:border-zinc-700' => $task['count'] === 0,
                        'border-amber-300 dark:border-amber-700' => $task['count'] > 0 && $task['tone'] === 'amber',
                        'border-red-300 dark:border-red-700' => $task['count'] > 0 && $task['tone'] === 'red',
                        'border-blue-300 dark:border-blue-700' => $task['count'] > 0 && $task['tone'] === 'blue',
                    ])>
                        <span @class([
                            'flex size-10 shrink-0 items-center justify-center rounded-lg text-white',
                            'bg-zinc-300 dark:bg-zinc-700' => $task['count'] === 0,
                            'bg-amber-500' => $task['count'] > 0 && $task['tone'] === 'amber',
                            'bg-red-600' => $task['count'] > 0 && $task['tone'] === 'red',
                            'bg-blue-600' => $task['count'] > 0 && $task['tone'] === 'blue',
                        ])>
                            <flux:icon :name="$task['icon']" class="size-5" />
                        </span>
                        <span class="flex flex-col">
                            <span class="text-2xl font-bold leading-none">{{ $task['count'] }}</span>
                            <span class="text-sm text-zinc-500">{{ $task['label'] }}</span>
                        </span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    @if ($overview)
        <section class="grid grid-cols-3 gap-3">
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-xs text-zinc-500">Temuan berjalan</p>
                <p class="mt-1 text-2xl font-bold">{{ $overview['open'] }}</p>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-xs text-zinc-500">Temuan terlambat</p>
                <p @class(['mt-1 text-2xl font-bold', 'text-red-600' => $overview['late'] > 0])>{{ $overview['late'] }}</p>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-xs text-zinc-500">Selesai bulan ini</p>
                <p class="mt-1 text-2xl font-bold text-green-600">{{ $overview['doneThisMonth'] }}</p>
            </div>
        </section>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="flex flex-col gap-3">
            <div class="flex items-center justify-between">
                <flux:heading size="lg">Temuan saya</flux:heading>
                <flux:link :href="route('findings.mine')" wire:navigate class="text-sm">Semua</flux:link>
            </div>
            @forelse ($myFindings as $finding)
                <a href="{{ route('findings.show', $finding) }}" wire:navigate class="flex flex-col gap-1 rounded-xl border border-zinc-200 bg-white p-3 text-sm hover:border-accent dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="flex items-start justify-between gap-2">
                        <span class="font-medium">{{ $finding->area->name }}</span>
                        <x-status-badge :status="$finding->status" />
                    </div>
                    <span class="line-clamp-1 text-zinc-500">{{ $finding->description }}</span>
                    @php $days = $finding->daysLeft(); @endphp
                    <span @class([
                        'text-xs font-medium',
                        'text-red-600' => $finding->urgencyColor() === 'red',
                        'text-amber-600' => $finding->urgencyColor() === 'amber',
                        'text-green-600' => $finding->urgencyColor() === 'green',
                    ])>
                        {{ $days < 0 ? 'Terlambat '.abs($days).' hari' : ($days === 0 ? 'Deadline hari ini' : $days.' hari lagi') }}
                    </span>
                </a>
            @empty
                <p class="rounded-xl border border-dashed border-zinc-300 p-4 text-center text-sm text-zinc-500 dark:border-zinc-600">Tidak ada temuan yang perlu Anda kerjakan.</p>
            @endforelse
        </section>

        <section class="flex flex-col gap-3">
            <div class="flex items-center justify-between">
                <flux:heading size="lg">Permintaan barang</flux:heading>
                <flux:link :href="route('wispo.requests.mine')" wire:navigate class="text-sm">Semua</flux:link>
            </div>
            @forelse ($myRequests as $request)
                <a href="{{ route('wispo.requests.mine') }}" wire:navigate class="flex items-start justify-between gap-2 rounded-xl border border-zinc-200 bg-white p-3 text-sm hover:border-accent dark:border-zinc-700 dark:bg-zinc-900">
                    <span class="flex min-w-0 flex-col">
                        <span class="truncate font-medium">{{ $request->item->name }}</span>
                        <span class="text-zinc-500">{{ $request->request_type->getLabel() }} · {{ $request->qty }} {{ $request->item->unit }}
                            @if ($request->expected_return_at) · kembali {{ $request->expected_return_at->translatedFormat('d M') }} @endif</span>
                    </span>
                    <x-status-badge :status="$request->status" />
                </a>
            @empty
                <p class="rounded-xl border border-dashed border-zinc-300 p-4 text-center text-sm text-zinc-500 dark:border-zinc-600">Tidak ada permintaan yang berjalan.</p>
            @endforelse
        </section>

        <section class="flex flex-col gap-3">
            <div class="flex items-center justify-between">
                <flux:heading size="lg">Booking mendatang</flux:heading>
                <flux:link :href="route('bookings.mine')" wire:navigate class="text-sm">Semua</flux:link>
            </div>
            @forelse ($myBookings as $booking)
                <a href="{{ route('bookings.mine') }}" wire:navigate class="flex items-start justify-between gap-2 rounded-xl border border-zinc-200 bg-white p-3 text-sm hover:border-accent dark:border-zinc-700 dark:bg-zinc-900">
                    <span class="flex min-w-0 flex-col">
                        <span class="truncate font-medium">{{ $booking->area->name }}</span>
                        <span class="text-zinc-500">{{ $booking->start_time->translatedFormat('D, d M') }} · {{ $booking->start_time->format('H:i') }}–{{ $booking->end_time->format('H:i') }}</span>
                    </span>
                    <x-booking-status-badge :booking="$booking" />
                </a>
            @empty
                <p class="rounded-xl border border-dashed border-zinc-300 p-4 text-center text-sm text-zinc-500 dark:border-zinc-600">Belum ada booking mendatang.</p>
            @endforelse
        </section>
    </div>
</div>
