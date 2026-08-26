<?php

namespace App\Filament\Resources\Assets\Schemas;

use App\Models\Asset;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class AssetInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('serial_no'),
                TextEntry::make('qr_code')
                    ->placeholder('-'),
                TextEntry::make('productLine.name')
                    ->label('Product line')
                    ->placeholder('-'),
                TextEntry::make('deviceType.name')
                    ->label('Device type'),
                TextEntry::make('size')
                    ->placeholder('-'),
                TextEntry::make('manufactured_date')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('purchase_cost')
                    ->money()
                    ->placeholder('-'),
                TextEntry::make('purchase_date')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('current_status'),
                TextEntry::make('currentWarehouse.name')
                    ->label('Current warehouse')
                    ->placeholder('-'),
                TextEntry::make('note')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (Asset $record): bool => $record->trashed()),
            ]);
    }
}
