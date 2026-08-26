<?php

namespace App\Filament\Resources\DeviceTypes\Pages;

use App\Filament\Resources\DeviceTypes\DeviceTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ListDeviceTypes extends ListRecords
{
    protected static string $resource = DeviceTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->modalHeading('Thêm mới loại thiết bị')
                ->modalDescription('Định nghĩa danh mục chủng loại vật tư / thiết bị LED.')
                ->modalWidth(Width::ThreeExtraLarge),
        ];
    }
}
