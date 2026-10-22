<?php

namespace App\Filament\Resources\Findings;

use App\Enums\FindingStatus;
use App\Filament\Resources\Findings\Pages\ListFindings;
use App\Filament\Resources\Findings\Pages\ViewFinding;
use App\Filament\Resources\Findings\Schemas\FindingInfolist;
use App\Filament\Resources\Findings\Tables\FindingsTable;
use App\Models\Finding;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class FindingResource extends Resource
{
    protected static ?string $model = Finding::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static string|UnitEnum|null $navigationGroup = 'Temuan 5R';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'temuan';

    protected static ?string $pluralModelLabel = 'Semua Temuan';

    public static function getNavigationBadge(): ?string
    {
        $count = Finding::where('status', FindingStatus::BelumDitugaskan)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Laporan baru yang belum ditugaskan';
    }

    public static function infolist(Schema $schema): Schema
    {
        return FindingInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FindingsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFindings::route('/'),
            'view' => ViewFinding::route('/{record}'),
        ];
    }
}
