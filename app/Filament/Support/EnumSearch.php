<?php

namespace App\Filament\Support;

use BackedEnum;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Database\Eloquent\Builder;

/**
 * Pencarian tabel Filament berdasarkan label enum yang tampil di layar (mis. "tinggi" → priority = tinggi,
 * "terlambat" → status = terlambat), karena kolom enum menyimpan nilai, bukan label.
 */
class EnumSearch
{
    /**
     * @param  class-string<BackedEnum&HasLabel>  $enum
     */
    public static function query(string $column, string $enum): \Closure
    {
        return function (Builder $query, string $search) use ($column, $enum): Builder {
            $needle = mb_strtolower(trim($search));

            $matches = collect($enum::cases())
                ->filter(fn (BackedEnum&HasLabel $case) => str_contains(mb_strtolower($case->getLabel()), $needle)
                    || str_contains(mb_strtolower((string) $case->value), $needle))
                ->map(fn (BackedEnum $case) => $case->value)
                ->values()
                ->all();

            // Tanpa kecocokan: tambahkan kondisi yang selalu salah agar OR dengan kolom lain tetap benar.
            return $matches === []
                ? $query->whereRaw('1 = 0')
                : $query->whereIn($column, $matches);
        };
    }

    /**
     * Pencarian teks tidak peka huruf besar/kecil (PostgreSQL ILIKE).
     */
    public static function text(string $column): \Closure
    {
        return fn (Builder $query, string $search): Builder => $query->where($column, 'ilike', '%'.trim($search).'%');
    }

    /**
     * Pencarian teks pada relasi, mis. nama area atau nama PIC.
     */
    public static function relation(string $relation, string $column): \Closure
    {
        return fn (Builder $query, string $search): Builder => $query->whereHas(
            $relation,
            fn (Builder $q) => $q->where($column, 'ilike', '%'.trim($search).'%'),
        );
    }
}
