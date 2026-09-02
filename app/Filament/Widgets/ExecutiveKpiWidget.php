<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Models\Contract;
use App\Models\EventAssignment;
use App\Models\Order;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ExecutiveKpiWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    public static int $gridW = 24;

    public static int $gridH = 4;

    protected function getStats(): array
    {
        $currentMonth = Carbon::now();
        $lastMonth = Carbon::now()->subMonth();

        // 1. Month revenue & growth
        $thisMonthRev = (float) Order::where('status', '!=', OrderStatus::Cancelled)
            ->whereYear('request_date', $currentMonth->year)
            ->whereMonth('request_date', $currentMonth->month)
            ->sum('value');

        $lastMonthRev = (float) Order::where('status', '!=', OrderStatus::Cancelled)
            ->whereYear('request_date', $lastMonth->year)
            ->whereMonth('request_date', $lastMonth->month)
            ->sum('value');

        $growth = $lastMonthRev > 0
            ? round((($thisMonthRev - $lastMonthRev) / $lastMonthRev) * 100, 1)
            : 0;

        // 2. Outstanding Receivables (Công nợ hợp đồng chưa thu)
        $totalContractValue = (float) Contract::sum('contract_value');
        $totalReceived = (float) Order::sum('total_paid');
        $outstandingDebt = max(0, $totalContractValue - $totalReceived);

        // 3. Crew on field (Nhân sự đang trực tiếp thi công sự kiện hôm nay)
        $today = now()->toDateString();
        $crewOnField = EventAssignment::where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->distinct('user_id')
            ->count('user_id');

        // 4. Completed orders this month
        $completedOrdersMonth = Order::where('status', OrderStatus::Completed)
            ->whereYear('updated_at', $currentMonth->year)
            ->whereMonth('updated_at', $currentMonth->month)
            ->count();

        $revTrend = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = Carbon::now()->subMonths($i);
            $revTrend[] = (float) Order::where('status', '!=', OrderStatus::Cancelled)
                ->whereYear('request_date', $m->year)
                ->whereMonth('request_date', $m->month)
                ->sum('value');
        }

        return [
            Stat::make('Doanh Thu Tháng Này', number_format($thisMonthRev, 0, ',', '.').' đ')
                ->description($growth >= 0 ? "+{$growth}% so với tháng trước" : "{$growth}% so với tháng trước")
                ->descriptionIcon($growth >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->chart($revTrend)
                ->color($growth >= 0 ? 'success' : 'danger'),

            Stat::make('Công Nợ Cần Thu', number_format($outstandingDebt, 0, ',', '.').' đ')
                ->description('Tổng công nợ các hợp đồng đang thực hiện')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color($outstandingDebt > 50000000 ? 'danger' : 'warning'),

            Stat::make('KTV Đang Đi Sự Kiện', "{$crewOnField} nhân sự")
                ->description('Đang phụ trách hiện trường hôm nay')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('info'),

            Stat::make('Dự Án Hoàn Tất Tháng', (string) $completedOrdersMonth)
                ->description('Đơn hàng đã hoàn thành và trả kho xong')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('emerald'),
        ];
    }
}
