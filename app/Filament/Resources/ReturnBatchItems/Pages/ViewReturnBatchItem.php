<?php

namespace App\Filament\Resources\ReturnBatchItems\Pages;

use App\Filament\Resources\ReturnBatchItems\ReturnBatchItemResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewReturnBatchItem extends ViewRecord
{
    protected static string $resource = ReturnBatchItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
