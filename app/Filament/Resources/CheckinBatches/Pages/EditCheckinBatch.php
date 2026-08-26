<?php

namespace App\Filament\Resources\CheckinBatches\Pages;

use App\Filament\Resources\CheckinBatches\CheckinBatchResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCheckinBatch extends EditRecord
{
    protected static string $resource = CheckinBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
