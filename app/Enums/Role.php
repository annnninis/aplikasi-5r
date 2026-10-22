<?php

namespace App\Enums;

enum Role: string
{
    case Staff = 'staff';
    case Koordinator5R = 'koordinator_5r';
    case Management = 'management';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Staff => 'Staff',
            self::Koordinator5R => 'Koordinator 5R',
            self::Management => 'Management',
            self::Admin => 'Admin',
        };
    }

    /**
     * Pilihan untuk form/select: ['staff' => 'Staff', ...].
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $role) => [$role->value => $role->label()])
            ->all();
    }
}
