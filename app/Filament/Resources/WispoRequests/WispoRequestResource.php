<?php

namespace App\Filament\Resources\WispoRequests;

use App\Enums\WispoRequestStatus;
use App\Filament\Resources\WispoRequests\Pages\ListWispoRequests;
use App\Filament\Resources\WispoRequests\Tables\WispoRequestsTable;
use App\Models\WispoRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class WispoRequestResource extends Resource
{
    protected static ?string $model = WispoRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxStack;

    protected static string|UnitEnum|null $navigationGroup = 'Gudang (WISPO)';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'permintaan-barang';

    protected static ?string $modelLabel = 'permintaan';

    protected static ?string $pluralModelLabel = 'Permintaan & Peminjaman';

    public static function getNavigationBadge(): ?string
    {
        $pending = WispoRequest::where('status', WispoRequestStatus::MenungguPersetujuan)->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Menunggu persetujuan';
    }

    public static function table(Table $table): Table
    {
        return WispoRequestsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWispoRequests::route('/'),
        ];
    }
}
