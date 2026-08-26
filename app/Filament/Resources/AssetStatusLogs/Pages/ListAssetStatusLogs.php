<?php

namespace App\Filament\Resources\AssetStatusLogs\Pages;

use App\Filament\Resources\AssetStatusLogs\AssetStatusLogResource;
use Filament\Resources\Pages\ListRecords;

class ListAssetStatusLogs extends ListRecords
{
    protected static string $resource = AssetStatusLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
