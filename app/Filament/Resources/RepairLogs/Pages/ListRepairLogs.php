<?php

namespace App\Filament\Resources\RepairLogs\Pages;

use App\Filament\Resources\RepairLogs\RepairLogResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class ListRepairLogs extends ListRecords
{
    protected static string $resource = RepairLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon(Heroicon::Plus)
                ->modalHeading('Tạo phiếu bảo trì & sửa chữa')
                ->modalDescription('Ghi nhận thiết bị gặp sự cố, mô tả hư hỏng và chi phí sửa chữa.')
                ->modalWidth(Width::FourExtraLarge),
        ];
    }
}
