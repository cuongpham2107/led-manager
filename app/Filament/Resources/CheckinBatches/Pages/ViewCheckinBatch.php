<?php

namespace App\Filament\Resources\CheckinBatches\Pages;

use App\Filament\Resources\CheckinBatches\CheckinBatchResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCheckinBatch extends ViewRecord
{
    protected static string $resource = CheckinBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
