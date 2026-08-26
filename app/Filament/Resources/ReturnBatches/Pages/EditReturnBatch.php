<?php

namespace App\Filament\Resources\ReturnBatches\Pages;

use App\Filament\Resources\ReturnBatches\ReturnBatchResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditReturnBatch extends EditRecord
{
    protected static string $resource = ReturnBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
