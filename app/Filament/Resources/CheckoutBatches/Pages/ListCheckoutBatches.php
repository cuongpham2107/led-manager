<?php

namespace App\Filament\Resources\CheckoutBatches\Pages;

use App\Filament\Resources\CheckoutBatches\CheckoutBatchResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCheckoutBatches extends ListRecords
{
    protected static string $resource = CheckoutBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
