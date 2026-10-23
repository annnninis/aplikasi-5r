<?php

namespace App\Filament\Resources\WispoItems\Pages;

use App\Filament\Resources\WispoItems\WispoItemResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Livewire\Attributes\On;

class ViewWispoItem extends ViewRecord
{
    protected static string $resource = WispoItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }

    /**
     * Stok di kartu atas ikut berubah setelah transaksi dicatat dari tabel riwayat.
     */
    #[On('refresh-item')]
    public function refreshItem(): void
    {
        $this->record->refresh();
    }
}
