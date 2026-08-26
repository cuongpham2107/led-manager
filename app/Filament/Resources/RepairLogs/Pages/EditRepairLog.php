<?php

namespace App\Filament\Resources\RepairLogs\Pages;

use App\Filament\Resources\RepairLogs\RepairLogResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditRepairLog extends EditRecord
{
    protected static string $resource = RepairLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
