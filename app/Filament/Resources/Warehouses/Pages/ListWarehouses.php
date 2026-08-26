<?php

namespace App\Filament\Resources\Warehouses\Pages;

use App\Filament\Resources\Warehouses\WarehouseResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ListWarehouses extends ListRecords
{
    protected static string $resource = WarehouseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->modalHeading('Thêm mới kho hàng')
                ->modalDescription('Nhập thông tin kho hàng lưu trữ và quản lý thiết bị LED.')
                ->modalWidth(Width::FourExtraLarge),
        ];
    }
}
