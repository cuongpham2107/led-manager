<?php

namespace App\Filament\Resources\AssetStatusLogs\Pages;

use App\Filament\Resources\AssetStatusLogs\AssetStatusLogResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAssetStatusLog extends ViewRecord
{
    protected static string $resource = AssetStatusLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
