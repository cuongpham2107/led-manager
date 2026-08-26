<?php

namespace App\Filament\Resources\CheckinBatchItems\Pages;

use App\Filament\Resources\CheckinBatchItems\CheckinBatchItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCheckinBatchItems extends ListRecords
{
    protected static string $resource = CheckinBatchItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
