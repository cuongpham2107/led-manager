<?php

namespace App\Filament\Resources\Agencies\Pages;

use App\Filament\Resources\Agencies\AgencyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class ListAgencies extends ListRecords
{
    protected static string $resource = AgencyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Thêm đại lý mới')
                ->icon(Heroicon::Plus)
                ->modalHeading('Thêm mới Đại lý tỉnh')
                ->modalDescription('Đăng ký đại lý cấp tỉnh, tỷ lệ % hoa hồng và định mức bàn giao 1.000m².')
                ->modalWidth(Width::FourExtraLarge),
        ];
    }
}
