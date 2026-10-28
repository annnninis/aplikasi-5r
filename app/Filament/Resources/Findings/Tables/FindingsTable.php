<?php

namespace App\Filament\Resources\Findings\Tables;

use App\Enums\FindingPriority;
use App\Enums\FindingStatus;
use App\Filament\Resources\Findings\FindingActions;
use App\Filament\Support\EnumSearch;
use App\Models\Finding;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FindingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['area', 'reporter', 'pic', 'photos']))
            ->defaultSort('created_at', 'desc')
            ->searchPlaceholder('Cari area, deskripsi, status, prioritas, PIC, pelapor')
            ->columns([
                ImageColumn::make('before_photo')
                    ->label('Foto')
                    ->disk('public')
                    ->state(fn (Finding $record): ?string => $record->photos->firstWhere('type.value', 'before')?->photo_path)
                    ->square()
                    ->imageSize(48),
                TextColumn::make('area.name')
                    ->label('Area')
                    ->description(fn (Finding $record): string => str($record->description)->limit(60))
                    // Cari di deskripsi dan nama area.
                    ->searchable(query: fn (Builder $query, string $search) => $query->where(fn (Builder $q) => $q
                        ->where('description', 'ilike', '%'.trim($search).'%')
                        ->orWhereHas('area', fn (Builder $a) => $a->where('name', 'ilike', '%'.trim($search).'%'))))
                    ->wrap(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->searchable(query: EnumSearch::query('status', FindingStatus::class)),
                TextColumn::make('priority')
                    ->label('Prioritas')
                    ->badge()
                    ->searchable(query: EnumSearch::query('priority', FindingPriority::class))
                    ->sortable(),
                TextColumn::make('pic.name')
                    ->label('PIC')
                    ->searchable(query: EnumSearch::relation('pic', 'name'))
                    ->placeholder('Belum ada'),
                TextColumn::make('deadline')
                    ->label('Deadline')
                    ->date('d M Y')
                    ->sortable()
                    ->placeholder('-')
                    ->color(fn (Finding $record): ?string => $record->status === FindingStatus::Terlambat ? 'danger' : null),
                TextColumn::make('reporter.name')
                    ->label('Pelapor')
                    ->searchable(query: EnumSearch::relation('reporter', 'name'))
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Dilaporkan')
                    ->since()
                    ->dateTimeTooltip('d M Y, H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('area_id')
                    ->label('Area')
                    ->relationship('area', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('priority')
                    ->label('Prioritas')
                    ->options(FindingPriority::class),
                SelectFilter::make('pic_id')
                    ->label('PIC')
                    ->relationship('pic', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                ViewAction::make(),
                ActionGroup::make([
                    FindingActions::verify(),
                    FindingActions::requestRevision(),
                    FindingActions::assign(),
                    FindingActions::reject(),
                ]),
            ]);
    }
}
