<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum Role: string implements HasColor, HasLabel
{
    case Staff = 'staff';
    case Koordinator5R = 'koordinator_5r';
    case Management = 'management';
    case Admin = 'admin';

    public function getLabel(): string
    {
        return match ($this) {
            self::Staff => 'Staff',
            self::Koordinator5R => 'Koordinator 5R',
            self::Management => 'Management',
            self::Admin => 'Admin',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Staff => 'gray',
            self::Koordinator5R => 'info',
            self::Management => 'warning',
            self::Admin => 'danger',
        };
    }
}
