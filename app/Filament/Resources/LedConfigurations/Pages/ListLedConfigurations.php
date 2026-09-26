<?php

namespace App\Filament\Resources\LedConfigurations\Pages;

use App\Filament\Resources\LedConfigurations\LedConfigurationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class ListLedConfigurations extends ListRecords
{
    protected static string $resource = LedConfigurationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon(Heroicon::Plus)
                ->modalHeading('Thêm cấu hình LED')
                ->modalDescription('Các tấm cùng dòng sản phẩm phải cùng card nhận, kiểu quét và đầu phát mới ghép được thành một màn.')
                ->modalWidth(Width::ThreeExtraLarge),
        ];
    }
}
