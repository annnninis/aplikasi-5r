<?php

use App\Models\WispoItem;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Title('Stok Barang')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $category = '';

    #[Url]
    public bool $lowOnly = false;

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'category', 'lowOnly'], true)) {
            $this->resetPage();
        }
    }

    public function with(): array
    {
        $items = WispoItem::active()
            ->when($this->search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'ilike', "%{$this->search}%")
                ->orWhere('item_code', 'ilike', "%{$this->search}%")
                ->orWhere('brand_model', 'ilike', "%{$this->search}%")))
            ->when($this->category !== '', fn ($q) => $q->where('category', $this->category))
            ->when($this->lowOnly, fn ($q) => $q->lowStock())
            ->orderBy('name')
            ->paginate(24);

        return [
            'items' => $items,
            'categories' => WispoItem::active()->distinct()->orderBy('category')->pluck('category'),
        ];
    }
}; ?>

<div class="flex w-full flex-col gap-6">
    <div>
        <flux:heading size="xl">Stok Barang WISPO</flux:heading>
        <flux:text class="mt-1">Stok alat pendukung kerja di Gudang SPO 3A.</flux:text>
    </div>

    <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
        <flux:input wire:model.live.debounce.400ms="search" icon="magnifying-glass" placeholder="Cari nama atau kode barang..." class="sm:max-w-sm" />
        <flux:select wire:model.live="category" class="sm:max-w-56">
            <flux:select.option value="">Semua kategori</flux:select.option>
            @foreach ($categories as $cat)
                <flux:select.option value="{{ $cat }}">{{ $cat }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:checkbox wire:model.live="lowOnly" label="Hanya stok menipis" class="sm:pb-2" />
    </div>

    @if ($items->isEmpty())
        <div class="rounded-xl border border-dashed border-zinc-300 p-8 text-center text-sm text-zinc-500 dark:border-zinc-600">
            Tidak ada barang yang cocok.
        </div>
    @else
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
            @foreach ($items as $item)
                <div wire:key="{{ $item->id }}" class="flex flex-col overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="flex aspect-square items-center justify-center bg-zinc-100 dark:bg-zinc-800">
                        @if ($item->photo_path)
                            <img src="{{ Storage::disk('public')->url($item->photo_path) }}" alt="{{ $item->name }}" loading="lazy" class="size-full object-cover">
                        @else
                            <flux:icon.cube class="size-10 text-zinc-400" />
                        @endif
                    </div>
                    <div class="flex flex-1 flex-col gap-1 p-3">
                        <p class="font-mono text-xs text-zinc-500">{{ $item->item_code }}</p>
                        <p class="line-clamp-2 text-sm font-semibold leading-snug">{{ $item->name }}</p>
                        <p class="text-xs text-zinc-500">{{ $item->category }}@if ($item->storage_location) · {{ $item->storage_location }}@endif</p>
                        <div class="mt-auto flex items-center justify-between gap-2 pt-2">
                            <span class="text-lg font-bold">{{ $item->current_stock_qty }} <span class="text-xs font-normal text-zinc-500">{{ $item->unit }}</span></span>
                            @if ($item->isLowStock())
                                <flux:badge size="sm" color="red">Menipis</flux:badge>
                            @else
                                <flux:badge size="sm" color="green">Aman</flux:badge>
                            @endif
                        </div>
                        @if ($item->current_stock_qty > 0)
                            <flux:button size="sm" variant="primary" class="mt-2 w-full" :href="route('wispo.requests.create', $item)" wire:navigate>Ajukan</flux:button>
                        @else
                            <flux:button size="sm" class="mt-2 w-full" disabled>Stok kosong</flux:button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        {{ $items->links() }}
    @endif
</div>
