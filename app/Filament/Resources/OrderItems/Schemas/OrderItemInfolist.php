<?php

namespace App\Filament\Resources\OrderItems\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class OrderItemInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('order.id')
                    ->label('Order'),
                TextEntry::make('productLine.name')
                    ->label('Product line')
                    ->placeholder('-'),
                TextEntry::make('deviceType.name')
                    ->label('Device type'),
                TextEntry::make('quantity_required')
                    ->numeric(),
                TextEntry::make('unit_price')
                    ->money()
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
            ]);
    }
}
