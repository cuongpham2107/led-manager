<?php

namespace App\Filament\Resources\QuotationItems\Pages;

use App\Filament\Resources\QuotationItems\QuotationItemResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewQuotationItem extends ViewRecord
{
    protected static string $resource = QuotationItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
