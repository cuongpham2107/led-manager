<?php

namespace App\Filament\Resources\CheckoutBatchItems\Pages;

use App\Filament\Resources\CheckoutBatchItems\CheckoutBatchItemResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCheckoutBatchItem extends CreateRecord
{
    protected static string $resource = CheckoutBatchItemResource::class;
}
