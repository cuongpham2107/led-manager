<?php

namespace App\Filament\Resources\CheckoutBatches\Pages;

use App\Filament\Resources\CheckoutBatches\CheckoutBatchResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;

class EditCheckoutBatch extends EditRecord
{
    protected static string $resource = CheckoutBatchResource::class;

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

    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['selected_assets']);
        unset($data['product_line_id']);

        return $data;
    }
}
