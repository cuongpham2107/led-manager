<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\EventCalendarWidget;
use App\Filament\Widgets\LatestOrdersWidget;
use App\Filament\Widgets\MonthlyRevenueChartWidget;
use App\Filament\Widgets\StatsOverviewWidget;
use App\Filament\Widgets\WarehouseStatusChartWidget;
use BackedEnum;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;
use JohnRivera7\FilamentWidgetGrid\Concerns\HasWidgetGrid;
use UnitEnum;

class Dashboard extends BaseDashboard
{
    use HasWidgetGrid;

    protected static string|UnitEnum|null $navigationGroup = 'Tổng quan';

    protected static ?string $navigationLabel = 'Tổng quan';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static ?int $navigationSort = 1;

    public function getWidgets(): array
    {
        return [
            StatsOverviewWidget::class,
            WarehouseStatusChartWidget::class,
            MonthlyRevenueChartWidget::class,
            LatestOrdersWidget::class,
            EventCalendarWidget::class,
        ];
    }
}
