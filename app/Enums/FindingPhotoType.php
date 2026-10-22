<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Jenis foto temuan.
 */
enum FindingPhotoType: string implements HasColor, HasLabel
{
    case Before = 'before';
    case After = 'after';

    public function getLabel(): string
    {
        return match ($this) {
            self::Before => 'Sebelum',
            self::After => 'Sesudah',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Before => 'gray',
            self::After => 'success',
        };
    }
}
