<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Kategori pemakaian barang WISPO.
 */
enum UsageCategory: string implements HasColor, HasLabel
{
    case Rutin = 'rutin';
    case ShutdownWaterCleaning = 'shutdown_water_cleaning';
    case TurnAround = 'turn_around';
    case CrashProgram = 'crash_program';

    public function getLabel(): string
    {
        return match ($this) {
            self::Rutin => 'Rutin',
            self::ShutdownWaterCleaning => 'Shutdown Water Cleaning',
            self::TurnAround => 'Turn Around',
            self::CrashProgram => 'Crash Program',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Rutin => 'gray',
            self::ShutdownWaterCleaning => 'info',
            self::TurnAround => 'warning',
            self::CrashProgram => 'danger',
        };
    }
}
