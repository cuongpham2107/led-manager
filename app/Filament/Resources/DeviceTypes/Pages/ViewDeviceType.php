<?php

namespace App\Filament\Resources\DeviceTypes\Pages;

use App\Filament\Resources\DeviceTypes\DeviceTypeResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewDeviceType extends ViewRecord
{
    protected static string $resource = DeviceTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
