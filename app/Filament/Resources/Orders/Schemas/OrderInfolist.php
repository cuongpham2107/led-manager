<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Models\Order;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('order_no'),
                TextEntry::make('note')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('warehouse.name')
                    ->label('Warehouse'),
                TextEntry::make('customer.name')
                    ->label('Customer'),
                TextEntry::make('quotation.id')
                    ->label('Quotation')
                    ->placeholder('-'),
                TextEntry::make('request_date')
                    ->date(),
                TextEntry::make('expected_return_date')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('area_m2')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('event')
                    ->placeholder('-'),
                TextEntry::make('deviceType.name')
                    ->label('Device type')
                    ->placeholder('-'),
                TextEntry::make('value')
                    ->numeric(),
                TextEntry::make('status'),
                TextEntry::make('salesUser.name')
                    ->label('Sales user')
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (Order $record): bool => $record->trashed()),
            ]);
    }
}
