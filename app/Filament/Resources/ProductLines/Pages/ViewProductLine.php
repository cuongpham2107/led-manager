<?php

namespace App\Filament\Resources\ProductLines\Pages;

use App\Filament\Resources\ProductLines\ProductLineResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewProductLine extends ViewRecord
{
    protected static string $resource = ProductLineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
