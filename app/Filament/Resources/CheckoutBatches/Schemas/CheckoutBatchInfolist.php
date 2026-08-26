<?php

namespace App\Filament\Resources\CheckoutBatches\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CheckoutBatchInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('code'),
                TextEntry::make('order.id')
                    ->label('Order'),
                TextEntry::make('customer.name')
                    ->label('Customer'),
                TextEntry::make('warehouse.name')
                    ->label('Warehouse'),
                TextEntry::make('required_area_m2')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('deviceType.name')
                    ->label('Device type')
                    ->placeholder('-'),
                TextEntry::make('expected_return_date')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('status'),
                TextEntry::make('created_by')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('dispatched_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
