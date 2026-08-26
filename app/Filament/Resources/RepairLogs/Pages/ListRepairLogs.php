<?php

namespace App\Filament\Resources\RepairLogs\Pages;

use App\Filament\Resources\RepairLogs\RepairLogResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRepairLogs extends ListRecords
{
    protected static string $resource = RepairLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
