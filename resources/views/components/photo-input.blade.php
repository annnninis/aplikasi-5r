@props([
    'model',
    'label' => 'Foto',
    'resetOn' => 'photo-input-reset',
])

{{-- Input foto ramah HP: buka kamera, perkecil di browser, lalu unggah ke properti Livewire $model. --}}
<div
    x-data="{ preview: null, uploading: false, progress: 0 }"
    x-on:livewire-upload-start="uploading = true"
    x-on:livewire-upload-finish="uploading = false"
    x-on:livewire-upload-error="uploading = false"
    x-on:livewire-upload-progress="progress = $event.detail.progress"
    x-on:{{ $resetOn }}.window="preview = null; $refs.photoInput.value = ''"
    class="flex flex-col gap-2"
>
    <flux:label>{{ $label }} <span class="text-red-500">*</span></flux:label>

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
                $wire.upload('{{ $model }}', small);
            "
        >
    </label>

    <div x-show="uploading" class="h-1.5 w-full overflow-hidden rounded bg-zinc-200 dark:bg-zinc-700">
        <div class="h-full bg-accent transition-all" :style="`width: ${progress}%`"></div>
    </div>
    <span x-show="preview && !uploading" class="text-xs text-zinc-500">Ketuk foto untuk mengganti.</span>
    <flux:error :name="$model" />
</div>
