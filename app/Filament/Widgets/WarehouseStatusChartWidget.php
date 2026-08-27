<?php

namespace App\Filament\Widgets;

use App\Enums\AssetStatus;
use App\Models\Asset;
use Filament\Widgets\ChartWidget;

class WarehouseStatusChartWidget extends ChartWidget
{
    protected ?string $heading = 'Phân Bổ Tình Trạng Thiết Bị';

    protected ?string $description = 'Tỷ lệ theo trạng thái hiện tại của toàn bộ thiết bị trong kho';

    protected static ?int $sort = 2;

    public static int $gridW = 6;

    public static int $gridH = 10;

    protected ?string $maxHeight = '280px';

    protected function getData(): array
    {
        $ready = Asset::where('current_status', AssetStatus::Ready)->count();
        $inEvent = Asset::where('current_status', AssetStatus::InEvent)->count();
        $inTransit = Asset::where('current_status', AssetStatus::InTransit)->count();
        $repairing = Asset::where('current_status', AssetStatus::Repairing)->count();
        $disposed = Asset::where('current_status', AssetStatus::Disposed)->count();

        return [
            'datasets' => [
                [
                    'label' => 'Số lượng thiết bị',
                    'data' => [$ready, $inEvent, $inTransit, $repairing, $disposed],
                    'backgroundColor' => [
                        'rgba(16, 185, 129, 0.85)',  // emerald for Ready
                        'rgba(59, 130, 246, 0.85)',  // blue for InEvent
                        'rgba(245, 158, 11, 0.85)',  // amber for InTransit
                        'rgba(239, 68, 68, 0.85)',   // red for Repairing
                        'rgba(107, 114, 128, 0.7)',   // gray for Disposed
                    ],
                    'borderColor' => [
                        '#059669',
                        '#2563eb',
                        '#d97706',
                        '#dc2626',
                        '#4b5563',
                    ],
                    'borderWidth' => 2,
                    'hoverOffset' => 8,
                ],
            ],
            'labels' => [
                "Sẵn sàng ({$ready})",
                "Đang sự kiện ({$inEvent})",
                "Đang vận chuyển ({$inTransit})",
                "Đang bảo trì ({$repairing})",
                "Thanh lý ({$disposed})",
            ],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                    'labels' => [
                        'usePointStyle' => true,
                        'pointStyle' => 'circle',
                        'padding' => 16,
                    ],
                ],
            ],
            'cutout' => '60%',
        ];
    }
}
