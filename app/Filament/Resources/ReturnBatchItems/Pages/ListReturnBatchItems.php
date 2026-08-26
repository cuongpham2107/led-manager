<?php

namespace App\Filament\Resources\ReturnBatchItems\Pages;

use App\Filament\Resources\ReturnBatchItems\ReturnBatchItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListReturnBatchItems extends ListRecords
{
    protected static string $resource = ReturnBatchItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
