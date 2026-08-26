<?php

namespace App\Filament\Resources\ReturnBatchItems\Pages;

use App\Filament\Resources\ReturnBatchItems\ReturnBatchItemResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditReturnBatchItem extends EditRecord
{
    protected static string $resource = ReturnBatchItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
