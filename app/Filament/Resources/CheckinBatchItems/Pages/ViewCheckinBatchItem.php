<?php

namespace App\Filament\Resources\CheckinBatchItems\Pages;

use App\Filament\Resources\CheckinBatchItems\CheckinBatchItemResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCheckinBatchItem extends ViewRecord
{
    protected static string $resource = CheckinBatchItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
