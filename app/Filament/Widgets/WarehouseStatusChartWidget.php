<?php

namespace App\Filament\Widgets;

use App\Enums\AssetStatus;
use App\Models\Asset;
use Filament\Widgets\ChartWidget;

class WarehouseStatusChartWidget extends ChartWidget
{
    protected ?string $heading = 'Phân Bổ Tình Trạng Thiết Bị Kho';

    protected static ?int $sort = 2;

    public static int $gridW = 12;

    public static int $gridH = 8;

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
                        '#10b981', // green for Ready
                        '#3b82f6', // blue for InEvent
                        '#f59e0b', // amber for InTransit
                        '#ef4444', // red for Repairing
                        '#6b7280', // gray for Disposed
                    ],
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
}
