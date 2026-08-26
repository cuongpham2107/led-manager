<?php

namespace App\Filament\Resources\CheckinBatchItems\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CheckinBatchItemInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('checkinBatch.id')
                    ->label('Checkin batch'),
                TextEntry::make('asset.id')
                    ->label('Asset'),
                TextEntry::make('condition')
                    ->placeholder('-'),
                TextEntry::make('condition_note')
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
