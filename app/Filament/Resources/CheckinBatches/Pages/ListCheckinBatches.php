<?php

namespace App\Filament\Resources\CheckinBatches\Pages;

use App\Filament\Resources\CheckinBatches\CheckinBatchResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCheckinBatches extends ListRecords
{
    protected static string $resource = CheckinBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
