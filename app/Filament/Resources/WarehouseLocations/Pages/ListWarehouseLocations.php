<?php

namespace App\Filament\Resources\WarehouseLocations\Pages;

use App\Filament\Resources\WarehouseLocations\WarehouseLocationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class ListWarehouseLocations extends ListRecords
{
    protected static string $resource = WarehouseLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon(Heroicon::Plus)
                ->modalHeading('Thêm mới vị trí kho')
                ->modalDescription('Định nghĩa khu vực, kệ hoặc dãy lưu trữ thiết bị trong kho.')
                ->modalWidth(Width::Large),
        ];
    }
}
