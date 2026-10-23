<?php

use App\Enums\UsageCategory;
use App\Enums\WispoRequestType;
use App\Models\WispoItem;
use App\Models\WispoRequest;
use App\Services\WispoRequestService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Title('Ajukan Barang')] class extends Component {
    public WispoItem $item;

    public string $request_type = 'permintaan';

    public string $usage_category = 'rutin';

    public ?int $qty = 1;

    public string $purpose = '';

    public ?string $expected_return_at = null;

    public function mount(WispoItem $item): void
    {
        abort_unless($item->is_active, 404);
        $this->authorize('create', WispoRequest::class);
        $this->item = $item;
        $this->expected_return_at = today()->addDays(7)->toDateString();
    }

    protected function rules(): array
    {
        return [
            'request_type' => ['required', 'in:permintaan,peminjaman'],
            'usage_category' => ['required', 'in:'.collect(UsageCategory::cases())->pluck('value')->join(',')],
            'qty' => ['required', 'integer', 'min:1', 'max:'.max(1, $this->item->current_stock_qty)],
            'purpose' => ['required', 'string', 'min:5', 'max:500'],
            'expected_return_at' => ['required_if:request_type,peminjaman', 'nullable', 'date', 'after_or_equal:today'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'qty' => 'jumlah',
            'purpose' => 'tujuan penggunaan',
            'expected_return_at' => 'tanggal rencana kembali',
            'usage_category' => 'kategori pemakaian',
        ];
    }

    public function submit(WispoRequestService $requests): void
    {
        $this->authorize('create', WispoRequest::class);
        $this->validate();

        $requests->submit(
            Auth::user(),
            $this->item->refresh(),
            WispoRequestType::from($this->request_type),
            UsageCategory::from($this->usage_category),
            (int) $this->qty,
            $this->purpose,
            $this->request_type === 'peminjaman' ? Carbon::parse($this->expected_return_at) : null,
        );

        session()->flash('status', 'Pengajuan terkirim. Petugas Gudang akan meninjau dan memberi kabar.');
        $this->redirectRoute('wispo.requests.mine', navigate: true);
    }

    public function with(): array
    {
        return ['usageCategories' => UsageCategory::cases()];
    }
}; ?>

<div class="mx-auto flex w-full max-w-xl flex-col gap-6">
    <div>
        <flux:text class="text-sm"><a href="{{ route('wispo.stock') }}" wire:navigate class="hover:underline">Stok Barang</a> / Ajukan</flux:text>
        <flux:heading size="xl" class="mt-1">Ajukan Barang</flux:heading>
    </div>

    <div class="flex gap-4 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex size-20 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-800">
            @if ($item->photo_path)
                <img src="{{ Storage::disk('public')->url($item->photo_path) }}" alt="{{ $item->name }}" class="size-full object-cover">
            @else
                <flux:icon.cube class="size-8 text-zinc-400" />
            @endif
        </div>
        <div class="min-w-0">
            <p class="font-mono text-xs text-zinc-500">{{ $item->item_code }}</p>
            <p class="font-semibold">{{ $item->name }}</p>
            <p class="text-sm text-zinc-500">Tersedia <span class="font-semibold text-zinc-800 dark:text-zinc-100">{{ $item->current_stock_qty }} {{ $item->unit }}</span>
                @if ($item->storage_location) · {{ $item->storage_location }} @endif</p>
        </div>
    </div>

    @if ($item->current_stock_qty < 1)
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-200">
            Stok barang ini sedang kosong, jadi belum bisa diajukan.
        </div>
    @else
        <form wire:submit="submit" class="flex flex-col gap-5 rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:radio.group wire:model.live="request_type" label="Jenis pengajuan" variant="segmented">
                <flux:radio value="permintaan" label="Permintaan" />
                <flux:radio value="peminjaman" label="Peminjaman" />
            </flux:radio.group>
            <flux:text class="-mt-3 text-xs">
                @if ($request_type === 'peminjaman')
                    Barang dipinjam sementara dan <strong>wajib dikembalikan</strong>.
                @else
                    Barang diambil dan tidak perlu dikembalikan (mis. habis pakai).
                @endif
            </flux:text>

            <flux:input wire:model="qty" type="number" min="1" :max="$item->current_stock_qty" label="Jumlah ({{ $item->unit }})" />

            <flux:select wire:model="usage_category" label="Kategori pemakaian">
                @foreach ($usageCategories as $category)
                    <flux:select.option value="{{ $category->value }}">{{ $category->getLabel() }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:textarea wire:model="purpose" label="Tujuan penggunaan" rows="2" placeholder="Contoh: Komunikasi tim saat inspeksi area boiler." />

            @if ($request_type === 'peminjaman')
                <flux:input wire:model="expected_return_at" type="date" :min="today()->toDateString()" label="Rencana tanggal kembali" />
            @endif

            <flux:button type="submit" variant="primary" class="w-full" wire:loading.attr="disabled">Kirim Pengajuan</flux:button>
        </form>
    @endif
</div>
