<?php

namespace App\Filament\Resources\CheckoutBatchItems\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CheckoutBatchItemInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('checkoutBatch.id')
                    ->label('Checkout batch'),
                TextEntry::make('asset.id')
                    ->label('Asset'),
                IconEntry::make('is_dispatched')
                    ->boolean(),
                TextEntry::make('dispatched_by')
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
