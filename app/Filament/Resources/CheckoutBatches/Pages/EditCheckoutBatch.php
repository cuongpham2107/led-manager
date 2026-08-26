<?php

namespace App\Filament\Resources\CheckoutBatches\Pages;

use App\Filament\Resources\CheckoutBatches\CheckoutBatchResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCheckoutBatch extends EditRecord
{
    protected static string $resource = CheckoutBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
