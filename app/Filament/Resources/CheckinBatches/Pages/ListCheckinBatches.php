<?php

namespace App\Filament\Resources\CheckinBatches\Pages;

use App\Filament\Resources\CheckinBatches\CheckinBatchResource;
use App\Filament\Resources\CheckinBatches\Widgets\CheckinBatchStatsWidget;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCheckinBatches extends ListRecords
{
    protected static string $resource = CheckinBatchResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            CheckinBatchStatsWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tạo đợt nhập')
                ->icon('heroicon-o-plus'),
        ];
    }
}
