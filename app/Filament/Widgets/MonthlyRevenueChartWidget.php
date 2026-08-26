<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Models\Order;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class MonthlyRevenueChartWidget extends ChartWidget
{
    protected ?string $heading = 'Doanh Thu 6 Tháng Gần Nhất (Triệu VNĐ)';

    protected static ?int $sort = 3;

    public static int $gridW = 12;

    public static int $gridH = 8;

    protected ?string $maxHeight = '280px';

    protected function getData(): array
    {
        $months = [];
        $values = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthLabel = 'T'.$month->format('m/Y');
            $months[] = $monthLabel;

            $monthTotal = Order::where('status', '!=', OrderStatus::Cancelled)
                ->whereYear('request_date', $month->year)
                ->whereMonth('request_date', $month->month)
                ->sum('value');

            $values[] = round($monthTotal / 1000000, 1);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Doanh thu (triệu đ)',
                    'data' => $values,
                    'backgroundColor' => '#3b82f6',
                    'borderColor' => '#1d4ed8',
                    'borderRadius' => 6,
                ],
            ],
            'labels' => $months,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
