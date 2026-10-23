<?php

namespace App\Filament\Pages;

use App\Enums\Role;
use App\Filament\Pages\Wispo\Widgets\ActiveLoans;
use App\Filament\Pages\Wispo\Widgets\LowStockItems;
use App\Filament\Pages\Wispo\Widgets\RecentTransactions;
use App\Filament\Pages\Wispo\Widgets\WispoStats;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

/**
 * Ringkasan WISPO (PRD MVP B7): sekilas kondisi gudang untuk Petugas Gudang, Admin, dan Management.
 */
class WarehouseSummary extends Page
{
    protected string $view = 'filament.pages.warehouse-summary';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Gudang (WISPO)';

    protected static ?int $navigationSort = 0;

    protected static ?string $navigationLabel = 'Ringkasan Gudang';

    protected static ?string $title = 'Ringkasan Gudang';

    protected static ?string $slug = 'ringkasan-gudang';

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user !== null && ($user->isAdmin() || $user->isPetugasGudang() || $user->hasRole(Role::Management));
    }

    protected function getHeaderWidgets(): array
    {
        return [
            WispoStats::class,
            LowStockItems::class,
            ActiveLoans::class,
            RecentTransactions::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 2;
    }
}
