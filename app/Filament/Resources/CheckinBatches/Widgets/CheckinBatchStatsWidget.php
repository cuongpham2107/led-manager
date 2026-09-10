<?php

namespace App\Filament\Resources\CheckinBatches\Widgets;

use App\Enums\BatchStatus;
use App\Models\CheckinBatch;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class CheckinBatchStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        /** @var User|null $user */
        $user = Auth::user();
        $whId = $user?->getScopedWarehouseId();

        $base = CheckinBatch::query();
        if ($whId) {
            $base->where('warehouse_id', $whId);
        }

        $totalBatches = (clone $base)->count();
        $pendingCount = (clone $base)->where('status', BatchStatus::Pending)->count();
        $inProgressCount = (clone $base)->where('status', BatchStatus::InProgress)->count();
        $completedCount = (clone $base)->where('status', BatchStatus::Completed)->count();

        return [
            Stat::make('Tổng đợt nhập', number_format($totalBatches, 0, ',', '.').' đợt')
                ->description('Toàn bộ đợt nhập kho')
                ->descriptionIcon('heroicon-m-inbox-stack')
                ->color('primary'),

            Stat::make('Chờ xử lý', number_format($pendingCount, 0, ',', '.').' đợt')
                ->description('Đợt nhập đang chờ')
                ->descriptionIcon('heroicon-m-clock')
                ->color('gray'),

            Stat::make('Đang quét PDA', number_format($inProgressCount, 0, ',', '.').' đợt')
                ->description('Đang nhận hàng trong kho')
                ->descriptionIcon('heroicon-m-viewfinder-circle')
                ->color('warning'),

            Stat::make('Đã hoàn tất', number_format($completedCount, 0, ',', '.').' đợt')
                ->description('Nhận hàng thành công')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),
        ];
    }
}
