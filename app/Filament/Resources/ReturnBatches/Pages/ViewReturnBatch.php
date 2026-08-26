<?php

namespace App\Filament\Resources\ReturnBatches\Pages;

use App\Filament\Resources\ReturnBatches\ReturnBatchResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewReturnBatch extends ViewRecord
{
    protected static string $resource = ReturnBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
