<?php

namespace App\Filament\Widgets;

use App\Enums\AssetStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentType;
use App\Enums\QuotationStatus;
use App\Models\Asset;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Quotation;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    public static int $gridW = 24;

    public static int $gridH = 4;

    protected function getStats(): array
    {
        $totalRevenue = (float) Order::where('status', '!=', OrderStatus::Cancelled)->sum('value');
        $activeOrders = Order::whereIn('status', [
            OrderStatus::OutboundCreated,
            OrderStatus::Dispatched,
        ])->count();

        $totalAssets = Asset::count();
        $readyAssets = Asset::where('current_status', AssetStatus::Ready)->count();
        $utilizationRate = $totalAssets > 0 ? round((($totalAssets - $readyAssets) / $totalAssets) * 100, 1) : 0;

        $pendingQuotations = Quotation::whereIn('status', [
            QuotationStatus::Draft,
            QuotationStatus::Sent,
            QuotationStatus::Approved,
        ])->count();

        $totalPaid = (float) Payment::where('type', '!=', PaymentType::Refund)->sum('amount');

        return [
            Stat::make('Tổng Doanh Thu Hợp Đồng', number_format($totalRevenue, 0, ',', '.').' đ')
                ->description('Đã thu tiền thực tế: '.number_format($totalPaid, 0, ',', '.').' đ')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Đơn Hàng Đang Chạy', (string) $activeOrders)
                ->description('Đang xuất kho hoặc đang đi sự kiện')
                ->descriptionIcon('heroicon-m-truck')
                ->color('warning'),

            Stat::make('Tỷ Lệ Khai Thác Kho', "{$utilizationRate}%")
                ->description("{$readyAssets} / {$totalAssets} thiết bị sẵn sàng trong kho")
                ->descriptionIcon('heroicon-m-cube')
                ->color($utilizationRate > 70 ? 'danger' : 'info'),

            Stat::make('Báo Giá Cần Xử Lý', (string) $pendingQuotations)
                ->description('Báo giá nháp / đã gửi / chờ chuyển đơn')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary'),
        ];
    }
}
