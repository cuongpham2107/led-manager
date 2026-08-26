<?php

namespace App\Filament\Resources\AssetStatusLogs\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class AssetStatusLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('asset.id')
                    ->label('Asset'),
                TextEntry::make('from_status')
                    ->placeholder('-'),
                TextEntry::make('to_status'),
                TextEntry::make('fromWarehouse.name')
                    ->label('From warehouse')
                    ->placeholder('-'),
                TextEntry::make('toWarehouse.name')
                    ->label('To warehouse')
                    ->placeholder('-'),
                TextEntry::make('source_type')
                    ->placeholder('-'),
                TextEntry::make('source_id')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('changed_by')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('note')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->dateTime(),
            ]);
    }
}
