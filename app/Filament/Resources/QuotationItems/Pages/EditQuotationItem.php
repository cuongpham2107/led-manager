<?php

namespace App\Filament\Resources\QuotationItems\Pages;

use App\Filament\Resources\QuotationItems\QuotationItemResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditQuotationItem extends EditRecord
{
    protected static string $resource = QuotationItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
