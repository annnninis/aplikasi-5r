<?php

namespace App\Filament\Resources\Findings;

use App\Enums\FindingPriority;
use App\Enums\FindingStatus;
use App\Models\Finding;
use App\Models\User;
use App\Services\FindingService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

/**
 * Aksi temuan yang dipakai di tabel dan halaman detail. Logika status ada di FindingService.
 */
class FindingActions
{
    public static function assign(): Action
    {
        return Action::make('assign')
            ->label(fn (Finding $record): string => $record->pic_id ? 'Ganti PIC' : 'Tugaskan PIC')
            ->icon('heroicon-o-user-plus')
            ->color('primary')
            ->visible(fn (Finding $record): bool => in_array($record->status, [
                FindingStatus::BelumDitugaskan,
                FindingStatus::BelumSelesai,
                FindingStatus::Terlambat,
            ], true))
            ->authorize(fn (Finding $record): bool => Auth::user()->can('manage', $record))
            ->modalHeading('Tugaskan PIC')
            ->modalDescription('Deadline dihitung otomatis dari prioritas: Tinggi 1 hari, Sedang 7 hari, Rendah 14 hari.')
            ->fillForm(fn (Finding $record): array => [
                'pic_id' => $record->pic_id ?? $record->area->default_pic_id,
                'priority' => $record->priority,
            ])
            ->schema([
                Select::make('pic_id')
                    ->label('PIC')
                    ->options(fn (): array => User::where('is_active', true)->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->required()
                    ->helperText('Terisi otomatis dengan PIC default area, boleh diganti.'),
                Select::make('priority')
                    ->label('Prioritas')
                    ->options(FindingPriority::class)
                    ->required(),
            ])
            ->action(function (Finding $record, array $data): void {
                app(FindingService::class)->assign(
                    $record,
                    User::findOrFail($data['pic_id']),
                    $data['priority'] instanceof FindingPriority ? $data['priority'] : FindingPriority::from($data['priority']),
                );

                Notification::make()
                    ->title('PIC ditugaskan')
                    ->body("Deadline: {$record->deadline->translatedFormat('d M Y')}")
                    ->success()
                    ->send();
            });
    }

    public static function reject(): Action
    {
        return Action::make('reject')
            ->label('Tolak')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->visible(fn (Finding $record): bool => $record->status === FindingStatus::BelumDitugaskan)
            ->authorize(fn (Finding $record): bool => Auth::user()->can('manage', $record))
            ->modalHeading('Tolak laporan')
            ->modalDescription('Gunakan untuk laporan duplikat atau yang bukan masalah 5R. Pelapor akan melihat alasannya.')
            ->schema([
                Textarea::make('reason')
                    ->label('Alasan penolakan')
                    ->required()
                    ->maxLength(500),
            ])
            ->action(function (Finding $record, array $data): void {
                app(FindingService::class)->reject($record, $data['reason']);

                Notification::make()->title('Laporan ditolak')->success()->send();
            });
    }
}
