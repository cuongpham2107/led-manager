<?php

namespace App\Filament\Resources\ReturnBatches\Pages;

use App\Filament\Resources\ReturnBatches\ReturnBatchResource;
use Filament\Resources\Pages\ListRecords;

class ListReturnBatches extends ListRecords
{
    protected static string $resource = ReturnBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
