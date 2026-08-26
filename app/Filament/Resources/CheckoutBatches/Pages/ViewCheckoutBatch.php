<?php

namespace App\Filament\Resources\CheckoutBatches\Pages;

use App\Filament\Resources\CheckoutBatches\CheckoutBatchResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCheckoutBatch extends ViewRecord
{
    protected static string $resource = CheckoutBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
