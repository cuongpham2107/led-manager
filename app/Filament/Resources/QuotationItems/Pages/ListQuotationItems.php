<?php

namespace App\Filament\Resources\QuotationItems\Pages;

use App\Filament\Resources\QuotationItems\QuotationItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListQuotationItems extends ListRecords
{
    protected static string $resource = QuotationItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
