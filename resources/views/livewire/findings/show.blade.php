<?php

use App\Models\Finding;
use App\Services\FindingService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Title('Detail Temuan')] class extends Component {
    use WithFileUploads;

    public Finding $finding;

    public $afterPhoto;

    public string $note = '';

    public bool $justSubmitted = false;

    public function mount(Finding $finding): void
    {
        $this->authorize('view', $finding);
        $this->finding = $finding;
    }

    protected function rules(): array
    {
        return [
            'afterPhoto' => ['required', 'image', 'max:5120'],
            'note' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }

    protected function validationAttributes(): array
    {
        return ['afterPhoto' => 'foto sesudah', 'note' => 'catatan'];
    }

    public function submit(FindingService $findings): void
    {
        $this->authorize('resolve', $this->finding);
        $this->validate();

        $findings->submitResolution($this->finding, Auth::user(), $this->afterPhoto, $this->note);

        $this->reset('afterPhoto', 'note');
        $this->dispatch('resolution-submitted');
        $this->justSubmitted = true;
        $this->finding->refresh();
    }

    public function with(): array
    {
        $this->finding->load(['area', 'reporter', 'pic', 'verifier', 'photos']);

        return [
            'before' => $this->finding->photos->filter(fn ($p) => $p->type->value === 'before'),
            'after' => $this->finding->photos->filter(fn ($p) => $p->type->value === 'after'),
            'canResolve' => Auth::user()->can('resolve', $this->finding) && $this->finding->isOpenForPic(),
        ];
    }
}; ?>

<div class="mx-auto flex w-full max-w-2xl flex-col gap-6">
    <div class="flex items-start justify-between gap-3">
        <div>
            <flux:text class="text-sm"><a href="{{ route('findings.mine') }}" wire:navigate class="hover:underline">Temuan Saya</a> / Detail</flux:text>
            <flux:heading size="xl" class="mt-1">{{ $finding->area->name }}</flux:heading>
        </div>
        <x-status-badge :status="$finding->status" />
    </div>

    @if ($justSubmitted)
        <div class="rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-200" role="status">
            <p class="font-semibold">Tindak lanjut terkirim.</p>
            <p>Koordinator 5R akan memverifikasi perbaikan Anda.</p>
        </div>
    @endif

    @if ($finding->revision_note && $finding->isOpenForPic())
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-200">
            <p class="font-semibold">Perlu revisi</p>
            <p>{{ $finding->revision_note }}</p>
        </div>
    @endif

    @if ($finding->rejection_reason)
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-200">
            <p class="font-semibold">Laporan ditolak</p>
            <p>{{ $finding->rejection_reason }}</p>
        </div>
    @endif

    <div class="grid gap-4 rounded-xl border border-zinc-200 bg-white p-5 text-sm dark:border-zinc-700 dark:bg-zinc-900 sm:grid-cols-2">
        <div class="sm:col-span-2">
            <p class="text-zinc-500">Deskripsi</p>
            <p class="mt-1">{{ $finding->description }}</p>
        </div>
        <div>
            <p class="text-zinc-500">Prioritas</p>
            <x-status-badge :status="$finding->priority" class="mt-1" />
        </div>
        <div>
            <p class="text-zinc-500">Deadline</p>
            <p class="mt-1 font-medium">{{ $finding->deadline?->translatedFormat('d M Y') ?? 'Belum ditentukan' }}</p>
        </div>
        <div>
            <p class="text-zinc-500">PIC</p>
            <p class="mt-1">{{ $finding->pic?->name ?? 'Belum ditugaskan' }}</p>
        </div>
        <div>
            <p class="text-zinc-500">Pelapor</p>
            <p class="mt-1">{{ $finding->reporter->name }} · {{ $finding->created_at->translatedFormat('d M Y') }}</p>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <flux:heading size="sm" class="mb-2">Foto sebelum</flux:heading>
            @foreach ($before as $photo)
                <a href="{{ Storage::disk('public')->url($photo->photo_path) }}" target="_blank">
                    <img src="{{ Storage::disk('public')->url($photo->photo_path) }}" alt="Foto sebelum" class="w-full rounded-lg object-cover">
                </a>
            @endforeach
        </div>
        <div>
            <flux:heading size="sm" class="mb-2">Foto sesudah</flux:heading>
            @forelse ($after as $photo)
                <a href="{{ Storage::disk('public')->url($photo->photo_path) }}" target="_blank" class="mb-2 block">
                    <img src="{{ Storage::disk('public')->url($photo->photo_path) }}" alt="Foto sesudah" class="w-full rounded-lg object-cover">
                </a>
            @empty
                <div class="flex aspect-video items-center justify-center rounded-lg border border-dashed border-zinc-300 text-sm text-zinc-500 dark:border-zinc-600">Belum ada</div>
            @endforelse
            @if ($finding->resolution_note)
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">Catatan PIC: {{ $finding->resolution_note }}</p>
            @endif
        </div>
    </div>

    @if ($canResolve)
        <form wire:submit="submit" class="flex flex-col gap-5 rounded-xl border border-accent/40 bg-white p-5 dark:bg-zinc-900">
            <div>
                <flux:heading size="lg">Kirim tindak lanjut</flux:heading>
                <flux:text class="mt-1 text-sm">Setelah perbaikan selesai di lapangan, foto kondisi sesudahnya.</flux:text>
            </div>

            <x-photo-input model="afterPhoto" label="Foto sesudah perbaikan" reset-on="resolution-submitted" />

            <flux:textarea wire:model="note" label="Catatan penyelesaian" rows="2" placeholder="Contoh: Kardus sudah dibuang, area sudah dibersihkan." />

            <flux:button type="submit" variant="primary" class="w-full" wire:loading.attr="disabled" wire:target="submit,afterPhoto">
                Kirim untuk Diverifikasi
            </flux:button>
        </form>
    @endif
</div>
