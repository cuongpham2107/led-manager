<?php

namespace App\Filament\Resources\CheckoutBatchItems\Pages;

use App\Filament\Resources\CheckoutBatchItems\CheckoutBatchItemResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCheckoutBatchItem extends EditRecord
{
    protected static string $resource = CheckoutBatchItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
