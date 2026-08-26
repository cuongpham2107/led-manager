<?php

namespace App\Filament\Resources\Assets\Pages;

use App\Filament\Resources\Assets\AssetResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ListAssets extends ListRecords
{
    protected static string $resource = AssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->modalHeading('Thêm thiết bị mới vào kho')
                ->modalDescription('Nhập mã serial, loại thiết bị và vị trí kho lưu trữ ban đầu.')
                ->modalWidth(Width::FourExtraLarge),
        ];
    }
}
