<?php

namespace App\Filament\Resources\ReturnBatches\Pages;

use App\Filament\Resources\ReturnBatches\ReturnBatchResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;

class EditReturnBatch extends EditRecord
{
    protected static string $resource = ReturnBatchResource::class;

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
