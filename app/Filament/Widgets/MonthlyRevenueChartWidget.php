<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class MonthlyRevenueChartWidget extends ChartWidget
{
    protected ?string $heading = 'Doanh Thu 6 Tháng Gần Nhất';

    protected ?string $description = 'Biểu đồ doanh thu hợp đồng theo tháng (triệu VNĐ)';

    protected static ?int $sort = 3;

    public static int $gridW = 6;

    public static int $gridH = 10;

    protected ?string $maxHeight = '280px';

    protected function getData(): array
    {
        $months = [];
        $values = [];

        /** @var User|null $user */
        $user = Auth::user();
        $agencyId = $user?->getScopedAgencyId();
        $whId = $user?->getScopedWarehouseId();

        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthLabel = 'T'.$month->format('m/Y');
            $months[] = $monthLabel;

            $q = Order::where('status', '!=', OrderStatus::Cancelled)
                ->whereYear('request_date', $month->year)
                ->whereMonth('request_date', $month->month);

            if ($agencyId) {
                $q->where(function ($sub) use ($agencyId, $whId) {
                    $sub->where('agency_id', $agencyId);
                    if ($whId) {
                        $sub->orWhere('warehouse_id', $whId);
                    }
                });
            } elseif ($whId) {
                $q->where('warehouse_id', $whId);
            }

            $monthTotal = $q->sum('value');
            $values[] = round($monthTotal / 1000000, 1);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Doanh thu (triệu đ)',
                    'data' => $values,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.8)',
                    'borderColor' => '#2563eb',
                    'borderRadius' => 8,
                    'borderWidth' => 1,
                    'hoverBackgroundColor' => 'rgba(37, 99, 235, 1)',
                ],
            ],
            'labels' => $months,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'grid' => [
                        'color' => 'rgba(156, 163, 175, 0.15)',
                    ],
                    'ticks' => [
                        'callback' => '{{callback}}',
                    ],
                ],
                'x' => [
                    'grid' => [
                        'display' => false,
                    ],
                ],
            ],
        ];
    }
}
