<?php

namespace App\Filament\Resources\CheckoutBatchItems\Pages;

use App\Filament\Resources\CheckoutBatchItems\CheckoutBatchItemResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCheckoutBatchItem extends ViewRecord
{
    protected static string $resource = CheckoutBatchItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
