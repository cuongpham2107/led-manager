<?php

namespace App\Filament\Resources\CheckinBatches\Pages;

use App\Filament\Resources\CheckinBatches\CheckinBatchResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;

class EditCheckinBatch extends EditRecord
{
    protected static string $resource = CheckinBatchResource::class;

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
