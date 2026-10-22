<?php

namespace App\Filament\Resources\Findings\Schemas;

use App\Models\Finding;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FindingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        $photos = fn (string $type) => fn (Finding $record): array => $record->photos
            ->filter(fn ($photo) => $photo->type->value === $type)
            ->pluck('photo_path')
            ->all();

        return $schema
            ->columns(3)
            ->components([
                Section::make('Laporan')
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('area.name')->label('Area'),
                        TextEntry::make('status')->label('Status')->badge(),
                        TextEntry::make('description')->label('Deskripsi')->columnSpanFull(),
                        ImageEntry::make('before_photos')
                            ->label('Foto sebelum')
                            ->disk('public')
                            ->state($photos('before'))
                            ->imageHeight(240)
                            ->columnSpanFull(),
                        ImageEntry::make('after_photos')
                            ->label('Foto sesudah')
                            ->disk('public')
                            ->state($photos('after'))
                            ->imageHeight(240)
                            ->placeholder('Belum ada')
                            ->columnSpanFull(),
                        TextEntry::make('resolution_note')->label('Catatan PIC')->placeholder('-')->columnSpanFull(),
                        TextEntry::make('revision_note')->label('Catatan revisi')->placeholder('-')->columnSpanFull(),
                        TextEntry::make('rejection_reason')->label('Alasan penolakan')->placeholder('-')->columnSpanFull(),
                    ]),
                Section::make('Penanganan')
                    ->columnSpan(1)
                    ->schema([
                        TextEntry::make('priority')->label('Prioritas')->badge(),
                        TextEntry::make('pic.name')->label('PIC')->placeholder('Belum ditugaskan'),
                        TextEntry::make('deadline')->label('Deadline')->date('d M Y')->placeholder('-'),
                        TextEntry::make('reporter.name')->label('Pelapor'),
                        TextEntry::make('created_at')->label('Dilaporkan')->dateTime('d M Y, H:i'),
                        TextEntry::make('assigned_at')->label('Ditugaskan')->dateTime('d M Y, H:i')->placeholder('-'),
                        TextEntry::make('verifier.name')->label('Diverifikasi oleh')->placeholder('-'),
                        TextEntry::make('resolved_at')->label('Selesai')->dateTime('d M Y, H:i')->placeholder('-'),
                    ]),
            ]);
    }
}
