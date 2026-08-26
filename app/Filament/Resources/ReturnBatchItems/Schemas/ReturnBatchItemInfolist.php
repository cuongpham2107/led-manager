<?php

namespace App\Filament\Resources\ReturnBatchItems\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ReturnBatchItemInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('returnBatch.id')
                    ->label('Return batch'),
                TextEntry::make('asset.id')
                    ->label('Asset'),
                TextEntry::make('checkoutBatchItem.id')
                    ->label('Checkout batch item')
                    ->placeholder('-'),
                TextEntry::make('grade')
                    ->placeholder('-'),
                TextEntry::make('grade_note')
                    ->placeholder('-')
                    ->columnSpanFull(),
                IconEntry::make('is_received')
                    ->boolean(),
                TextEntry::make('received_by')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('received_at')
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
