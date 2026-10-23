<?php

namespace App\Filament\Resources\WispoItems;

use App\Filament\Resources\WispoItems\Pages\CreateWispoItem;
use App\Filament\Resources\WispoItems\Pages\EditWispoItem;
use App\Filament\Resources\WispoItems\Pages\ListWispoItems;
use App\Filament\Resources\WispoItems\Pages\ViewWispoItem;
use App\Filament\Resources\WispoItems\Schemas\WispoItemForm;
use App\Filament\Resources\WispoItems\Schemas\WispoItemInfolist;
use App\Filament\Resources\WispoItems\Tables\WispoItemsTable;
use App\Models\WispoItem;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class WispoItemResource extends Resource
{
    protected static ?string $model = WispoItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static string|UnitEnum|null $navigationGroup = 'Gudang (WISPO)';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'barang';

    protected static ?string $modelLabel = 'barang';

    protected static ?string $pluralModelLabel = 'Master Barang';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationBadge(): ?string
    {
        $low = WispoItem::active()->lowStock()->count();

        return $low > 0 ? (string) $low : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Barang dengan stok menipis';
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'item_code'];
    }

    public static function form(Schema $schema): Schema
    {
        return WispoItemForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return WispoItemInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WispoItemsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWispoItems::route('/'),
            'create' => CreateWispoItem::route('/create'),
            'view' => ViewWispoItem::route('/{record}'),
            'edit' => EditWispoItem::route('/{record}/edit'),
        ];
    }
}
