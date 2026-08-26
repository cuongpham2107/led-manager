<?php

namespace App\Filament\Resources\AssetStatusLogs\Pages;

use App\Filament\Resources\AssetStatusLogs\AssetStatusLogResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAssetStatusLog extends EditRecord
{
    protected static string $resource = AssetStatusLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
