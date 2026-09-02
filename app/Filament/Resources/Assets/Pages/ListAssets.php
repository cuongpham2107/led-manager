<?php

namespace App\Filament\Resources\Assets\Pages;

use App\Enums\AssetStatus;
use App\Filament\Resources\Assets\AssetResource;
use App\Filament\Resources\Assets\Widgets\AssetStatsOverviewWidget;
use App\Models\Asset;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListAssets extends ListRecords
{
    protected static string $resource = AssetResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Tất cả')
                ->badge(Asset::count()),
            'ready' => Tab::make('Sẵn sàng')
                ->badge(Asset::where('current_status', AssetStatus::Ready)->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('current_status', AssetStatus::Ready)),
            'in_event' => Tab::make('Đang đi sự kiện')
                ->badge(Asset::where('current_status', AssetStatus::InEvent)->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('current_status', AssetStatus::InEvent)),
            'in_transit' => Tab::make('Đang vận chuyển')
                ->badge(Asset::where('current_status', AssetStatus::InTransit)->count())
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('current_status', AssetStatus::InTransit)),
            'repairing' => Tab::make('Bảo dưỡng / Sửa chữa')
                ->badge(Asset::where('current_status', AssetStatus::Repairing)->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('current_status', AssetStatus::Repairing)),
            'disposed' => Tab::make('Thanh lý')
                ->badge(Asset::where('current_status', AssetStatus::Disposed)->count())
                ->badgeColor('gray')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('current_status', AssetStatus::Disposed)),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            AssetStatsOverviewWidget::class,
        ];
    }
}
