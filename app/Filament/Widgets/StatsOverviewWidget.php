<?php

namespace App\Filament\Widgets;

use App\Enums\AssetStatus;
use App\Enums\OrderStatus;
use App\Enums\QuotationStatus;
use App\Models\Asset;
use App\Models\Order;
use App\Models\Quotation;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    public static int $gridW = 24;

    public static int $gridH = 4;

    protected function getStats(): array
    {
        $whId = auth()->user()?->getScopedWarehouseId();

        $orderQuery = Order::query();
        $assetQuery = Asset::query();

        if ($whId) {
            $orderQuery->where('warehouse_id', $whId);
            $assetQuery->where('current_warehouse_id', $whId);
        }

        $totalRevenue = (float) (clone $orderQuery)->where('status', '!=', OrderStatus::Cancelled)->sum('value');
        $totalPaid = (float) (clone $orderQuery)->sum('total_paid');

        $activeOrders = (clone $orderQuery)->whereIn('status', [
            OrderStatus::OutboundCreated,
            OrderStatus::Dispatched,
        ])->count();

        $totalAssets = (clone $assetQuery)->count();
        $readyAssets = (clone $assetQuery)->where('current_status', AssetStatus::Ready)->count();
        $utilizationRate = $totalAssets > 0 ? round((($totalAssets - $readyAssets) / $totalAssets) * 100, 1) : 0;

        $pendingQuotations = Quotation::whereNotIn('status', [
            QuotationStatus::Converted,
            QuotationStatus::Expired,
            QuotationStatus::Rejected,
        ])->count();

        // Sparkline: Monthly revenue trend (6 months)
        $revenueTrend = $this->getMonthlyTrend(
            fn (Carbon $month) => (float) (clone $orderQuery)->where('status', '!=', OrderStatus::Cancelled)
                ->whereYear('request_date', $month->year)
                ->whereMonth('request_date', $month->month)
                ->sum('value')
        );

        // Sparkline: Monthly active orders trend
        $ordersTrend = $this->getMonthlyTrend(
            fn (Carbon $month) => (clone $orderQuery)->whereYear('request_date', $month->year)
                ->whereMonth('request_date', $month->month)
                ->count()
        );

        // Sparkline: Monthly utilization trend
        $utilizationTrend = $this->getMonthlyTrend(
            fn (Carbon $month) => (clone $assetQuery)->where('current_status', '!=', AssetStatus::Ready)
                ->whereDate('created_at', '<=', $month->endOfMonth())
                ->count()
        );

        // Sparkline: Monthly quotation trend
        $quotationTrend = $this->getMonthlyTrend(
            fn (Carbon $month) => Quotation::whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->count()
        );

        $collectionRate = $totalRevenue > 0 ? round(($totalPaid / $totalRevenue) * 100, 1) : 0;

        return [
            Stat::make('Tổng Doanh Thu Hợp Đồng', number_format($totalRevenue, 0, ',', '.').' đ')
                ->description("Đã thu {$collectionRate}%: ".number_format($totalPaid, 0, ',', '.').' đ')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart($revenueTrend)
                ->color('success'),

            Stat::make('Đơn Hàng Đang Chạy', (string) $activeOrders)
                ->description('Đang xuất kho hoặc đang đi sự kiện')
                ->descriptionIcon('heroicon-m-truck')
                ->chart($ordersTrend)
                ->color('warning'),

            Stat::make('Tỷ Lệ Khai Thác Kho', "{$utilizationRate}%")
                ->description("{$readyAssets} / {$totalAssets} thiết bị sẵn sàng trong kho")
                ->descriptionIcon('heroicon-m-cube')
                ->chart($utilizationTrend)
                ->color($utilizationRate > 70 ? 'danger' : 'info'),

            Stat::make('Báo Giá Cần Xử Lý', (string) $pendingQuotations)
                ->description('Báo giá nháp / đã gửi / chờ chuyển đơn')
                ->descriptionIcon('heroicon-m-document-text')
                ->chart($quotationTrend)
                ->color('primary'),
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
