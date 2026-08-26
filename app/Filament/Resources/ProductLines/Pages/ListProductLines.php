<?php

namespace App\Filament\Resources\ProductLines\Pages;

use App\Filament\Resources\ProductLines\ProductLineResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ListProductLines extends ListRecords
{
    protected static string $resource = ProductLineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->modalHeading('Thêm dòng sản phẩm LED mới')
                ->modalDescription('Nhập thông số kỹ thuật (Pixel Pitch, kích thước cabinet, độ sáng, tỷ lệ dự phòng) cho dòng màn hình LED.')
                ->modalWidth(Width::FiveExtraLarge),
        ];
    }
}
