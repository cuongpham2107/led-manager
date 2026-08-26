<?php

namespace App\Filament\Resources\RepairLogs\Pages;

use App\Filament\Resources\RepairLogs\RepairLogResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewRepairLog extends ViewRecord
{
    protected static string $resource = RepairLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
