<?php

namespace App\Filament\Resources\Assets\Widgets;

use App\Enums\AssetStatus;
use App\Models\Asset;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AssetStatsOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalAssets = Asset::count();
        $totalCost = (float) Asset::sum('purchase_cost');

        $readyCount = Asset::where('current_status', AssetStatus::Ready)->count();
        $readyPercent = $totalAssets > 0 ? round(($readyCount / $totalAssets) * 100, 1) : 0;

        $inEventCount = Asset::where('current_status', AssetStatus::InEvent)->count();
        $inTransitCount = Asset::where('current_status', AssetStatus::InTransit)->count();
        $busyCount = $inEventCount + $inTransitCount;

        $repairingCount = Asset::where('current_status', AssetStatus::Repairing)->count();
        $disposedCount = Asset::where('current_status', AssetStatus::Disposed)->count();

        // Sparkline: Monthly new assets added to stock (6 months)
        $newAssetsTrend = $this->getMonthlyTrend(
            fn (Carbon $month) => Asset::whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->count()
        );

        // Sparkline: Monthly assets out of warehouse (InEvent + InTransit snapshot)
        $busyAssetsTrend = $this->getMonthlyTrend(
            fn (Carbon $month) => Asset::whereIn('current_status', [AssetStatus::InEvent, AssetStatus::InTransit])
                ->whereDate('created_at', '<=', $month->endOfMonth())
                ->count()
        );

        // Sparkline: Monthly repair count trend
        $repairTrend = $this->getMonthlyTrend(
            fn (Carbon $month) => Asset::where('current_status', AssetStatus::Repairing)
                ->whereDate('created_at', '<=', $month->endOfMonth())
                ->count()
        );

        $avgCost = $totalAssets > 0 ? $totalCost / $totalAssets : 0;

        return [
            Stat::make('Tổng thiết bị trong kho', number_format($totalAssets, 0, ',', '.').' thiết bị')
                ->description('Tổng nguyên giá: '.number_format($totalCost, 0, ',', '.').' đ')
                ->descriptionIcon('heroicon-m-cube')
                ->chart($newAssetsTrend)
                ->color('primary'),

            Stat::make('Sẵn sàng trong kho', number_format($readyCount, 0, ',', '.')." thiết bị ({$readyPercent}%)")
                ->description('Thiết bị sẵn sàng xuất kho đi sự kiện')
                ->descriptionIcon('heroicon-m-check-circle')
                ->chart(array_map(fn () => $readyCount, range(1, 6)))
                ->color('success'),

            Stat::make('Đang chạy sự kiện / Vận chuyển', number_format($busyCount, 0, ',', '.').' thiết bị')
                ->description("{$inEventCount} tại sự kiện • {$inTransitCount} đang vận chuyển")
                ->descriptionIcon('heroicon-m-truck')
                ->chart($busyAssetsTrend)
                ->color('warning'),

            Stat::make('Bảo dưỡng / Sửa chữa', number_format($repairingCount, 0, ',', '.').' thiết bị')
                ->description($disposedCount > 0 ? "{$repairingCount} bảo dưỡng • {$disposedCount} đã thanh lý" : 'Cần kiểm tra kỹ thuật')
                ->descriptionIcon('heroicon-m-wrench-screwdriver')
                ->chart($repairTrend)
                ->color($repairingCount > 0 ? 'danger' : 'gray'),
        ];
    }

    /**
     * Generate a 6-month sparkline trend using the given callback.
     *
     * @param  \Closure(Carbon): (int|float)  $callback
     * @return array<int, int|float>
     */
    private function getMonthlyTrend(\Closure $callback): array
    {
        $trend = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $trend[] = $callback($month);
        }

        return $trend;
    }
}
