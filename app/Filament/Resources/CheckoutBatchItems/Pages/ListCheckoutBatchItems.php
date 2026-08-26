<?php

namespace App\Filament\Resources\CheckoutBatchItems\Pages;

use App\Filament\Resources\CheckoutBatchItems\CheckoutBatchItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCheckoutBatchItems extends ListRecords
{
    protected static string $resource = CheckoutBatchItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
