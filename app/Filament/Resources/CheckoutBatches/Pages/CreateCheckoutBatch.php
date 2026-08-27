<?php

namespace App\Filament\Resources\CheckoutBatches\Pages;

use App\Filament\Resources\CheckoutBatches\CheckoutBatchResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;

class CreateCheckoutBatch extends CreateRecord
{
    protected static string $resource = CheckoutBatchResource::class;

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }
}
