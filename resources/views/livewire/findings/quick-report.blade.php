<?php

use App\Enums\FindingPriority;
use App\Models\Area;
use App\Models\Finding;
use App\Services\FindingService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Title('Lapor Cepat')] class extends Component {
    use WithFileUploads;

    public $photo;

    public string $area_id = '';

    public string $description = '';

    public string $priority = 'sedang';

    public ?string $lastReportId = null;

    protected function rules(): array
    {
        return [
            'photo' => ['required', 'image', 'max:5120'],
            'area_id' => ['required', 'exists:areas,id'],
            'description' => ['required', 'string', 'min:10', 'max:1000'],
            'priority' => ['required', 'in:tinggi,sedang,rendah'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'photo' => 'foto',
            'area_id' => 'area',
            'description' => 'deskripsi',
            'priority' => 'prioritas',
        ];
    }

    public function submit(FindingService $findings): void
    {
        $this->authorize('create', Finding::class);
        $this->validate();

        $finding = $findings->report(
            Auth::user(),
            Area::findOrFail($this->area_id),
            $this->description,
            FindingPriority::from($this->priority),
            $this->photo,
        );

        $this->lastReportId = $finding->id;
        $this->reset('photo', 'area_id', 'description', 'priority');
        $this->dispatch('report-submitted');
    }

    public function with(): array
    {
        return [
            'areas' => Area::active()->orderBy('name')->get(['id', 'name']),
            'priorities' => FindingPriority::cases(),
            'myReports' => Finding::with('area:id,name')
                ->where('reported_by', Auth::id())
                ->latest()
                ->limit(5)
                ->get(),
        ];
    }
}; ?>

<div class="mx-auto flex w-full max-w-xl flex-col gap-6">
    <div>
        <flux:heading size="xl">Lapor Cepat</flux:heading>
        <flux:text class="mt-1">Lihat area berantakan, kotor, atau barang rusak? Foto dan laporkan di sini.</flux:text>
    </div>

    @if ($lastReportId)
        <div class="rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-200" role="status">
            <p class="font-semibold">Laporan terkirim. Terima kasih!</p>
            <p>Koordinator 5R akan menunjuk PIC untuk menindaklanjutinya.</p>
        </div>
    @endif

    <form wire:submit="submit" class="flex flex-col gap-5 rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
        {{-- Foto: dikompres di browser dulu, baru diunggah --}}
        <div
            x-data="{ preview: null, uploading: false, progress: 0 }"
            x-on:livewire-upload-start="uploading = true"
            x-on:livewire-upload-finish="uploading = false"
            x-on:livewire-upload-error="uploading = false"
            x-on:livewire-upload-progress="progress = $event.detail.progress"
            x-on:report-submitted.window="preview = null; $refs.photoInput.value = ''"
            class="flex flex-col gap-2"
        >
            <flux:label>Foto kondisi <span class="text-red-500">*</span></flux:label>

            <label class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed border-zinc-300 p-4 text-center hover:border-accent dark:border-zinc-600">
                <template x-if="preview">
                    <img :src="preview" alt="Pratinjau foto" class="max-h-64 rounded-md object-contain">
                </template>
                <template x-if="!preview">
                    <span class="flex flex-col items-center gap-1 text-sm text-zinc-500">
                        <flux:icon.camera class="size-8" />
                        Ketuk untuk ambil foto atau pilih dari galeri
                    </span>
                </template>
                <input
                    type="file"
                    accept="image/*"
                    capture="environment"
                    class="sr-only"
                    x-ref="photoInput"
                    x-on:change="
                        const file = $event.target.files[0];
                        if (!file) return;
                        const small = await window.compressImage(file);
                        preview = URL.createObjectURL(small);
                        $wire.upload('photo', small);
                    "
                >
            </label>

            <div x-show="uploading" class="h-1.5 w-full overflow-hidden rounded bg-zinc-200 dark:bg-zinc-700">
                <div class="h-full bg-accent transition-all" :style="`width: ${progress}%`"></div>
            </div>
            <span x-show="preview && !uploading" class="text-xs text-zinc-500">Ketuk foto untuk mengganti.</span>
            <flux:error name="photo" />
        </div>

        <flux:select wire:model="area_id" label="Area" placeholder="Pilih area...">
            @foreach ($areas as $area)
                <flux:select.option value="{{ $area->id }}">{{ $area->name }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:textarea
            wire:model="description"
            label="Deskripsi masalah"
            rows="3"
            placeholder="Contoh: Kardus bekas menumpuk di samping lemari arsip, menghalangi jalan."
        />

        <flux:radio.group wire:model="priority" label="Seberapa mendesak?" variant="segmented">
            @foreach ($priorities as $p)
                <flux:radio value="{{ $p->value }}" label="{{ $p->getLabel() }}" />
            @endforeach
        </flux:radio.group>
        <flux:text class="-mt-3 text-xs">Pilih "Tinggi" untuk hal yang membahayakan keselamatan (K3).</flux:text>

        <flux:button type="submit" variant="primary" class="w-full" wire:loading.attr="disabled" wire:target="submit,photo">
            Kirim Laporan
        </flux:button>
    </form>

    @if ($myReports->isNotEmpty())
        <div class="flex flex-col gap-3">
            <flux:heading size="lg">Laporan saya terakhir</flux:heading>
            <ul class="divide-y divide-zinc-200 rounded-xl border border-zinc-200 bg-white dark:divide-zinc-700 dark:border-zinc-700 dark:bg-zinc-900">
                @foreach ($myReports as $report)
                    <li class="flex items-start justify-between gap-3 p-3 text-sm">
                        <div class="min-w-0">
                            <p class="font-medium">{{ $report->area->name }}</p>
                            <p class="truncate text-zinc-500">{{ $report->description }}</p>
                            <p class="text-xs text-zinc-400">{{ $report->created_at->translatedFormat('d M Y, H:i') }}</p>
                        </div>
                        <x-status-badge :status="$report->status" />
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
