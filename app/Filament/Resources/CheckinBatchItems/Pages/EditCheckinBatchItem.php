<?php

namespace App\Filament\Resources\CheckinBatchItems\Pages;

use App\Filament\Resources\CheckinBatchItems\CheckinBatchItemResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCheckinBatchItem extends EditRecord
{
    protected static string $resource = CheckinBatchItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
