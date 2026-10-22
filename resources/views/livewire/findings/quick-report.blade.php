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
        <x-photo-input model="photo" label="Foto kondisi" reset-on="report-submitted" />

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
                    <li>
                        <a href="{{ route('findings.show', $report) }}" wire:navigate class="flex items-start justify-between gap-3 p-3 text-sm hover:bg-zinc-50 dark:hover:bg-zinc-800">
                            <div class="min-w-0">
                                <p class="font-medium">{{ $report->area->name }}</p>
                                <p class="truncate text-zinc-500">{{ $report->description }}</p>
                                <p class="text-xs text-zinc-400">{{ $report->created_at->translatedFormat('d M Y, H:i') }}</p>
                            </div>
                            <x-status-badge :status="$report->status" />
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
